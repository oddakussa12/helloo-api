<?php
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

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
    function notify($category = 'user.like' , $data = array() , $isJpush=true, $anonymous=false)
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
                if($k=='from'&&($v instanceof \Illuminate\Database\Eloquent\Model))
                {
                    $notifynder->{$k}($v->{$v->getKeyName()});
                }else{
                    $notifynder->{$k}($v);
                }
            }
        }
        if($isJpush)
        {
            $notifynder->sendWithJpush();
        }else{
            $notifynder->send();
        }
    }
}

if(!function_exists('notify_remove')){
    function notify_remove($category_id , $object ,$from=null)
    {
        $contact_id = $object->{$object->getKeyName()};
        $from_id = $from==null?auth()->id():$from->user_id;
        if(!is_array($category_id)){
            $user = $object;
            $category_id = [$category_id];
        }else{
            $user = $object->owner;
        }
        $user->getNotificationRelation()->where(function($query) use ($contact_id , $category_id , $from_id){
            $query
                ->where('from_id' , $from_id)
                ->where('contact_id' , $contact_id)
                ->whereIn('category_id' , $category_id);
        })->delete();
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
    function rate_comment_v2($comments, $create_time, $likes=0 , $gravity = 0)
    {
        $gravity = $gravity==0?post_gravity():$gravity;
        $ctime = strtotime($create_time);
        $intervals = time()-$ctime;
        if($intervals<86400)
        {
            $numerator = round(floatval(config('common.like_weight')) , 5)*$likes + $comments + 1;
        }else{
            $numerator = $comments + 1;
        }
        return $numerator / pow(floor((time()-$ctime)/3600) + 2, $gravity);
    }
}

if (!function_exists('rate_comment_v3')) {
    /**
     * Calculates the rate for sorting by hot.
     *
     * @param $comments
     * @param $create_time
     * @param int $likes
     * @param int $commenters
     * @param int $countries
     * @param float $gravity
     * @return float
     */
    function rate_comment_v3($comments, $create_time, $likes=0 , $commenters=0 , $countries=0 , $gravity = 0)
    {
        $gravity = $gravity==0?post_gravity():$gravity;
        $ctime = strtotime($create_time);
        $intervals = time()-$ctime;
        $likeWeight = config('common.like_weight');
        $commentWeight = config('common.comment_weight');
        $commenterWeight = config('common.commenter_weight');
        $postCountryWeight = config('common.post_country_weight');
        $numerator = round(floatval($commentWeight) , 5)*$comments
            + round(floatval($commenterWeight) , 5)*$commenters
            + round(floatval($postCountryWeight) , 5)*$countries
            + 1;
        if($intervals<86400)
        {
            $numerator = $numerator + round(floatval($likeWeight) , 5)*$likes
                + round(floatval($commentWeight) , 5)*$comments
                + round(floatval($commenterWeight) , 5)*$commenters
                + round(floatval($postCountryWeight) , 5)*$countries
                + 1;
        }
        return $numerator / pow(floor((time()-$ctime)/3600) + 2, $gravity);
    }
}

if (!function_exists('domain')) {
    /**
     * Calculates the rate for sorting by hot.
     *
     * @param null $domain
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
    function first_rate_comment_v2($gravity = 0)
    {
        $gravity = $gravity==0?post_gravity():$gravity;
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
                    try {
                        $str = wordLimit($value , 0 , $limit);
                    }catch (\Exception $e)
                    {
                        $limit = $limit+4;
                        $str = str_limit_by_lang($value , 0 , $limit);
                    }
                    break;
                case 'zh-CN':
                case 'zh-TW':
                case 'zh-HK':
                case 'ja':
                case 'ko':
                case 'ar':
                    $str = str_limit($value , $limit);
                    break;
                default:
                    $str = mb_str_limit($value  ,$limit);
                    break;
            }
        }
        return $str;
    }
}
if (! function_exists('app_signature')) {
    function app_signature(&$params)
    {
        if (!isset($params['time_stamp']))
        {
            $params['time_stamp'] = time();
        }
        if (!isset($params['version']))
        {
            $params['version'] = uniqid();
        }
        if (!isset($params['platform']))
        {
            $params['platform'] = 1;
        }
        // 1. 字典升序排序
        ksort($params);

        // 2. 拼按URL键值对
        $str = '';
        foreach ($params as $key => $value)
        {
            if ($value !== '')
            {
                $str .= $key . '=' . $value . '&';
            }
        }
        if($params['platform']==1)
        {
            $appkey = config('common.android_secret');
        }else{
            $appkey = config('common.ios_secret');
        }
        // 3. 拼接app_key
        $str .= 'app_key=' . $appkey;
        // 4. MD5运算+转换大写，得到请求签名

        $sign = strtolower(md5($str));

        return $sign;
    }
}

if (! function_exists('common_signature')) {
    function common_signature(&$params)
    {
        if (!isset($params['time_stamp']))
        {
            $params['time_stamp'] = time();
        }
        // 1. 字典升序排序
        ksort($params);

        // 2. 拼按URL键值对
        $str = '';
        foreach ($params as $key => $value)
        {
            if ($value !== '')
            {
                $str .= $key . '=' . $value . '&';
            }
        }
        // 3. 拼接app_key
        $str .= 'app_key=' . config('common.common_secret');
        // 4. MD5运算+转换大写，得到请求签名

        $sign = strtolower(md5($str));

        return $sign;
    }
}

if (! function_exists('post_view')) {
    function post_view($view_count=1)
    {
        if ($view_count < 6) {
            switch ($view_count)
            {
                case 1:
                    $num = mt_rand(50 , 100);
                    break;
                case 2:
                    $num = mt_rand(100 , 800);
                    break;
                case 3:
                    $num = mt_rand(800 , 2000);
                    break;
                case 4:
                    $num = mt_rand(2000 , 4000);
                    break;
                default:
                    $num = mt_rand(4000 , 7000);
                    break;
            }
            return $num;
        }
        return ceil(round($view_count * 1.37 , 3)*1000)+mt_rand(1,10);
    }
}

if (!function_exists('emoji_test')) {
    function emoji_test($text)
    {
        $len = mb_strlen($text);
        for ($i = 0; $i < $len; $i++) {
            $word = mb_substr($text, $i, 1);
            if (strlen($word) > 3) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('post_gravity'))
{
    function post_gravity(float $rate=0)
    {
        if($rate>0)
        {
            \Cache::forget('post_gravity');
        }
        return \Cache::rememberForever('post_gravity' , function() use ($rate){
            return $rate>0?$rate:config('common.rate_coefficient');
        });
    }
}

if (!function_exists('dx_switch'))
{
    function dx_switch($key='dx_switch' , $switch=null)
    {
        if($switch!==null)
        {
            Cache::forget($key);
        }
        return Cache::rememberForever($key , function() use ($switch){
            return array('switch'=>intval($switch));
        });
    }
}

if (!function_exists('dx_uuid'))
{
    function dx_uuid($post_uuid='' , $key='dx_uuid')
    {
        if($post_uuid!='')
        {
            Cache::forget($key);
        }
        return Cache::rememberForever($key , function() use ($post_uuid){
            return array('post_uuid'=>strval($post_uuid));
        });
    }
}

if (! function_exists('block_user')) {

    function block_user($userName)
    {
        $users = array();
        $filePath = 'blacklist/blacklist.json';
        if(\Storage::exists($filePath))
        {
            $list = (array)\json_decode(\Storage::get($filePath) , true);
            if(isset($list['list']))
            {
                $users = $list['list'];
            }
        }
        array_push($users , $userName);
        $users = array_values(array_unique($users));
        \Storage::put($filePath , \json_encode(array('list'=>$users) , JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        return $users;
    }
}

if (! function_exists('unblock_user')) {

    function unblock_user($userName)
    {
        $users = array();
        $filePath = 'blacklist/blacklist.json';
        if(\Storage::exists($filePath))
        {
            $list = (array)\json_decode(\Storage::get($filePath) , true);
            if(isset($list['list']))
            {
                $users = $list['list'];
                $key = array_search($userName , $users);
                if($key!==false)
                {
                    unset($users[$key]);
                }
            }
        }
        $users = array_values(array_unique($users));
        \Storage::put($filePath , \json_encode(array('list'=>$users) , JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        return $users;
    }
}











