<?php
namespace App\Custom\Notifynder\Senders;

use App\Custom\Constant\Constant;
use App\Jobs\Jpush;
use App\Custom\Notifynder\Models\Notification;
use App\Services\JpushService;
use App\Services\NPushService;
use Fenos\Notifynder\Contracts\SenderContract;
use Fenos\Notifynder\Contracts\SenderManagerContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JpushNotificationSender implements SenderContract
{
    /**
     * @var array
     */
    protected $notification;

    protected $build;

    /**
     * Create a new email notification sender instance.
     *
     * @param  array  $build
     */
    public function __construct($build)
    {
        $this->build = $build[0];
        $this->notification = $build[0]->getNotifications();
    }

    /**
     * Send the notification.
     *
     * @param  SenderManagerContract  $sender
     * @return void
     */
    public function send(SenderManagerContract $sender)
    {
        $from         = $this->build->getFrom();
        $notification = $sender->send($this->notification);
        $extra        = !is_array($notification->extra) ? json_decode($notification->extra, true) : $notification->extra;
        $this->sendJpush($notification, $from, $extra);
    }

    /**
     * Send an email notification.
     *
     * @param Notification $notification
     * @param null $from
     * @param array $extra
     * @return void
     */
    public function sendJpush(Notification $notification, $from=null, $extra=[]) {
        $to_id    = $notification->to_id;
        $category = $notification->category;
        $type     = '';
        switch ($category->name)
        {
            case 'user.like':
                $type = 'like';
                break;
            case 'user.post_like':// 帖子点赞
                $type = 'post_like';
                break;
            case 'user.publish.post': // 发帖
                $type = 'publish_post';
                break;
            case 'user.post_comment':
                $type = 'post_comment';
                break;
            case 'user.comment':
                $type = 'comment';
                break;
            case 'user.following':
                $type = 'follow';
                break;
        }
        if(!empty($type))
        {
            $user_name = 'some one';
            if (!empty($from->user_nick_name)) {
                $user_name = $from->user_nick_name;
            } elseif(!empty($from->user_name)) {
                $user_name = $from->user_name;
            }

            Jpush::dispatch($type, $user_name , $to_id, $extra)->onQueue(Constant::QUEUE_PUSH_NAME);
        }
    }
}