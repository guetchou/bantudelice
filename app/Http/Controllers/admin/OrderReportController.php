<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Restaurant;
use App\Services\Reports\AccountingOrderExporter;
use App\Services\Reports\CommercialOrderExporter;
use App\Services\Reports\OrderReportQuery;
use Illuminate\Http\Request;

class OrderReportController extends Controller
{
    public function index(Request $request, OrderReportQuery $reports)
    {
        $filters = $reports->filters($request);
        $query = $reports->orders($filters);

        $summary = [
            'orders' => (clone $query)->count(),
            'gross_total' => (float) (clone $query)->sum('total'),
            'admin_commission' => (float) (clone $query)->sum('admin_commission'),
            'restaurant_commission' => (float) (clone $query)->sum('restaurant_commission'),
        ];

        $restaurants = Restaurant::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.reports.orders', compact('filters', 'summary', 'restaurants'));
    }

    public function accounting(
        Request $request,
        OrderReportQuery $reports,
        AccountingOrderExporter $exporter
    ) {
        return $exporter->download($reports->filters($request));
    }

    public function commercial(
        Request $request,
        OrderReportQuery $reports,
        CommercialOrderExporter $exporter
    ) {
        return $exporter->download($reports->filters($request));
    }
}
