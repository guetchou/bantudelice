<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\ConfigService;

class RegisterEmail extends Mailable
{
    use Queueable, SerializesModels;
    public $data;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $fromAddress = ConfigService::getNoreplyEmail();
        $fromName = ConfigService::getCompanyName();
        $subject = ConfigService::getRegistrationEmailSubject();
        
        return $this->from($fromAddress, $fromName)
                    ->subject($subject)
                    ->view("mail.registerEmail")
                    ->with('data', $this->data);
    }
}
