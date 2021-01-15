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
        SchemaAs::dropIfExists('ry_chats_'.$index);
        SchemaAs::create('ry_chats_'.$index, function (Blueprint $table) {
            $table->increments('chat_id')->comment('主键');
            $table->string('chat_msg_uid' , 64)->charset('utf8')->comment('融云消息ID');
            $table->integer('chat_from_id' , false , true)->comment('消息发送者ID');
//            $table->string('chat_from_name' , 64)->comment('消息发送者账号');
            $table->integer('chat_to_id' , false , true)->comment('消息接收者ID');
            $table->string('chat_msg_type' , 128)->charset('utf8')->comment('消息类型');
            $table->string('chat_time' , 32)->charset('utf8')->comment('消息创建时间');
            $table->string('chat_source' , 64)->charset('utf8')->comment('消息来源');
            $table->tinyInteger('chat_extend' , false , true)->default(1)->comment('消息扩展');
            $table->dateTime('chat_created_at')->comment('日期');
        });
        SchemaAs::dropIfExists('ry_messages_'.$index);
        SchemaAs::create('ry_messages_'.$index, function (Blueprint $table) {
            $table->increments('id')->comment('主键');
            $table->string('message_id' , 64)->charset('utf8')->comment('融云消息ID');
            $table->text('message_content')->comment('融云消息内容');
            $table->string('message_type' , 32)->charset('utf8')->comment('融云消息类型');
            $table->string('message_time' , 32)->charset('utf8')->comment('融云消息日期');
            $table->dateTime('created_at')->comment('日期');
        });
    }

}