<?php

namespace App\Jobs;

use App\Services\NotificationService;
use App\User;
use App\Restaurant;
use App\UserToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Job pour envoyer les notifications de commande (client + restaurant)
 */
class SendOrderNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $failOnTimeout = true;

    protected $userId;
    protected $orderNo;
    protected $restaurantId;

    /**
     * Create a new job instance.
     *
     * @param int $userId
     * @param string $orderNo
     * @param int|null $restaurantId
     */
    public function __construct(int $userId, string $orderNo, ?int $restaurantId = null)
    {
        $this->userId = $userId;
        $this->orderNo = $orderNo;
        $this->restaurantId = $restaurantId;
        $this->onConnection(config('module_queues.modules.food.connection', 'database_food'));
        $this->onQueue(config('module_queues.modules.food.queue', 'food'));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Notification à l'utilisateur
        try {
            $userToken = UserToken::where('user_id', $this->userId)->first();
            if ($userToken && $userToken->device_tokens) {
                NotificationService::sendToDevice(
                    $userToken->device_tokens,
                    'Commande confirmée',
                    'Votre commande #' . $this->orderNo . ' a été confirmée et est en préparation.',
                    'orderConfirmed',
                    $this->userId,
                    'user',
                    [
                        'module' => 'food',
                        'order_no' => $this->orderNo,
                        'route_path' => NotificationService::routePath('track.order', ['orderNo' => $this->orderNo]),
                        'deep_link' => 'bantudelice://food/orders/' . $this->orderNo,
                        'sound_key' => 'food_status',
                        'audio_cue' => 'order_status_soft',
                        'actions' => [
                            ['id' => 'open_order', 'label' => 'Suivre', 'path' => NotificationService::routePath('track.order', ['orderNo' => $this->orderNo])],
                        ],
                        'websocket_channel' => 'food.order.' . $this->orderNo . '.status',
                        'websocket_event' => 'food.order.status.updated',
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::warning('Erreur notification client', [
                'user_id' => $this->userId,
                'error' => $e->getMessage()
            ]);
        }

        // Notification au restaurant (push + email fallback web)
        if ($this->restaurantId) {
            try {
                $restaurant = Restaurant::find($this->restaurantId);
                if ($restaurant && $restaurant->user_id) {
                    $restaurantUser = User::where('id', $restaurant->user_id)
                                         ->where('type', 'restaurant')
                                         ->first();
                    if ($restaurantUser) {
                        $restaurantToken = UserToken::where('user_id', $restaurantUser->id)->first();
                        $hasPushToken = $restaurantToken && $restaurantToken->device_tokens;

                        if ($hasPushToken) {
                            NotificationService::sendToDevice(
                                $restaurantToken->device_tokens,
                                'Nouvelle commande',
                                'Nouvelle commande #' . $this->orderNo . ' reçue.',
                                'newOrder',
                                $restaurantUser->id,
                                'restaurant',
                                [
                                    'module'            => 'food',
                                    'order_no'          => $this->orderNo,
                                    'route_path'        => NotificationService::routePath('restaurant.all_orders', ['focus' => $this->orderNo]),
                                    'deep_link'         => 'bantudelice://food/restaurant/orders/' . $this->orderNo,
                                    'sound_key'         => 'food_restaurant_alert',
                                    'audio_cue'         => 'restaurant_order_attention',
                                    'actions'           => [
                                        ['id' => 'open_order', 'label' => 'Voir', 'path' => NotificationService::routePath('restaurant.all_orders', ['focus' => $this->orderNo])],
                                    ],
                                    'websocket_channel' => 'food.restaurant.' . $this->restaurantId . '.orders',
                                    'websocket_event'   => 'food.restaurant.order.updated',
                                ]
                            );
                        }

                        // Fallback email — toujours envoyé (web + app)
                        $emailTo = $restaurantUser->email ?: ($restaurant->email ?? null);
                        if ($emailTo) {
                            $orderUrl = url('/restaurant/show_order/' . $this->orderNo);
                            Mail::send([], [], function ($m) use ($emailTo, $restaurantUser, $restaurant, $orderUrl) {
                                $orderNo  = $this->orderNo;
                                $restName = $restaurant->name ?? 'Votre restaurant';
                                $m->to($emailTo, $restaurantUser->name ?? $restName)
                                  ->subject("🍽️ Nouvelle commande #$orderNo — BantuDelice")
                                  ->html(
                                    "<div style='font-family:sans-serif;max-width:520px;margin:0 auto;padding:24px;'>"
                                    . "<img src='" . url('frontend/images/BuntuDelice.png') . "' alt='BantuDelice' style='height:40px;margin-bottom:20px;'>"
                                    . "<h2 style='color:#009543;margin:0 0 12px;'>Nouvelle commande reçue</h2>"
                                    . "<p style='color:#475569;'>Commande <strong>#$orderNo</strong> pour <strong>$restName</strong>.</p>"
                                    . "<p style='color:#475569;'>Connectez-vous à votre espace pour accepter et préparer cette commande.</p>"
                                    . "<a href='$orderUrl' style='display:inline-block;background:#009543;color:#fff;text-decoration:none;padding:12px 28px;border-radius:99px;font-weight:700;margin-top:16px;'>Voir la commande</a>"
                                    . "<p style='color:#94a3b8;font-size:12px;margin-top:24px;'>BantuDelice — Brazzaville &amp; Pointe-Noire</p>"
                                    . "</div>"
                                  );
                            });
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Erreur notification restaurant', [
                    'restaurant_id' => $this->restaurantId,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array
     */
    public function backoff()
    {
        return [30, 60]; // Retry après 30s, 60s
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("food:send-order-notifications:{$this->orderNo}"))->expireAfter(120),
        ];
    }
}
