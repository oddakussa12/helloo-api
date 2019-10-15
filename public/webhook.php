<?php


error_reporting(E_ALL);
//需要自动部署的项目目录
$dir =  "..";
$cmd = <<<DOC
    cd $dir && git pull origin master && composer update  2>&1
DOC;

//'2>&1'是让执行管道输出结果。
$log = shell_exec($cmd);
file_put_contents('git.log' , 'date:'.date('Y-m-d H:i:s').'  log:'.$log.PHP_EOL , FILE_APPEND);
echo json_encode(array('log'=>$log));die;