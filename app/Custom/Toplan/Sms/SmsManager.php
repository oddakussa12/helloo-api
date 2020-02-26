<?php

namespace App\Custom\Toplan\Sms;

use URL;
use PhpSms;
use Validator;
use Toplan\Sms\SmsManager as Manager;

class SmsManager extends Manager
{
    public function requestVerifySms()
    {
        $minutes = self::getCodeValidMinutes();
        $code = $this->verifyCode();
        \Log::error($code);
        $for = $this->input(self::getMobileField());
        \Log::error($for);
        $content = $this->generateSmsContent($code, $minutes);
        $templates = $this->generateTemplates(self::VERIFY_SMS);
        \Log::error($templates);
        $tplData = $this->generateTemplateData($code, $minutes, self::VERIFY_SMS);

        $result = PhpSms::make($templates)->to($for)->data($tplData)->content($content)->send();
        if ($result === null || $result === true || (isset($result['success']) && $result['success'])) {
            $this->state['sent'] = true;
            $this->state['to'] = $for;
            $this->state['code'] = $code;
            $this->state['deadline'] = time() + ($minutes * 60);
            $this->storeState();
            $this->setCanResendAfter(self::getInterval());

            return self::generateResult(true, 'sms_sent_success');
        }

        return self::generateResult(false, 'sms_sent_failure');
    }

}
