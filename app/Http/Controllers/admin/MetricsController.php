<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\MetricsService;
use Illuminate\Http\Request;

/**
 * Controller pour les métriques et KPIs admin
 */
class MetricsController extends Controller
{
    protected $metricsService;

    public function __construct(MetricsService $metricsService)
    {
        $this->metricsService = $metricsService;
    }

    /**
     * Dashboard métriques (vue principale)
     * 
     * GET /admin/metrics
     */
    public function index()
    {
        $realtimeMetrics = $this->metricsService->getRealtimeMetrics();
        $historicalMetrics = $this->metricsService->getHistoricalMetrics(30);
        
        return view('admin.metrics.dashboard', compact('realtimeMetrics', 'historicalMetrics'));
    }

    /**
     * API: Métriques temps réel (JSON)
     * 
     * GET /api/admin/metrics/realtime
     */
    public function realtime()
    {
        return response()->json([
            'status' => true,
            'data' => $this->metricsService->getRealtimeMetrics(),
            'timestamp' => now()->toIso8601String()
        ]);
    }

    /**
     * API: Métriques historiques (JSON)
     * 
     * GET /api/admin/metrics/historical?days=30
     */
    public function historical(Request $request)
    {
        $days = (int) $request->get('days', 30);
        $days = max(1, min(365, $days)); // Limiter entre 1 et 365 jours

        return response()->json([
            'status' => true,
            'data' => $this->metricsService->getHistoricalMetrics($days),
            'days' => $days
        ]);
    }
}

