<?php
namespace App\Rules;

use libphonenumber\PhoneNumberUtil;
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
        return false;
        $value = "+".$value;
        $phoneUtil = PhoneNumberUtil::getInstance();
        try {
            $numberProto = $phoneUtil->parse($value);
            $phone = $numberProto->getNationalNumber();
            $phoneCountry = $numberProto->getCountryCode();
            $phone = \DB::table('users_phones')->where('user_phone_country', $phoneCountry)->where('user_phone', $phone)->first();
            return blank($phone);
        } catch (\libphonenumber\NumberParseException $e) {
            \Log::error($e->getMessage());
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