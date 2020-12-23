<?php
namespace App\Custom\NEIm\NeResponse;

use App\Custom\NEIm\NetEaseImResponse;

final class SingleFileResponse extends NetEaseImResponse
{
    
    private $url = "";
    
    
    public function __construct(callable $reqeust) {
        parent::__construct($reqeust);
        if (!empty($this->response_array['url'])) $this->url = $this->response_array['url'];
    }
    
    /**
     * @override
     * @return type
     */
    public function get_data():array {
        return $this->friends;
    }
    
    public function get_url():string
    {
        return $this->url;
    }
    
}