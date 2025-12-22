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
use Illuminate\Support\Facades\Log;

/**
 * Job pour envoyer les notifications de commande (client + restaurant)
 */
class SendOrderNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
                    'user'
                );
            }
        } catch (\Exception $e) {
            Log::warning('Erreur notification client', [
                'user_id' => $this->userId,
                'error' => $e->getMessage()
            ]);
        }

        // Notification au restaurant
        if ($this->restaurantId) {
            try {
                $restaurant = Restaurant::find($this->restaurantId);
                if ($restaurant && $restaurant->user_id) {
                    $restaurantUser = User::where('id', $restaurant->user_id)
                                         ->where('type', 'restaurant')
                                         ->first();
                    if ($restaurantUser) {
                        $restaurantToken = UserToken::where('user_id', $restaurantUser->id)->first();
                        if ($restaurantToken && $restaurantToken->device_tokens) {
                            NotificationService::sendToDevice(
                                $restaurantToken->device_tokens,
                                'Nouvelle commande',
                                'Nouvelle commande #' . $this->orderNo . ' reçue.',
                                'newOrder',
                                $restaurantUser->id,
                                'restaurant'
                            );
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
}

