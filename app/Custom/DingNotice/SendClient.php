<?php
/**
 * Created by PhpStorm.
 * User: wangju
 * Date: 2019-05-17
 * Time: 20:37
 */

namespace App\Custom\DingNotice;


interface SendClient
{
    public function send($params): array;
}
