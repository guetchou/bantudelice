<?php

namespace App\Services;

use App\Driver;
use App\Services\CommerceSignalService;
use App\Jobs\SendOrderChatNotificationJob;
use App\Order;
use App\OrderChat;
use App\OrderChatMessage;
use App\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderChatService
{
    public function threadForOrder(Order $order): OrderChat
    {
        $order->loadMissing(['restaurant', 'delivery.driver']);

        $driverId = $this->resolveDriverId($order);

        $thread = OrderChat::firstOrNew(['order_id' => $order->id]);
        $thread->fill([
            'order_no' => $order->order_no,
            'customer_user_id' => $order->user_id,
            'restaurant_user_id' => $order->restaurant->user_id ?? null,
            'driver_id' => $driverId,
        ]);
        $thread->save();

        return $thread;
    }

    public function resolveRole(?User $user, Order $order): ?string
    {
        if (!$user) {
            return null;
        }

        if ((int) $order->user_id === (int) $user->id) {
            return 'customer';
        }

        if ($order->restaurant && (int) ($order->restaurant->user_id ?? 0) === (int) $user->id) {
            return 'restaurant';
        }

        $driver = $this->resolveDriverForUser($user);
        if ($driver && ((int) ($order->driver_id ?? 0) === (int) $driver->id || (int) optional($order->delivery)->driver_id === (int) $driver->id)) {
            return 'driver';
        }

        if (($user->type ?? null) === 'admin') {
            return 'admin';
        }

        return null;
    }

    public function viewDataForOrder(Order $order, ?User $user, bool $touchRead = true): ?array
    {
        $role = $this->resolveRole($user, $order);

        if (!$role) {
            return null;
        }

        $thread = $this->threadForOrder($order);
        $thread->load(['customer', 'restaurantUser', 'driver']);
        $unreadCount = $this->unreadCountForThread($thread, $role);

        if ($touchRead) {
            $this->touchRead($thread, $role);
            $thread->refresh();
        }

        $messages = $thread->messages()
            ->orderBy('id')
            ->get()
            ->map(fn (OrderChatMessage $message) => $this->messagePayload($message, $role, $thread))
            ->values()
            ->all();

        return [
            'can_view' => true,
            'can_write' => in_array($role, ['customer', 'restaurant', 'driver', 'admin'], true),
            'role' => $role,
            'thread_id' => $thread->id,
            'order_no' => $order->order_no,
            'title' => sprintf('Conversation commande #%s', $order->order_no),
            'participants' => $this->participantsPayload($thread, $order),
            'messages' => $messages,
            'unread_count' => $unreadCount,
            'unread_label' => $this->badgeLabelForCount($unreadCount),
            'messages_url' => route('orders.chat.messages', ['orderNo' => $order->order_no]),
            'store_url' => route('orders.chat.store', ['orderNo' => $order->order_no]),
        ];
    }

    public function badgeDataForOrder(Order $order, ?User $user): array
    {
        $role = $this->resolveRole($user, $order);

        return $this->badgeDataForOrderNo((string) ($order->order_no ?? ''), $role);
    }

    public function badgeDataForOrderNo(string $orderNo, ?string $role): array
    {
        $payload = [
            'order_no' => $orderNo,
            'role' => $role,
            'count' => 0,
            'unread_count' => 0,
            'has_unread' => false,
            'label' => null,
            'thread_id' => null,
        ];

        if (!$role || $orderNo === '') {
            return $payload;
        }

        $thread = OrderChat::where('order_no', $orderNo)->first();
        if (!$thread) {
            return $payload;
        }

        $count = $this->unreadCountForThread($thread, $role);

        $payload['thread_id'] = $thread->id;
        $payload['count'] = $count;
        $payload['unread_count'] = $count;
        $payload['has_unread'] = $count > 0;
        $payload['label'] = $this->badgeLabelForCount($count);

        return $payload;
    }

    public function sendMessage(Order $order, User $user, string $message): OrderChatMessage
    {
        $role = $this->resolveRole($user, $order);

        if (!$role) {
            throw new InvalidArgumentException('Vous ne pouvez pas écrire dans cette conversation.');
        }

        $body = trim($message);
        if ($body === '') {
            throw new InvalidArgumentException('Le message est vide.');
        }

        $thread = $this->threadForOrder($order);

        $chatMessage = DB::transaction(function () use ($thread, $user, $role, $body) {
            $message = $thread->messages()->create([
                'sender_user_id' => $user->id,
                'sender_role' => $role,
                'message' => $body,
            ]);

            $thread->forceFill([
                'last_message_at' => now(),
                'last_message_by_role' => $role,
            ])->save();

            $this->touchRead($thread, $role);

            return $message;
        });

        SendOrderChatNotificationJob::dispatch($chatMessage->id);
        app(CommerceSignalService::class)->emitOrder($order, 'order_chat.message_sent', [
            'module' => 'food',
            'severity' => 'info',
            'sender_role' => $role,
            'message_id' => $chatMessage->id,
            'subject_type' => OrderChatMessage::class,
            'subject_id' => $chatMessage->id,
        ]);

        return $chatMessage;
    }

    public function broadcastMessageById(int $messageId): void
    {
        $message = OrderChatMessage::with(['chat.order.restaurant', 'chat.order.delivery.driver', 'chat.customer', 'chat.restaurantUser', 'chat.driver'])
            ->find($messageId);

        if (!$message || !$message->chat || !$message->chat->order) {
            return;
        }

        $order = $message->chat->order;
        $order->loadMissing(['restaurant', 'delivery.driver', 'user']);

        $title = sprintf('Message sur la commande #%s', $order->order_no);
        $body = mb_strimwidth((string) $message->message, 0, 120, '…');

        $recipients = [];

        if ($message->sender_role !== 'customer' && $order->user_id) {
            $recipients[] = ['type' => 'user', 'id' => $order->user_id];
        }

        if ($message->sender_role !== 'restaurant' && $order->restaurant && $order->restaurant->user_id) {
            $recipients[] = ['type' => 'user', 'id' => $order->restaurant->user_id];
        }

        $driverId = $this->resolveDriverId($order);
        if ($message->sender_role !== 'driver' && $driverId) {
            $recipients[] = ['type' => 'driver', 'id' => $driverId];
        }

        foreach ($recipients as $recipient) {
            if ($recipient['type'] === 'user') {
                NotificationService::sendToUser($recipient['id'], $title, $body, [
                    'key' => 'order_chat_message',
                    'channel' => 'user',
                    'order_no' => $order->order_no,
                    'sender_role' => $message->sender_role,
                ]);
            } elseif ($recipient['type'] === 'driver') {
                NotificationService::sendToDriver($recipient['id'], $title, $body, [
                    'key' => 'order_chat_message',
                    'order_no' => $order->order_no,
                    'sender_role' => $message->sender_role,
                ]);
            }
        }
    }

    public function messagesPayload(OrderChat $thread, string $currentRole): array
    {
        return $thread->messages()
            ->orderBy('id')
            ->get()
            ->map(fn (OrderChatMessage $message) => $this->messagePayload($message, $currentRole, $thread))
            ->values()
            ->all();
    }

    public function touchRead(OrderChat $thread, string $role): void
    {
        $field = match ($role) {
            'customer' => 'customer_last_read_at',
            'restaurant' => 'restaurant_last_read_at',
            'driver' => 'driver_last_read_at',
            'admin' => 'admin_last_read_at',
            default => null,
        };

        if (!$field) {
            return;
        }

        $thread->forceFill([$field => now()])->save();
    }

    protected function unreadCountForThread(OrderChat $thread, string $role): int
    {
        $field = $this->readFieldForRole($role);
        if (!$field) {
            return 0;
        }

        $query = $thread->messages()->where('sender_role', '!=', $role);

        if (!empty($thread->{$field})) {
            $query->where('created_at', '>', $thread->{$field});
        }

        return (int) $query->count();
    }

    protected function readFieldForRole(string $role): ?string
    {
        return match ($role) {
            'customer' => 'customer_last_read_at',
            'restaurant' => 'restaurant_last_read_at',
            'driver' => 'driver_last_read_at',
            'admin' => 'admin_last_read_at',
            default => null,
        };
    }

    protected function badgeLabelForCount(int $count): ?string
    {
        if ($count <= 0) {
            return null;
        }

        return $count === 1 ? '1 nouveau message' : $count . ' nouveaux messages';
    }

    protected function participantsPayload(OrderChat $thread, Order $order): array
    {
        $order->loadMissing(['restaurant', 'delivery.driver', 'user']);
        $readFields = [
            'customer' => $thread->customer_last_read_at,
            'restaurant' => $thread->restaurant_last_read_at,
            'driver' => $thread->driver_last_read_at,
            'admin' => $thread->admin_last_read_at,
        ];

        return [
            'customer' => [
                'label' => 'Client',
                'name' => $order->user->name ?? 'Client',
                'read_at' => optional($readFields['customer'])->toIso8601String(),
                'read_label' => $this->readLabel($readFields['customer']),
            ],
            'restaurant' => [
                'label' => 'Restaurant',
                'name' => $order->restaurant->name ?? 'Restaurant',
                'read_at' => optional($readFields['restaurant'])->toIso8601String(),
                'read_label' => $this->readLabel($readFields['restaurant']),
            ],
            'driver' => [
                'label' => 'Livreur',
                'name' => optional($order->delivery?->driver)->name ?? optional($order->driver)->name ?? 'Livreur',
                'read_at' => optional($readFields['driver'])->toIso8601String(),
                'read_label' => $this->readLabel($readFields['driver']),
            ],
            'admin' => [
                'label' => 'Support',
                'name' => 'Admin',
                'read_at' => optional($readFields['admin'])->toIso8601String(),
                'read_label' => $this->readLabel($readFields['admin']),
            ],
        ];
    }

    protected function messagePayload(OrderChatMessage $message, string $currentRole, ?OrderChat $thread = null): array
    {
        $roleLabels = [
            'customer' => 'Client',
            'restaurant' => 'Restaurant',
            'driver' => 'Livreur',
            'admin' => 'Support',
            'system' => 'Système',
        ];

        $mine = $message->sender_role === $currentRole;
        $seenByRoles = $thread ? $this->seenByRolesForMessage($message, $thread, $currentRole) : [];

        return [
            'id' => $message->id,
            'role' => $message->sender_role,
            'mine' => $mine,
            'label' => $mine && $currentRole !== 'admin' ? 'Vous' : ($roleLabels[$message->sender_role] ?? ucfirst($message->sender_role)),
            'body' => $message->message,
            'time' => optional($message->created_at)->format('d/m à H:i'),
            'seen_by_roles' => $seenByRoles,
            'seen_count' => count($seenByRoles),
            'seen_label' => $this->seenLabel($seenByRoles),
        ];
    }

    protected function seenByRolesForMessage(OrderChatMessage $message, OrderChat $thread, string $currentRole): array
    {
        $roles = ['customer', 'restaurant', 'driver', 'admin'];
        $seenBy = [];

        foreach ($roles as $role) {
            if ($role === $message->sender_role) {
                continue;
            }

            $field = $this->readFieldForRole($role);
            if (!$field) {
                continue;
            }

            $readAt = $thread->{$field} ?? null;
            if ($readAt && $message->created_at && $message->created_at->lte($readAt)) {
                $seenBy[] = $role;
            }
        }

        if ($currentRole === 'admin') {
            return $seenBy;
        }

        return array_values(array_unique($seenBy));
    }

    protected function seenLabel(array $seenByRoles): ?string
    {
        if (empty($seenByRoles)) {
            return null;
        }

        $labels = array_map(function (string $role) {
            return match ($role) {
                'customer' => 'client',
                'restaurant' => 'restaurant',
                'driver' => 'livreur',
                default => $role,
            };
        }, $seenByRoles);

        return 'Vu par ' . implode(', ', $labels);
    }

    protected function readLabel($readAt): ?string
    {
        if (empty($readAt)) {
            return null;
        }

        try {
            if (is_object($readAt) && method_exists($readAt, 'format')) {
                return 'Lu le ' . $readAt->format('d/m à H:i');
            }

            return 'Lu';
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function resolveDriverId(Order $order): ?int
    {
        if (!empty($order->delivery?->driver_id)) {
            return (int) $order->delivery->driver_id;
        }

        if (!empty($order->driver_id)) {
            return (int) $order->driver_id;
        }

        return null;
    }

    protected function resolveDriverForUser(User $user): ?Driver
    {
        $driver = Driver::where('email', $user->email)
            ->orWhere('phone', $user->phone)
            ->first();

        if (!$driver && ($user->type ?? null) === 'driver') {
            $driver = Driver::where('name', $user->name)->first();
        }

        return $driver;
    }
}
