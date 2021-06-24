<?php

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Cache;
use \Illuminate\Support\Facades\Redis;


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
    function notify($category='user.like', $data =[], $isJpush=true, $anonymous=false)
    {
        $params     = ['from'=>'', 'to'=>'', 'extra'=>[], 'url'=>'', 'expire'=>'', 'setField'=>[]];
        $op         = array_intersect_key($data , $params);
        $notifynder = Notifynder::category($category);
        if($anonymous) {
            unset($op['from']);
            $notifynder->anonymous();
        }
        foreach ($op as $k=>$v) {
            if ($k=='setField') {
                $notifynder->{$k}($v[0], $v[1]);
            } else {
                if ($k=='from' && ($v instanceof Model)) {
                    $notifynder->{$k}($v->{$v->getKeyName()});
                } else {
                    $notifynder->{$k}($v);
                }
            }
        }
        if ($isJpush) {
            $notifynder->sendWithJpush();
        } else {
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
        $realIp = '0.0.0.0';
        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

                foreach ($arr as $ip) {
                    $ip = trim($ip);
                    if ($ip != 'unknown') {
                        $realIp = $ip;
                        break;
                    }
                }
            } else if (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $realIp = $_SERVER['HTTP_CLIENT_IP'];
            } else if (isset($_SERVER['REMOTE_ADDR'])) {
                $realIp = $_SERVER['REMOTE_ADDR'];
            } else {
                $realIp = '0.0.0.0';
            }
        } else if (getenv('HTTP_X_FORWARDED_FOR')) {
            $realIp = getenv('HTTP_X_FORWARDED_FOR');
        } else if (getenv('HTTP_CLIENT_IP')) {
            $realIp = getenv('HTTP_CLIENT_IP');
        } else {
            $realIp = getenv('REMOTE_ADDR');
        }
        preg_match('/[\\d\\.]{7,15}/', $realIp, $onlineIp);
        return (!empty($onlineIp[0]) ? $onlineIp[0] : '0.0.0.0');
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
     * @param $likes
     * @param $commenters
     * @param $countries
     * @param $gravity
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
     * @param mixed $gravity
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
            return auth()->user()->followings()->whereIn('common_follows.followable_id' , $userIds)->pluck('user_id')->all();
        }
        return array();
    }
}

if (!function_exists('userPostLike')) {

    function userPostLike($postIds)
    {
        if(auth()->check()&&!empty($postIds))
        {
            return auth()->user()->likes()->WithType("App\Models\Post")->whereIn('common_likes.likable_id' , $postIds)->pluck('likable_id')->all();
        }
        return array();
    }
}

if (!function_exists('userPostDislike')) {

    function userPostDislike($postIds)
    {
        if(auth()->check()&&!empty($postIds))
        {
            return auth()->user()->dislikes()->WithType("App\Models\Post")->whereIn('post_dislikes.dislikable_id' , $postIds)->pluck('dislikable_id')->all();
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
        $agent = new Agent();
        if($agent->match('YooulAndroid'))
        {
            $app_key = config('common.android_secret');
        }elseif($agent->match('YoouliOS')){
            $app_key = config('common.ios_secret');
        }else{
            $app_key = config('common.common_secret');
        }

        $str .= 'app_key=' . $app_key;
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
        if($rate!=0)
        {
            $postRate = $rate;
            Redis::set('post_rate' , $rate);
        }else{
            $postRate = floatval(Redis::get('post_rate'));
            $postRate = $postRate<=0||$postRate>=2?1:$postRate;
        }
        return $postRate;
    }
}

if (!function_exists('index_switch'))
{
    function index_switch()
    {
//        if(apcu_exists('index_switch'))
//        {
//            return apcu_fetch('index_switch');
//        }
        return Redis::get('index_switch');
//        apcu_add('index_switch' , $indexSwitch);
//        return $indexSwitch;

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
/*
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
}*/
if (! function_exists('carousel_post')) {

    function carousel_post($postUuid , $locale , $image)
    {
        $posts = array();
        $filePath = 'carousel/posts.json';
        if(\Storage::exists($filePath))
        {
            $posts = (array)\json_decode(\Storage::get($filePath) , true);
            $keys = array_keys($posts);
            if(!in_array($postUuid , $keys))
            {
                $tmp[$postUuid] = array($locale=>$image);
                $posts = $tmp+$posts;
            }else{
                $post = $posts[$postUuid];
                $post[$locale] = $image;
                $posts[$postUuid] = $post;
            }
        }else{
            $posts[$postUuid] = array($locale=>$image);
        }
        \Storage::put($filePath , \json_encode($posts , JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        return $posts;
    }
}

if (! function_exists('non_carousel_post')) {

    function non_carousel_post($postUuid)
    {
        $posts = array();
        $filePath = 'carousel/posts.json';
        if(\Storage::exists($filePath))
        {
            $posts = (array)\json_decode(\Storage::get($filePath) , true);
            $keys = array_keys($posts);
            if(in_array($postUuid , $keys))
            {
                unset($posts[$postUuid]);
            }
        }
        \Storage::put($filePath , \json_encode($posts , JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        return $posts;
    }
}

if (! function_exists('carousel_post_list')) {

    function carousel_post_list()
    {
        $images = array();
        $filePath = 'carousel/posts.json';
        if(\Storage::exists($filePath))
        {
            $posts = collect((array)\json_decode(\Storage::get($filePath) , true));
            $locale = locale();
            $posts->each(function($item , $key) use ($locale , &$images){
                $image = isset($item[$locale])?$item[$locale]:(isset($item['en'])?$item['en']:'');
                if(!empty($image))
                {
                    $images[$key] = config('common.qnUploadDomain.thumbnail_domain').$image;
                }
            })->all();
        }
        return $images;
    }
}

if (!function_exists('ry_server'))
{
    function ry_server($flag = false)
    {
        $key = 'ry_server';
        $serverUrl = config('latrell-rcloud.server_url');
        if($flag)
        {
            $k = array_search(Cache::get($key) , $serverUrl);
            Cache::forget($key);
            if($k!==false)
            {
                unset($serverUrl[$k]);
            }
        }
        return Cache::rememberForever($key , function() use ($serverUrl){
            return array_random($serverUrl);
        });
    }
}

if (!function_exists('fromAzureGoogleToNiu'))
{
    function fromAzureGoogleToNiu($language)
    {
        $support = true;
        $languagesFile = 'niu/languages.json';
        if(\Storage::exists($languagesFile))
        {
            $languages = \Storage::get($languagesFile);
            $languages = \json_decode($languages , true);
            if(in_array($language , $languages))
            {
                return array('language'=>$language , 'support'=>$support);
            }
            switch ($language)
            {
                case 'nb':
                    $language = 'no';
                    break;
                case 'pt-pt':
                    $language = 'pt';
                    break;
                case 'sr-Cyrl':
                case 'sr-Latn':
                    $language = 'sr';
                    break;
                case 'zh-CN':
                case 'zh-Hans':
                    $language = 'zh';
                    break;
                case 'zh-Hant':
                case 'zh-TW':
                    $language = 'cht';
                    break;
                case 'jw':
                    $language = 'jv';
                    break;
                case 'tl':
                    $language = 'fil';
                    break;
                case 'hmn':
                    $language = 'mww';
                    break;
                default:
                    $support = false;
                    break;
            }
        }
        return array('language'=>$language , 'support'=>$support);

    }
}
if (!function_exists('SupportToNiu'))
{
    function SupportToNiu($language)
    {
        switch ($language)
        {
            case 'nb':
                $language = 'no';
                break;
            case 'pt-pt':
                $language = 'pt';
                break;
            case 'sr-Cyrl':
            case 'sr-Latn':
                $language = 'sr';
                break;
            case 'zh-Hant':
            case 'zh-TW':
            case 'zh-HK':
                $language = 'cht';
                break;
            case 'yue':
            case 'zh-Hans':
            case 'zh-CN':
                $language = 'zh';
                break;
        }
        return $language;

    }
}
if (!function_exists('niuAzureToGoogle'))
{
    //ty 微软检测语言大溪地语只有小牛支持翻译谷歌暂时不支持
    function niuAzureToGoogle($language)
    {
        switch ($language)
        {
            case 'nb':
                $lang = 'no';
                break;
            case 'he':
                $lang = 'iw';
                break;
            case 'pt-pt':
                $lang = 'pt';
                break;
            case 'sr-Cyrl':
            case 'sr-Latn':
                $lang = 'sr';
                break;
            case 'yue':
            case 'cht':
            case 'zh-Hant':
                $lang = 'zh-TW';
                break;
            case 'zh':
            case 'zh-Hans':
                $lang = 'zh-CN';
                break;
            default:
                $lang = $language;
                break;
        }
        return $lang;
    }
}

if(!function_exists('googleToNiu'))
{
    function googleToNiu($language)
    {
        switch ($language)
        {
            case 'hmn':
                $lang = 'mww';
                break;
            case 'iw':
                $lang = 'he';
                break;
            case 'jw':
                $lang = 'jv';
                break;
            case 'tl':
                $lang = 'fil';
                break;
            case 'zh-CN':
                $lang = 'zh';
                break;
            case 'zh-TW':
                $lang = 'cht';
                break;
            default:
                $lang = $language;
                break;
        }
        return $lang;
    }
}

if(!function_exists('azureToNiu'))
{
    function azureToNiu($language)
    {
        switch ($language)
        {
            case 'nb':
                $lang = 'no';
                break;
            case 'pt-pt':
                $lang = 'pt';
                break;
            case 'sr-Cyrl':
            case 'sr-Latn':
                $lang = 'sr';
                break;
            case 'yue':
            case 'zh-Hans':
                $lang = 'zh';
                break;
            case 'zh-Hant':
                $lang = 'cht';
                break;
            default:
                $lang = $language;
                break;
        }
        return $lang;
    }
}

if (!function_exists('getContinent'))
{
    function getContinent()
    {
        static $continents = array();
        if(blank($continents))
        {
            $continents = \json_decode(\Storage::get('area/continents.json') , true);
        }
        return $continents;
    }
}

if (!function_exists('getContinentCountry'))
{
    function getContinentCountry()
    {
        static $countryContinent = array();
        if(blank($countryContinent))
        {
            $countryContinent = \json_decode(\Storage::get('area/country_continent.json') , true);
        }
        return $countryContinent;
    }
}

if (!function_exists('getContinentByCountry'))
{
    function getContinentByCountry($country)
    {
        $country = strtolower($country);
        $continentCountry = getContinentCountry();
        if(array_key_exists($country , $continentCountry))
        {
            return $continentCountry[$country]['continent'];
        }
        return 'other';
    }
}
if (!function_exists('getUserCountryId'))
{
    function getUserCountryId($value)
    {
        $index = array_search(strtoupper($value) , config('countries'));
        if($index===false)
        {
            $index = 236;
        }else{
            $index = $index+1;
        }
        return $index;
    }
}

/**
 * 通过countryId 获取帖子的 countryName
 */
if (!function_exists('getPostCountryName')) {
    function getPostCountryName($countryId, $default=true)
    {
        $country_code = config('countries');
        $country      = ($countryId - 1);
        if ($country == -1) {
            return 'world';
        }
        if (array_key_exists($country, $country_code)) {
            return strtolower($country_code[$country]);
        }
        return $default ? strtolower($country_code[235]) : 'world';
    }
}

/**
 * 通过countryId 获取用户的 countryName
 */
if (!function_exists('getUserCountryName')) {
    function getUserCountryName($countryId, $default=true)
    {
        $country_code = config('countries');
        $country = ($countryId-1);
        if(array_key_exists($country , $country_code))
        {
            return strtolower($country_code[$country]);
        }
        return strtolower($country_code[235]);
    }
}

/**
 * 日期转换
 */
if (!function_exists('dateTrans')) {
    function dateTrans($time)
    {
        $locale = locale();
        if ($locale == 'zh-CN') {
            Carbon::setLocale('zh');
        } elseif ($locale == 'zh-TW' || $locale == 'zh-HK') {
            Carbon::setLocale('zh_TW');
        } else {
            $locale = 'en';
            Carbon::setLocale($locale);
            $translator = \Carbon\Translator::get($locale);
            $translator->setMessages($locale, [
                'minute' => ':count m|:count m',
                'hour'   => ':count h|:count h',
                'day'    => ':count d|:count d',
                'month'  => ':count mo|:count mo',
                'year'   => ':count yr|:count yr',
            ]);
        }
        return Carbon::parse($time)->diffForHumans();
    }
}

/**
 * @param $type
 * @param $value
 * @return mixed
 * Post Model中 post_media
 */
if (!function_exists('postMedia')) {
    function postMedia($type, $value)
    {
        $imgDomain     = config('common.qnUploadDomain.thumbnail_domain');
        $thumbDomain   = config('common.awsUploadDomain.thumbnail_domain');
        $videoDomain   = config('common.awsUploadDomain.video_domain');
        $videoDomainCn = config('common.awsUploadDomain.video_domain_cn');

        if ($type == 'video') {
            $value = gettype($value)=='array'?$value:\json_decode($value , true);
            $domain = domain() == 'api.mmantou.cn' ? $videoDomainCn : $videoDomain;
            $value[$type]['video_url'] = $domain . $value[$type]['video_url'];
            $value[$type]['video_thumbnail_url'] = $domain . $value[$type]['video_thumbnail_url'];
            $video_subtitle = (array)$value[$type]['video_subtitle_url'];
            $video_subtitle = \array_filter($video_subtitle, function ($v, $k) {
                return !empty($v) && !empty($k);
            }, ARRAY_FILTER_USE_BOTH);
            $value[$type]['video_subtitle_url'] = \array_map(function ($v) {
                return config('common.qnUploadDomain.subtitle_domain') . $v;
            }, $video_subtitle);
        } else if ($type == 'news') {
            $value = gettype($value)=='array'?$value:\json_decode($value , true);
            $value[$type]['news_cover_image'] = $imgDomain . $value[$type]['news_cover_image'];
        } else if ($type == 'image') {
            $value = gettype($value)=='array'?$value:\json_decode($value , true);
            $value[$type]['image_cover'] = $imgDomain . $value[$type]['image_cover'];
            $image_url = $value[$type]['image_url'];
            $value[$type]['image_url'] = \array_map(function ($v) use ($imgDomain) {
                return $imgDomain . $v . '?imageMogr2/auto-orient/interlace/1|imageslim';
            }, $image_url);
            $value[$type]['thumb_image_url'] = \array_map(function ($v) use ($imgDomain) {
                return $imgDomain . $v . '?imageView2/5/w/192/h/192/interlace/1|imageslim';
            }, $image_url);
        }
        return $value;
    }

}

if (!function_exists('millisecond')) {
    function millisecond()
    {
        list($mses, $sec) = explode(' ', microtime());
        return  (float)sprintf('%.0f', (floatval($mses) + floatval($sec)) * 1000);
    }
}


/**
 * @return mixed|null
 * PHP 数组按多个字段排序
 */
if (!function_exists('sortArrByManyField')) {
    function sortArrByManyField()
    {
        $args = func_get_args(); // 获取函数的参数的数组
        if (empty($args)) {
            return null;
        }
        $arr = array_shift($args);
        if (!is_array($arr)) {
            return $arr;
        }
        foreach ($args as $key => $field) {
            if (is_string($field)) {
                $temp = array();
                foreach ($arr as $index => $val) {
                    $temp[$index] = $val[$field];
                }
                $args[$key] = $temp;
            }
        }
        $args[] = &$arr; //引用值
        call_user_func_array('array_multisort', $args);
        return array_pop($args);
    }
}

/**
 * 数组按某一个字段去重
 */
if (!function_exists('assoc_unique')) {
    function assoc_unique($arr, $key, $sort=false)
    {
        $tmp_arr = array();
        foreach ($arr as $k => $v) {
            if (in_array($v[$key], $tmp_arr)) {//搜索$v[$key]是否在$tmp_arr数组中存在，若存在返回true
                unset($arr[$k]);
            } else {
                $tmp_arr[] = $v[$key];
            }
        }
        if($sort) sort($arr); //sort函数对数组进行排序
        return $arr;
    }

}

if (!function_exists('rate_comment_v4')) {
    /**
     * Calculates the rate for sorting by hot.
     *
     * @param $comments
     * @param $create_time
     * @param $likes
     * @param $commenters
     * @param $countries
     * @param $gravity
     * @param $x
     * @return float
     */
    function rate_comment_v4($comments, $create_time, $likes=0 , $commenters=0 , $countries=0 , $gravity = 0 , $x = 1)
    {
        $gravity = $gravity==0?post_gravity():$gravity;
        $x = intval($x);
        $ctime = strtotime($create_time);
//        $intervals = time()-$ctime;
        $likeWeight = config('common.like_weight_v2')*$x;
        $commentWeight = config('common.comment_weight_v2')*$x;
        $commenterWeight = config('common.commenter_weight_v2')*$x;
        $postCountryWeight = config('common.post_country_weight_v2')*$x;
        $numerator = round(floatval($likeWeight) , 5)*$likes
            +round(floatval($commentWeight) , 5)*$comments
            + round(floatval($commenterWeight) , 5)*$commenters
            + round(floatval($postCountryWeight) , 5)*$countries
            + 1;
//        if($intervals<86400)
//        {
//            $numerator = $numerator + round(floatval($likeWeight) , 5)*$likes
//                + round(floatval($commentWeight) , 5)*$comments
//                + round(floatval($commenterWeight) , 5)*$commenters
//                + round(floatval($postCountryWeight) , 5)*$countries
//                + 1;
//        }
        return $numerator / pow(floor((time()-$ctime)/3600) + 2, $gravity);
    }
}

if (!function_exists('tempPostLikeNum')) {
    /**
     * get post temp like num
     *
     * @param $count
     * @return int
     */
    function tempPostLikeNum($count)
    {
        return 0;
        $multiple = 0;
        while ($multiple<=0.1)
        {
            $multiple = randFloat();
        }
        if($count>0&&$count<=5)
        {
            $coefficient = 2.68;
        }elseif ($count>5&&$count<=10)
        {
            $coefficient = 3.19;
        }elseif ($count>10&&$count<=30)
        {
            $coefficient = 1.24;
        }elseif ($count>30&&$count<=150)
        {
            $coefficient = 0.85;
        }elseif ($count>150&&$count<=800)
        {
            $coefficient = 0.47;
        }else{
            $coefficient = 0.21;
        }
        return round(($count+$multiple)*$coefficient);
    }
}

if (! function_exists('randFloat')) {
    /**
     * Random decimal
     *
     * @param int $min
     * @param int $max
     * @return int
     */
    function randFloat($min=0, $max=1){
        return $min + mt_rand()/mt_getrandmax() * ($max-$min);
    }
}

if (! function_exists('fakeLike')) {
    /**
     * fake like
     * @param $num
     * @param $coefficient
     * @return int
     */
    function fakeLike($num, $coefficient = 99)
    {
        if($num<7)
        {
//            return ceil($num * $num * randFloat(0.51, 1)+mt_rand(0,2));
            switch ($num){
                case 1:
                    $like = mt_rand(1 , 3);
                    break;
                case 2:
                    $like = mt_rand(4 , 8);
                    break;
                case 3:
                    $like = mt_rand(9 , 19);
                    break;
                case 4:
                    $like = mt_rand(20 , 80);
                    break;
                case 5:
                    $like = mt_rand(81 , 160);
                    break;
                default:
                    $max = 7*$coefficient;
                    $like = mt_rand(161 , $max-1);
                    break;
            }
            return $like;
        }elseif($num>=7&&$num<=2000){
            $coefficient = $coefficient <= 0 ? 99 : $coefficient;
            return intval($num * $coefficient * randFloat(0.91, 1));
        }else{
            return ($coefficient-1)*2000+$num;
        }
    }
}

if (!function_exists('splitJointQnImageUrl')) {
    function splitJointQnImageUrl($value='') {
        if (empty($value)) {
            return $value;
        }
        if (preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",$value)) {
            return $value;
        }
        return config('common.qnUploadDomain.avatar_domain').$value.'?imageView2/0/w/250/h/250/interlace/1|imageslim';
    }
}

if (!function_exists('formatNumber')) {
    function formatNumber(int $value=0) {
        if($value<=999)
        {
            return $value;
        }elseif ($value>999&&$value<1000000)
        {
            return strval(round($value/1000 , 1))."K";
        }elseif ($value>999999&&$value<1000000000)
        {
            return strval(round($value/1000000 , 1))."M";
        }else{
            return strval(round($value/1000000000, 1))."B";
        }

    }
}

if (!function_exists('age')) {
    function age($birthday){
        $birthday = strval($birthday);
        $time = strtotime($birthday);
        if(strval(date('Y-m-d' , $time))!==$birthday)
        {
            $birthday = date('Y-m-d');
        }
        list($year,$month,$day) = explode("-",$birthday);
        $year_diff = date("Y") - $year;
        $month_diff = date("m") - $month;
        $day_diff  = date("d") - $day;
        if ($day_diff < 0 || $month_diff < 0)
            $year_diff--;
        return $year_diff;
    }
}

// $str = '他是个傻@B bitchSADF啊，傻xx逼! é-f-é-u-é-c-é-k-é?';
if (!function_exists('sensitiveWordFilter')) {
    function sensitiveWordFilter($str , $words)
    {
        $flag = false;
        // 提取中文部分，防止其中夹杂英语等
        preg_match_all("/[\x{4e00}-\x{9fa5}]+/u", $str, $match);
        $chineseStr = implode('', $match[0]);
        $englishStr = strtolower(preg_replace("/[^A-Za-z0-9\.\-]/", " ", $str));

        $flag_arr = array('？', '！', '￥', '（', '）', '：' , '‘' , '’', '“', '”', '《' , '》', '，',
            '…', '。', '、', 'nbsp', '】', '【' ,'～', '#', '$', '^', '%', '@', '!', '*', '-'. '_', '+', '=');
        $contentFilter = preg_replace('/\s/', '', preg_replace("/[[:punct:]]/", '',
            strip_tags(html_entity_decode(str_replace($flag_arr, '', $str), ENT_QUOTES, 'UTF-8'))));

        // 全匹配过滤,去除特殊字符后过滤中文及提取中文部分
        foreach ($words as $word)
        {
            // 判断是否包含敏感词,可以减少这里的判断来降低过滤级别，
            if (strpos($str, $word) !== false || strpos($contentFilter, $word) !== false || strpos($chineseStr, $word) !== false
                || strpos($englishStr, $word) !== false) {
                return '敏感词:' . $word;
            }
        }
        return $flag;
    }
}

if (!function_exists('arraySort')) {
    /**
     * @param $array
     * @param $keys
     * @param string $sort
     * @return array
     */
    function arraySort($array, $keys, $sort='asc'): array
    {
        $newArr = $valArr = array();
        foreach ($array as $key=>$value) {
            $valArr[$key] = $value[$keys];
        }
        ($sort == 'asc') ?  asort($valArr) : arsort($valArr);
        reset($valArr);
        foreach($valArr as $key=>$value) {
            $newArr[$key] = $array[$key];
        }
        return $newArr;
    }
}

if (!function_exists('opensslEncrypt')) {
    /**
     * @param $data
     * @param $key
     * @return void
     */
    function opensslEncrypt($data , $key)
    {
        return base64_encode(openssl_encrypt($data,"AES-128-CBC",$key,OPENSSL_RAW_DATA , '1234567890789456'));
    }
}

if (!function_exists('opensslDecrypt')) {
    /**
     * @param $data
     * @param $key
     * @return void
     */
    function opensslDecrypt($data , $key)
    {
        return openssl_decrypt(base64_decode($data),"AES-128-CBC",$key,OPENSSL_RAW_DATA);
    }
}

if (! function_exists('escape_like')) {
    /**
     * @param $string
     * @return mixed
     */
    function escape_like($string)
    {
        $search = array('%', '_' , '\'', '\\');
        $replace   = array('', '\_' , '', '');
        return str_replace($search, $replace, $string);
    }
}

if (! function_exists('hashDbIndex')) {

    function hashDbIndex($string, $hashNumber = 8)
    {
        $checksum = crc32(md5(strtolower(strval($string))));
        if (8 == PHP_INT_SIZE) {
            if ($checksum > 2147483647) {
                $checksum = $checksum & (2147483647);
                $checksum = ~($checksum - 1);
                $checksum = $checksum & 2147483647;
            }
        }
        return (abs($checksum) % intval($hashNumber));
    }
}