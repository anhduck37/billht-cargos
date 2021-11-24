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
    public $type;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $type)
    {
        //
        $this->data = $data;
        $this->type = $type;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $template = '';
        $title = '';
        if($this->type == 2){
            $template = $this->view('template.email_success');
            $title = 'Đơn hàng '. $this->data->order_code .' của bạn đã được chuyển thành công!';
        } else {
            $template = $this->view('template.email_confirm');
            $title = 'Đơn hàng '. $this->data->order_code .' của bạn đã được xác nhận!';
        }
        return $template->from($this->data->sender->sender_email, 'HT Express')
                ->subject($title)
                ->with('order', $this->data);
    }
}
