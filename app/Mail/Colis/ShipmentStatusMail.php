<?php

namespace App\Mail\Colis;

use App\Domain\Colis\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ShipmentStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public $shipment;

    public function __construct(Shipment $shipment)
    {
        $this->shipment = $shipment;
    }

    public function build()
    {
        return $this->subject("Suivi de votre colis BantuDelice : " . $this->shipment->status->label())
                    ->markdown('emails.colis.status_changed');
    }
}

