<?php
namespace App\Rules;

use libphonenumber\PhoneNumberUtil;
use Illuminate\Support\Facades\Log;
use libphonenumber\NumberParseException;
use Illuminate\Contracts\Validation\Rule;

class UserPhone implements Rule
{
    private $attribute;
    /**
     * 判断验证规则是否通过。
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $value = "+".$value;
        $phoneUtil = PhoneNumberUtil::getInstance();
        try {
            $numberProto = $phoneUtil->parse($value);
            $result = $phoneUtil->isValidNumber($numberProto);
            if($result===true)
            {
                $phone = $numberProto->getNationalNumber();
                $phoneCountry = $numberProto->getCountryCode();
                if($phoneCountry=='86')
                {
                    $result = (bool)preg_match('/^[1](([3][0-9])|([4][5-9])|([5][0-3,5-9])|([6][5,6])|([7][0-8])|([8][0-9])|([9][1,8,9]))[0-9]{8}$/', $phone, $matches);
                }
            }
            if($result===false)
            {
                $error = array(
                    'type'=>'phone_valid_error',
                    'ip'=>getRequestIpAddress(),
                    'url'=>request()->getPathInfo(),
                    'route'=>request()->route()->getName(),
                    'params'=>request()->all(),
                );
                Log::error(\json_encode($error , JSON_UNESCAPED_UNICODE));
            }
            return $result;
        } catch (NumberParseException $e) {
            $error = array(
                'type'=>'phone_error',
                'ip'=>getRequestIpAddress(),
                'url'=>request()->getPathInfo(),
                'route'=>request()->route()->getName(),
                'params'=>request()->all(),
                'message'=>$e->getMessage(),
            );
            Log::error(\json_encode($error , JSON_UNESCAPED_UNICODE));
            return false;
        }
    }

    /**
     * 获取验证错误信息。
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.custom.phone.wrong_format');
    }


}