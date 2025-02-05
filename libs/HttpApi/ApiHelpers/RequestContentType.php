<?php

namespace Codexdelta\Libs\HttpApi\ApiHelpers;

enum RequestContentType
{
    case APPLICATION_JSON;
    case WWW_FORM_URLENCODED;

    public function value(): string
    {
        return match($this) {
            RequestContentType::APPLICATION_JSON => 'application/json',
            RequestContentType::WWW_FORM_URLENCODED => 'application/x-www-form-urlencoded'
        };
    }
} 