<?php

namespace App\Services;


use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

class NiuTranslateService
{

    const MAX_RETRIES = 2;

    private $host = 'http://api.niutrans.com';

    private $path = '/NiuTransServer/translation';

    private $api_key = '';

    private $client;

    private $connect_timeout = 5;

    private $debug = false;

    private $decode_content = true;

    private $timeout = 10;

    private $params = array();

    /**
     * TranslateService constructor.
     */
    public function __construct()
    {
        $this->api_key = config('translatable.niu_translate_key');
        $this->params = array(
            'apikey'=>$this->api_key
        );
        // 创建 Handler
        $handlerStack = HandlerStack::create(new CurlHandler());
        // 创建重试中间件，指定决策者为 $this->retryDecider(),指定重试延迟为 $this->retryDelay()
        $handlerStack->push(Middleware::retry($this->retryDecider(), $this->retryDelay()));

        $this->client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $this->host,
            // You can set any number of default request options.
            'connect_timeout'=>$this->connect_timeout,
            'debug'=>$this->debug,
            'decode_content'=>$this->decode_content,
            'verify'=>false,
            'timeout'=>$this->timeout,
//            'form_params' => $this->params,
            'handler' => $handlerStack
        ]);
    }


    /**
     * @return array
     */
    public function languages()
    {
        return $this->translate->languages();
    }

    /**
     * @param array $str
     * @param array $option
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function translateBatch(array $str , $option=array())
    {
        $options['to'] = isset($option['target'])?$option['target']:'en';
        $options['from'] = isset($option['source'])?$option['source']:'auto';
        $verification= fromAzureGoogleToNiu($options['from']);
        if(!$verification['support'])
        {
            abort(417 , 'This language is not supported by NiuTrans：'.$options['from']);
        }
        $options['from'] = $verification['language'];
        $options['to'] = SupportToNiu($options['to']);
        $options['src_text'] = join("\n" , $str);
        $params = $options+$this->params;
        $params = array_filter($params , function ($v , $k){
            return !blank($k)&&!blank($v);
        } , ARRAY_FILTER_USE_BOTH );
        $response = $this->client->request('POST', $this->path, [
            'form_params' => $params
        ]);
        $result = \json_decode($response->getBody()->getContents() , true);
        if(isset($result['error_code']))
        {
            abort(417 , \json_encode($result , JSON_UNESCAPED_UNICODE));
        }
        $i = 0;
        $tgt_text = explode("\n" , $result['tgt_text']);
        return array_map(function ($v) use ($str, $result , &$i){
            $translate = array(
                "source"=>$result['from'],
                "input"=>$str[$i],
                "text"=>$v,
                "model"=>null
            );
            $i++;
            return $translate;
        } , $tgt_text);
    }

    /**
     * @param $str
     * @param array $option
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function translate(string $str , $option=array())
    {
        $options['to'] = isset($option['target'])?$option['target']:'en';
        $options['from'] = isset($option['source'])?$option['source']:app(AzureTranslateService::class)->detectLanguage($str);
        $verification= fromAzureGoogleToNiu($options['from']);
        if(!$verification['support'])
        {
            abort(417 , 'This language is not supported by NiuTrans：'.$options['from']);
        }
        $options['from'] = $verification['language'];
        $options['to'] = SupportToNiu($options['to']);
        $options['src_text'] = $str;
        $params = $options+$this->params;
        $params = array_filter($params , function ($v , $k){
            return !blank($k)&&!blank($v);
        } , ARRAY_FILTER_USE_BOTH );
        $response = $this->client->request('POST', $this->path, [
            'form_params' => $params
        ]);
        $result = \json_decode($response->getBody()->getContents() , true);
        if(isset($result['error_code']))
        {
            abort(417 , \json_encode($result , JSON_UNESCAPED_UNICODE));
        }
        return $result['tgt_text'];
    }


    /**
     * @param string $str
     * @param array $option
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function onlyTranslate(string $str , $option=array())
    {
        $options['to'] = isset($option['target'])?$option['target']:'en';
        $options['from'] = isset($option['source'])?$option['source']:app(AzureTranslateService::class)->detectLanguage($str);
        $verification= fromAzureGoogleToNiu($options['from']);
        if(!$verification['support'])
        {
            return array('source'=>$options['from'] , 'translate'=>$str , 'target'=>$options['to']);
        }
        $options['from'] = $verification['language'];
        $options['to'] = SupportToNiu($options['to']);
        $options['src_text'] = $str;
        $params = $options+$this->params;
        $params = array_filter($params , function ($v , $k){
            return !blank($k)&&!blank($v);
        } , ARRAY_FILTER_USE_BOTH );
        $response = $this->client->request('POST', $this->path, [
            'form_params' => $params
        ]);
        $result = \json_decode($response->getBody()->getContents() , true);
        if(isset($result['error_code']))
        {
            \Log::error('Niu translation error：'.\json_encode($result));
            return array('source'=>$options['from'] , 'translate'=>$str , 'target'=>$options['to']);
        }
        return array('source'=>niuAzureToGoogle($result['from']) , 'translate'=>$result['tgt_text'] , 'target'=>niuAzureToGoogle($result['to']));
    }


    protected function retryDecider()
    {
        return function ($retries,Request $request,Response $response = null,RequestException $exception = null) {
            // 超过最大重试次数，不再重试
            if ($retries >= self::MAX_RETRIES) {
                return false;
            }

            // 请求失败，继续重试
            if ($exception instanceof ConnectException) {
                return true;
            }

            if ($response) {
                // 如果请求有响应，但是状态码大于等于500，继续重试(这里根据自己的业务而定)
                if ($response->getStatusCode() >= 500) {
                    return true;
                }
            }

            return false;
        };
    }

    protected function retryDelay()
    {
        return function ($numberOfRetries) {
            return 1000 * $numberOfRetries;
        };
    }
}
