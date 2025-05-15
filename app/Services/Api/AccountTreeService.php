<?php

namespace App\Services\Api;

use GuzzleHttp\Client;
use App\Models\Account;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Services\Api\AuthTokenService;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class AccountTreeService
{
    protected string $baseUrl;
    protected Client $client;
    protected bool $isProductionMode = false;
    public function __construct(Client $client, bool $productionMode = false)
    {
        $this->baseUrl = 'https://open.iopgps.com/api/account/tree';
        $this->client = $client;
        $this->isProductionMode = $productionMode;

        if ($productionMode) {
            Log::info('AccountTreeService iniciado en modo PRODUCCIÓN con timeouts extendidos');
        }
    }
    /**
     * Crea un cliente HTTP con configuración personalizada.
     * 
     * @param int $timeout Tiempo máximo para completar la solicitud completa (en segundos)
     * @param int $connectTimeout Tiempo máximo para establecer la conexión inicial (en segundos)
     * @param bool $verifySSL Verificar certificados SSL
     * @param string|null $proxy URL del proxy si se necesita
     * @param bool $directMode Si es true, intenta conectar directamente con opciones más agresivas
     * @return Client
     */
    private function createConfiguredClient(
        int $timeout = 120,
        int $connectTimeout = 30,
        bool $verifySSL = true,
        ?string $proxy = null,
        bool $directMode = false
    ): Client {
        $config = [
            'timeout' => $timeout,
            'connect_timeout' => $connectTimeout,
            'http_errors' => false,
            'verify' => $verifySSL,
            'debug' => false,
            'curl' => [
                CURLOPT_TCP_KEEPALIVE => 1,
                CURLOPT_TCP_KEEPIDLE => 120,
                CURLOPT_TCP_NODELAY => 1,
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, // Forzar IPv4 para evitar problemas con IPv6
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_FORBID_REUSE => false,  // Permitir reutilización de conexiones
                CURLOPT_FRESH_CONNECT => false, // No forzar una nueva conexión
                CURLOPT_DNS_CACHE_TIMEOUT => 600 // Cache DNS por 10 minutos
            ]
        ];

        // Modo directo con opciones más agresivas para problemas de conexión
        if ($directMode) {
            $config['curl'][CURLOPT_INTERFACE] = null; // Usar cualquier interfaz disponible
            $config['curl'][CURLOPT_CONNECTTIMEOUT_MS] = $connectTimeout * 1000;
            $config['curl'][CURLOPT_TIMEOUT_MS] = $timeout * 1000;
            $config['curl'][CURLOPT_TCP_FASTOPEN] = 1; // Usar TCP Fast Open si está disponible
            $config['curl'][CURLOPT_DNS_USE_GLOBAL_CACHE] = false; // No usar cache DNS global

            // Intentar resolver la IP manualmente y usarla directamente si podemos
            $host = parse_url($this->baseUrl, PHP_URL_HOST);
            if ($host) {
                $ip = gethostbyname($host);
                if ($ip && $ip !== $host) {
                    Log::info("Resolviendo DNS manualmente", ['host' => $host, 'ip' => $ip]);
                    // Usar la IP pero mantener el hostname para SNI en HTTPS
                    $config['curl'][CURLOPT_RESOLVE] = ["$host:443:$ip"];
                }
            }
        }

        // Añadir proxy si está configurado
        if ($proxy) {
            $config['proxy'] = $proxy;
        }

        return new Client($config);
    }

    /**
     * Método optimizado para entornos de producción (VPS) con tiempos de espera más largos
     * y más reintentos.
     *
     * @param int|null $accountId ID de la cuenta (opcional)
     * @return array
     */
    public function fetchAndSyncAccountsForProduction($accountId = null): array
    {
        // Usar configuración específica para entornos de producción:
        // - Más reintentos (5 en lugar de 3)
        // - Modo de producción activado (timeouts más largos, SSL verificación opcional)
        return $this->fetchAndSyncAccounts($accountId, 5, true);
    }

    /**
     * Obtiene el accessToken desde Redis.
     */
    private function getAccessToken(): ?string
    {
        return Redis::get('iopgps_access_token');
    }
    /**
     * Establece el modo de producción para optimizar la configuración.
     * 
     * @param bool $mode True para activar modo producción
     * @return self
     */
    public function setProductionMode(bool $mode = true): self
    {
        $this->isProductionMode = $mode;
        return $this;
    }

    /**
     * Comprueba si el servidor API está accesible
     * 
     * @param int $timeout Timeout en segundos
     * @return bool
     */
    private function isApiServerReachable(int $timeout = 5): bool
    {
        $host = parse_url($this->baseUrl, PHP_URL_HOST);

        // Primero intentamos un ping a nivel de TCP (puerto 443 para HTTPS)
        $startTime = microtime(true);
        $socket = @fsockopen($host, 443, $errno, $errstr, $timeout);

        if ($socket) {
            fclose($socket);
            $pingTime = round((microtime(true) - $startTime) * 1000);
            Log::info("API server es accesible via TCP/IP", [
                'host' => $host,
                'pingTime' => $pingTime . 'ms'
            ]);
            return true;
        }

        // Si TCP falla, intentamos resolución DNS
        $ip = gethostbyname($host);
        if ($ip && $ip !== $host) {
            Log::info("API server tiene resolución DNS pero TCP falló", [
                'host' => $host,
                'ip' => $ip,
                'error' => "$errno: $errstr"
            ]);

            // Intentar ping directo a IP como último recurso
            $socket = @fsockopen($ip, 443, $errno, $errstr, $timeout);
            if ($socket) {
                fclose($socket);
                Log::info("API server accesible directamente por IP", ['ip' => $ip]);
                return true;
            }
        }

        Log::warning("API server inaccesible", [
            'host' => $host,
            'resolvedIp' => $ip ?? 'No resuelto',
            'error' => "$errno: $errstr"
        ]);

        return false;
    }
    /**
     * Obtiene la estructura de cuentas desde la API y la guarda/actualiza en la base de datos.
     *
     * @param int|null $accountId ID de la cuenta (opcional)
     * @param int $maxRetries Número máximo de reintentos
     * @param bool $isProduction Si es true, usa configuraciones más agresivas para entornos de producción
     * @return array
     */    public function fetchAndSyncAccounts($accountId = null, int $maxRetries = 3, bool $isProduction = false): array
    {
        // Verificar si ProcessUnitsJob está en ejecución
        if (Redis::exists('iopgps_process_units_in_progress')) {
            $startTime = Redis::get('iopgps_process_units_in_progress');
            $elapsedTime = time() - $startTime;

            Log::info("ProcessUnitsJob en ejecución (iniciado hace {$elapsedTime} segundos). Esperando a que termine...");

            // Esperar hasta 30 segundos para que finalice ProcessUnitsJob
            $waitCount = 0;
            while (Redis::exists('iopgps_process_units_in_progress') && $waitCount < 30) {
                sleep(1);
                $waitCount++;
            }

            if (Redis::exists('iopgps_process_units_in_progress')) {
                Log::warning("ProcessUnitsJob sigue en ejecución después de esperar 30 segundos. Continuando de todas formas.");
            } else {
                Log::info("ProcessUnitsJob completado. Continuando con AccountTreeService.");
            }
        }

        // Establecer un lock en Redis para indicar que AccountTreeService está en uso
        Redis::set('iopgps_account_tree_in_progress', time(), 'EX', 300); // Expira en 5 minutos por seguridad

        try {
            // Usar el modo producción de la instancia si no se especifica
            $isProduction = $isProduction || $this->isProductionMode;

            $accessToken = $this->getAccessToken();

            if (!$accessToken) {
                Log::error("No hay accessToken disponible en Redis.");
                $authTokenService = app(AuthTokenService::class);
                $token = $authTokenService->getAccessToken();

                return ['error' => 'Access token no encontrado.'];
            }

            $url = $this->baseUrl;
            if ($accountId) {
                $url .= "?id={$accountId}";
            }

            // Verificar si el servidor API está accesible antes de intentar
            $isReachable = $this->isApiServerReachable();

            // Configuración adaptada según el entorno
            $timeout = $isProduction ? 180 : 60; // 3 minutos en producción, 1 minuto en local
            $connectTimeout = $isProduction ? 60 : 30; // 1 minuto en producción, 30 segundos en local

            // En producción, podemos tener más problemas de red
            $verifySSL = !$isProduction; // En producción podemos desactivar verificación SSL si hay problemas

            // Usamos modo directo si la comprobación de accesibilidad falló
            $directMode = !$isReachable;

            if (!$isReachable) {
                Log::warning("Usando modo de conexión directa debido a que el servidor API no responde", [
                    'url' => $url
                ]);
            }

            // Crear un cliente HTTP configurado con un timeout más largo
            $configuredClient = $this->createConfiguredClient(
                timeout: $timeout,
                connectTimeout: $connectTimeout,
                verifySSL: $verifySSL,
                directMode: $directMode
            );

            $attempts = 0;
            $lastError = null;
            $startTime = microtime(true);

            Log::info("Iniciando solicitud a API de cuentas", [
                'url' => $url,
                'entorno' => $isProduction ? 'producción' : 'desarrollo',
                'timeout' => $timeout,
                'maxRetries' => $maxRetries,
                'directMode' => $directMode
            ]);

            while ($attempts < $maxRetries) {
                $attempts++;
                try {
                    Log::info("Intento {$attempts} de conexión a la API", [
                        'url' => $url,
                        'tiempoTranscurrido' => round(microtime(true) - $startTime, 2) . ' segundos'
                    ]);

                    $response = $configuredClient->request('GET', $url, [
                        'headers' => [
                            'accessToken' => $accessToken,
                            'User-Agent' => 'IOPGPS-WebServices/1.0',
                            'Accept' => 'application/json'
                        ],
                    ]);

                    $statusCode = $response->getStatusCode();
                    $body = json_decode($response->getBody()->getContents(), true);

                    $tiempoTotal = round(microtime(true) - $startTime, 2);
                    Log::info("Respuesta de la API recibida", [
                        'statusCode' => $statusCode,
                        'tiempoTotal' => $tiempoTotal . ' segundos',
                        'tamaño' => isset($body) ? strlen(json_encode($body)) : 0 . ' bytes'
                    ]);

                    if ($statusCode !== 200 || empty($body) || !isset($body['code'])) {
                        Log::error("Respuesta inesperada de la API", ['statusCode' => $statusCode, 'body' => $body]);
                        return ['error' => 'Respuesta inesperada de la API'];
                    }

                    if ($body['code'] !== 0) {
                        Log::error("Error en respuesta API", ['response' => $body]);
                        return ['error' => $body['result'] ?? 'Error desconocido'];
                    }
                    return $this->syncAccounts($body);
                } catch (RequestException | GuzzleException $e) {
                    $lastError = $e;
                    $waitTime = pow(2, $attempts); // Espera exponencial: 2, 4, 8 segundos

                    // Obtener detalles específicos del error para mejor diagnóstico
                    $errorDetails = [];
                    if (method_exists($e, 'getHandlerContext')) {
                        $context = $e->getHandlerContext();
                        $errorDetails = [
                            'total_time' => $context['total_time'] ?? 'N/A',
                            'namelookup_time' => $context['namelookup_time'] ?? 'N/A',
                            'connect_time' => $context['connect_time'] ?? 'N/A',
                            'size_upload' => $context['size_upload'] ?? 'N/A',
                            'size_download' => $context['size_download'] ?? 'N/A',
                            'speed_download' => $context['speed_download'] ?? 'N/A',
                            'primary_ip' => $context['primary_ip'] ?? 'N/A',
                            'primary_port' => $context['primary_port'] ?? 'N/A',
                            'local_ip' => $context['local_ip'] ?? 'N/A',
                            'local_port' => $context['local_port'] ?? 'N/A',
                        ];
                    }

                    // Si es un error de timeout o conexión y no estamos en modo directo,
                    // intentamos inmediatamente con modo directo antes de esperar
                    $errorCode = $e->getCode();
                    $isConnectionError =
                        strpos($e->getMessage(), 'timed out') !== false ||
                        strpos($e->getMessage(), 'Timeout was reached') !== false ||
                        strpos($e->getMessage(), 'Failed to connect') !== false;

                    if ($isConnectionError && !$directMode && $attempts <= $maxRetries) {
                        Log::warning("Detectado error de conexión. Reintentando inmediatamente en modo directo", [
                            'error' => $e->getMessage(),
                            'intento' => $attempts
                        ]);

                        // Cambiamos a modo directo y reintentamos inmediatamente
                        $directMode = true;
                        $configuredClient = $this->createConfiguredClient(
                            timeout: $timeout,
                            connectTimeout: $connectTimeout,
                            verifySSL: $verifySSL,
                            directMode: true
                        );

                        try {
                            Log::info("Reintento inmediato con modo directo", [
                                'url' => $url,
                                'tiempoTranscurrido' => round(microtime(true) - $startTime, 2) . ' segundos'
                            ]);

                            // Intentar de nuevo en modo directo
                            $response = $configuredClient->request('GET', $url, [
                                'headers' => [
                                    'accessToken' => $accessToken,
                                    'User-Agent' => 'IOPGPS-WebServices/1.0',
                                    'Accept' => 'application/json'
                                ],
                            ]);

                            $statusCode = $response->getStatusCode();
                            $body = json_decode($response->getBody()->getContents(), true);

                            $tiempoTotal = round(microtime(true) - $startTime, 2);
                            Log::info("Respuesta de la API recibida en modo directo", [
                                'statusCode' => $statusCode,
                                'tiempoTotal' => $tiempoTotal . ' segundos'
                            ]);

                            if ($statusCode !== 200 || empty($body) || !isset($body['code'])) {
                                Log::error("Respuesta inesperada de la API en modo directo", [
                                    'statusCode' => $statusCode,
                                    'body' => $body
                                ]);
                                // Continuar con el proceso normal de reintento
                            } else if ($body['code'] !== 0) {
                                Log::error("Error en respuesta API en modo directo", ['response' => $body]);
                                // Continuar con el proceso normal de reintento
                            } else {
                                // Si llegamos aquí, tuvimos éxito en modo directo
                                return $this->syncAccounts($body);
                            }
                        } catch (\Exception $innerEx) {
                            // Si falla el modo directo, continuamos con el ciclo normal de reintentos
                            Log::warning("El reintento en modo directo también falló", [
                                'error' => $innerEx->getMessage()
                            ]);
                        }
                    }

                    Log::warning("Error al conectar con la API (intento {$attempts}/{$maxRetries})", [
                        'message' => $e->getMessage(),
                        'esperando' => "{$waitTime} segundos antes del próximo intento",
                        'tiempoTranscurrido' => round(microtime(true) - $startTime, 2) . ' segundos',
                        'detalles' => $errorDetails,
                        'directMode' => $directMode
                    ]);

                    if ($attempts < $maxRetries) {
                        sleep($waitTime);
                    }
                } catch (\Exception $e) {
                    Log::error("Error inesperado", [
                        'message' => $e->getMessage(),
                        'tiempoTranscurrido' => round(microtime(true) - $startTime, 2) . ' segundos',
                        'trace' => $e->getTraceAsString()
                    ]);
                    return ['error' => 'Error inesperado: ' . $e->getMessage()];
                }
            }

            $tiempoTotal = round(microtime(true) - $startTime, 2);

            // Si llegamos aquí, todos los intentos fallaron
            Log::error("Todos los intentos de conexión a la API fallaron", [
                'mensaje' => $lastError ? $lastError->getMessage() : 'Error desconocido',
                'intentos' => $maxRetries,
                'tiempoTotal' => $tiempoTotal . ' segundos',
                'url' => $url
            ]);

            // Sugerencias para resolución
            $sugerencias = [
                'Verificar la conectividad de red del servidor',
                'Comprobar si la API está disponible desde otras ubicaciones',
                'Considerar el uso de un proxy',
                'Aumentar aún más los tiempos de timeout',
                'Verificar la configuración del firewall del servidor'
            ];

            return [
                'error' => 'No se pudo conectar con la API después de ' . $maxRetries . ' intentos. Último error: ' .
                    ($lastError ? $lastError->getMessage() : 'Error desconocido'),
                'tiempoTotal' => $tiempoTotal,
                'sugerencias' => $sugerencias
            ];
        } finally {
            // Eliminar el lock de Redis cuando terminemos
            Redis::del('iopgps_account_tree_in_progress');
            Log::info("Lock para AccountTreeService liberado");
        }
    }
    /**
     * Guarda o actualiza las cuentas en la base de datos.
     */
    private function syncAccounts(array $data): array
    {
        $accounts = [];

        // El nodo principal es la respuesta, que ya contiene la información de la cuenta
        if (isset($data['accountId'])) {
            $this->extractAndSyncAccounts($data, $accounts);
        } else {
            Log::error("Estructura de datos inesperada", ['data' => $data]);
            return ['error' => 'Estructura de datos de cuenta inesperada'];
        }

        Log::info("Sincronización completada", ['totalAccounts' => count($accounts)]);
        return [
            'success' => true,
            'message' => 'Sincronización completada con éxito',
            'totalAccounts' => count($accounts)
        ];
    }
    /**
     * Extrae cuentas de la respuesta API y las sincroniza en la base de datos.
     */
    private function extractAndSyncAccounts(array $node, array &$accounts): void
    {
        // Asegurarse de tener los campos necesarios
        if (!isset($node['accountId']) || !isset($node['account']) || !isset($node['userName'])) {
            Log::warning("Nodo de cuenta incompleto", ['node' => $node]);
            return;
        }

        $accountData = [
            'accountId' => $node['accountId'],
            'parentAccountId' => $node['parentAccountId'] ?? null,
            'userName' => $node['userName'],
            'account' => $node['account'],
        ];

        // Crear si no existe, actualizar si ya existe
        try {
            Account::updateOrCreate(
                ['accountId' => $accountData['accountId']], // Condición para buscar
                $accountData // Datos a actualizar o crear
            );

            Log::info("Cuenta sincronizada correctamente", ['accountId' => $accountData['accountId'], 'account' => $accountData['account']]);
            $accounts[] = $accountData;
        } catch (\Exception $e) {
            Log::error("Error al sincronizar cuenta", [
                'accountId' => $accountData['accountId'],
                'error' => $e->getMessage()
            ]);
        }

        // Recorrer hijos
        if (!empty($node['childAccounts'])) {
            foreach ($node['childAccounts'] as $child) {
                $this->extractAndSyncAccounts($child, $accounts);
            }
        }
    }

    /**
     * Método altamente optimizado para entornos con problemas de conectividad, usando
     * múltiples técnicas para superar problemas de red.
     *
     * @param int|null $accountId ID de la cuenta (opcional)
     * @param string|null $alternativeDns DNS alternativo (ej. 8.8.8.8 para Google DNS)
     * @return array
     */
    public function fetchAccountsUltimateMode($accountId = null, ?string $alternativeDns = null): array
    {
        // Asegurar que estamos en modo producción
        $this->setProductionMode(true);

        // Configurar DNS alternativo temporalmente si se proporciona
        $originalNameservers = null;
        if ($alternativeDns) {
            // Guardar nameservers actuales
            $originalNameservers = file_get_contents('/etc/resolv.conf');

            try {
                // Escribir DNS alternativo (requiere permisos de escritura)
                $cmd = "echo 'nameserver $alternativeDns' | sudo tee /etc/resolv.conf";
                exec($cmd, $output, $returnVar);

                if ($returnVar !== 0) {
                    Log::warning("No se pudo configurar DNS alternativo", [
                        'command' => $cmd,
                        'returnVar' => $returnVar
                    ]);
                } else {
                    Log::info("DNS alternativo configurado temporalmente", ['dns' => $alternativeDns]);
                }
            } catch (\Exception $e) {
                Log::error("Error al intentar configurar DNS alternativo", ['error' => $e->getMessage()]);
            }
        }

        try {
            // Configurar el cliente con una estrategia extremadamente tolerante a fallos
            $result = $this->fetchAndSyncAccounts($accountId, 7, true); // 7 reintentos, modo producción
            return $result;
        } finally {
            // Restaurar DNS original si lo modificamos
            if ($originalNameservers !== null) {
                try {
                    file_put_contents('/etc/resolv.conf', $originalNameservers);
                    Log::info("DNS restaurado a configuración original");
                } catch (\Exception $e) {
                    Log::error("Error al restaurar DNS original", ['error' => $e->getMessage()]);
                }
            }
        }
    }
}
