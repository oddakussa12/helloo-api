<?php

namespace App\Jobs;

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

    }
}
