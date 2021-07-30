<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class RemoveSpecialPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:special_price {max?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove Special Price';
    /**
     * @var array|string
     */
    private $max;

    private $_pids = array();

    private $_pidFile = '/home/ec2-user/daemon.pid';

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
     *s
     * @return void
     */
    public function handle()
    {
        $this->_pidFile = storage_path('app/tmp/daemon.pid');
        $max = $this->argument('max');
        $this->max = intval(is_numeric($max)&&$max>0&&$max<3?$max:1);
        if (php_sapi_name() != 'cli') {
            die('Should run in CLI');
        }
        //创建子进程
        $pid = pcntl_fork();
        if ($pid == -1) {
            return 'fork fail';
        } elseif ($pid) {
            //终止父进程
            exit('parent process');
        }
        var_dump('sub process');
        //在子进程中创建新的会话
        if (posix_setsid() === -1) {
            die('Could not detach');
        }
        //改变工作目录
        chdir('/');
        //重设文件创建的掩码
        umask(0);
        $fp = fopen($this->_pidFile, 'w') or die("Can't create pid file");
        //把当前进程的id写入到文件中
        fwrite($fp, posix_getpid());
        fclose($fp);
        //关闭文件描述符
        fclose(STDIN);
        fclose(STDOUT);
        fclose(STDERR);
        $this->_createWorkers();
    }

    /**
     * 守护进程的任务
     */
    private function _createWorkers()
    {
        while (true)
        {
            $this->_runWorker();
            $this->_waitWorker();
            usleep(500);
        }

    }

    private function _runWorker()
    {
        for ($i=0;$i<=$this->max;$i++)
        {
            $key = 'special-'.$i;
            if(array_key_exists($key, $this->_pids)) {
                continue;
            }
            $pid = pcntl_fork();
            if(!$pid) {
                $this->job();
                exit();
            } else {
                $this->_pids[$key] = $pid;
            }
        }
    }

    private function _waitWorker()
    {
        $deadPid = pcntl_waitpid(-1, $status, WNOHANG);
        while ($deadPid > 0) {
            unset($this->_pids[array_search($deadPid, $this->_pids)]);
            $deadPid = pcntl_waitpid(-1, $status, WNOHANG);
        }
    }

    private function job()
    {
        DB::table('special_goods')->where('status' , 1)->orderByDesc('updated_at')->chunk(100 , function ($goods){
            foreach ($goods as $g)
            {
                $keys = $goodsIds = array();
                $key = "helloo:business:goods:service:special:".$g->goods_id;
                $specialG = Redis::hgetall($key);
                if($specialG===null||$specialG===false)
                {
                    array_push($keys , $key);
                    array_push($goodsIds , $g->goods_id);
                }
                if(!empty($keys))
                {
                    Redis::del($keys);
                    DB::table('special_goods')->whereIn('goods_id' , $goodsIds)->update(array(
                        'status'=>1,
                        'updated_at'=>date('Y-m-d H:i:s'),
                    ));
                }
            }
        });
    }

}
