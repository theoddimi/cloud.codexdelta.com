<?php

namespace Codexdelta\Libs\Exceptions;

class ExceptionDto
{
    /**
     * @param string $message
     * @param int $httpCode
     */
    protected function __construct(protected string $message, protected int $httpCode)
    {}

    public static function new(string $message, int $httpCode): ExceptionDto
    {
        return new self($message, $httpCode);
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }
}