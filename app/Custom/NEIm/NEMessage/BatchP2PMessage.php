<?php
namespace App\Custom\NEIm\NEMessage;

class BatchP2PMessage{
    
    private $message;
    
    public $from;
    
    public $to;
    
    public function __construct(AbstractNEMessage $message)
    {
        $this->message = $message;
    }
    
    public function get_body():string
    {
        return $this->message->toString();
    }
    
    public function get_type():int
    {
        return $this->message->getType();
    }
    
    public function get_tos():string
    {
        return json_encode($this->to);
    }
    
    public function get_options():string
    {
        return json_encode($this->get_options());
    }
}