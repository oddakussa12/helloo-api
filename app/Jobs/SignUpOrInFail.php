<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Godruoyi\Snowflake\Snowflake;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SignUpOrInFail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $param;
    private $message;
    private $ip;
    private $createdAt;
    /**
     * @var string
     */
    private $route;

    public function __construct($message)
    {
        $this->param = request()->all();
        $this->message = $message;
        $this->ip = getRequestIpAddress();
        $this->createdAt = Carbon::now()->toDateTimeString();
        $this->route = request()->route()->getName();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $message = is_string($this->message)?$this->message:\json_encode($this->message , JSON_UNESCAPED_UNICODE);
        DB::table('auth_failed_logs')->insert(
            array(
                'id'=>(new Snowflake)->id(),
                'route'=>$this->route,
                'param'=>\json_encode($this->param , JSON_UNESCAPED_UNICODE),
                'message'=>$message,
                'ip'=>$this->ip,
                'created_at'=>$this->createdAt,
            )
        );
    }

}
