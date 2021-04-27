<?php
if(isset($_GET['u']))
{
    $u = strval($_GET['u']);
    if(!empty($u))
    {
        session_start();
        if(empty($_SESSION['view']))
        {
            $_SESSION['view']=time();
        }
        $session = $_SESSION['view'];
        $ip = getRequestIpAddress();
        curl($_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].'/api/statistics/log' , array(
            'ip'=>$ip,
            'session'=>$session,
            'u'=>$u,
            'time'=>time(),
        ) , true , true);
    }
    header('content-type:text/html;charset=uft-8');
    header('location:https://play.google.com/store/apps/details?id=com.helloo');
}
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


function curl($url, $params = false, $isPost = 0, $https = 0)
{
    $httpInfo = array();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36/HellooSelf');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($https) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
    }
    if ($isPost) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_URL, $url);
    } else {
        if ($params) {
            if (is_array($params)) {
                $params = http_build_query($params);
            }
            curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
        } else {
            curl_setopt($ch, CURLOPT_URL, $url);
        }
    }

    $response = curl_exec($ch);
    if ($response === false) {
        return false;
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $httpInfo = array_merge($httpInfo, curl_getinfo($ch));
    curl_close($ch);
    return $response;
}