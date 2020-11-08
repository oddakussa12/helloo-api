<?php

namespace App\Custom\Toplan\Sms;

use PhpSms;
use Toplan\Sms\SmsManager as Manager;

class SmsManager extends Manager
{
    public function requestVerifySms($for=null , $code=null , $type='')
    {
        $code = blank($code)?mt_rand(111111 , 999999):$code;
        $for = blank($for)?$this->input(self::getMobileField()):$for;
        if($type=='update_phone')
        {
            $templates = config('laravel-sms.other_templates');
        }elseif($type=='sign_in')
        {
            $templates = config('laravel-sms.sign_in');
        }else{
            $templates = $this->generateTemplates(self::VERIFY_SMS);
        }
        $tplData = array('code'=>$code);
        $result = PhpSms::make($templates)->to($for)->data($tplData)->send();
//        if ($result === null || $result === true || (isset($result['success']) && $result['success'])) {
//            $this->state['sent'] = true;
//            $this->state['to'] = $for;
//            $this->state['code'] = $code;
//            $this->state['deadline'] = time() + ($minutes * 60);
////            $this->storeState();
////            $this->setCanResendAfter(self::getInterval());
//
//            return self::generateResult(true, 'sms_sent_success');
//        }
//
//        return self::generateResult(false, 'sms_sent_failure');
    }

}
