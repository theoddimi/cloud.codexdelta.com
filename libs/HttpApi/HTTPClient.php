<?php

namespace Codexdelta\Libs\HttpApi;

use Exception;

class HTTPClient
{
    const HTTP_METHOD_POST = 'POST';
    const HTTP_METHOD_GET = 'GET';
    const RESPONSE_CODE_OK = 200;

    /**
     * @var array
     */
    protected array $options;

    /**
     * @param array $options
     * @return $this
     */
    private function initDefaults(array $options = []): self
    {
        if (count($options) > 0) {
            $this->options = $options;
        } else {
            $this->setOptions([
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
            ]);
        }

        return $this;
    }

    /**
     * @param string $endpoint
     * @param array $curlOptions
     * @return Request
     */
    public static function createRequest(string $endpoint, array $curlOptions = []): Request
    {
        $request = Request::create($endpoint);
        $request->initDefaults($curlOptions);

        return $request;
    }

    /**
     * @param string $endpoint
     * @param array $headers
     * @param array $curlOptions
     * @return Request
     * @throws Exception
     */
    public static function createRequestWithHeaders(string $endpoint, array $headers, array $curlOptions = []): Request
    {
        $request = self::createRequest($endpoint, $curlOptions);
        $request->setHeaders($headers);

        return $request;
    }


    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param $value
     * @return void
     */
    public function addOptionUserAgent($value)
    {
        $this->addOption(CURLOPT_USERAGENT, $value);
    }

    /**
     * @param $curlOption
     * @param $value
     * @return void
     */
    protected function addOption($curlOption, $value)
    {
        $this->options[$curlOption] = $value;
    }

    /**
     * @param Request $request
     * @return Response
     */
    protected function execute(Request $request): Response
    {
        $curl = curl_init();
        curl_setopt_array($curl, $this->options);

        $response = curl_exec($curl);
        $responseInfo = curl_getinfo($curl);
        $responseCode = strval(curl_getinfo($curl, CURLINFO_HTTP_CODE));

        $apiResponse = Response::create($request, $responseCode);
        $apiResponse->setResponseBody($response);

        curl_close($curl);

        return $apiResponse;
    }
}
