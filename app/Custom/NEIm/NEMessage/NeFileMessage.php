<?php
namespace App\Custom\NEIm\NEMessage;

final class NeFIleMessage extends AbstractNEMessage
{

    public $body = [];

    public $type = 6;

    /**
     * @return false|string
     */
    public function toString()
    {
        return json_encode($this->body);
    }
    
    public function getType()
    {
        return $this->type;
    }
    
    public function setName(string $name)
    {
        $this->body['name'] = $name;
    }
    
    public function setMd5(string $md5)
    {
        $this->body['md5'] = $md5;
    }
    
    public function setUrl(string $url)
    {
        $this->body['url'] = $url;
    }
    
    public function setExt(string $ext)
    {
        $this->body['ext'] = $ext;
    }
    
    public function setSize(int $size)
    {
        $this->body['size'] = $size;
    }

}

