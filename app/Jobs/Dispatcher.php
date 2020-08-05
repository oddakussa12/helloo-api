<?php


namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class Dispatcher implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $params;
    private $method;
    private $uri;

    public function __construct($uri , $method='post'  , $params=array())
    {
        $this->params = $params;
        $this->method = $method;
        $this->uri = $uri;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $dispatcher = app('Dingo\Api\Dispatcher');
        try{
            $dispatcher->with($this->params)->{$this->method}($this->uri);
        }catch (\Exception $e)
        {
            \Log::error("dispatcher uri:".$this->uri." error:".\json_encode($e->getMessage() , JSON_UNESCAPED_UNICODE));
        }
    }
}