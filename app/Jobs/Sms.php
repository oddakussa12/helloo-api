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
     * @var Credentials
     */
    private $credentials;
    /**
     * @var SnsClient
     */
    private $smsClient;

    public function __construct($phone=null , $code=null , $user_phone_country="86")
    {
        $this->phone = $phone;
        $this->code = $code;
        $this->user_phone_country = $user_phone_country;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $phone = "+".$this->user_phone_country.$this->phone;
        if($this->user_phone_country=='86')
        {
            SmsManager::requestVerifySms($phone , $this->code);
        }else{
            $awsKey = config('phpsms.aws.key');
            $awsSecret = config('phpsms.aws.secret');
            $credentials = new Credentials($awsKey, $awsSecret);
            $smsClient = new SnsClient([
                'region' => 'ap-southeast-1',
                'version' => '2010-03-31',
                'credentials' => $credentials
            ]);
            $message = config('laravel-sms.verifySmsContent') ?: config('laravel-sms.global_content');
            $content = sprintf($message, $this->code);
            try {
                $result = $smsClient->publish([
                    'Message' => $content,
                    'PhoneNumber' => $phone,
                ]);
                \Log::error(\json_encode($result , JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            } catch (AwsException $e) {
                \Log::error($e->getMessage());
            }

        }
    }
}
