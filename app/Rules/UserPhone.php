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
            $phone = $numberProto->getNationalNumber();
            $phoneCountry = $numberProto->getCountryCode();
            if($result===true)
            {
                if($phoneCountry=='86')
                {
                    $result = (bool)preg_match('/^[1](([3][0-9])|([4][5-9])|([5][0-3,5-9])|([6][5,6,7])|([7][0-8])|([8][0-9])|([9][1,8,9]))[0-9]{8}$/', $phone, $matches);
                }
            }else{
                if($phoneCountry=='62'&&substr($phone , 0 , 2)=='62')
                {
                    $phone = substr($phone , 2);
                    $numberProto = $phoneUtil->parse("+".$phoneCountry.$phone);
                    $result = $phoneUtil->isValidNumber($numberProto);
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
                Log::info('phone_valid_error' , $error);
            }
            return $result;
        } catch (NumberParseException $e) {
            $error = array(
                'type'=>'phone_error',
                'ip'=>getRequestIpAddress(),
                'url'=>request()->getPathInfo(),
                'route'=>request()->route()->getName(),
                'params'=>request()->all(),
                'error_type'=>$e->getErrorType(),
                'error_message'=>$e->getMessage()
            );
            Log::info('phone_error' , $error);
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