<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Facades\DB;

/**
 * Vacía las colas de TODOS los webservices que comparten este Redis
 * (iopgps, navixy, ...), sin importar el REDIS_PREFIX de cada app.
 */
class ClearAllQueuesCommand extends Command
{
    protected $signature = 'queues:clear-all
                            {--only= : Solo colas cuyo nombre contenga alguno de estos textos (separados por coma), ej: navixy}
                            {--dry-run : Solo mostrar lo que se borraría}
                            {--failed : Vaciar también la tabla failed_jobs de esta app}
                            {--force : No pedir confirmación}';

    protected $description = 'Vacía las colas Redis de todos los webservices (pendientes, delayed y reserved)';

    public function handle(): int
    {
        // Conexiones a revisar: la de colas y la de caché (locks de ShouldBeUnique)
        $targets = [
            'default' => '*queues:*',
            'cache' => '*laravel_unique_job*',
        ];

        $found = [];

        foreach ($targets as $connection => $pattern) {
            if (!config("database.redis.{$connection}")) {
                continue;
            }

            $redis = $this->rawConnection($connection);

            foreach ($this->scan($redis, $pattern) as $key) {
                if (!$this->matchesOnly($key)) {
                    continue;
                }

                $found[] = [
                    'connection' => $connection,
                    'key' => $key,
                    'type' => $this->typeOf($redis, $key),
                    'items' => $this->countItems($redis, $key),
                ];
            }
        }

        if (empty($found)) {
            $this->info('No hay colas que vaciar.');
        } else {
            $this->table(['Conexión', 'Clave', 'Tipo', 'Jobs'], array_map(fn($row) => array_values($row), $found));
            $this->info('Total jobs: ' . array_sum(array_column($found, 'items')));
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry-run: no se borró nada.');
            return self::SUCCESS;
        }

        if (empty($found) && !$this->option('failed')) {
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('¿Vaciar estas colas?')) {
            return self::FAILURE;
        }

        foreach ($found as $row) {
            $this->rawConnection($row['connection'])->command('del', [$row['key']]);
        }

        $this->info(count($found) . ' claves eliminadas.');

        if ($this->option('failed')) {
            $table = config('queue.failed.table', 'failed_jobs');
            $deleted = DB::table($table)->delete();
            $this->info("{$deleted} jobs fallidos eliminados de {$table}.");
        }

        return self::SUCCESS;
    }

    /**
     * Conexión Redis sin prefijo, para ver las claves de todas las apps.
     */
    protected function rawConnection(string $name)
    {
        static $connections = [];

        if (!isset($connections[$name])) {
            $config = config('database.redis');
            $config['options']['prefix'] = '';

            $manager = new RedisManager(app(), $config['client'] ?? 'phpredis', $config);
            $connections[$name] = $manager->connection($name);
        }

        return $connections[$name];
    }

    protected function scan($redis, string $pattern): array
    {
        $keys = [];
        $cursor = '0';

        do {
            // scan() de Laravel normaliza las diferencias entre phpredis y predis
            [$cursor, $batch] = $redis->scan($cursor, ['match' => $pattern, 'count' => 1000]) ?: ['0', []];

            foreach ($batch ?? [] as $key) {
                $keys[$key] = true;
            }
        } while ((string) $cursor !== '0');

        return array_keys($keys);
    }

    protected function matchesOnly(string $key): bool
    {
        $only = array_filter(array_map('trim', explode(',', (string) $this->option('only'))));

        if (empty($only)) {
            return true;
        }

        foreach ($only as $needle) {
            if (str_contains($key, $needle)) {
                return true;
            }
        }

        return false;
    }

    protected function typeOf($redis, string $key): string
    {
        $type = $redis->command('type', [$key]);

        // phpredis devuelve constantes numéricas, predis un objeto Status
        return match (true) {
            is_int($type) => [1 => 'string', 2 => 'set', 3 => 'list', 4 => 'zset', 5 => 'hash'][$type] ?? 'none',
            default => (string) $type,
        };
    }

    protected function countItems($redis, string $key): int
    {
        // queues:<nombre>:notify solo es una señal para block_for, no contiene jobs
        if (str_ends_with($key, ':notify')) {
            return 0;
        }

        return match ($this->typeOf($redis, $key)) {
            'list' => (int) $redis->command('llen', [$key]),
            'zset' => (int) $redis->command('zcard', [$key]),
            default => 1,
        };
    }
}
