<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\NetworkFeedbackRequest;
use App\Models\Feedback;
use App\Http\Requests\StoreFeedbackRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;

class FeedbackController extends BaseController
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreFeedbackRequest $request)
    {
        $content = strval($request->input('content' , ''));
        $image = strval($request->input('image' , ''));
        $feedback = new Feedback();
        $feedback->create(array('content'=>$content , 'image'=>$image , 'user_id'=>intval(auth()->id())));
        return $this->response->created();
    }

    /**
     * @param NetworkFeedbackRequest $request
     * @return \Dingo\Api\Http\Response
     * 用户提交网络状态
     */
    public function network(NetworkFeedbackRequest $request)
    {
        $params = $request->only([
            'app_code',
            'app_name',
            'app_version',
            'system_type',
            'system_version',
            'carriname',
            'iso_country_code',
            'mobile_country_code',
            'mobile_network_code',
            'domain',
            'networking',
            'network_type',
            'local_ip',
            'local_gateway',
            'local_dns',
            'remote_domain',
            'dns_result',
            'tcp_connect_test',
            'ping'
        ]);

        if (auth()->check()) {
            $user = auth()->user();
            $params['user_id'] = $user->user_id;
        }
        foreach ($params as $key=>$param) {
            $params[$key] = trim(trim($param), ',');
        }
        $agent = new Agent();
        $params['device_id']   = $agent->getHttpHeader('DeviceId');
        $params['app_version'] = $agent->getHttpHeader('HellooVersion');
        $params['ping']        = !empty($params['ping']) ? $params['ping'] : '';
        $params['real_ip']     = getRequestIpAddress();
        $params['time']        = date("Y-m-d");
        $params['created_at']  = date("Y-m-d H:i:s");

        DB::table('network_logs')->insert($params);
        return $this->response->accepted();
    }

}
