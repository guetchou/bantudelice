<?php

namespace App\Mail;

use App\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Services\ConfigService;
use App\Services\OrderTrackingTokenService;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public int $tries = 1;

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function build(): static
    {
        return $this
            ->from(ConfigService::getNoreplyEmail(), ConfigService::getCompanyName())
            ->subject('Commande confirmée #' . $this->order->order_no . ' — BantuDelice')
            ->view('mail.orderConfirmation')
            ->with([
                'order' => $this->order,
                'trackingUrl' => app(OrderTrackingTokenService::class)->publicUrlForOrder($this->order),
            ]);
    }
}
