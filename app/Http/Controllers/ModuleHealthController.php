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
