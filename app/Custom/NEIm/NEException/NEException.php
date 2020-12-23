<?php
namespace App\Custom\NEIm\NEException;

use Illuminate\Support\Facades\Log;

class NEException extends \RuntimeException
{
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null) {
        Log::info('net_ease_error' , array(
            'message'=>$message,
            'code'=>$code,
        ));
        parent::__construct($message, $code, $previous);
    }
}