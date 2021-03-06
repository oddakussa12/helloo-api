<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use libphonenumber\PhoneNumberUtil;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Messages\Contracts\MessageInterface;
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
    

    private $globalSms;


    public function __construct($phone ,  $message)
    {
        $this->phone = $phone;

        $this->message = $message;

        $this->globalSms = config('common.global_sms');

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
            $phoneCountry = $this->phone->getIDDCode();
        }else{
            $phone = $this->phone;
            $phoneUtil = PhoneNumberUtil::getInstance();
            try {
                $numberProto = $phoneUtil->parse($phone);
                $result = $phoneUtil->isValidNumber($numberProto);
                if($result===true)
                {
                    $phoneCountry = $numberProto->getCountryCode();
                }else{
                    $error = array(
                        'type'=>'send_sms_phone_valid_error',
                        'params'=>$phone,
                    );
                    Log::info('send_sms_phone_valid_error' , $error);
                    return false;
                }
            }catch (\Exception $e)
            {
                $error = array(
                    'type'=>'send_sms_phone_illegal_error',
                    'params'=>$phone,
                );
                Log::info('send_sms_phone_illegal_error' , $error);
                return false;
            }
        }
        if($this->message instanceof MessageInterface)
        {
            $code = $this->message->code;
        }else{
            $message = $this->message;
            $code = $message['code'];
        }
        if($phoneCountry==86)
        {
            $gateways = ['aliYunCNCustom'];
        }else{
            $globalSms = $this->globalSms;
            $gateways = [$globalSms];
        }
        $id = DB::table('short_messages')->insertGetId(
            array(
                'gateways'=>\json_encode($gateways),
                'phone'=>ltrim($phone , "+"),
                'code'=>$code ,
                'created_at'=>$now,
                'updated_at'=>$now,
            )
        );
        try{
            $sms->send($this->phone, $this->message , $gateways);
            if($this->message instanceof MessageInterface&&method_exists($this->message , 'afterSend'))
            {
                $this->message->afterSend($phone);
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
                $messages[$gateway] = $exception->getMessage();
            }
            Log::info('send_sms_phone_fail' , $messages);
        }
        DB::table('short_messages')->where('id' , $id)->update(
            array('message'=>\json_encode($messages , JSON_UNESCAPED_UNICODE) , 'status'=>$result)
        );
    }
}
