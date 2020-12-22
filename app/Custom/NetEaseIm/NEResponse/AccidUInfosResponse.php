<?php
namespace App\Custom\NetEaseIm\NeResponse;

use App\Custom\NetEaseIm\NetEaseImResponse;

final class AccifUInfosResponse extends NetEaseImResponse
{
    
    private $uinfos = [];
    
    public function __construct(callable $reqeust) {
        parent::__construct($reqeust);
        if (!empty($this->response_array['uinfos'])) $this->uinfos = $this->response_array['uinfos'];
    }
    
    /**
     * @override
     * @return type
     */
    public function get_data() {
        return $this->uinfos;
    }
}