<?php

namespace Codexdelta\Libs\Exceptions;


class FourOhFourException extends CdxException
{
    public function __construct()
    {
        parent::__construct(ExceptionCase::RESOURCE_NOT_FOUND_404);
    }

    protected function shouldReport()
    {

    }

    protected function shouldNotReport()
    {

    }

    public function render()
    {

    }
}