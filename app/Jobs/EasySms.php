<?php

namespace App\Jobs;

use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use SmsManager;
use Aws\Sns\SnsClient;
use Illuminate\Bus\Queueable;
use App\Messages\SignInMessage;
use Aws\Exception\AwsException;
use Aws\Credentials\Credentials;
use App\Custom\EasySms\PhoneNumber;
use Illuminate\Queue\SerializesModels;
use App\Messages\ForgetPasswordMessage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class EasySms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * @var null
     */
    private $phone;
    /**
     * @var null
     */
    private $code;
    /**
     * @var string
     */
    private $user_phone_country;

    /**
     * @var string
     */
    private $type;

    public function __construct($phone=null , $code='' , $user_phone_country="86" , $type='')
    {
        $this->phone = $phone;
        $this->code = $code;
        $this->user_phone_country = $user_phone_country;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return mixed
     */
    public function handle()
    {
        $sms = app('easy-sms');
        $message = array();
        if($this->type='sign_in')
        {
            $message = new SignInMessage($this->code);
        }elseif($this->type='forget_password'){
            $message = new ForgetPasswordMessage($this->code);
        }

        $phone = new PhoneNumber($this->phone , $this->user_phone_country);

        try{
            $result = $sms->send($phone, $message);
        }catch (NoGatewayAvailableException $e)
        {
            $exceptions = $e->getExceptions();
            foreach ($exceptions as $gateway=>$exception)
            {
                \Log::error($exception->getMessage());
            }
        }
    }
}
