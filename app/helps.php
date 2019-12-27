<?php
use Illuminate\Support\Str;

if (! function_exists('locale')) {
    function locale($locale = null)
    {
        if (is_null($locale)) {
            return app()->getLocale();
        }
        app()->setLocale($locale);
        return app()->getLocale();
    }
}

if(!function_exists('supportedLocales')){
    function supportedLocales()
    {
        return LaravelLocalization::getSupportedLocales();
    }
}

if(!function_exists('dynamicSetLocales')){
    function dynamicSetLocales($langs = array())
    {
        $locales = config('translatable.locales');
        if(!empty($langs)&&is_array($langs))
        {
            foreach ($langs as $lang)
            {
                if(!in_array($lang , $locales))
                {
                    array_push($locales , $lang);
                }
            }
            app('config')->set('translatable.locales', $locales);
        }
    }

}

if(!function_exists('notify')){
    function notify($category = 'user.like' , $data = array() , $anonymous=false)
    {
	//echo 1;die;
        $params = array('from'=>'' , 'to'=>'' , 'extra'=>array() , 'url'=>'' , 'expire'=>'' , 'setField'=>array());
        $op = array_intersect_key($data , $params);
        $notifynder = Notifynder::category($category);
        if($anonymous)
        {
            unset($op['from']);
            $notifynder->anonymous();
        }
        foreach ($op as $k=>$v)
        {
            if($k=='setField')
            {
                $notifynder->{$k}($v[0] , $v[1]);
            }else{
                $notifynder->{$k}($v);
            }
        }
        $notifynder->send();
    }
}

if(!function_exists('notify_remove')){
    function notify_remove($category_id , $object)
    {
        $contact_id = $object->{$object->getKeyName()};
        if(!is_array($category_id)){
            $object->getNotificationRelation()->where(function($query) use ($contact_id , $category_id){
                $query
                    ->where('from_id' , auth()->id())
                    ->where('contact_id' , $contact_id)
                    ->where('category_id' , $category_id);
            })->delete();
        }else{
            $object->owner->getNotificationRelation()->where(function($query) use ($contact_id , $category_id){
                $query
                    ->where('from_id' , auth()->id())
                    ->where('contact_id' , $contact_id)
                    ->whereIn('category_id' , $category_id);
            })->delete();
        }
    }
}

if (!function_exists('rate')) {
    /**
     * Calculates the rate for sorting by hot.
     *
     * @param int       $likes
     * @param timestamp $created
     *
     * @return float
     */
    function rate($likes, $created)
    {
        $startTime = 1473696439; // strtotime('2016-09-12 16:07:19')
        $created = strtotime($created);
        $timeDiff = $created - $startTime;

        $x = $likes;

        if ($x > 0) {
            $y = 1;
        } elseif ($x == 0) {
            $y = 0;
        } else {
            $y = -1;
        }

        if (abs($x) >= 1) {
            $z = abs($x);
        } else {
            $z = 1;
        }

        return (log10($z) * $y) + ($timeDiff / 45000);
    }
}

if (!function_exists('getRequestIpAddress')) {
    /**
     * Returns the real IP address of the request even if the website is using Cloudflare.
     *
     * @return string
     */
    function getRequestIpAddress()
    {
        $realip = '0.0.0.0';
        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $realip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else if (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $realip = $_SERVER['HTTP_CLIENT_IP'];
            } else {
                $realip = $_SERVER['REMOTE_ADDR'];
            }
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $realip = getenv('HTTP_X_FORWARDED_FOR');
            } else if (getenv('HTTP_CLIENT_IP')) {
                $realip = getenv('HTTP_CLIENT_IP');
            } else {
                $realip = getenv('REMOTE_ADDR');
            }
        }
        return $realip;
    }
}

if (!function_exists('rate_comment')) {
    /**
     * Calculates the rate for sorting by hot.
     *
     * @param $comments
     * @param timestamp $created
     *
     * @return float
     */
    function rate_comment($comments, $created)
    {
        $startTime = 1556728818; // strtotime('2016-09-12 16:07:19')

        $created = strtotime($created);
        $timeDiff = $created - $startTime;

        $x = $comments;

        if ($x > 0) {
            $y = 1;
        } elseif ($x == 0) {
            $y = 0;
        } else {
            $y = -1;
        }

        if (abs($x) >= 1) {
            $z = abs($x);
        } else {
            $z = 1;
        }

        return (log10($z) * $y) + ($timeDiff / 45000);
    }
}

if (!function_exists('rate_comment_v2')) {
    /**
     * Calculates the rate for sorting by hot.
     *
     * @param $comments
     * @param $create_time
     * @param int $likes
     * @param float $gravity
     * @return float
     */
    function rate_comment_v2($comments, $create_time, $likes=0 , $gravity = 1)
    {
        $ctime = strtotime($create_time);
        return ($likes + $comments + 1) / pow(floor((time()-$ctime)/3600) + 2, $gravity);
    }
}

if (!function_exists('domains')) {
    /**
     * Calculates the rate for sorting by hot.
     *
     * @param string $item
     * @return float
     */

    function domain($domain=null,$item='host')
    {
        if($domain==null){
            $url = parse_url(url()->current());
        }else{
            $url = parse_url($domain);
        }
        if(isset($url[$item]))
        {
            return $url[$item];
        }
        return '';

    }
}


if (!function_exists('first_rate_comment_v2')) {
    /**
     * Calculates the rate for sorting by hot.
     *
     * @param float $gravity
     * @return float
     */
    function first_rate_comment_v2($gravity = 1)
    {
        return 1 / pow(2, $gravity);
    }
}
if (!function_exists('userFollow')) {

    function userFollow($userIds)
    {
        if(auth()->check()&&!empty($userIds))
        {
            $followers = auth()->user()->followings()->whereIn('common_follows.followable_id' , $userIds)->pluck('user_id')->all();
            return $followers;
        }
        return array();

    }
}


if (!function_exists('getMyFollow')) {

    function getMyFollow()
    {
        return auth()->user()->followings()->get();
    }
}

if (!function_exists('getFollowMe')) {

    function getFollowMe()
    {
        return auth()->user()->followers()->get();
    }
}

if (!function_exists('wordLimit'))
{
    function wordLimit($str, $start, $length = null, $end = '...')
    {
        // 先正常截取一遍.
        $res = substr($str, $start, $length);
        $strlen = strlen($str);

        /* 接着判断头尾各6字节是否完整(不残缺) */
        // 如果参数start是正数
        if ($start >= 0) {
            // 往前再截取大约6字节
            $next_start = $start + $length; // 初始位置
            $next_len = $next_start + 6 <= $strlen ? 6 : $strlen - $next_start;
            $next_segm = substr($str, $next_start, $next_len);
            // 如果第1字节就不是 完整字符的首字节, 再往后截取大约6字节
            $prev_start = $start - 6 > 0 ? $start - 6 : 0;
            $prev_segm = substr($str, $prev_start, $start - $prev_start);
        } // start是负数
        else {
            // 往前再截取大约6字节
            $next_start = $strlen + $start + $length; // 初始位置
            $next_len = $next_start + 6 <= $strlen ? 6 : $strlen - $next_start;
            $next_segm = substr($str, $next_start, $next_len);

            // 如果第1字节就不是 完整字符的首字节, 再往后截取大约6字节.
            $start = $strlen + $start;
            $prev_start = $start - 6 > 0 ? $start - 6 : 0;
            $prev_segm = substr($str, $prev_start, $start - $prev_start);
        }
        // 判断前6字节是否符合utf8规则
        if (preg_match('@^([x80-xBF]{0,5})[xC0-xFD]?@', $next_segm, $bytes)) {
            if (!empty($bytes[1])) {
                $bytes = $bytes[1];
                $res .= $bytes;
            }
        }
        // 判断后6字节是否符合utf8规则
        $ord0 = ord($res[0]);
        if (128 <= $ord0 && 191 >= $ord0) {
            // 往后截取 , 并加在res的前面.
            if (preg_match('@[xC0-xFD][x80-xBF]{0,5}$@', $prev_segm, $bytes)) {
                if (!empty($bytes[0])) {
                    $bytes = $bytes[0];
                    $res = $bytes . $res;
                }
            }
        }
        if (strlen($res) < $strlen) {
            $res = $res . $end;
        }
        return $res;
    }
}

if (! function_exists('str_words')) {

    /**
     * Limit the number of words in a string.
     *
     * @param  string  $value
     * @param  int     $words
     * @param  string  $end
     * @return string
     */
    function str_words($value, $words = 100, $end = '...')
    {
        return Str::words($value, $words, $end);
    }
}

if (! function_exists('mb_str_limit')) {

    /**
     * Limit the number of words in a string.
     *
     * @param  string  $value
     * @param  int     $limit
     * @param  string  $end
     * @return string
     */
    function mb_str_limit($value, $limit = 100, $end = '...')
    {
        if (Str::length($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $limit , 'UTF-8')).$end;
    }
}

if (! function_exists('str_limit_by_lang')) {
    /**
     * Limit the number of characters in a string.
     *
     * @param  string  $value
     * @param  string  $lang
     * @param  int     $limit
     * @param  string  $end
     * @return string
     */
    function str_limit_by_lang($value, $lang , $limit = 100, $end = '...')
    {
        if(empty($value))
        {
            $str = $value;
        }else{
            switch ($lang)
            {
                case 'en':
                case 'id':
                case 'de':
                case 'fr':
                case 'es':
                    $str = wordLimit($value , 0 , $limit = 100);
                    break;
                case 'zh-CN':
                case 'zh-TW':
                case 'zh-HK':
                case 'ja':
                case 'ko':
                case 'ar':
                    $str = str_limit($value , $limit = 100);
                    break;
                default:
                    $str = mb_str_limit($value  ,100);
                    break;
            }
        }
        return $str;
    }
}











