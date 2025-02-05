<?php

namespace Codexdelta\Libs\Exceptions;


class InvalidArgumentException extends CdxException
{
    public function __construct(ExceptionCase $exceptionCase)
    {
        parent::__construct($exceptionCase);
    }
}