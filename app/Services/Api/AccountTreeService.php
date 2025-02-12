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

    public function __construct(Client $client)
    {
        $this->baseUrl = 'https://open.iopgps.com/api/account/tree';
        $this->client = $client;
    }

    /**
     * Obtiene el accessToken desde Redis.
     */
    private function getAccessToken(): ?string
    {
        return Redis::get('iopgps_access_token');
    }

    /**
     * Obtiene la estructura de cuentas desde la API y la guarda/actualiza en la base de datos.
     */
    public function fetchAndSyncAccounts($accountId = null): array
    {
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

        try {
            $response = $this->client->request('GET', $url, [
                'headers' => [
                    'accessToken' => $accessToken,
                ],
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

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
            Log::error("Error al conectar con la API", ['message' => $e->getMessage()]);
            return ['error' => 'Error al conectar con la API'];
        } catch (\Exception $e) {
            Log::error("Error inesperado", ['message' => $e->getMessage()]);
            return ['error' => 'Error inesperado'];
        }
    }

    /**
     * Guarda o actualiza las cuentas en la base de datos.
     */
    private function syncAccounts(array $data): array
    {
        $accounts = [];

        $this->extractAndSyncAccounts($data, $accounts);

        return $accounts;
    }

    /**
     * Extrae cuentas de la respuesta API y las sincroniza en la base de datos.
     */
    private function extractAndSyncAccounts(array $node, array &$accounts): void
    {

        $accountData = [
            'accountId' => $node['accountId'],
            'parentAccountId' => $node['parentAccountId'] ?? null,
            'userName' => $node['userName'],
            'account' => $node['account'],
        ];

        // Crear si no existe, actualizar si ya existe
        Account::updateOrCreate(
            ['accountId' => $accountData['accountId']], // Condición para buscar
            $accountData // Datos a actualizar o crear
        );

        $accounts[] = $accountData;

        // Recorrer hijos
        if (!empty($node['childAccounts'])) {
            foreach ($node['childAccounts'] as $child) {
                $this->extractAndSyncAccounts($child, $accounts);
            }
        }
    }
}
