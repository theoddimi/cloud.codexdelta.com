<?php

namespace Codexdelta\Libs\HttpApi;

class Response {


    /**
     * @var Request
     */
    private Request $request;


    /**
     * @var string
     */
    private string $responseCode;


    /**
     * @var array
     */
    private array $responseHeader;


    /**
     * @var string
     */
    private string $responseBody;

    /**
     * @param Request $request
     * @param string $responseCode
     * @return Response
     */
    public static function create(
        Request $request,
//        array $headers,
        string $responseCode
    ): Response {
        $apiResponse = new Response();
//        $apiResponse->setResponseHeader($headers);
        $apiResponse->setRequest($request);
        $apiResponse->setResponseCode($responseCode);

        return $apiResponse;
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    /**
     * @return int
     */
    public function getResponseCode(): int
    {
        return $this->responseCode;
    }

    /**
     * @param int $responseCode
     */
    public function setResponseCode(int $responseCode): void
    {
        $this->responseCode = $responseCode;
    }

    /**
     * @return array
     */
    public function getResponseHeader(): array
    {
        return $this->responseHeader;
    }

    /**
     * @param mixed $responseHeader
     */
    public function setResponseHeader(array $responseHeader): void
    {
        $this->responseHeader = $responseHeader;
    }

    /**
     * @return string
     */
    public function getResponseBody(): string
    {
        return $this->responseBody;
    }

    /**
     * @param string $responseBody
     * @return void
     */
    public function setResponseBody(string $responseBody): void
    {
        $this->responseBody = $responseBody;
    }
}
