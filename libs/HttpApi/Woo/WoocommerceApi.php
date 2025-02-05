<?php

namespace Codexdelta\Libs\HttpApi\Woo;

use Codexdelta\Libs\HttpApi\ApiHelpers\ApiHelpers;
use Codexdelta\Libs\HttpApi\ApiHelpers\RequestContentType;
use Exception;

class WoocommerceApi {

    const TOTAL_PRODUCTS_PER_PAGE = 100;

    /**
     * @param WoocommerceResourceEndpoint $endpoint
     * @param array $requestHeaders
     * @param RequestContentType|null $contentType
     * @param string|null $responseBody
     * @param string|null $responseInfo
     * @param array $responseHeaders
     * @param int|null $responseCode
     * @param string $authorizationHash
     */
    protected function __construct(
        protected WoocommerceResourceEndpoint $endpoint,
        protected array $endpointSegments,
        protected array $endpointQueryParameters,
        protected array $requestHeaders,
        protected ?array $requestBody,
        protected ?RequestContentType $contentType,
        protected ?string $responseBody = null,
        protected ?array $responseInfo = null,
        protected array $responseHeaders = [],
        protected ?int $responseCode = null,
        private string $authorizationHash = ''
    ){
        if (null !== $contentType) {
            $this->requestHeaders[] = 'Content-Type: ' . $contentType->value();
            // Probablly another property like requestBody should be set to sent CURLOPT_POSTFIELDS at curl since content type is set
        }

        if ($this->endpoint->isEndpointWithBody() && null === $requestBody) {
            throw new \Exception('Empty body for request: ' . $this->endpoint->value());
        }

        $this->authorizationHash = base64_encode($_ENV['CUSTOMER_KEY'] . ':' . $_ENV['CUSTOMER_SECRET']);
        $authorizationHeader = $this->buildAuthorizationHeaderString();

        $this->requestHeaders[] = $authorizationHeader;
    }

    /**
     * @param WoocommerceResourceEndpoint $endpoint
     * @param array $requestHeaders
     * @param array|null $requestBody
     * @param RequestContentType|null $contentType
     * @return self
     * @throws \Exception
     */
    public static function initRequest(
        WoocommerceResourceEndpoint $endpoint,
        array $endpointParameters = [],
        array $queryParameters = [],
        array $requestHeaders = [],
        ?array $requestBody = null,
        ?RequestContentType $contentType = null
    ): self {
        return new self(
            endpoint: $endpoint,
            endpointSegments: $endpointParameters,
            endpointQueryParameters: $queryParameters,
            requestHeaders: $requestHeaders,
            requestBody: $requestBody,
            contentType: $contentType
        );
    }

    /**
     * @param int $perPage
     * @param int $page
     * @return WoocommerceApi
     */
    public function exec(int $perPage = self::TOTAL_PRODUCTS_PER_PAGE, int $page = 1): self
    {
        $endpointUrl = match($this->endpoint->httpMethod()) {
            'GET' => $this->composeEndpoint($perPage, $page),
            default => $this->composeEndpoint()
        };

        $this->setupAndSendRequestToEndpoint($endpointUrl);

        return $this;
    }

    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }

    public function getEndpoint(): WoocommerceResourceEndpoint
    {
        return $this->endpoint;
    }

    public function setEndpoint(WoocommerceResourceEndpoint $endpoint): void
    {
        $this->endpoint = $endpoint;
    }

    public function getEndpointSegments(): array
    {
        return $this->endpointSegments;
    }

    public function setEndpointSegments(array $endpointSegments): void
    {
        $this->endpointSegments = $endpointSegments;
    }

    public function getRequestHeaders(): array
    {
        return $this->requestHeaders;
    }

    public function setRequestHeaders(array $requestHeaders): void
    {
        $this->requestHeaders = $requestHeaders;
    }

    public function getRequestBody(): ?array
    {
        return $this->requestBody;
    }

    public function setRequestBody(?array $requestBody): void
    {
        $this->requestBody = $requestBody;
    }

    public function getContentType(): ?RequestContentType
    {
        return $this->contentType;
    }

    public function setContentType(?RequestContentType $contentType): void
    {
        $this->contentType = $contentType;
    }

    public function getResponseInfo(): ?array
    {
        return $this->responseInfo;
    }

    public function setResponseInfo(?array $responseInfo): void
    {
        $this->responseInfo = $responseInfo;
    }

    public function getResponseHeaders(): array
    {
        return $this->responseHeaders;
    }

    public function setResponseHeaders(array $responseHeaders): void
    {
        $this->responseHeaders = $responseHeaders;
    }

    public function getResponseCode(): ?int
    {
        return $this->responseCode;
    }

    public function setResponseCode(?int $responseCode): void
    {
        $this->responseCode = $responseCode;
    }


    private function composeEndpoint(int $perPage = null, int $page = null): string
    {
        $encodedQueryString = '';

        if (null !== $perPage && null !== $page) {
            $encodedQueryString = '?' . http_build_query(
                array_merge(
                    $this->endpointQueryParameters,
                    ['per_page' => $perPage, 'page' => $page]
                )
            );
        }

        return ApiHelpers::resolveEndpoint(
                $this->endpoint->value(),
                implode(",", $this->endpointSegments)
            ) . $encodedQueryString;
    }

    /**
     * @param string $endpoint
     * @return void
     */
    private function setupAndSendRequestToEndpoint(string $endpoint): void
    {
        $curl = curl_init();

        $curlOption = array(
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $this->endpoint->httpMethod(),
            CURLOPT_HTTPHEADER => $this->requestHeaders
        );

        if ($this->endpoint->isEndpointWithBody()) {
            $curlOption[CURLOPT_POSTFIELDS] = json_encode($this->requestBody);
        }

        curl_setopt_array($curl, $curlOption);

        $this->responseBody = curl_exec($curl);
        $this->responseInfo = curl_getinfo($curl);
        $this->responseCode = strval(curl_getinfo($curl, CURLINFO_HTTP_CODE));

        curl_close($curl);
    }

    /**
     * @return string
     */
    private function buildAuthorizationHeaderString(): string
    {
        return 'Authorization: Basic ' . $this->authorizationHash;
    }
}
