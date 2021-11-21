<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $title;
    public $from_address;
    public $from_sender;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $title, $from_address, $from_sender)
    {
        //
        $this->data = $data;
        $this->title = $title;
        $this->from_address = $from_address;
        $this->from_sender = $from_sender;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('template.email')
            ->from($this->from_address, $this->from_sender)
            ->subject($this->title)
            ->with('data', $this->data);
    }
}
