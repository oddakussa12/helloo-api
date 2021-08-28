<?php

use Carbon\Carbon;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
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
        if($domain===null){
            $url = parse_url(url()->current());
        }else{
            $url = parse_url($domain);
        }
        return $url[$item] ?? '';
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
            $apikey = config('common.android_secret');
        }else{
            $apikey = config('common.ios_secret');
        }
        // 3. 拼接app_key
        $str .= 'app_key=' . $apikey;
        // 4. MD5运算+转换大写，得到请求签名

        return strtolower(md5($str));
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
        if($agent->match('HellooAndroid'))
        {
            $app_key = config('common.android_secret');
        }elseif($agent->match('HellooiOS')){
            $app_key = config('common.ios_secret');
        }else{
            $app_key = config('common.common_secret');
        }

        $str .= 'app_key=' . $app_key;
        // 4. MD5运算+转换大写，得到请求签名

        return strtolower(md5($str));
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
        }elseif ($value<1000000)
        {
            return strval(round($value/1000 , 1))."K";
        }elseif ($value<1000000000)
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


if (!function_exists('opensslEncrypt')) {
    /**
     * @param $data
     * @param $key
     * @return string
     */
    function opensslEncrypt($data , $key)
    {
        return base64_encode(openssl_encrypt($data,"AES-128-CBC",$key,OPENSSL_RAW_DATA , $key));
    }
}

if (!function_exists('opensslDecrypt')) {
    /**
     * @param $data
     * @param $key
     * @return false|string
     */
    function opensslDecrypt($data , $key)
    {
        return openssl_decrypt(base64_decode($data),"AES-128-CBC",$key,OPENSSL_RAW_DATA);
    }
}

if (!function_exists('opensslDecryptV2')) {
    /**
     * @param $data
     * @param $key
     * @return false|string
     * @return false|string
     */
    function opensslDecryptV2($data , $key)
    {
        if(strlen($key)==16)
        {
            return openssl_decrypt(base64_decode($data),"AES-128-CBC",$key,OPENSSL_RAW_DATA , $key);
        }
        return opensslDecrypt($data , $key);
    }
}

if (! function_exists('escape_like')) {
    /**
     * @param $string
     * @return array|string|string[]
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


if (! function_exists('batchUpdate')) {
    /**
     * $where = [ 'id' => [180, 181, 182, 183], 'user_id' => [5, 15, 11, 1]];
     * $needUpdateFields = [ 'view_count' => [11, 22, 33, 44], 'updated_at' => ['2019-11-06 06:44:58', '2019-11-30 19:59:34', '2019-11-05 11:58:41', '2019-12-13 01:27:59']];
     *
     * 最终执行的 sql 语句如下所示
     *
     * UPDATE articles SET
     * view_count = CASE
     * WHEN id = 183 AND user_id = 1 THEN 44
     * WHEN id = 182 AND user_id = 11 THEN 33
     * WHEN id = 181 AND user_id = 15 THEN 22
     * WHEN id = 180 AND user_id = 5 THEN 11
     * ELSE view_count END,
     * updated_at = CASE
     * WHEN id = 183 AND user_id = 1 THEN '2019-12-13 01:27:59'
     * WHEN id = 182 AND user_id = 11 THEN '2019-11-05 11:58:41'
     * WHEN id = 181 AND user_id = 15 THEN '2019-11-30 19:59:34'
     * WHEN id = 180 AND user_id = 5 THEN '2019-11-06 06:44:58'
     * ELSE updated_at END
     *
     *
     * 批量更新数据
     *
     * @param string $tableName  需要更新的表名称
     * @param array $where  需要更新的条件
     * @param array $needUpdateFields  需要更新的字段
     * @return bool|int  更新数据的条数
     */
    function batchUpdate(string $tableName, array $where, array $needUpdateFields)
    {

        if (empty($where) || empty($needUpdateFields)) return false;
        // 第一个条件数组的值
        $firstWhere = $where[array_keys(array_slice($where , 0 , 1))[0]];
        // 第一个条件数组的值的总数量
        $whereFirstValCount = count($firstWhere);
        // 需要更新的第一个字段的值的总数量
        $needUpdateFieldsValCount = count(array_values(array_slice($needUpdateFields , 0 , 1))[0]);
        if ($whereFirstValCount !== $needUpdateFieldsValCount) return false;
        // 所有的条件字段数组
        $whereKeys = array_keys($where);

        // 绑定参数
        $building = [];

//        $whereArr = [
//          0 => "id = 180 AND ",
//          1 => "user_id = 5 AND ",
//          2 => "id = 181 AND ",
//          3 => "user_id = 15 AND ",
//          4 => "id = 182 AND ",
//          5 => "user_id = 11 AND ",
//          6 => "id = 183 AND ",
//          7 => "user_id = 1 AND ",
//        ]
        $whereArr = [];
        $whereBuilding = [];
        foreach ($firstWhere as $k => $v) {
            foreach ($whereKeys as $whereKey) {
//                $whereArr[] = "{$whereKey} = {$where[$whereKey][$k]} AND ";
                $whereArr[] = "{$whereKey} = ? AND ";
                $whereBuilding[] = $where[$whereKey][$k];
            }
        }

//        $whereArray = [
//            0 => "id = 180 AND user_id = 5",
//            1 => "id = 181 AND user_id = 15",
//            2 => "id = 182 AND user_id = 11",
//            3 => "id = 183 AND user_id = 1",
//        ]
        $whereArrChunck = array_chunk($whereArr, count($whereKeys));
        $whereBuildingChunck = array_chunk($whereBuilding, count($whereKeys));

        $whereArray = [];
        foreach ($whereArrChunck as $val) {
            $valStr = '';
            foreach ($val as $vv) {
                $valStr .= $vv;
            }
            // 去除掉后面的 AND 字符及空格
            $whereArray[] = rtrim($valStr, "AND ");
        }

        // 需要更新的字段数组
        $needUpdateFieldsKeys = array_keys($needUpdateFields);

        // 拼接 sql 语句
        $sqlStr = '';
        foreach ($needUpdateFieldsKeys as $needUpdateFieldsKey) {
            $str = '';
            foreach ($whereArray as $kk => $vv) {
//                $str .= ' WHEN ' . $vv . ' THEN ' . $needUpdateFields[$needUpdateFieldsKey][$kk];
                $str .= ' WHEN ' . $vv . ' THEN ? ';
                // 合并需要绑定的参数
                $building[] = array_merge($whereBuildingChunck[$kk], [$needUpdateFields[$needUpdateFieldsKey][$kk]]);
            }
            $sqlStr .= $needUpdateFieldsKey . ' = CASE ' . $str . ' ELSE ' . $needUpdateFieldsKey . ' END, ';
        }

        // 去除掉后面的逗号及空格
        $sqlStr = rtrim($sqlStr, ', ');

        $tblSql = 'UPDATE ' . $tableName . ' SET ';

        $tblSql = $tblSql . $sqlStr;

        $building = array_reduce($building,"array_merge",array());
        return array(
            'sql'=>$tblSql,
            'building'=>$building,
        );
    }
}

if(!function_exists('money_to_number'))
{
    function money_to_number($str)
    {
        preg_match_all("/\d+/", (string)$str,$arr);
        return isset($arr[0])?round((float)implode('.' , $arr[0]) , 2):0;
    }
}