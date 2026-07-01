<?php

namespace App\Http\Controllers;

use App\Services\OperationalHealthService;

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
        $payload = $this->health->readiness();

        return response()->json($payload, $payload['ready'] ? 200 : 503);
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
