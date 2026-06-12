<?php

namespace App\Services;

use App\Order;
use Illuminate\Support\Facades\Log;

/**
 * Responsabilité unique : notifications métier food.
 * Traduit un statut métier commande en messages multi-canal (push client, push restaurant, push livreur, SMS).
 */
class FoodOrderNotificationService
{
    public function notifyStatusChange(Order $order, string $businessStatus, array $context = []): void
    {
        $order->loadMissing(['user', 'restaurant', 'delivery.driver']);

        $messages = $order->isPickup()
            ? $this->pickupMessages($order)
            : $this->deliveryMessages($order);

        if (!isset($messages[$businessStatus])) {
            return;
        }

        $payload = $messages[$businessStatus];
        $meta = [
            'key'      => 'food_order_' . $businessStatus,
            'order_no' => $order->order_no,
            'type'     => $businessStatus,
        ];

        try {
            if (isset($payload['user']) && $order->user_id) {
                [$title, $body] = $payload['user'];
                NotificationService::sendToUser($order->user_id, $title, $body, [
                    'key'               => $meta['key'],
                    'module'            => 'food',
                    'channel'           => 'user',
                    'type'              => $meta['type'],
                    'order_no'          => $order->order_no,
                    'route_path'        => NotificationService::routePath('track.order', ['orderNo' => $order->order_no]),
                    'deep_link'         => 'bantudelice://food/orders/' . $order->order_no,
                    'sound_key'         => 'food_status',
                    'audio_cue'         => 'order_status_soft',
                    'actions'           => [
                        ['id' => 'open_order', 'label' => 'Suivre',   'path' => NotificationService::routePath('track.order', ['orderNo' => $order->order_no])],
                        ['id' => 'open_help',  'label' => 'Signaler', 'path' => NotificationService::routePath('track.order', ['orderNo' => $order->order_no]) . '#incident'],
                    ],
                    'websocket_channel' => 'food.order.' . $order->order_no . '.status',
                    'websocket_event'   => 'food.order.status.updated',
                    'presence_channel'  => 'food.order.' . $order->order_no . '.presence',
                ]);

                if (!empty($order->user?->phone)) {
                    SmsService::sendOrderNotification(
                        $order->user->phone,
                        $order->order_no,
                        $this->mapBusinessStatusToSmsStatus($businessStatus),
                        ['driver_name' => $order->delivery->driver->name ?? null]
                    );
                }
            }

            if (isset($payload['restaurant']) && $order->restaurant?->user_id) {
                [$title, $body] = $payload['restaurant'];
                NotificationService::sendToUser($order->restaurant->user_id, $title, $body, [
                    'key'               => $meta['key'],
                    'module'            => 'food',
                    'channel'           => 'restaurant',
                    'type'              => $meta['type'],
                    'order_no'          => $order->order_no,
                    'route_path'        => NotificationService::routePath('restaurant.all_orders', ['focus' => $order->order_no]),
                    'deep_link'         => 'bantudelice://food/restaurant/orders/' . $order->order_no,
                    'sound_key'         => 'food_restaurant_alert',
                    'audio_cue'         => 'restaurant_order_attention',
                    'actions'           => [
                        ['id' => 'open_order', 'label' => 'Voir', 'path' => NotificationService::routePath('restaurant.all_orders', ['focus' => $order->order_no])],
                    ],
                    'websocket_channel' => 'food.restaurant.' . $order->restaurant_id . '.orders',
                    'websocket_event'   => 'food.restaurant.order.updated',
                    'presence_channel'  => 'food.order.' . $order->order_no . '.presence',
                ]);
            }

            if (isset($payload['driver']) && $order->delivery?->driver_id) {
                [$title, $body] = $payload['driver'];
                NotificationService::sendToDriver($order->delivery->driver_id, $title, $body, [
                    'key'               => $meta['key'],
                    'module'            => 'food',
                    'type'              => $meta['type'],
                    'order_no'          => $order->order_no,
                    'route_path'        => NotificationService::routePath('driver.deliveries', ['order_no' => $order->order_no]),
                    'deep_link'         => 'bantudelice://food/deliveries/' . $order->order_no,
                    'sound_key'         => 'food_driver_mission',
                    'audio_cue'         => 'driver_order_assignment',
                    'actions'           => [
                        ['id' => 'open_delivery', 'label' => 'Mission', 'path' => NotificationService::routePath('driver.deliveries', ['order_no' => $order->order_no])],
                    ],
                    'websocket_channel' => 'food.delivery.' . $order->delivery->driver_id . '.orders',
                    'websocket_event'   => 'food.delivery.assignment.updated',
                    'presence_channel'  => 'food.order.' . $order->order_no . '.presence',
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Notification repas échouée', [
                'order_no'        => $order->order_no,
                'business_status' => $businessStatus,
                'error'           => $e->getMessage(),
            ]);
        }
    }

    private function mapBusinessStatusToSmsStatus(string $businessStatus): string
    {
        return match ($businessStatus) {
            'accepted'                      => 'confirmed',
            'in_kitchen'                    => 'preparing',
            'ready_for_pickup', 'driver_assigned' => 'ready',
            'picked_up'                     => 'picked_up',
            'out_for_delivery'              => 'on_the_way',
            'delivered'                     => 'delivered',
            'cancelled'                     => 'cancelled',
            default                         => 'confirmed',
        };
    }

    private function pickupMessages(Order $order): array
    {
        return [
            'pending_restaurant_acceptance' => [
                'user'       => ['Commande reçue', "Votre commande retrait #{$order->order_no} a bien été reçue. En attente d'acceptation du restaurant."],
                'restaurant' => ['Nouvelle commande retrait', "Nouvelle commande retrait #{$order->order_no}. Acceptez ou refusez."],
            ],
            'accepted' => [
                'user'       => ['Commande acceptée', "Le restaurant a accepté votre commande à retirer #{$order->order_no}."],
                'restaurant' => ['Commande acceptée', "La commande retrait #{$order->order_no} est validée."],
            ],
            'in_kitchen' => [
                'user' => ['Préparation en cours', "Votre commande retrait #{$order->order_no} est en préparation."],
            ],
            'ready_for_pickup' => [
                'user'       => ['Commande prête au retrait', "Votre commande #{$order->order_no} est prête. Présentez votre code de retrait au restaurant."],
                'restaurant' => ['Commande prête', "La commande retrait #{$order->order_no} attend le client."],
            ],
            'customer_arrived' => [
                'user'       => ['Présentez votre code', "Le restaurant prépare la remise de votre commande #{$order->order_no}."],
                'restaurant' => ['Client arrivé', "Le client est signalé comme présent pour la commande #{$order->order_no}."],
            ],
            'picked_up_by_customer' => [
                'user'       => ['Commande retirée', "Votre commande #{$order->order_no} a été retirée avec succès."],
                'restaurant' => ['Commande clôturée', "La commande retrait #{$order->order_no} est clôturée."],
            ],
            'no_show' => [
                'user'       => ['Retrait non effectué', "Le retrait de la commande #{$order->order_no} n'a pas été finalisé dans les délais."],
                'restaurant' => ['Client absent', "La commande retrait #{$order->order_no} est en statut client absent."],
            ],
            'closed' => [
                'user'       => ['Retrait clôturé', "La commande retrait #{$order->order_no} est clôturée."],
                'restaurant' => ['Retrait clôturé', "La commande retrait #{$order->order_no} est clôturée."],
            ],
            'cancelled' => [
                'user'       => ['Commande annulée', "La commande #{$order->order_no} a été annulée."],
                'restaurant' => ['Commande annulée', "La commande #{$order->order_no} a été annulée."],
            ],
        ];
    }

    private function deliveryMessages(Order $order): array
    {
        return [
            'pending_restaurant_acceptance' => [
                'user'       => ['Commande reçue', "Votre commande #{$order->order_no} a bien été reçue. En attente d'acceptation du restaurant."],
                'restaurant' => ['Nouvelle commande', "Nouvelle commande #{$order->order_no} reçue. Acceptez-la pour démarrer la préparation."],
            ],
            'accepted' => [
                'user'       => ['Commande acceptée', "Le restaurant a accepté votre commande #{$order->order_no}."],
                'restaurant' => ['Commande acceptée', "La commande #{$order->order_no} est validée pour la cuisine."],
            ],
            'in_kitchen' => [
                'user' => ['Préparation en cours', "Votre commande #{$order->order_no} est en préparation."],
            ],
            'ready_for_pickup' => [
                'user'   => ['Commande prête', "Votre commande #{$order->order_no} est prête au restaurant."],
                'driver' => ['Commande prête', "La commande #{$order->order_no} est prête pour récupération."],
            ],
            'driver_assigned' => [
                'user'   => ['Livreur assigné', "Un livreur a été assigné à votre commande #{$order->order_no}."],
                'driver' => ['Nouvelle mission', "Une livraison vous a été assignée pour la commande #{$order->order_no}."],
            ],
            'picked_up' => [
                'user' => ['Commande récupérée', "Le livreur a récupéré votre commande #{$order->order_no}."],
            ],
            'out_for_delivery' => [
                'user' => ['Commande en route', "Votre commande #{$order->order_no} est en route vers vous."],
            ],
            'delivered' => [
                'user'       => ['Commande livrée', "Votre commande #{$order->order_no} a été livrée."],
                'restaurant' => ['Commande clôturée', "La commande #{$order->order_no} est marquée livrée."],
            ],
            'cancelled' => [
                'user'       => ['Commande annulée', "La commande #{$order->order_no} a été annulée."],
                'restaurant' => ['Commande annulée', "La commande #{$order->order_no} a été annulée."],
                'driver'     => ['Mission annulée', "La livraison liée à la commande #{$order->order_no} a été annulée."],
            ],
        ];
    }
}
