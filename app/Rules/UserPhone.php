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
            return $phoneUtil->isValidNumber($numberProto);
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
        return 'Wrong format of phone number';
    }


}