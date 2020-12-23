<?php
namespace App\Custom\NEIm\NEMessage;

use App\Custom\NEIm\NEMessage\Contracts\NEMessage as NEMessageInterface;

abstract class AbstractNeMessage implements NEMessageInterface
{
    public $from;
    
    public $ope;
    
    public $to;

    public $type;

    public $body;

    public $options;
    
    public function __construct(MessageOptions $messageOptions=null) {
        $this->options = $messageOptions;
    }
    
    public function getOptions():string
    {
        return $this->options->toString();
    }

    public function toString(): string
    {
        return json_encode($this->body);
    }

    public function setFrom($from)
    {
        $this->from = $from;
        return $this;
    }

    public function setOpe($ope)
    {
        $this->ope=$ope;
        return $this;
    }

    public function setTo($to)
    {
        $this->to = $to;
        return $this;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    public function __get(string $name)
    {
        return $this->$name;
    }

}