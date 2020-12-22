<?php
namespace App\Custom\NetEaseIm\NEMessage;

interface NEMessage
{
    public function toString():string;
    
    public function getType():int;
}