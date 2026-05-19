<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\CommerceAnalyticsService;
use App\Services\CommerceIntegrationRegistry;
use Illuminate\Http\Request;

class CommerceAnalyticsController extends Controller
{
    public function index(Request $request, CommerceAnalyticsService $analytics, CommerceIntegrationRegistry $integrations)
    {
        $overview = $analytics->overview((int) $request->get('days', config('commerce.analytics.window_days', 7)));
        $integrationsReport = $integrations->healthReport();

        return view('admin.commerce_analytics.index', compact('overview', 'integrationsReport'));
    }
}
