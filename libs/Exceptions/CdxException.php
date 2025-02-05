<?php

namespace Codexdelta\Libs\Exceptions;

use Exception;
use ReflectionMethod;

abstract class CdxException extends Exception
{
    public function __construct(private ExceptionCase $exceptionCase, $message = null)
    {
        $exception = $exceptionCase->fetch($message);

        parent::__construct($exception->getMessage(), $exception->getHttpCode());
    }

    public function withMessage(string $message)
    {
        return new static($this->exceptionCase, $message);
    }

    public static function fire(ExceptionCase $exceptionCase = null): static
    {
        $r = new ReflectionMethod(static::class, '__construct');
        $params = $r->getParameters();

        if (count($params) > 0 && null === $exceptionCase) {
            throw new Exception('Exception case parameter is missing for exception ' . static::class);
        }

        if (count($params) > 0) {
            return new static($exceptionCase);
        }

        return new static();
    }
}