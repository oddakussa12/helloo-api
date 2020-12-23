<?php
namespace pp\Custom\NEIm\NEException;

class NEParamsCheckException extends NEException
{
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}