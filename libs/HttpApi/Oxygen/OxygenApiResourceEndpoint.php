<?php

namespace Codexdelta\Libs\HttpApi\Oxygen;

enum OxygenApiResourceEndpoint
{
    case PRODUCTS;

    public function value(): string
    {
        return 'https://api.oxygen.gr/v1/' . match ($this) {
            OxygenApiResourceEndpoint::PRODUCTS => 'products',
        };
    }

    public function httpMethod(): string
    {
        return match ($this) {
            OxygenApiResourceEndpoint::PRODUCTS => 'GET',
        };
    }
}