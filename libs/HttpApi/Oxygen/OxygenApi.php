<?php

namespace Codexdelta\Libs\HttpApi\Oxygen;

use Codexdelta\Libs\HttpApi\HTTPClient;
use Codexdelta\Libs\HttpApi\HttpHeadersEnum;
use Codexdelta\Libs\HttpApi\Request;
use Codexdelta\Libs\HttpApi\Response;
use Exception;

class OxygenApi
{

    protected function __construct(
        private readonly string $token
    ){}

    public static function init(): self
    {
        return new self(env('OXYGEN_API_TOKEN'));
    }

    /**
     * @param string $endpoint
     * @param array $headers
     * @return Request
     * @throws Exception
     */
    private function initRequestForEndpointWithHeaders(string $endpoint, array $headers): Request
    {
        return HTTPClient::createRequestWithHeaders($endpoint, $headers);
    }

    /**
     * @return Response
     * @throws Exception
     */
    public function getProducts(int $page=1)
    {
        $endpoint = OxygenApiResourceEndpoint::PRODUCTS->value() . '?page=' . $page;

        $headers = [
            new HttpHeadersEnum(
                HttpHeadersEnum::HEADER_KEY_AUTHORIZATION,
                'Bearer ' . $this->token
            ),
            new HttpHeadersEnum(
                HttpHeadersEnum::HEADER_KEY_CONTENT_TYPE,
                HttpHeadersEnum::HEADER_VALUE_CONTENT_TYPE_JSON
            )
        ];

        $request = $this->initRequestForEndpointWithHeaders($endpoint, $headers);

        return $request->get();
    }
}