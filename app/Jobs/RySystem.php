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


    public function __construct($senderId, $targetId, $objectName, $content, $userAgent='')
    {
        $user = $content['userInfo'] ?? [];
        if (!empty($user)) {
            $user = gettype($user) == 'array' ? (object)$user : $user;
            $extra['extra'] = [
                'un' => !empty($user->user_nick_name) ? $user->user_nick_name : ($user->user_name ?? ''),
                'ua' => userCover($user->user_avatar ?? ''),
                'ui' => $user->user_id,
                'ug' => $user->user_gender,
                'devicePlatformName' => userAgentMobile($userAgent) ?? '',
            ];
            $content['userInfo'] = [];
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
            'content'    => $this->content
        ));

    }
}
