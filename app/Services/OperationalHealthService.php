<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OperationalHealthService
{
    protected array $workerServices = [
        'food' => 'worker-food',
        'colis' => 'worker-colis',
        'transport' => 'worker-transport',
    ];

    public function modules(): array
    {
        $modules = config('bantudelice_modules', []);

        return collect($modules)->mapWithKeys(function (array $config, string $module) {
            $tables = $config['tables'] ?? [];
            $tableHealth = [];

            foreach ($tables as $table) {
                $tableHealth[$table] = Schema::hasTable($table);
            }

            return [$module => [
                'module' => $module,
                'label' => $config['label'] ?? ucfirst($module),
                'enabled' => (bool) ($config['enabled'] ?? true),
                'queue' => $config['queue'] ?? null,
                'database_ok' => $this->databaseOk(),
                'tables' => $tableHealth,
                'healthy' => (bool) ($config['enabled'] ?? true)
                    && $this->databaseOk()
                    && ! in_array(false, $tableHealth, true),
            ]];
        })->all();
    }

    public function module(string $module): ?array
    {
        return $this->modules()[$module] ?? null;
    }

    public function dependencies(): array
    {
        $payments = config('external-services.payments', []);
        $notifications = config('external-services.notifications', []);
        $geo = config('external-services.geolocation', []);
        $mailDriver = config('mail.driver');
        $mtnMomo = $payments['mtn_momo'] ?? [];
        $collections = $mtnMomo['collections'] ?? [];
        $disbursements = $mtnMomo['disbursements'] ?? [];
        $collectionsState = $this->providerState($collections, ['subscription_key', 'api_user', 'api_key']);
        $disbursementsState = $this->providerState($disbursements, ['subscription_key', 'api_user', 'api_key']);

        return [
            'payment' => [
                'healthy' => $this->hasConfiguredPaymentProvider($payments),
                'providers' => [
                    'mtn_momo' => [
                        'enabled' => (bool) data_get($mtnMomo, 'enabled', false),
                        'configured' => $collectionsState['configured'],
                        'collections' => $collectionsState,
                        'disbursements' => $disbursementsState,
                    ],
                    'airtel_money' => $this->providerState($payments['airtel_money'] ?? [], ['client_id', 'client_secret']),
                    'stripe' => $this->providerState($payments['stripe'] ?? [], ['key', 'secret']),
                    'paypal' => $this->providerState($payments['paypal'] ?? [], ['client_id', 'secret']),
                ],
            ],
            'maps' => [
                'healthy' => $this->mapsHealthy($geo),
                'providers' => [
                    'google_maps' => [
                        'enabled' => (bool) data_get($geo, 'google_maps.enabled', false),
                        'configured' => ! empty(data_get($geo, 'google_maps.api_key')),
                    ],
                    'openstreetmap' => [
                        'enabled' => (bool) data_get($geo, 'openstreetmap.enabled', false),
                        'configured' => ! empty(data_get($geo, 'openstreetmap.nominatim_url')),
                    ],
                ],
            ],
            'sms' => [
                'healthy' => $this->smsHealthy($notifications),
                'providers' => [
                    'twilio' => $this->providerState($notifications['twilio'] ?? [], ['sid', 'token', 'from']),
                    'africastalking' => $this->providerState($notifications['africastalking'] ?? [], ['username', 'api_key']),
                    'bulkgate' => $this->providerState($notifications['bulkgate'] ?? [], ['application_id', 'api_key']),
                    'sms_local' => $this->providerState($notifications['sms_local'] ?? [], ['api_key', 'api_url']),
                ],
                'demo_fallback' => true,
            ],
            'mail' => [
                'healthy' => $this->mailHealthy(),
                'driver' => $mailDriver,
                'host' => config('mail.host'),
                'configured' => $this->mailConfigured($mailDriver),
            ],
        ];
    }

    public function queues(): array
    {
        $queues = config('queue.bantudelice', []);
        $jobsTable = Schema::hasTable('jobs');
        $failedJobsTable = Schema::hasTable('failed_jobs');
        $queueMap = [
            'food' => [
                'name' => $queues['food'] ?? 'food',
                'connection' => 'database_food',
            ],
            'colis' => [
                'name' => $queues['colis'] ?? 'colis',
                'connection' => 'database_colis',
            ],
            'transport' => [
                'name' => $queues['transport'] ?? 'transport',
                'connection' => 'database_transport',
            ],
        ];

        return [
            'driver' => config('queue.default'),
            'jobs_table' => $jobsTable,
            'failed_jobs_table' => $failedJobsTable,
            'healthy' => config('queue.default') === 'sync' ? $failedJobsTable : ($jobsTable && $failedJobsTable),
            'queues' => collect($queueMap)->map(function (array $queue) use ($jobsTable, $failedJobsTable) {
                return array_merge($queue, $this->queueMetrics($queue['name'], $jobsTable, $failedJobsTable));
            })->all(),
        ];
    }

    public function failedJobsOverview(int $limit = 20): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return [
                'counts' => [],
                'recent' => [],
            ];
        }

        $recent = DB::table('failed_jobs')
            ->select(['id', 'queue', 'connection', 'failed_at', 'exception'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'queue' => $job->queue,
                    'module' => $this->moduleFromQueue((string) $job->queue),
                    'connection' => $job->connection,
                    'failed_at' => $job->failed_at,
                    'exception_head' => strtok((string) $job->exception, "\n") ?: 'Exception inconnue',
                ];
            })
            ->all();

        $counts = DB::table('failed_jobs')
            ->select('queue', DB::raw('COUNT(*) as total'))
            ->groupBy('queue')
            ->get()
            ->map(function ($row) {
                return [
                    'queue' => $row->queue,
                    'module' => $this->moduleFromQueue((string) $row->queue),
                    'total' => (int) $row->total,
                ];
            })
            ->all();

        return [
            'counts' => $counts,
            'recent' => $recent,
        ];
    }

    public function workers(): array
    {
        $services = collect($this->workerServices)->map(function (string $serviceName, string $module) {
            return [
                'module' => $module,
                'service' => $serviceName,
                'active' => $this->serviceIsActive($serviceName),
                'status' => $this->serviceStatus($serviceName),
            ];
        })->all();

        return [
            'healthy' => collect($services)->every(fn (array $service) => $service['active'] === true),
            'services' => $services,
        ];
    }

    protected function databaseOk(): bool
    {
        try {
            DB::select('select 1 as ok');
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function providerState(array $config, array $requiredKeys): array
    {
        $enabled = (bool) ($config['enabled'] ?? false);
        $configured = collect($requiredKeys)->every(function ($key) use ($config) {
            return ! empty($config[$key] ?? null);
        });

        return [
            'enabled' => $enabled,
            'configured' => $configured,
        ];
    }

    protected function hasConfiguredPaymentProvider(array $payments): bool
    {
        return $this->providerState($payments['mtn_momo']['collections'] ?? [], ['subscription_key', 'api_user', 'api_key'])['configured']
            || $this->providerState($payments['airtel_money'] ?? [], ['client_id', 'client_secret'])['configured']
            || $this->providerState($payments['stripe'] ?? [], ['key', 'secret'])['configured']
            || $this->providerState($payments['paypal'] ?? [], ['client_id', 'secret'])['configured'];
    }

    protected function mapsHealthy(array $geo): bool
    {
        $googleReady = (bool) data_get($geo, 'google_maps.enabled', false) && ! empty(data_get($geo, 'google_maps.api_key'));
        $osmReady = ! empty(data_get($geo, 'openstreetmap.nominatim_url'));

        return $googleReady || $osmReady;
    }

    protected function smsHealthy(array $notifications): bool
    {
        return $this->providerState($notifications['twilio'] ?? [], ['sid', 'token', 'from'])['configured']
            || $this->providerState($notifications['africastalking'] ?? [], ['username', 'api_key'])['configured']
            || $this->providerState($notifications['bulkgate'] ?? [], ['application_id', 'api_key'])['configured']
            || $this->providerState($notifications['sms_local'] ?? [], ['api_key', 'api_url'])['configured'];
    }

    protected function mailHealthy(): bool
    {
        return $this->mailConfigured(config('mail.driver'));
    }

    protected function mailConfigured(?string $driver): bool
    {
        if ($driver === 'log' || $driver === 'array') {
            return true;
        }

        if ($driver === 'smtp') {
            return ! empty(config('mail.host')) && ! empty(config('mail.from.address'));
        }

        return ! empty(config('mail.from.address'));
    }

    protected function queueMetrics(string $queueName, bool $jobsTable, bool $failedJobsTable): array
    {
        $pending = 0;
        $reserved = 0;
        $failed = 0;
        $oldestPendingSeconds = null;

        if ($jobsTable) {
            $pending = DB::table('jobs')
                ->where('queue', $queueName)
                ->whereNull('reserved_at')
                ->count();

            $reserved = DB::table('jobs')
                ->where('queue', $queueName)
                ->whereNotNull('reserved_at')
                ->count();

            $oldestCreatedAt = DB::table('jobs')
                ->where('queue', $queueName)
                ->whereNull('reserved_at')
                ->min('created_at');

            if ($oldestCreatedAt !== null) {
                $oldestPendingSeconds = max(0, now()->timestamp - (int) $oldestCreatedAt);
            }
        }

        if ($failedJobsTable) {
            $failed = DB::table('failed_jobs')
                ->where('queue', $queueName)
                ->count();
        }

        return [
            'pending_jobs' => $pending,
            'reserved_jobs' => $reserved,
            'failed_jobs' => $failed,
            'oldest_pending_seconds' => $oldestPendingSeconds,
        ];
    }

    protected function moduleFromQueue(string $queueName): string
    {
        return match ($queueName) {
            config('queue.bantudelice.food', 'food') => 'food',
            config('queue.bantudelice.colis', 'colis') => 'colis',
            config('queue.bantudelice.transport', 'transport') => 'transport',
            default => 'shared',
        };
    }

    protected function serviceIsActive(string $serviceName): ?bool
    {
        $status = $this->serviceStatus($serviceName);

        if ($status === null) {
            return null;
        }

        return trim($status) === 'active';
    }

    protected function serviceStatus(string $serviceName): ?string
    {
        if (! function_exists('shell_exec')) {
            return null;
        }

        try {
            $output = @shell_exec(sprintf('systemctl is-active %s 2>/dev/null', escapeshellarg($serviceName)));
            if ($output === null) {
                return null;
            }

            $status = trim($output);
            return $status !== '' ? $status : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
