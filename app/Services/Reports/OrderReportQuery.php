<?php

namespace App\Services\Reports;

use App\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderReportQuery
{
    public function filters(Request $request): array
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
            'restaurant_id' => 'nullable|integer|exists:restaurants,id',
            'payment_method' => 'nullable|string|max:50',
            'business_status' => 'nullable|string|max:80',
        ]);

        $from = Carbon::createFromFormat(
            'Y-m-d',
            $validated['date_from'] ?? now()->startOfMonth()->format('Y-m-d')
        )->startOfDay();
        $to = Carbon::createFromFormat(
            'Y-m-d',
            $validated['date_to'] ?? now()->format('Y-m-d')
        )->endOfDay();

        abort_if($from->diffInDays($to) > 366, 422, 'La période ne peut pas dépasser 366 jours.');

        return [
            'date_from' => $from,
            'date_to' => $to,
            'restaurant_id' => $validated['restaurant_id'] ?? null,
            'payment_method' => trim((string) ($validated['payment_method'] ?? '')) ?: null,
            'business_status' => trim((string) ($validated['business_status'] ?? '')) ?: null,
        ];
    }

    public function orders(array $filters): Builder
    {
        $query = Order::query()->whereIn('orders.id', $this->representativeIds());
        $this->apply($query, $filters);

        return $query;
    }

    public function representativeIds()
    {
        return DB::table('orders')->selectRaw('MIN(id)')->groupBy('order_no');
    }

    public function apply($query, array $filters): void
    {
        $query->whereBetween('orders.created_at', [$filters['date_from'], $filters['date_to']]);

        foreach (['restaurant_id', 'payment_method', 'business_status'] as $field) {
            if ($filters[$field]) {
                $query->where('orders.' . $field, $filters[$field]);
            }
        }
    }
}
