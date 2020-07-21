<?php
namespace App\Rules;

use Illuminate\Support\Facades\Redis;
use Illuminate\Contracts\Validation\Rule;

class UserNameAndEmailUnique implements Rule
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
        $this->attribute = $attribute;
        $userUniqueKey = config('redis-key.user.user_'.$attribute);
        return !(bool)Redis::SISMEMBER($userUniqueKey , mb_convert_case($value, MB_CASE_LOWER, "UTF-8"));
    }

    /**
     * 获取验证错误信息。
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.custom.'.$this->attribute.'.unique');
    }


}