<?php


error_reporting(E_ALL);
//需要自动部署的项目目录
$dir =  '/home/wwwroot/test.api.yooul.net';

//'2>&1'是让执行管道输出结果。
echo shell_exec("cd $dir && git pull origin master");