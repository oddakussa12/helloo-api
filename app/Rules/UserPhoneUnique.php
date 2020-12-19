<?php
namespace App\Rules;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;
use Illuminate\Contracts\Validation\Rule;

class UserPhoneUnique implements Rule
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
            $phoneNum = $numberProto->getNationalNumber();
            $phoneCountry = $numberProto->getCountryCode();
            if($phoneCountry=='62'&&substr($phoneNum , 0 , 2)=='62')
            {
                $phoneNum = substr($phoneNum , 2);
            }
            $phone = DB::table('users_phones')->where('user_phone_country', $phoneCountry)->where('user_phone', $phoneNum)->first();
            return blank($phone);
        } catch (NumberParseException $e) {
            $error = array(
                'type'=>'phone_unique_illegal',
                'ip'=>getRequestIpAddress(),
                'url'=>request()->getPathInfo(),
                'route'=>request()->route()->getName(),
                'params'=>request()->all(),
                'error_type'=>$e->getErrorType(),
                'error_message'=>$e->getMessage()
            );
            Log::info('phone_unique_illegal' , $error);
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
        return trans('validation.custom.phone.unique');
    }


}