<?php

namespace Codexdelta\Libs\HttpApi\ApiHelpers;
class ApiHelpers
{
    /**
     * @param $endpoint
     * @param ...$params
     * @return string
     */
    public static function resolveEndpoint($endpoint, ...$params): string
    {
        preg_match_all('/({.*?})/', $endpoint, $matches);

        if (count(reset($matches)) === 0 && (count($params) === 0 || strlen(reset($params)) === 0)) {
            return $endpoint;
        }

        if (count(reset($matches)) !== count($params)) {
            echo 'Error mismatch of arguments counts.';
            exit;
        }

        $resolvedEndpoint = $endpoint;

        foreach (reset($matches) as $key => $match) {
            $resolvedEndpoint = str_replace($match, $params[$key], $resolvedEndpoint);
        }

        return $resolvedEndpoint;
    }
}
