<?php

namespace Codexdelta\Libs\HttpApi;

use Exception;

class Request extends HTTPClient
{
    /**
     * @var array
     */
    protected array $data;

    /**
     * @var HttpHeadersEnum[]
     */
    protected array $headers = [];

    /**
     * @var string
     */
    protected string $requestEndpoint;

    /**
     * @var string
     */
    protected string $httpMethod;

    /**
     * @param string $endpoint
     * @return Request
     */
    protected static function create(string $endpoint): Request
    {
        $request = new self;
        $request->requestEndpoint = $endpoint;

        return $request;
    }

    /**
     * @param array $headers
     * @return Response
     * @throws Exception
     */
    public function get(array $headers = []): Response
    {
        $this->httpMethod = self::HTTP_METHOD_GET;
        if (count($headers) > 0) {
            $this->setHeaders($headers);
        }
        $this->prepareOptionsForRequest();

        return $this->execute($this);
    }

    /**
     * @param array $data
     * @param array $headers
     * @return Response
     * @throws Exception
     */
    public function post(array $data, array $headers = []): Response
    {
        $this->httpMethod = self::HTTP_METHOD_POST;
        $this->data = $data;
        $this->setHeaders($headers);
        $this->prepareOptionsForRequest();

        return $this->execute($this);
    }

    /**
     * @return void
     */
    protected function prepareOptionsForRequest(): void
    {
        $this->setupEndpointOfRequest();
        $this->setupHeadersForRequest();
        if ($this->isPostRequest()) {
            $this->setupPostFieldsForRequest();
        }
    }

    /**
     * @return void
     */
    protected function setupEndpointOfRequest(): void
    {
        if (!empty($this->requestEndpoint)) {
            $this->options[CURLOPT_URL] = $this->requestEndpoint;
        }
    }

    /**
     * @return void
     */
    protected function setupHeadersForRequest(): void
    {
        $this->options[CURLOPT_HTTPHEADER] = $this->prepareHeadersForRequest();
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function setupPostFieldsForRequest(): void
    {
        $this->options[CURLOPT_POSTFIELDS] = $this->composePostData();
    }

    /**
     * @return array
     */
    protected function prepareHeadersForRequest(): array
    {
        $headers = [];

        foreach ($this->headers as $header) {
            $headers[] = $header->getValue();
        }

        return $headers;
    }

    /**
     * @return string|null
     * @throws Exception
     */
    protected function composePostData(): ?string
    {
        $requestContentType = HttpHeadersEnum::findContentTypeValueInHeadersIfExist($this->headers);

        if (HttpHeadersEnum::HEADER_VALUE_CONTENT_TYPE_FORM_URLENCODED === $requestContentType) {
            return http_build_query($this->data);
        }

        if (HttpHeadersEnum::HEADER_VALUE_CONTENT_TYPE_JSON === $requestContentType) {
            return json_encode($this->data, true);
        }

        return '';
    }

    /**
     * @return bool
     */
    public function isGetRequest(): bool
    {
        return $this->httpMethod === self::HTTP_METHOD_GET;
    }

    /**
     * @return bool
     */
    public function isPostRequest(): bool
    {
        return $this->httpMethod === self::HTTP_METHOD_POST;
    }

    /**
     * @param array $headers
     * @return $this
     * @throws Exception
     */
    public function setHeaders(array $headers): self
    {
        foreach ($headers as $header) {
            if ($header instanceof HttpHeadersEnum) {
                $this->headers[] = $header;
            } else {
                throw new Exception('Headers should contain only HttpHeaderEnum instances');
            }
        }

        return $this;
    }

    /**
     * @param HttpHeadersEnum $header
     * @return $this
     */
    public function addHeader(HttpHeadersEnum $header): self
    {
        foreach ($this->headers as $key=>$objectHeader) {
            if ($objectHeader->getHeaderKey() === $header->getHeaderKey()) {
                $this->headers[$key] = $header;

                return $this;
            }
        }

        $this->headers[] = $header;

        return $this;
    }
}
