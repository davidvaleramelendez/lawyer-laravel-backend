<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OnlineAnfrage extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $ticket;
    public $to;
    public $from;
    public $name;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($ticket, $name)
    {
        $this->ticket = $ticket;
        $this->name = $name;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('contact-imap');
    }
}
