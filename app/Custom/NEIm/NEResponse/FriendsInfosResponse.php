<?php
namespace App\Custom\NEIm\NEResponse;

use App\Custom\NEIm\NetEaseImResponse;

final class FriendsInfosResponse extends NetEaseImResponse
{
    
    private $friends = [];
    
    private $size = 0;
    
    public function __construct(callable $reqeust) {
        parent::__construct($reqeust);
        if (!empty($this->response_array['friends'])) $this->friends = $this->response_array['uinfos'];
        if (!empty($this->response_array['size'])) $this->size = $this->response_array['size'];
    }

    /**
     * @override
     * @return array
     */
    public function get_data():array
    {
        return $this->friends;
    }
    
    public function get_size():int
    {
        return $this->size;
    }
}