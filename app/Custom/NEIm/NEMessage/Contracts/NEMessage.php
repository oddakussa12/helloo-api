<?php
namespace App\Custom\NEIm\NEMessage\Contracts;

interface NEMessage
{
    public function setFrom($from);

    public function setOpe($ope);

    public function setTo($to);

    public function setType($type);

    public function setBody($body);

    public function toString();
}