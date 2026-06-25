<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Order;
use App\Restaurant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashCollectionController extends Controller
{
    private const STATUSES = [
        'pending_collection',
        'collected',
        'collection_failed',
        'disputed',
    ];

    public function index(Request $request)
    {
        $this->validateFilters($request);
        $query = $this->filteredQuery($request);

        $orders = $query
            ->latest('orders.created_at')
            ->paginate(50)
            ->withQueryString();

        $summaryQuery = $this->filteredQuery($request, false);
        $summary = (clone $summaryQuery)
            ->selectRaw('cash_collection_status, COUNT(*) AS total')
            ->groupBy('cash_collection_status')
            ->pluck('total', 'cash_collection_status');

        $totalAmount = (float) (clone $summaryQuery)->sum('total');

        $restaurants = Restaurant::query()
            ->whereIn('id', function ($subQuery) {
                $subQuery->from('orders')
                    ->select('restaurant_id')
                    ->where('payment_method', 'cash')
                    ->whereNotNull('cash_collection_status')
                    ->distinct();
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.order.cash_collections', compact(
            'orders',
            'summary',
            'totalAmount',
            'restaurants'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->validateFilters($request);
        $query = $this->filteredQuery($request)
            ->orderBy('orders.id');

        $filename = 'encaissements-cash-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'wb');

            // BOM UTF-8 pour Excel.
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'N° commande',
                'Date commande',
                'Restaurant',
                'Livreur',
                'Statut collecte',
                'Montant total (FCFA)',
                'Collecté le',
                'Confirmé le',
                'Référence / note',
            ], ';');

            $query->chunkById(500, function ($orders) use ($output): void {
                foreach ($orders as $order) {
                    fputcsv($output, [
                        $order->order_no,
                        $this->formatDate($order->created_at),
                        $order->restaurant->name ?? '',
                        $order->driver->name ?? '',
                        $order->cash_collection_status,
                        (float) ($order->total ?? 0),
                        $this->formatDate($order->cash_collected_at),
                        $this->formatDate($order->cash_collection_confirmed_at),
                        $order->cash_collection_reference ?? '',
                    ], ';');
                }
            }, 'orders.id', 'id');

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function filteredQuery(Request $request, bool $withRelations = true): Builder
    {
        $representativeOrderIds = DB::table('orders')
            ->selectRaw('MIN(id)')
            ->where('payment_method', 'cash')
            ->whereNotNull('cash_collection_status')
            ->groupBy('order_no');

        $query = Order::query()
            ->whereIn('orders.id', $representativeOrderIds);

        if ($withRelations) {
            $query->with(['restaurant:id,name', 'user:id,name', 'driver:id,name']);
        }

        $status = (string) $request->query('status', '');
        if (in_array($status, self::STATUSES, true)) {
            $query->where('cash_collection_status', $status);
        }

        if ($request->filled('restaurant_id')) {
            $query->where('restaurant_id', (int) $request->query('restaurant_id'));
        }

        if ($request->filled('order_no')) {
            $orderNo = trim((string) $request->query('order_no'));
            $query->where('order_no', 'like', '%' . addcslashes($orderNo, '%_') . '%');
        }

        $this->applyDateFilter($query, $request);

        return $query;
    }

    private function validateFilters(Request $request): void
    {
        $request->validate([
            'status' => 'nullable|in:' . implode(',', self::STATUSES),
            'restaurant_id' => 'nullable|integer|exists:restaurants,id',
            'order_no' => 'nullable|string|max:100',
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
        ]);
    }

    private function applyDateFilter(Builder $query, Request $request): void
    {
        if ($request->filled('date_from')) {
            $query->where('orders.created_at', '>=', Carbon::createFromFormat('Y-m-d', $request->query('date_from'))->startOfDay());
        }

        if ($request->filled('date_to')) {
            $query->where('orders.created_at', '<=', Carbon::createFromFormat('Y-m-d', $request->query('date_to'))->endOfDay());
        }
    }

    private function formatDate($value): string
    {
        if (! $value) {
            return '';
        }

        return Carbon::parse($value)
            ->timezone(config('app.timezone'))
            ->format('d/m/Y H:i');
    }
}
