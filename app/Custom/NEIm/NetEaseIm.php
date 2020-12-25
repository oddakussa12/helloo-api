<?php
namespace App\Custom\NEIm;

use App\Custom\NEIm\NEMessage\BatchP2PMessage;
use App\Custom\NEIm\NEMessage\AbstractNEMessage;
use App\Custom\NEIm\NEException\NEParamsCheckException;
use App\Custom\NEIm\NEException\NEUploadFileNotFoundException;
use App\Custom\NEIm\NEMessage\BroadcastMessage;
use App\Custom\NEIm\NEMessage\NeSelfDefineMessage;
use App\Custom\NEIm\NeResponse\BroadcastResponse;
use App\Custom\NEIm\NeResponse\SingleFileResponse;
use App\Custom\NEIm\NeResponse\AccidBlockMuteListResponse;

class NetEaseIm
{
    private $httpRequest = null;
    private $appKey;                //开发者平台分配的AppKey
    private $appSecret;             //开发者平台分配的AppSecret,可刷新
    private $nonce;     //随机数（最大长度128个字符）
    private $currentTime;              //当前UTC时间戳，从1970年1月1日0点0 分0 秒开始到现在的秒数(String)
    private $checkSum;    //SHA1(AppSecret + Nonce + CurTime),三个参数拼接的字符串，进行SHA1哈希计算，转化成16进制字符(String，小写)

    public const CONTENT_TYPE = "application/x-www-form-urlencoded;charset=utf-8";
    public const HEX_DIGITS = "0123456789abcdef";
    /////////////////////protocal
    public const OP_P2P_BLACK = 1;
    public const OP_P2P_MUTE = 2;
    // 取消黑名单或静音
    public const OP_UNSET = 0;
    // 加入黑名单或静音
    public const OP_SET = 1;
    public function __construct(array $config) {
        $this->check_config($config);
        $this->httpRequest = new HttpRequest();
        $this->httpRequest->add_header("AppKey", $this->appKey);
        $this->httpRequest->add_header("Content-Type", self::CONTENT_TYPE);
        $this->checkSumBuilder();
    }
    
    private function check_config(array $config):bool
    {
        if (empty($config['AppKey'])) {
            throw new NEParamsCheckException("param check faild AppKey is neccesary");
        }
        if (empty($config['AppSecret'])) {
            throw new NEParamsCheckException("Param check faild AppSecret is neccesary");
        }
        $this->appKey = $config['AppKey'];
        $this->appSecret = $config['AppSecret'];
        return true;
    }

    /**
     * API checksum校验生成
     * @return string $CheckSum(对象私有属性)
     */
    private function checkSumBuilder():string {
        //此部分生成随机字符串
        $hex_digits = self::HEX_DIGITS;
        for ($i = 0; $i < 128; $i++) {   //随机字符串最大128个字符，也可以小于该数
            $this->nonce .= $hex_digits[rand(0, 15)];
        }
        $this->currentTime = time(); //当前时间戳，以秒为单位

        $join_string = $this->appSecret . $this->nonce . $this->currentTime;
        $this->checkSum = sha1($join_string);
        $this->httpRequest->add_header("CurTime", $this->currentTime);
        $this->httpRequest->add_header("Nonce", $this->nonce);
        $this->httpRequest->add_header("CheckSum", $this->checkSum);
        return $this->checkSum;
    }
    
    
    #########################################accid crud

    /**
     * create netease response
     *
     * @tested
     * @param string $acc_id
     * @param string $name
     * @param array $left_param
     * @return NetEaseImResponse
     */
    public function create_acc_id(
            string $acc_id, string $name, array $left_param = []):NetEaseImResponse
    {
        $data = [
            'accid' => $acc_id,
            'name' => $name,
        ];
        $data = array_merge($left_param, $data);
        $this->httpRequest->set_url(NEConstants::create_acc_id);
        $this->httpRequest->set_data($data);
        return new NetEaseImResponse(
                function(){return $this->httpRequest->https_post();});
    }
    
    public function update_acc_id(string $acc_id, array $props, string $token): NetEaseImResponse
    {
        $data = [
            'accid' => $acc_id,
            'props' => json_encode($props),
            'token' => $token,
        ];
        $this->httpRequest->set_url(NEConstants::update_accid);
        $this->httpRequest->set_data($data);
        return new NetEaseImResponse(
                function(){return $this->httpRequest->https_post();});
    }
    
    public function refresh_acc_id_token(string $acc_id): NetEaseImResponse
    {
        $data = [
            'accid' => $acc_id
        ];
        $this->httpRequest->set_url(NEConstants::refresh_accid);
        $this->httpRequest->set_data($data);
        return new NetEaseImResponse(
                function(){return $this->httpRequest->https_post();});
    }
    
    public function block_acc_id(
            string $acc_id, bool $need_kick = false): NetEaseImResponse
    {
        $data = [
            'accid' => $acc_id,
            'needkick' => $need_kick ? 'true': 'false',
        ];
        $this->httpRequest->set_url(NEConstants::block_accid);
        $this->httpRequest->set_data($data);
        return new NetEaseImResponse(
                function(){return $this->httpRequest->https_post();});
    }
    
    public function unblock_acc_id(string $acc_id): NetEaseImResponse
    {
        $data = [
            'accid' => $acc_id,
        ];
        $this->httpRequest->set_url(NEConstants::unblock_accid);
        $this->httpRequest->set_data($data);
        return new NetEaseImResponse(
                function(){return $this->httpRequest->https_post();});
    }
    
    ###########################################accid infos start
    
    public function update_acc_id_info(
            string $acc_id, string $name = '',string $icon = '',
            string $sign = '', string $email = '', string $birth = '', 
            string $mobile = '', int $gender = 0, array $ex = []): NetEaseImResponse
    {
        $data['accid'] = $acc_id;
        if (!empty($name))$data['name'] = $name;
        if (!empty($icon))$data['icon'] = $icon;
        if (!empty($sign))$data['sign'] = $sign;
        if (!empty($email))$data['email'] = $email;
        if (!empty($birth))$data['birth'] = $birth;
        if (!empty($mobile))$data['mobile'] = $mobile;
        if (!empty($gender))$data['gender'] = $gender;
        if (!empty($ex))$data['ex'] = json_encode ($ex);
        $this->httpRequest->set_url(NEConstants::update_accid_info);
        $this->httpRequest->set_data($data);
        return new NetEaseImResponse(
                function(){return $this->httpRequest->https_post();});
    }
    
    public function get_acc_id_infos(array $acc_ids):NetEaseImResponse
    {
        if (count($acc_ids) > 200) throw new NEParamsCheckException(
                "count of accids in query can not over 200");
        $data['accid'] = json_encode($acc_ids);
        $this->httpRequest->set_url(NEConstants::get_accid_infos);
        $this->httpRequest->set_data($data);
        return new NeResponse\AccifUInfosResponse(function(){
            return $this->httpRequest->https_post();
        });
    }
    #############################################accid infos end
    #############################################member setting start
    public function set_don_nop(
            string $acc_id, bool $don_nop_open = true):NetEaseImResponse
    {
        $data['accid'] = $acc_id;
        $data['donnopOpen'] = $don_nop_open?'true':'false';
        $this->httpRequest->set_url(NEConstants::set_dunnop);
        $this->httpRequest->set_data($data);
        return new NetEaseImResponse(function(){
            return $this->httpRequest->https_post();
        });
    }
    #############################################member setting end
    #############################################member relations ship
    public function add_friend(
            string $acc_id, string $faccid, int $type, string $msg):NetEaseImResponse
    {
        $data['accid'] = $acc_id;
        $data['faccid'] = $faccid;
        $data['type'] = $type;
        $data['msg'] = $msg;
        $this->httpRequest->set_url(NEConstants::add_friends);
        $this->httpRequest->set_data($data);
        return new NetEaseImResponse(function(){
            return $this->httpRequest->https_post();
        });
    }
    
    public function update_friends(
            string $acc_id, string $fac_cid, string $alias, string $ex):NetEaseImResponse
    {
        $data['accid'] = $acc_id;
        $data['faccid'] = $fac_cid;
        $data['alias'] = $alias;
        $data['ex'] = $ex;
        $this->httpRequest->set_url(NEConstants::update_friends);
        $this->httpRequest->set_data($data);
        return new NetEaseImResponse(function(){
            return $this->httpRequest->https_post();
        });
    }
    
    public function delete_friends(
            string $acc_id, string $fac_cid):NetEaseImResponse
    {
        $data['accid'] = $acc_id;
        $data['faccid'] = $fac_cid;
        $this->httpRequest->set_url(NEConstants::delete_friends);
        $this->httpRequest->set_data($data);
        return new NetEaseImResponse(function(){
            return $this->httpRequest->https_post();
        });
    }
    
    public function get_friends_relations(
            string $acc_id, int $update_time, int $create_time):NeResponse\FriendsInfosResponse
    {
        $data['accid'] = $acc_id;
        $data['updatetime'] = $update_time;
        $data['createtime'] = $create_time;
        $this->httpRequest->set_url(NEConstants::get_friends);
        $this->httpRequest->set_data($data);
        return new NeResponse\FriendsInfosResponse(function(){
            return $this->httpRequest->https_post();
        });
    }
    
    protected function quiet_black_sb(
            string $acc_id, string $targetAcc, int $relationType, int $value): NetEaseImResponse
    {
        $data['accid'] = $acc_id;
        $data['targetAcc'] = $targetAcc;
        $data['relationType'] = $relationType;
        $data['value'] = $value;
        $this->httpRequest->set_url(NEConstants::quiet_black_friends);
        $this->httpRequest->set_data($data);
        return new NetEaseImResponse(function(){
            return $this->httpRequest->https_post();
        });
    }
    
    public function p2p_mute_user(string $acc_id, string $targetAcc): NetEaseImResponse
    {
        return $this->quiet_black_sb($acc_id, $targetAcc, self::OP_P2P_MUTE, self::OP_SET);
    }
    
    public function p2p_unmute_user(string $acc_id, string $targetAcc):NetEaseImResponse
    {
        return $this->quiet_black_sb($acc_id, $targetAcc, self::OP_P2P_MUTE, self::OP_UNSET);
    }
    
    public function p2p_block_user(string $acc_id, string $targetAcc):NetEaseImResponse
    {
        return $this->quiet_black_sb($accid, $targetAcc, self::OP_P2P_BLACK, self::OP_SET);
    }
    
    public function p2p_unblock_user(string $acc_id, string $targetAcc):NetEaseImResponse
    {
        return $this->quiet_black_sb($accid, $targetAcc, self::OP_P2P_BLACK, self::OP_UNSET);
    }
    
    public function list_black_and_mute_list(string $acc_id):NetEaseImResponse
    {
        $data['accid'] = $acc_id;
        $this->httpRequest->set_url(NEConstants::quiet_black_friends_list);
        $this->httpRequest->set_data($data);
        return new AccidBlockMuteListResponse(function(){
            return $this->httpRequest->https_post();
        });
    }
    ##############################################message 

    /**
     * 0 表示文本消息,
     * 1 表示图片，
     * 2 表示语音，
     * 3 表示视频，
     * 4 表示地理位置信息，
     * 6 表示文件，
     * 100 自定义消息类型
     *
     * @param AbstractNEMessage $message
     * @param bool $antispam
     * @param array $antispamCustom
     * @param array $option
     * @param array $pushContent
     * @param array $payload
     * @param array $ext
     * @param array $forcePushList
     * @param string $forcePushContent
     * @param bool $forcePushAll
     * @param string $bid
     * @param int $useYiDun
     * @return NetEaseImResponse
     */
    public function message_send(
        AbstractNEMessage $message,
        bool $antispam =false,
        array $antispamCustom = [],
        array $option = [],
        array $pushContent = [],
        array $payload = [],
        array $ext = [],
        array $forcePushList = [],
        string $forcePushContent = '',
        bool $forcePushAll = true,
        string $bid = '',
        int $useYiDun = 0):NetEaseImResponse
    {
        $data['from'] = $message->from;
        $data['ope'] = $message->ope;
        $data['to'] = $message->to;
        $data['type'] = $message->type;
        $data['body'] = $message->toString();
        if (!empty($antispam)) $data['antispam'] = 'true';
        if (!empty($antispamCustom)) $data['antispamCustom'] = json_encode($antispamCustom);
        if (!empty($option)) $data['option'] = $message->getOptions();
        if (!empty($pushcontent)) $data['pushcontent'] = json_encode($pushContent);
        if (!empty($ext)) $data['ext'] = json_encode($ext);
        if (!empty($forcepushlist)) $data['forcepushlist'] = json_encode($forcePushList);
        if (!empty($forcepushcontent)) $data['forcepushcontent'] = $forcePushContent;
        $data['forcepushall'] = $forcePushAll;
        if (!empty($bid)) $data['bid'] = $bid;
        if (!empty($useYidun)) $data['useYidun'] = $useYiDun;
        $this->httpRequest->set_url(NEConstants::send_msg);
        $this->httpRequest->set_data($data);
        return new NetEaseImResponse(function(){
            return $this->httpRequest->https_post();
        });
    }
    
    /**
     * 批量发送点对点普通消息
     * 
     * @param BatchP2PMessage $messages
     * @param array $pushcontent
     * @param array $payload
     * @param array $ext
     * @param string $bid
     * @param int $useYidun
     * @return NetEaseImResponse
     */
    public function send_batch_attach_msg(
        BatchP2PMessage $messages, array $pushcontent = [],
            array $payload = [], array $ext = [], string $bid = '', 
            int $useYidun = 0): NetEaseImResponse
    {
        $data['fromAccid'] = $messages->from;
        $data['toAccids'] = $messages->get_tos();
        $data['type'] = $messages->get_type();
        $data['body'] = $messages->get_body();
        $data['option'] = $messages->get_options();
        if (!empty($pushcontent)) $data['pushcontent'] = json_encode($pushcontent);
        if (!empty($payload)) $data['payload'] = json_encode($payload);
        if (!empty($ext)) $data['ext'] = json_encode($ext);
        if (!empty($bid)) $data['bid'] = $bid;
        if (!empty($useYidun)) $data['useYidun'] = $useYidun;
        $this->httpRequest->set_url(NEConstants::batch_send_p2p_msg);
        $this->httpRequest->set_data($data);
        return new NetEaseImResponse(function(){
            return $this->httpRequest->https_post();
        });
    }
    
    /**
     * 发送自定义系统通知
     * 
     * @param NeSelfDefineMessage $message
     * @param int $msgtype
     * @param array $pushcontent
     * @param array $payload
     * @param string $sound
     * @param int $save
     * @return NetEaseImResponse
     */
    public function send_attach_message(
            NeSelfDefineMessage $message, int $msgtype = 0,array $pushcontent = [],
            array $payload = [], string $sound = '', int $save = 2): NetEaseImResponse
    {
        $data['from'] = $message->from;
        $data['msgtype'] = $msgtype;
        $data['to'] = $message->to;
        $data['attach'] = $message->toString();
        if (!empty($pushcontent)) $data['pushcontent'] = json_encode ($pushcontent);
        if (!empty($payload)) $data['payload'] = json_encode ($payload);
        if (!empty($sound)) $data['sound'] = $sound;
        $data['save'] = $save;
        $data['option'] = $message->get_options();
        $this->httpRequest->set_url(NEConstants::self_define_sys_notify);
        $this->httpRequest->set_data($data);
        return new NetEaseImResponse(function(){
            return $this->httpRequest->https_post();
        });
    }
    
    /**
     * 
     * 文件上传 ，字符流需要base64编码，最大15M。
     * @param string $path
     * @param string $type
     * @param bool $ishttps
     * @return NetEaseImResponse
     * @throws NEUploadFileNotFoundException
     */
    public function upload_single_file(
            string $path, string $type, bool $ishttps = false) :NetEaseImResponse
    {
        if (!file_exists($path)) {
            throw new NEUploadFileNotFoundException("File '{$path}' Not found!");
        }
        $content = base64_encode(file_get_contents($path));
        $data['content'] = $content;
        if (!empty($type)) $data['type'] = $type;
        $data['ishttps'] = $ishttps;
        $this->httpRequest->set_url(NEConstants::file_upload);
        $this->httpRequest->set_data($data);
        return new SingleFileResponse(function(){
            return $this->httpRequest->https_post();
        });
    }
    
    /**
     * 文件上传（multipart方式）最大15M
     * 
     * @param string $path
     * @param string $type
     * @param bool $ishttps
     * @return NetEaseImResponse
     * @throws NEUploadFileNotFoundException
     */
    public function upload_single_file_multipart(
            string $path, string $type, bool $ishttps = false) :NetEaseImResponse
    {
        if (!file_exists($path)) {
            throw new NEUploadFileNotFoundException("File '{$path}' Not found!");
        }
        $this->httpRequest->add_header("Content-Type", "Content-Type:multipart/form-data;charset=utf-8");
        $content = file_get_contents($path);
        $data['content'] = $content;
        if (!empty($type)) $data['type'] = $type;
        $data['ishttps'] = $ishttps;
        $this->httpRequest->set_url(NEConstants::file_upload_multi);
        $this->httpRequest->set_data($data);
        return new SingleFileResponse(function(){
            return $this->httpRequest->https_post();
        });
    }
    
    /**
     * 消息撤回接口，可以撤回一定时间内的点对点与群消息
     * 
     * @param string $deleteMsgId 要撤回消息的msgid
     * @param int $timetag 要撤回消息的创建时间
     * @param int $type 7:表示点对点消息撤回，8:表示群消息撤回，其它为参数错误
     * @param string $from 发消息的accid
     * @param string $to 如果点对点消息，为接收消息的accid,如果群消息，为对应群的tid
     * @param string $msg 可以带上对应的描述 1表示忽略撤回时间检测，其它为非法参数，如果需要撤回时间检测，不填即可
     * @param string $ignoreTime
     */
    public function recall_message(
            string $deleteMsgId, int $timetag, int $type, 
            string $from, string $to, string $msg = '', string $ignoreTime = '') :NetEaseImResponse
    {
        $data['deleteMsgId'] = $deleteMsgId;
        $data['timetag'] = $timetag;
        $data['type'] = $type;
        $data['from'] = $from;
        $data['to'] = $to;
        if (!empty($msg)) $data['msg'] = $msg;
        if (!empty($ignoreTime)) $data['ignoreTime'] = $ignoreTime;
        $this->httpRequest->set_url(NEConstants::recall);
        $this->httpRequest->set_data($data);
        return new NetEaseImResponse(function(){
            return $this->httpRequest->https_post();
        });
    }
    
    /**
     * 发送广播消息
     * 
     * 1、广播消息，可以对应用内的所有用户发送广播消息，广播消息目前暂不支持第三方推送（APNS、小米、华为等）；
     * 2、广播消息支持离线存储，并可以自定义设置离线存储的有效期，最多保留最近100条离线广播消息；
     * 3、此接口受频率控制，一个应用一分钟最多调用10次，一天最多调用1000次，超过会返回416状态码；
     * 4、该功能目前需申请开通，详情可咨询您的客户经理。
     * 
     * body 	 String 	是	广播消息内容，最大4096字符
     * from 	 String	否	发送者accid, 用户帐号，最大长度32字符，必须保证一个APP内唯一
     * isOffline String	否	是否存离线，true或false，默认false
     * ttl 	 int	否	存离线状态下的有效期，单位小时，默认7天
     * targetOs 	String	否	目标客户端，默认所有客户端，jsonArray，格式：["ios","aos","pc","web","mac"]
     * @param BroadcastMessage $message
     * @return BroadcastResponse
     */
    public function broadcast_message(BroadcastMessage $message): BroadcastResponse
    {
        $data['body'] = $message->toString();
        $data['from'] = $message->from;
        $data['isOffline'] = $message->isOffline;
        $data['ttl'] = $message->ttl;
        $data['targetOs'] = $message->targetOs;
        $this->httpRequest->set_url(NEConstants::broadcast);
        $this->httpRequest->set_data($data);
        return new BroadcastResponse(function(){
            return $this->httpRequest->https_post();
        });
    }
}