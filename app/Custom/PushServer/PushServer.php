<?php
namespace App\Custom\PushServer;

use App\Custom\PushServer\HPush\Push as HPush;
use App\Custom\PushServer\Fcm\Push as FcmPush;
use App\Custom\PushServer\MiPush\Push as MiPush;
use Illuminate\Support\Facades\Log;

class PushServer
{
    private $type;
    private $params;
    private $uniqid;

    public function __construct($params)
    {
        $this->type   = array_get($params, 'deviceBrand');
        $this->params = $params;
    }

    public function Send()
    {
        if ($this->params['registerType'] == 'fcm') {
            $this->fcmPush();
        } else {
            if ($this->params['deviceBrand'] == 'huawei') {
                $this->huaweiPush();
            }
            if ($this->params['deviceBrand'] == 'xiaomi') {
                $this->xiaomiPush();
            }
            if ($this->params['deviceBrand'] == 'oppo') {
                $this->oppoPush();
            }
            if ($this->params['deviceBrand'] == 'vivo') {

            }
        }

    }

    /**
     * @return bool
     * Fcm
     */
    public function fcmPush()
    {
        try {
            $result = (new FcmPush($this->params))->send();
            if (!empty($result['success'])) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Push message Exception'.__FUNCTION__, ['code'=>$e->getCode(), 'msg'=> $e->getMessage()]);
        }
    }

    /**
     * @return bool
     * 华为推送
     */
    public function huaweiPush()
    {
        try {
            $result = (new HPush($this->params))->send();
            if ($result['code'] == '80000000') {
                Log::info('Push message success:'.__FUNCTION__, $result);
                return true;
            } else {
                Log::info('Push message fail:'.__FUNCTION__, $result);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Push message Exception'.__FUNCTION__, ['code'=>$e->getMessage(), 'msg'=> $e->getMessage()]);
        }
    }

    public function xiaomiPush()
    {
        try {
            $result = (new MiPush($this->params))->send();
            if ($result['code'] == 0) {
                Log::info('Push message success:'.__FUNCTION__, $result);
                return true;
            } else {
                Log::info('Push message fail:'.__FUNCTION__, $result);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Push message Exception'.__FUNCTION__, ['code'=>$e->getMessage(), 'msg'=> $e->getMessage()]);
        }
    }

    public function oppoPush()
    {
        try {
            $result = (new OPush($this->params))->send();
            if ($result['code'] == 0) {
                Log::info('Push message success:'.__FUNCTION__, $result);
                return true;
            } else {
                Log::info('Push message fail:'.__FUNCTION__, $result);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Push message Exception'.__FUNCTION__, ['code'=>$e->getMessage(), 'msg'=> $e->getMessage()]);
        }
    }
}