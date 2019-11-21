<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class TencentTranslateService
{

    private $url;
    private $appId;
    private $appKey;

    public function __construct()
    {
        $this->url = 'https://api.ai.qq.com/fcgi-bin/nlp/nlp_texttranslate';
        $this->appId = '2123421139';
        $this->appKey = 'rtR1M63KUjY332kg';
    }

    public function translate($str , $options)
    {
        $params['text'] = $str;
        if($options['source']=='zh-CN')
        {
            $params['source'] = 'zh';
            $params['target'] = 'en';
        }elseif($options['source']=='en'){
            $params['source'] = 'en';
            $params['target'] = 'zh';
        }else{
            return false;
        }
        $params['sign'] = $this->getReqSign($params);
        $translate = $this->doHttpPost($this->url, $params);
        if($translate)
        {
            $translate = \json_decode($translate);
            return $this->xssReplace($translate->data->target_text);
        }
        return $translate;
    }

    protected function getReqSign(&$params)
    {
        $params['app_id'] = isset($params['app_id'])?$params['app_id']:$this->appId;
        if (empty($params['nonce_str']))
        {
            $params['nonce_str'] = uniqid("{$params['app_id']}_");
        }
        if (empty($params['time_stamp']))
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
                $str .= $key . '=' . urlencode($value) . '&';
            }
        }
        $appkey = isset($params['app_key'])?$params['app_key']:$this->appKey;
        // 3. 拼接app_key
        $str .= 'app_key=' . $appkey;
        // 4. MD5运算+转换大写，得到请求签名
        $sign = strtoupper(md5($str));
        return $sign;
    }

    protected function doHttpPost($url, $params)
    {
        $curl = curl_init();
        $response = false;
        do
        {
            // 1. 设置HTTP URL (API地址)
            curl_setopt($curl, CURLOPT_URL, $url);

            // 2. 设置HTTP HEADER (表单POST)
            $head = array(
                'Content-Type: application/x-www-form-urlencoded'
            );
            curl_setopt($curl, CURLOPT_HTTPHEADER, $head);

            // 3. 设置HTTP BODY (URL键值对)
            $body = http_build_query($params);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);

            // 4. 调用API，获取响应结果
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_NOBODY, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($curl);
            $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($code != 200)
            {
                $msg = curl_error($curl);
                $response = false;
                $message = json_encode(array('ret' => -1, 'msg' => "sdk http post err: {$msg}", 'http_code' => $code));
                Log::error($message);
                break;
            }
        } while (0);
        curl_close($curl);
        return $response;
    }

    public function xssReplace($str)
    {
        return str_replace('< ' , '<' , $str);
    }

}
