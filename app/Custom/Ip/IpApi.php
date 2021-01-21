<?php
namespace App\Custom\Ip;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class IpApi
{
    /**
     * @return Client
     */
    public static function getHttpClient()
    {
        return new Client();
    }

    /**
     * @param string $type
     * @param string $ip
     * @param int $timeout
     * @param string $lang
     * @return string
     * @throws Exception|\GuzzleHttp\Exception\GuzzleException
     */
    public static function getIp($type = 'json', $ip = '', $timeout = 10,$lang = '')
    {
        $url = 'http://ip-api.com/' . $type . '/' . $ip;

        if (!in_array(strtolower($type), ['xml', 'json', 'php', 'csv'])) {
            throw new Exception('Invalid response type:' . $type);
        }

        self::checkIp($ip);

        $query = array_filter([
            'lang' => $lang ? $lang : 'zh-CN'
        ]);
        Log::info('ip_api' , array(
            'type'=>$type,
            'ip'=>$ip,
            'timeout'=>$timeout,
            'lang'=>$lang,
        ));
        try {
            return self::getHttpClient()->get($url, [
                'query' => $query, 'timeout' => $timeout,
            ])->getBody()->getContents();
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }

    }

    /**
     * @param $ip
     * @throws Exception
     */
    public static function checkIp($ip)
    {
        if (!empty($ip)) {
            if (!filter_var($ip, \FILTER_VALIDATE_IP)) {
                throw new Exception('Invalid Ip:' . $ip);
            }
        }
    }

    /**
     * @param $ip
     * @throws Exception
     */
    public static function checkIpV4($ip)
    {
        if (!empty($ip)) {
            if (!filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4)) {
                throw new Exception('Invalid IpV4:' . $ip);
            }
        }
    }

    /**
     * @param $ip
     * @throws Exception
     */
    public static function checkIpV6($ip)
    {
        if (!empty($ip)) {
            if (!filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6)) {
                throw new Exception('Invalid IpV6:' . $ip);
            }
        }
    }

    /**
     * @param $ip
     * @return string
     * @throws Exception
     */
    public static function IpV4toV6($ip)
    {
        self::checkIpV4($ip);
        $set = '0000:0000:0000:0000:0000:ffff:';
        $arr = explode('.', $ip);
        $new = [];
        foreach ($arr as $k => $value) {
            $tran = base_convert($value, 10, 16);
            if (strlen($tran) == 1) {
                $tran = '0' . $tran;
            }
            $new[$k] = $tran;
        }
        return $set . $new[0] . $new[1] . ':' . $new[2] . $new[3];
    }

    /**
     * @param $ip
     * @return string
     * @throws Exception
     */
    public static function IpV6toV4($ip)
    {
        self::checkIpV6($ip);
        $str = mb_substr($ip, 30, 38);
        $arr = explode(':', $str);
        $Ip1 = base_convert(mb_substr($arr[0], 0, 2), 16, 10);
        $Ip2 = base_convert(mb_substr($arr[0], 2, 4), 16, 10);
        $Ip3 = base_convert(mb_substr($arr[1], 0, 2), 16, 10);
        $Ip4 = base_convert(mb_substr($arr[1], 2, 4), 16, 10);
        return $Ip1 . '.' . $Ip2 . '.' . $Ip3 . '.' . $Ip4;
    }
}