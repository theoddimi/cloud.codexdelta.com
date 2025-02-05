<?php

namespace Codexdelta\Libs\HttpApi;
use Exception;

class HttpHeadersEnum
{
    const HEADER_KEY_CONTENT_TYPE = 'Content-Type';
    const HEADER_KEY_AUTHORIZATION = 'Authorization';
    const HEADER_KEY_ACCEPT = 'Accept';
    const HEADER_KEY_ACCEPT_LANGUAGE = 'Accept-Language';
    const HEADER_KEY_COOKIE = 'Cookie';
    const HEADER_VALUE_ACCEPT_ALL = '*/*';
    const HEADER_VALUE_ACCEPT_SKROUTZ = 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8';
    const HEADER_VALUE_CONTENT_TYPE_FORM_URLENCODED = 'application/x-www-form-urlencoded';
    const HEADER_VALUE_CONTENT_TYPE_JSON = 'application/json';
    const HEADER_VALUE_ACCEPT_LANGUAGE = 'en-US,en;q=0.9';
    const HEADER_VALUE_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3';


    /**
     * @var string
     */
    private string $value;

    /**
     * @param string $headerKey
     * @param string $headerValue
     */
    public function __construct(string $headerKey, string $headerValue)
    {
        $this->value = $headerKey . ':' . $headerValue;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getHeaderKey(): string
    {
        return explode(':', $this->value)[0];
    }

    /**
     * @return string
     */
    public function getHeaderValue(): string
    {
        return explode(':', $this->value)[1];
    }

    /**
     * @param array $headers
     * @return string|null
     * @throws Exception
     */
    public static function findContentTypeValueInHeadersIfExist(array $headers = []): ?string
    {
        foreach ($headers as $header) {
            if ($header instanceof self) {
                if ($header->getHeaderKey() === self::HEADER_KEY_CONTENT_TYPE) {
                    return $header->getHeaderValue();
                }
            } else {
                throw new Exception('Headers should only contain HttpHeaderEnum instances');
            }
        }

        return null;
    }
}
