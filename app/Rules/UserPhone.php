<?php
namespace App\Rules;

use libphonenumber\PhoneNumberUtil;
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
            if($result===false)
            {
                \Log::error('ValidNumber one');
                \Log::error(request()->route()->getName());
                \Log::error(\json_encode(request()->all()));
            }else{
                $phone = $numberProto->getNationalNumber();
                $phoneCountry = $numberProto->getCountryCode();
                if($phoneCountry=='86')
                {
                    $result = (bool)preg_match('/^[1](([3][0-9])|([4][5-9])|([5][0-3,5-9])|([6][5,6])|([7][0-8])|([8][0-9])|([9][1,8,9]))[0-9]{8}$/', $phone, $matches);
                }
            }
            return $result;
        } catch (\libphonenumber\NumberParseException $e) {
            \Log::error('ValidNumber two');
            \Log::error(getRequestIpAddress());
            \Log::error(request()->route()->getName());
            \Log::error(\json_encode(request()->all()));
            \Log::error($e->getMessage().":".$value);
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