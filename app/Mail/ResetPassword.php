<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ResetPassword extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $token;
    public $code;
    public $referer;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user , $token , $code , $referer)
    {
        //
        $this->user = $user;
        $this->token = $token;
        $this->code = $code;
        $this->referer = $referer;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('auth.password.reset')->with([
            'url' => 'http://'.$this->referer.'/reset/index.html?token='.$this->token.'&email='.$this->user->getEmailForPasswordReset(),
            'code'=>$this->code
        ]);
    }
}
