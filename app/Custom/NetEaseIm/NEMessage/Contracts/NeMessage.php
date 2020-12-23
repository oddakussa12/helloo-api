<?php
namespace App\Custom\NetEaseIm\NEMessage\Contracts;

interface NeMessage
{
    public function setFrom($from):self;

    public function setOpe($ope):self;

    public function setTo($to):self;

    public function setType($type):self;

    public function setBody($body):self;

    public function toString():string;
}