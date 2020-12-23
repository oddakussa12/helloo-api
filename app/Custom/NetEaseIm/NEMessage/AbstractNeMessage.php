<?php
namespace App\Custom\NetEaseIm\NEMessage;

use App\Custom\NetEaseIm\NEMessage\Contracts\NeMessage as NEMessageInterface;

class AbstractNeMessage implements NEMessageInterface
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
        // TODO: Implement toString() method.
    }

    public function setFrom($from): self
    {
        $this->from = $from;
        return $this;
    }

    public function setOpe($ope): self
    {
        $this->ope=$ope;
        return $this;
    }

    public function setTo($to): self
    {
        $this->to = $to;
        return $this;
    }

    public function setType($type): self
    {
        $this->type = $type;
        return $this;
    }

    public function setBody($body): self
    {
        $this->body = $body;
        return $this;
    }

    public function __get(string $name)
    {
        return $this->$name;
    }

}