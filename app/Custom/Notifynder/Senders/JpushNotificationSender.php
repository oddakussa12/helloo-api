<?php
namespace App\Custom\Notifynder\Senders;

use App\Jobs\Jpush;
use App\Custom\Notifynder\Models\Notification;
use App\Services\JpushService;
use App\Services\NPushService;
use Fenos\Notifynder\Contracts\SenderContract;
use Fenos\Notifynder\Contracts\SenderManagerContract;
use Illuminate\Support\Facades\DB;

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
        $from = $this->build->getFrom();
        $notification = $sender->send($this->notification);
        $this->sendJpush($notification , $from);
    }

    /**
     * Send an email notification.
     *
     * @param Notification $notification
     * @param null $from
     * @return void
     */
    public function sendJpush(Notification $notification , $from=null) {
        $to_id = $notification->to_id;
        $category = $notification->category;
        $type = '';
        switch ($category->name)
        {
            case 'user.like':
            case 'user.post_like':
                    $type = 'like';
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
            $user_name = empty($from->user_name)?'some one':$from->user_name;

            //$device = DB::table('devices')->where('user_id', $to_id)->orderBy('device_updated_at', 'desc')->first();
            //if(empty($device)) return false;

             //NpushService::commonPush($device, $user_name, $to_id, $type);


            Jpush::dispatch($type , $user_name , $to_id)->onQueue('op_npush');
        }
    }
}