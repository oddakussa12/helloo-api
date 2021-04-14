<?php

namespace App\Console\Commands;


use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema as SchemaAs;


class Schema extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schema:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schema start';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $index = Carbon::now()->addMonths(1)->format("Ym");
        if(!SchemaAs::hasTable('ry_chats_'.$index))
        {
            SchemaAs::create('ry_chats_'.$index, function (Blueprint $table) {
                $table->increments('chat_id')->unsigned()->comment('主键');
                $table->string('chat_msg_uid' , 64)->charset('utf8')->comment('融云消息ID');
                $table->integer('chat_from_id' , false , true)->comment('消息发送者ID');
                $table->integer('chat_to_id' , false , true)->comment('消息接收者ID');
                $table->string('chat_msg_type' , 128)->charset('utf8')->comment('消息类型');
                $table->string('chat_time' , 32)->charset('utf8')->comment('消息创建时间');
                $table->string('chat_source' , 64)->charset('utf8')->comment('消息来源');
                $table->tinyInteger('chat_extend' , false , true)->default(1)->comment('消息扩展');
                $table->dateTime('chat_created_at')->comment('日期');
                $table->index(array('chat_msg_type', 'chat_from_id', 'chat_to_id', 'chat_time') , 'chat_depth');
            });
        }

        if(!SchemaAs::hasTable('ry_messages_'.$index))
        {
            SchemaAs::create('ry_messages_'.$index, function (Blueprint $table) {
                $table->increments('id')->unsigned()->comment('主键');
                $table->string('message_id' , 64)->charset('utf8')->comment('融云消息ID');
                $table->text('message_content')->comment('融云消息内容');
                $table->string('message_type' , 32)->charset('utf8')->comment('融云消息类型');
                $table->string('message_time' , 32)->charset('utf8')->comment('融云消息日期');
                $table->dateTime('created_at')->comment('日期');
            });
        }

        if(!SchemaAs::hasTable('ry_video_messages_'.$index))
        {
            SchemaAs::create('ry_video_messages_'.$index, function (Blueprint $table) {
                $table->increments('id')->unsigned()->comment('主键');
                $table->string('message_id' , 64)->charset('utf8')->comment('融云消息ID');
                $table->string('video_url' , 128)->charset('utf8')->comment('视频地址');
                $table->tinyInteger('is_record')->default(0)->comment('是否为录制');
                $table->string('voice_name' , 32)->charset('utf8')->comment('变声类型');
                $table->string('bundle_name' , 64)->charset('utf8mb4')->comment('bundle名字');
                $table->dateTime('created_at')->comment('日期');
            });
        }

        if(!SchemaAs::hasTable('ry_audio_messages_'.$index))
        {
            SchemaAs::create('ry_video_messages_'.$index, function (Blueprint $table) {
                $table->increments('id')->unsigned()->comment('主键');
                $table->string('message_id' , 64)->charset('utf8')->comment('融云消息ID');
                $table->string('audio_url' , 128)->charset('utf8')->comment('语音地址');
                $table->tinyInteger('duration')->default(0)->comment('语音时长');
                $table->string('voice_name' , 32)->charset('utf8')->comment('变声类型');
                $table->dateTime('created_at')->comment('日期');
            });
        }

        if(!SchemaAs::hasTable('visit_logs_'.$index))
        {
            SchemaAs::create('visit_logs_'.$index, function (Blueprint $table) {
                $table->increments('id')->unsigned()->comment('主键');
                $table->string('user_id' , 32)->charset('utf8')->comment('用户ID');
                $table->string('referer' , 64)->charset('utf8')->comment('来源');
                $table->string('ip' , 128)->charset('utf8')->comment('IP');
                $table->string('version' , 32)->charset('utf8')->default('0')->comment('版本');
                $table->string('route' , 64)->charset('utf8')->default('')->comment('路由');
                $table->string('device_id' , 64)->charset('utf8')->default('')->comment('设备ID');
                $table->integer('visited_at' ,  false , true)->comment('访问时间');
                $table->date('created_at')->comment('创建时间');
            });
        }

        if(!SchemaAs::hasTable('dau_'.$index))
        {
            SchemaAs::create('dau_'.$index, function (Blueprint $table) {
                $table->increments('id')->unsigned()->comment('主键');
                $table->string('user_id' , 32)->charset('utf8')->comment('用户ID');
                $table->string('country' , 8)->charset('utf8')->comment('国家');
                $table->date('date')->comment('日期');
            });
        }

    }

}
