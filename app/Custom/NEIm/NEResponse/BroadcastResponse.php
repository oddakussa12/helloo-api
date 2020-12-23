<?php
namespace App\Custom\NEIm\NeResponse;

use App\Custom\NEIm\NetEaseImResponse;

final class BroadcastResponse extends NetEaseImResponse
{
    
    private $msg = [];
    
    private $size = 0;
    
    public function __construct(callable $reqeust) {
        parent::__construct($reqeust);
        if (!empty($this->response_array['msg'])) $this->msg = $this->response_array['msg'];
    }
    
    /**
     * @override
     * @return type
     */
    public function get_data():array {
        return $this->msg;
    }
}