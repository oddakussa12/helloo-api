<?php

namespace App\Jobs;

use SmsManager;
use Aws\Sns\SnsClient;
use Illuminate\Bus\Queueable;
use Aws\Exception\AwsException;
use Aws\Credentials\Credentials;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class Sms implements ShouldQueue
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

    public function __construct($phone=null , $code=123456 , $user_phone_country="86" , $type='')
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
        $phone = "+".$this->user_phone_country.$this->phone;
        if($this->user_phone_country=='86')
        {
            $type = $this->type;
            SmsManager::requestVerifySms($phone , $this->code , $type);
        }else{
            if($this->type=='update_phone')
            {
                $message = config('laravel-sms.verifySmsContent') ?: config('laravel-sms.update_phone');
            }else{
                $message = config('laravel-sms.verifySmsContent') ?: config('laravel-sms.forget_password');
            }
            $awsKey = config('phpsms.aws.key');
            $awsSecret = config('phpsms.aws.secret');
            $credentials = new Credentials($awsKey, $awsSecret);
            $smsClient = new SnsClient([
                'region' => 'ap-southeast-1',
                'version' => '2010-03-31',
                'credentials' => $credentials
            ]);
            $content = sprintf($message, $this->code);
            try {
                $result = $smsClient->publish([
                    'Message' => $content,
                    'PhoneNumber' => $phone,
                ]);
            } catch (AwsException $e) {
                \Log::error($e->getMessage());
            }
        }
    }
}
