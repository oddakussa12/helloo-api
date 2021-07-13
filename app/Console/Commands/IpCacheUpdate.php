<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class IpCacheUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ip_cache:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ip Cache Update';

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
        $this->call('geoip:update');
        $this->call('cache:clear');
        $process = new Process(['supervisorctl' , 'restart' , 'helloo_user_sign_up:*']);
        $process->run();
        // 命令行执行结果
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        dump($process->getOutput());
    }



}
