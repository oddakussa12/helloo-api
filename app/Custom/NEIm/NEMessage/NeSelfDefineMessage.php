<?php
namespace App\Custom\NEIm\NEMessage;

class NeSelfDefineMessage extends AbstractNEMessage
{

    public $body = [];

    public $type = 0;
    
    public function toString():string
    {
        return json_encode($this->body);
    }
    
    public function getType():int
    {
        return $this->type;
    }
    
    public function __set($name, $value) {
        $this->body[$name] = $value;
    }

}

