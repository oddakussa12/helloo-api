<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Validation\Rule;
use App\Models\RyChat as RyChats;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RyChat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $rule = [
            'fromUserId' => [
                'required',
            ],
            'toUserId' => [
                'required',
            ],
            'objectName' => [
                'required',
                Rule::in(['RC:TxtMsg', 'RC:VcMsg', 'RC:HQVCMsg', 'RC:ImgMsg', 'RC:GIFMsg', 'RC:ImgTextMsg', 'RC:FileMsg', 'RC:LBSMsg', 'RC:SightMsg', 'RC:CombineMsg', 'RC:PSImgTxtMsg', 'RC:PSMultiImgTxtMsg']),
            ],
            'content' => [
                'required',
            ],
            'channelType' => [
                'required',
                Rule::in(['PRIVATE', 'GROUP', 'CHATROOM', 'CUSTOMER_SERVICE', 'SYSTEM', 'APP_PUBLIC_SERVICE', 'PUBLIC_SERVICE']),
            ],
            'msgTimestamp' => [
                'required'
            ],
            'msgUID' => [
                'required',
            ],
            'sensitiveType' => [
                'required',
                Rule::in([0 , 1 , 2])
            ],
            'source' => [
                'filled',
            ],
            'groupUserIds' => [
                'filled',
            ],
        ];
        $validator = \Validator::make($this->data, $rule);
        if ($validator->fails()) {
            \Log::error(\json_encode($validator->errors()));
            \Log::error(\json_encode($this->data));
        }else{
            \Log::info(\json_encode($this->data));
        }

    }
}
