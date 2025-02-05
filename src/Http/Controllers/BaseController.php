<?php

namespace Codexdelta\App\Http\Controllers;

use Codexdelta\Libs\Http\CdxRequest;

class BaseController
{
    protected CdxRequest $request;
    public function __construct(...$args)
    {
        foreach ($args as $arg) {
            if ($arg instanceof CdxRequest) {
                $this->request = $arg;
            }
        }
    }
}