<?php

namespace App\Http\Controllers\V1;


use JWTAuth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use Aws\CognitoIdentity\CognitoIdentityClient;
use Aws\Sts\StsClient;


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

}
