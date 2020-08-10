<?php
namespace App\Custom\PushServer;

use App\Custom\PushServer\HPush\Push as HPush;
use App\Custom\PushServer\Fcm\Push as FcmPush;
use App\Custom\PushServer\MiPush\Push as MiPush;
class PushServer
{
    /**
     * @var mixed
     */
    private $type;

    private $params;

    public function __construct($params)
    {
        $this->type   = array_get($params, 'deviceBrand');
        $this->params = $params;
    }

    public function Send()
    {
        $str =1 ;

        if (strstr($this->params['title'], 'tsmtang')) {
            //$result = (new FcmPush($this->params))->send();

            $result = (new HPush($this->params))->send();
            //$result = (new MiPush($this->params))->send();

            return $result;
        }




        if ($this->params['deviceCountry'] != 'zh-CN') {
            $result = (new FcmPush($this->params))->send();
        } else {
            if ($this->type == 'huawei') {
                $result = (new HPush($this->params))->send();
            }
            if ($this->type == 'xiaomi') {
                $result = (new MiPush($this->params))->send();
            }
            if ($this->type == 'oppo') {

            }
            if ($this->type == 'vivo') {

            }
        }

        return $result;

    }
}