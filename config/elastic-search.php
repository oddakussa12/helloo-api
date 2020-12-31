<?php


return [
    'host'=>env('ELASTIC_SEARCH_HOST' , 'search-search-c2oeqlpjcbllgklscmsc4ehati.cn-north-1.es.amazonaws.com.cn'),
    'port'=>env('ELASTIC_SEARCH_PORT' , 443),
    'scheme'=>env('ELASTIC_SEARCH_SCHEME' , 'https'),
    'user' => env('ELASTIC_SEARCH_USER' , ''),
    'pass' => env('ELASTIC_SEARCH_PASS' , '')
];