<?php

namespace App\Http\Controllers\V1;

use App\Models\Feedback;
use App\Http\Requests\StoreFeedbackRequest;

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

}
