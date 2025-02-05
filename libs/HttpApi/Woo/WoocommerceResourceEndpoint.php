<?php

namespace Codexdelta\Libs\HttpApi\Woo;

enum WoocommerceResourceEndpoint
{
    case PRODUCTS;
    case RETRIEVE_PRODUCT;
    case UPDATE_PRODUCTS;
    case ORDERS;

    public function value(): string
    {
        return env("APP_URL") . match ($this) {
            WoocommerceResourceEndpoint::PRODUCTS => '/wp-json/wc/v3/products',
            WoocommerceResourceEndpoint::RETRIEVE_PRODUCT => '/wp-json/wc/v3/products/{PRODUCT-ID}',
            WoocommerceResourceEndpoint::UPDATE_PRODUCTS => '/wp-json/wc/v3/products/{PRODUCT-ID}',
            WoocommerceResourceEndpoint::ORDERS => '/wp-json/wc/v3/orders',
        };
    }

    public function httpMethod(): string
    {
        return match ($this) {
            WoocommerceResourceEndpoint::PRODUCTS => 'GET',
            WoocommerceResourceEndpoint::RETRIEVE_PRODUCT => 'GET',
            WoocommerceResourceEndpoint::UPDATE_PRODUCTS => 'PUT',
            WoocommerceResourceEndpoint::ORDERS => 'GET'
        };
    }

    /**
     * @return bool
     */
    public function isEndpointWithBody(): bool
    {
        return $this === WoocommerceResourceEndpoint::UPDATE_PRODUCTS;
    }
} 