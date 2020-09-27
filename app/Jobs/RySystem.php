<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Jenssegers\Agent\Agent;

class RySystem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string
     */
    private $senderId;

    /**
     * @var string
     */
    private $targetId;

    /**
     * @var string
     */
    private $content;

    /**
     * @var string
     */
    private $objectName;


    public function __construct($senderId, $targetId, $objectName, $content)
    {
        if (!empty($content['userInfo'])) {
            $extra['extra'] = [
                'un' => $content['userInfo']->user_nick_name ?? ($content['userInfo']->user_name ?? ''),
                'ua' => userCover($content['userInfo']->user_avatar ?? ''),
                'ui' => $content['userInfo']->user_id,
                'uc' => $content['userInfo']->user_country,
                'ul' => $content['userInfo']->user_level,
                'ug' => $content['userInfo']->user_gender,
                'devicePlatformName' => userAgent(new Agent()) ?? '',
            ];
            $content['userInfo'] = [];
            $content['userInfo'] = $extra;
        }

        $content['userInfo'] = $extra ?? [];

        $this->senderId      = $senderId;
        $this->targetId      = $targetId;
        $this->content       = $content;
        $this->objectName    = $objectName;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $result = app('rcloud')->getMessage()->System()->send(array(
            'senderId'   => $this->senderId,
            'targetId'   => $this->targetId,
            "objectName" => $this->objectName,
            'content'    => \json_encode($this->content)
        ));

        dump('System', $result, $result);
    }
}
