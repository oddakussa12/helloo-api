<?php
namespace App\Custom\NEIm\NEResponse;

use App\Custom\NEIm\NetEaseImResponse;

/**
 * Class AccidUInfosResponse
 * @package App\Custom\NEIm\NeResponse
 */
final class AccidUInfosResponse extends NetEaseImResponse
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