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
            //return (new HPush($this->params))->send();
            return (new FcmPush($this->params))->send();


        }
        if ($this->params['deviceCountry'] != 'zh-CN') {
            return (new FcmPush($this->params))->send();
        } else {
            if ($this->type == 'huawei') {
                return (new HPush($this->params))->send();
            }
            if ($this->type == 'xiaomi') {
                return (new MiPush($this->params))->send();
            }
            if ($this->type == 'oppo') {

            }
            if ($this->type == 'vivo') {

            }
        }


    }
}