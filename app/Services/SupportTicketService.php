<?php

namespace App\Services;

use App\Delivery;
use App\Order;
use App\Payment;
use App\SupportTicket;
use Illuminate\Support\Facades\Schema;

class SupportTicketService
{
    public function open(array $payload): ?SupportTicket
    {
        if (!Schema::hasTable('support_tickets')) {
            return null;
        }

        $ticket = SupportTicket::create(array_merge([
            'module' => $payload['module'] ?? 'food',
            'category' => $payload['category'] ?? 'incident',
            'priority' => $payload['priority'] ?? 'normal',
            'status' => $payload['status'] ?? 'open',
            'title' => $payload['title'] ?? 'Support ticket',
            'description' => $payload['description'] ?? null,
            'subject_type' => $payload['subject_type'] ?? null,
            'subject_id' => $payload['subject_id'] ?? null,
            'order_id' => $payload['order_id'] ?? null,
            'order_no' => $payload['order_no'] ?? null,
            'payment_id' => $payload['payment_id'] ?? null,
            'delivery_id' => $payload['delivery_id'] ?? null,
            'shipment_id' => $payload['shipment_id'] ?? null,
            'transport_booking_id' => $payload['transport_booking_id'] ?? null,
            'opened_by_type' => $payload['opened_by_type'] ?? null,
            'opened_by_id' => $payload['opened_by_id'] ?? null,
            'assigned_to_id' => $payload['assigned_to_id'] ?? null,
            'assigned_to_type' => $payload['assigned_to_type'] ?? null,
            'last_activity_at' => now(),
            'meta' => $payload['meta'] ?? [],
        ], $payload));

        app(CommerceSignalService::class)->emit('support.ticket_opened', [
            'domain' => 'commerce',
            'module' => $ticket->module,
            'severity' => $ticket->priority === 'high' ? 'warning' : 'info',
            'subject_type' => $ticket->subject_type,
            'subject_id' => $ticket->subject_id,
            'order_id' => $ticket->order_id,
            'order_no' => $ticket->order_no,
            'payment_id' => $ticket->payment_id,
            'payload' => $ticket->meta ?? [],
        ]);

        return $ticket;
    }

    public function openUnique(array $payload, array $uniqueKeys = ['module', 'category', 'order_id', 'subject_type', 'subject_id']): ?SupportTicket
    {
        if (!Schema::hasTable('support_tickets')) {
            return null;
        }

        $query = SupportTicket::query();

        foreach ($uniqueKeys as $key) {
            if (array_key_exists($key, $payload) && $payload[$key] !== null && $payload[$key] !== '') {
                $query->where($key, $payload[$key]);
            }
        }

        $existing = $query->whereIn('status', config('commerce.support.open_statuses', ['open']))->first();
        if ($existing) {
            $existing->update([
                'last_activity_at' => now(),
                'meta' => array_merge($existing->meta ?? [], $payload['meta'] ?? []),
            ]);

            return $existing->fresh();
        }

        return $this->open($payload);
    }

    public function openFromOrder(Order $order, string $category, string $title, string $description = null, array $meta = []): ?SupportTicket
    {
        return $this->open([
            'module' => 'food',
            'category' => $category,
            'title' => $title,
            'description' => $description,
            'subject_type' => Order::class,
            'subject_id' => $order->id,
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'payment_id' => optional($order->payment)->id,
            'opened_by_type' => $meta['opened_by_type'] ?? 'system',
            'opened_by_id' => $meta['opened_by_id'] ?? null,
            'priority' => $meta['priority'] ?? 'normal',
            'status' => $meta['status'] ?? 'open',
            'meta' => $meta,
        ]);
    }

    public function openFromDelivery(Delivery $delivery, string $category, string $title, string $description = null, array $meta = []): ?SupportTicket
    {
        $order = $delivery->order()->with('payment')->first();

        return $this->open([
            'module' => 'food',
            'category' => $category,
            'title' => $title,
            'description' => $description,
            'subject_type' => Delivery::class,
            'subject_id' => $delivery->id,
            'order_id' => $delivery->order_id,
            'order_no' => $order?->order_no,
            'payment_id' => optional($order?->payment)->id,
            'delivery_id' => $delivery->id,
            'opened_by_type' => $meta['opened_by_type'] ?? 'system',
            'opened_by_id' => $meta['opened_by_id'] ?? null,
            'priority' => $meta['priority'] ?? 'normal',
            'status' => $meta['status'] ?? 'open',
            'meta' => $meta,
        ]);
    }

    public function openFromPayment(Payment $payment, string $category, string $title, string $description = null, array $meta = []): ?SupportTicket
    {
        return $this->open([
            'module' => $payment->shipment_id ? 'colis' : ($payment->transport_booking_id ? 'transport' : 'food'),
            'category' => $category,
            'title' => $title,
            'description' => $description,
            'subject_type' => Payment::class,
            'subject_id' => $payment->id,
            'order_id' => $payment->order_id,
            'order_no' => optional($payment->order)->order_no,
            'payment_id' => $payment->id,
            'opened_by_type' => $meta['opened_by_type'] ?? 'system',
            'opened_by_id' => $meta['opened_by_id'] ?? null,
            'priority' => $meta['priority'] ?? 'normal',
            'status' => $meta['status'] ?? 'open',
            'meta' => $meta,
        ]);
    }

    public function resolve(SupportTicket $ticket, string $resolution, array $meta = []): SupportTicket
    {
        $ticket->update([
            'status' => $resolution,
            'resolved_at' => in_array($resolution, ['resolved', 'closed'], true) ? now() : $ticket->resolved_at,
            'resolution_notes' => $meta['resolution_notes'] ?? $ticket->resolution_notes,
            'last_activity_at' => now(),
            'meta' => array_merge($ticket->meta ?? [], $meta),
        ]);

        app(CommerceSignalService::class)->emit('support.ticket_updated', [
            'domain' => 'commerce',
            'module' => $ticket->module,
            'severity' => $resolution === 'resolved' ? 'info' : 'warning',
            'subject_type' => $ticket->subject_type,
            'subject_id' => $ticket->subject_id,
            'order_id' => $ticket->order_id,
            'order_no' => $ticket->order_no,
            'payment_id' => $ticket->payment_id,
            'payload' => array_merge($ticket->meta ?? [], $meta, ['resolution' => $resolution]),
        ]);

        return $ticket->fresh();
    }

    public function updateForDelivery(Delivery $delivery, string $resolution, array $meta = []): void
    {
        if (!Schema::hasTable('support_tickets')) {
            return;
        }

        SupportTicket::query()
            ->where('delivery_id', $delivery->id)
            ->whereIn('status', array_merge(
                config('commerce.support.open_statuses', ['open']),
                ['pending_redelivery', 'pending_review', 'pending_refund']
            ))
            ->orderByDesc('id')
            ->get()
            ->each(function (SupportTicket $ticket) use ($resolution, $meta) {
                $this->resolve($ticket, $resolution, $meta);
            });
    }
}
