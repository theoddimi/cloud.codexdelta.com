<?php

namespace Codexdelta\Libs\Exceptions;


class Handler
{
    public function __construct()
    {
        set_exception_handler(array($this, 'handler'));
    }

    public static function setup()
    {
        new self();
    }

    public function handler(\Throwable $exception)
    {
        http_response_code($exception->getCode());

        match (get_class($exception)) {
            FourOhFourException::class => view('404.twig')->sendContent(),
            InvalidArgumentException::class,
            MissingEnvironmentVariableException::class =>
            view('error.twig', ['error' => $exception->getMessage()])->sendContent(),
            default => throw $exception
        };
    }
}