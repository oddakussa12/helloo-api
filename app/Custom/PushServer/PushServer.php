<?php
namespace App\Custom\PushServer;

use App\Custom\PushServer\HPush\Push as HPush;
use App\Custom\PushServer\Fcm\Push as FcmPush;
use App\Custom\PushServer\MiPush\Push as MiPush;
use App\Custom\PushServer\OPush\Push as OPush;
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
        Log::info('message', $this->params);
        $deviceBrand = strtolower($this->params['deviceBrand']);
        if ($this->params['registerType'] == 'fcm') {
            $result = $this->fcmPush();
        } else {
            if (in_array($deviceBrand, ['huawei', 'honer'])) {
                $result = $this->huaweiPush();
            }
            if ($deviceBrand == 'xiaomi') {
                $result = $this->xiaomiPush();
            }
            if (in_array($deviceBrand, ['oppo', 'realme'])) {
                $result = $this->oppoPush();
            }
            if ($deviceBrand == 'vivo') {

            }
        }
        if (empty($result)) {
            // Log::info(__FUNCTION__.' params:', $this->params);
        }


    }

    /**
     * @return bool
     * Fcm
     */
    public function fcmPush()
    {
        try {
            $tokens = $this->params['registrationId'];
            $tokens = is_array($tokens) ? $tokens : [$tokens];
            $count  = count($tokens);
            $limit  = 1000;
            $page   = intval(ceil($count/$limit));

            for ($i=0; $i<$page; $i++) {
                $this->params['registrationId'] = array_slice($tokens, $i*$limit, $limit-1);
                $result = (new FcmPush($this->params))->send();
                if (!empty($result['success'])) {
                    return true;
                } else {
                    if (!empty($result['tokensToDelete'])) {
                         Log::info(__FUNCTION__.' Push message tokensToDelete:'.__FUNCTION__, $result);
                        return true;
                    } else {
                        Log::info(__FUNCTION__.' Push message fail:'.__FUNCTION__, $result);
                        return false;
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error(__FUNCTION__.' Push message Exception'.__FUNCTION__, ['code'=>$e->getCode(), 'msg'=> $e->getMessage()]);
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
                 Log::info(__FUNCTION__.' Push message success:'.__FUNCTION__, $result);
                return true;
            } else {
                Log::info(__FUNCTION__.' Push message fail:'.__FUNCTION__, $result);
                return false;
            }
        } catch (\Exception $e) {
            Log::error(__FUNCTION__.' Push message Exception'.__FUNCTION__, ['code'=>$e->getMessage(), 'msg'=> $e->getMessage()]);
        }
    }

    public function xiaomiPush()
    {
        try {
            $result = (new MiPush($this->params))->send();
            if ($result['code'] == 0) {
                Log::info(__FUNCTION__.' Push message success:'.__FUNCTION__, $result);
                return true;
            } else {
                Log::info(__FUNCTION__.' Push message fail:'.__FUNCTION__, $result);
                return false;
            }
        } catch (\Exception $e) {
            Log::error(__FUNCTION__.' Push message Exception'.__FUNCTION__, ['code'=>$e->getMessage(), 'msg'=> $e->getMessage()]);
        }
    }

    public function oppoPush()
    {
        try {
            $result = (new OPush($this->params))->send();
            if ($result['code'] == 0) {
                Log::info(__FUNCTION__.' Push message success:'.__FUNCTION__, $result);
                return true;
            } else {
                Log::info(__FUNCTION__.' Push message fail:'.__FUNCTION__, $result);
                return false;
            }
        } catch (\Exception $e) {
            Log::error(__FUNCTION__.' Push message Exception'.__FUNCTION__.'code:'.$e->getCode(). 'msg:'. $e->getMessage());
            return false;
        }
    }
}