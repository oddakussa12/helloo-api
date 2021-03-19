<?php

namespace App\Http\Controllers\V1;


use JWTAuth;
use Aws\S3\S3Client;
use GuzzleHttp\Client;
use Aws\Sts\StsClient;
use Aws\S3\PostObjectV4;
use Illuminate\Http\Request;
use Godruoyi\Snowflake\Snowflake;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Aws\CognitoIdentity\CognitoIdentityClient;


class AwsController extends BaseController
{

    public function identityToken(Request $request)
    {
        $id = strval(auth()->id());
        return $this->response->array($this->getIdentityToken($id));
    }

    public function sts()
    {
        $id = strval(auth()->id());
        $identityToken = $this->getIdentityToken($id);
        $client = new StsClient(config('aws-test.Sts'));
        $roleToAssumeArn = 'arn:aws:iam::707690420866:role/Cognito_HellooAuth_Role';
        $token = $identityToken['Token'];
        $result = $client->assumeRoleWithWebIdentity([
            'DurationSeconds'=>1200,
            'RoleArn' => $roleToAssumeArn,
            'RoleSessionName' => 'Helloo',
            'WebIdentityToken'=> $token,
        ]);
        dump($token);
        dd($result);
    }


    public function getIdentityToken($userId)
    {
        $key = "helloo:account:service:account:aws-identity-token:".$userId;
        if(Redis::exists($key))
        {
            $data = json_decode(Redis::get($key) , true);
        }else{
            $identity_pool_id = 'ap-southeast-1:9277f3ad-7aff-460f-b222-59f1e6e46595';
            if(in_array(domain() , config('common.online_domain')))
            {
                $aws = app('aws');
                $identityTokenClient = $aws->createCognitoIdentity();
            }else{
                $config = config('aws-test.CognitoIdentity');
                $identityTokenClient = CognitoIdentityClient::factory($config);
            }
            $identityToken = $identityTokenClient->getOpenIdTokenForDeveloperIdentity(array('IdentityPoolId' => $identity_pool_id, 'Logins' => array('login.helloo.com' => $userId) , 'TokenDuration'=>60));
            $identityToken->offsetUnset("@metadata");
            $data = $identityToken->toArray();
            Redis::set($key , \json_encode($data));
            Redis::expire($key , 600);
        }
        return $data;
    }

    public function preSignedUrl(string $key)
    {
//        $aws = app('aws');
//        $s3 = $aws->createS3();
//        $bucket = 'helloo-media';
//        $cmd = $s3->getCommand('PutObject', [
//            'ACL' => 'private',
//            'Bucket' => $bucket,
//            'Key' => $key,
//            'ContentType' => 'application/x-www-form-urlencoded',
//            'Policy' => $policy,
//        ]);
//        $preSignedRequest = $s3->createPresignedRequest($cmd, '+5 minutes');
//        return $this->response->created(null , array('preSignedUrl'=>(string)$preSignedRequest->getUri()));
    }

    public function form($type)
    {
        $name = request()->input('file' , '');
        $country = request()->input('country' , 'overseas');
        if($country=='cn'&&in_array(domain() , config('common.online_domain')))
        {
            return $this->response->array($this->cnForm($type));
            $http = new Client;
            $response = $http->get("http://test.api.helloo.mantouhealth.com/api/aws/{$type}/form", [
                    'query' => [
                            'file' => $name
                    ],
                ]
            );
            $this->cnForm($type);
            return $this->response->array(json_decode( $response->getBody(), true));
        }
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $filename = pathinfo($name, PATHINFO_FILENAME);
//        $ip = getRequestIpAddress();
//        $code = strtolower(geoip(getRequestIpAddress())->iso_code);
        switch ($extension)
        {
            case "tif":
            case "tiff":
                $contentType = "image/tiff";
                break;
            case "fax":
                break;
            case "gif":
                $contentType = "image/gif";
                break;
            case "ico":
                $contentType = "image/x-icon";
                break;
            case "jpe":
            case "jpeg":
            case "jfif":
            case "jpg":
                $contentType = "image/jpeg";
                break;
            case "png":
                $contentType = "image/png";
                break;
            case "wbmp":
                $contentType = "image/vnd.wap.wbmp";
                break;
            //video
            case "mp4":
                $contentType = "video/mp4";
                break;
            case "avi":
                $contentType = "video/avi";
                break;
            case "mpeg":
                $contentType = "video/mpg";
                break;
            case "wmv":
                $contentType = "video/x-ms-wmv";
                break;
            default:
                $contentType = "";
        }
        $aws = app('aws');
        $s3 = $aws->createS3();
        $dir = empty(auth()->id())?'other':md5(auth()->id());
        $path = $dir.'/'.date('Ymd').'/';
        if($type=='video')
        {
            $bucket = 'helloo-video';
            $expires = '+5 minutes';
            if($country=='overseas'&&in_array(domain() , config('common.online_domain')))
            {
                $xAmzDomain = 'https://video.helloo.mantouhealth.com/';
                $action = "https://helloo-video.s3-accelerate.amazonaws.com/";
//                $action = "https://helloo-video.s3.amazonaws.com/";
            }else{
                $xAmzDomain = 'https://test.video.helloo.mantouhealth.com/';
                $action = "https://helloo-video.s3.cn-north-1.amazonaws.com.cn/";
            }
            $contentLengthRange = 1024*1024*50;
        }else if($type=='image'){
            $bucket = 'helloo-image';
            $expires = '+5 minutes';
            if($country=='overseas'&&in_array(domain() , config('common.online_domain')))
            {
                $xAmzDomain = 'https://image.helloo.mantouhealth.com/';
                $action = "https://helloo-image.s3-accelerate.amazonaws.com/";
//                $action = "https://helloo-image.s3.amazonaws.com/";
            }else{
                $xAmzDomain = 'https://test.image.helloo.mantouhealth.com/';
                $action = "https://helloo-image.s3.cn-north-1.amazonaws.com.cn/";
            }
            $contentLengthRange = 1024*1024*10;
        }else if($type=='avatar'){
            $bucket = 'helloo-avatar';
            $expires = '+5 minutes';
            if($country=='overseas'&&in_array(domain() , config('common.online_domain')))
            {
                $xAmzDomain = 'https://avatar.helloo.mantouhealth.com/';
                $action = "https://helloo-avatar.s3-accelerate.amazonaws.com/";
//                $action = "https://helloo-avatar.s3.amazonaws.com/";
            }else{
                $xAmzDomain = 'https://test.avatar.helloo.mantouhealth.com/';
                $action = "https://helloo-avatar.s3.cn-north-1.amazonaws.com.cn/";
            }
            $contentLengthRange = 1024*1024*10;
        }else{
            return $this->response->noContent();
        }
        $filename = mb_strlen($filename)==32?$filename:app('snowflake')->id();
        $key = blank($extension)?$path.$filename:$path.$filename.'.'.$extension;
        $formInputs = [
            'acl' => 'private' ,
            'key' => $key ,
            'x-amz-domain'=>$xAmzDomain ,
            'success_action_status'=>'201'
        ];
        $options = [
            ['acl' => 'private'],
            ['success_action_status'=>"201"],
            ['bucket' => $bucket],
            ['content-length-range', 1, $contentLengthRange], // 8 KiB
            ['x-amz-domain'=>$xAmzDomain], // 8 KiB
            ['starts-with', '$key', $path],
        ];
        !blank($contentType)&&$formInputs['Content-Type'] = $contentType;
        !blank($contentType)&&array_push($options , ['Content-Type'=>$contentType]);
        $postObject = new PostObjectV4(
            $s3,
            $bucket,
            $formInputs,
            $options,
            $expires
        );
        $formInputs = $postObject->getFormInputs();
        return $this->response->array(array(
            'form'=>$formInputs,
            'action'=>$action,
            'domain'=>$xAmzDomain,
        ));
    }

    private function cnForm($type)
    {
        $name = request()->input('file' , '');
        $country = request()->input('country' , 'overseas');
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $filename = pathinfo($name, PATHINFO_FILENAME);
//        $ip = getRequestIpAddress();
//        $code = strtolower(geoip(getRequestIpAddress())->iso_code);
        switch ($extension)
        {
            case "tif":
            case "tiff":
                $contentType = "image/tiff";
                break;
            case "fax":
                break;
            case "gif":
                $contentType = "image/gif";
                break;
            case "ico":
                $contentType = "image/x-icon";
                break;
            case "jpe":
            case "jpeg":
            case "jfif":
            case "jpg":
                $contentType = "image/jpeg";
                break;
            case "png":
                $contentType = "image/png";
                break;
            case "wbmp":
                $contentType = "image/vnd.wap.wbmp";
                break;
            //video
            case "mp4":
                $contentType = "video/mp4";
                break;
            case "avi":
                $contentType = "video/avi";
                break;
            case "mpeg":
                $contentType = "video/mpg";
                break;
            case "wmv":
                $contentType = "video/x-ms-wmv";
                break;
            default:
                $contentType = "";
        }
        $config = config('aws.S3CN');
        $s3 = new S3Client($config);
        $dir = empty(auth()->id())?'other':md5(auth()->id());
        $path = $dir.'/'.date('Ymd').'/';
        if($type=='video')
        {
            $bucket = 'helloo-video';
            $expires = '+5 minutes';
            if($country=='overseas'&&in_array(domain() , config('common.online_domain')))
            {
                $xAmzDomain = 'https://video.helloo.mantouhealth.com/';
                $action = "https://helloo-video.s3-accelerate.amazonaws.com/";
//                $action = "https://helloo-video.s3.amazonaws.com/";
            }else{
                $xAmzDomain = 'https://test.video.helloo.mantouhealth.com/';
                $action = "https://helloo-video.s3.cn-north-1.amazonaws.com.cn/";
            }
            $contentLengthRange = 1024*1024*50;
        }else if($type=='image'){
            $bucket = 'helloo-image';
            $expires = '+5 minutes';
            if($country=='overseas'&&in_array(domain() , config('common.online_domain')))
            {
                $xAmzDomain = 'https://image.helloo.mantouhealth.com/';
                $action = "https://helloo-image.s3-accelerate.amazonaws.com/";
//                $action = "https://helloo-image.s3.amazonaws.com/";
            }else{
                $xAmzDomain = 'https://test.image.helloo.mantouhealth.com/';
                $action = "https://helloo-image.s3.cn-north-1.amazonaws.com.cn/";
            }
            $contentLengthRange = 1024*1024*10;
        }else if($type=='avatar'){
            $bucket = 'helloo-avatar';
            $expires = '+5 minutes';
            if($country=='overseas'&&in_array(domain() , config('common.online_domain')))
            {
                $xAmzDomain = 'https://avatar.helloo.mantouhealth.com/';
                $action = "https://helloo-avatar.s3-accelerate.amazonaws.com/";
//                $action = "https://helloo-avatar.s3.amazonaws.com/";
            }else{
                $xAmzDomain = 'https://test.avatar.helloo.mantouhealth.com/';
                $action = "https://helloo-avatar.s3.cn-north-1.amazonaws.com.cn/";
            }
            $contentLengthRange = 1024*1024*10;
        }else{
            return $this->response->noContent();
        }
        $filename = mb_strlen($filename)==32?$filename:app('snowflake')->id();
        $key = blank($extension)?$path.$filename:$path.$filename.'.'.$extension;
        $formInputs = [
            'acl' => 'private' ,
            'key' => $key ,
            'x-amz-domain'=>$xAmzDomain ,
            'success_action_status'=>'201'
        ];
        $options = [
            ['acl' => 'private'],
            ['success_action_status'=>"201"],
            ['bucket' => $bucket],
            ['content-length-range', 1, $contentLengthRange], // 8 KiB
            ['x-amz-domain'=>$xAmzDomain], // 8 KiB
            ['starts-with', '$key', $path],
        ];
        !blank($contentType)&&$formInputs['Content-Type'] = $contentType;
        !blank($contentType)&&array_push($options , ['Content-Type'=>$contentType]);
        $postObject = new PostObjectV4(
            $s3,
            $bucket,
            $formInputs,
            $options,
            $expires
        );
        $formInputs = $postObject->getFormInputs();
        return array(
            'form'=>$formInputs,
            'action'=>$action,
            'domain'=>$xAmzDomain,
        );
    }

}
