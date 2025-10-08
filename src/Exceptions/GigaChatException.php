<?php

namespace Tigusigalpa\GigaChat\Exceptions;

class GigaChatException extends \Exception
{
    /**
     * Create a new GigaChat exception
     * 
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}