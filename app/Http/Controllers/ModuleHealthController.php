<?php

namespace App\Http\Controllers;

use App\Services\OperationalHealthService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class ModuleHealthController extends Controller
{
    protected $health;

    public function __construct(OperationalHealthService $health)
    {
        $this->health = $health;
    }

    public function live()
    {
        return response()->json([
            'status' => 'ok',
            'service' => config('app.name'),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function ready()
    {
        $checks = [
            'database' => false,
            'redis' => null,
        ];

        try {
            DB::select('select 1 as ok');
            $checks['database'] = true;
        } catch (\Throwable $e) {
            report($e);
        }

        $redisRequired = config('cache.default') === 'redis'
            || config('queue.default') === 'redis'
            || config('session.driver') === 'redis';

        if ($redisRequired) {
            try {
                $pong = Redis::connection()->ping();
                $checks['redis'] = $pong === true || $pong === 'PONG';
            } catch (\Throwable $e) {
                $checks['redis'] = false;
                report($e);
            }
        }

        $ready = $checks['database'] === true
            && ($checks['redis'] === null || $checks['redis'] === true);

        return response()->json([
            'status' => $ready ? 'ok' : 'unavailable',
            'ready' => $ready,
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $ready ? 200 : 503);
    }

    public function index()
    {
        return response()->json([
            'status' => 'ok',
            'modules' => $this->health->modules(),
        ]);
    }

    public function show(string $module)
    {
        $payload = $this->health->module($module);

        if (! $payload) {
            return response()->json([
                'status' => 'error',
                'message' => 'Module inconnu.',
                'module' => $module,
            ], 404);
        }

        return response()->json($payload);
    }

    public function dependencies()
    {
        return response()->json([
            'status' => 'ok',
            'dependencies' => $this->health->dependencies(),
        ]);
    }

    public function queues()
    {
        return response()->json([
            'status' => 'ok',
            'queues' => $this->health->queues(),
        ]);
    }

    public function workers()
    {
        return response()->json([
            'status' => 'ok',
            'workers' => $this->health->workers(),
        ]);
    }
}
