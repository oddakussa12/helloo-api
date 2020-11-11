<?php

namespace App\Jobs;

use Carbon\Carbon;
use SmsManager;
use Aws\Sns\SnsClient;
use Illuminate\Bus\Queueable;
use App\Messages\SignInMessage;
use Aws\Exception\AwsException;
use Aws\Credentials\Credentials;
use Illuminate\Support\Facades\DB;
use App\Custom\EasySms\PhoneNumber;
use Illuminate\Queue\SerializesModels;
use App\Messages\ForgetPasswordMessage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;

class EasySms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * @var null
     */
    private $phone;

    /**
     * @var object
     */
    private $message;
    private $callback;

    public function __construct($phone ,  $message , $callback=null)
    {
        $this->phone = $phone;

        $this->message = $message;

        $this->callback = $callback;
    }

    /**
     * Execute the job.
     *
     * @return mixed
     */
    public function handle()
    {
        $sms = app('easy-sms');
        $now = Carbon::now()->toDateTimeString();
        if($this->phone instanceof PhoneNumberInterface)
        {
            $phone = $this->phone->getUniversalNumber();
        }else{
            $phone = $this->phone;
        }
        if($this->message instanceof MessageInterface)
        {
            $code = $this->message->code;
        }else{
            $message = $this->message;
            $code = $message['code'];
        }
        $id = DB::table('short_messages')->insertGetId(
            array(
                'phone'=>$phone,
                'code'=>$code ,
                'created_at'=>$now,
                'updated_at'=>$now,
            )
        );
        try{
            $sms->send($this->phone, $this->message);
            if($this->callback instanceof \Closure)
            {
                $callback = $this->callback;
                $callback();
            }
            $result = 1;
            $messages = 'success';
        }catch (NoGatewayAvailableException $e)
        {
            $result = 0;
            $messages = array();
            $exceptions = $e->getExceptions();
            foreach ($exceptions as $gateway=>$exception)
            {
                \Log::error($exception->getMessage());
                $messages[$gateway] = $exception->getMessage();
            }
        }
        DB::table('short_messages')->where('id' , $id)->update(
            array('message'=>$messages , 'status'=>$result)
        );
    }
}
