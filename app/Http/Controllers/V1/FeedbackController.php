<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\Request;
use App\Models\Feedback;
use App\Http\Requests\StoreFeedbackRequest;
use Illuminate\Support\Facades\Log;

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
        $image = $request->input('image' , '');
        $feedback = new Feedback();
        $feedback->create(array('content'=>$content , 'image'=>$image , 'user_id'=>intval(auth()->id())));
        return $this->response->created();
    }

    public function network(Request $request)
    {
        $params = $request->all();
       // dump($params);
        Log::info('传入参数', json_encode($params, true));
        return $this->response->accepted();

    }

}
