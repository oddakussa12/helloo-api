<?php

namespace App\Jobs;

use Carbon\Carbon;
use Jenssegers\Agent\Agent;
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


    /**
     * @var array
     */
    private $param;
    private $message;
    /**
     * @var string
     */
    private $ip;
    /**
     * @var string
     */
    private $createdAt;

    /**
     * @var string
     */
    private $route;
    /**
     * @var string|null
     */
    private $version;

    public function __construct($message)
    {
        $this->param = request()->all();
        $this->message = $message;
        $this->ip = getRequestIpAddress();
        $this->createdAt = Carbon::now()->toDateTimeString();
        $this->route = request()->route()->getName();
        $this->version = strval((new Agent())->getHttpHeader('HellooVersion'));
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
                'version'=>$this->version,
                'param'=>\json_encode($this->param , JSON_UNESCAPED_UNICODE),
                'message'=>$message,
                'ip'=>$this->ip,
                'created_at'=>$this->createdAt,
            )
        );
    }

}
