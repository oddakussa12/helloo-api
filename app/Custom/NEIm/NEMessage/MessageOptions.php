<?php
namespace App\Custom\NEIm\NEMessage;

class MessageOptions
{
    public $options = [];
    
    public function toString():string
    {
        return json_encode($this->options);
    }
    
    public function setRoam(bool $option): MessageOptions
    {
        $this->options['roam'] = $option;
        return $this;
    }
    
    public function setHistory(bool $option): MessageOptions
    {
        $this->options['history'] = $option;
        return $this;
    }
    
    public function setSenderSync(bool $option): MessageOptions
    {
        $this->options['sendersync'] = $option;
        return $this;
    }
    
    public function setPush(bool $option): MessageOptions
    {
        $this->options['push'] = $option;
        return $this;
    }
    
    public function setRoute(bool $option): MessageOptions
    {
        $this->options['route'] = $option;
        return $this;
    }
    
    public function setBadge(bool $option): MessageOptions
    {
        $this->options['badge'] = $option;
        return $this;
    }
    
    public function setNeedPushNick(bool $option): MessageOptions
    {
        $this->options['needPushNick'] = $option;
        return $this;
    }
    
    public function setPersistent(bool $option): MessageOptions
    {
        $this->options['persistent'] = $option;
        return $this;
    }
}