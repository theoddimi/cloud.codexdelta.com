<?php

namespace Codexdelta\App\Http\Controllers;

use Codexdelta\App\Clients\Notifications\NotificationClient;
use Codexdelta\App\Enums\NotificationType;
use Codexdelta\Libs\Exceptions\ExceptionCase;
use Codexdelta\Libs\Exceptions\InvalidArgumentException;
use Codexdelta\Libs\Exceptions\MissingEnvironmentVariableException;
use Codexdelta\Libs\Http\CdxRequest;
use Codexdelta\Libs\Http\CdxResponse;
use Codexdelta\Libs\HttpApi\ApiHelpers\RequestContentType;
use Codexdelta\Libs\HttpApi\Oxygen\OxygenApi;
use Codexdelta\Libs\HttpApi\Woo\WoocommerceApi;
use Codexdelta\Libs\HttpApi\Woo\WoocommerceResourceEndpoint;
use Codexdelta\Libs\Mailer\Mailer;
use Codexdelta\Libs\Url\UrlGenerator;
use Exception;
use http\Url;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SkroutzController
{
    const SKROUTZ_PROFIT_PERCENTAGE = 11.5;

    const PROCESS_ITEMS_FROM_CONFIG_PER_PAGE = 20;

    const IGNORE_OXYGEN_CODES_PREFIX = ["CDX"];
    /**
     * @throws Exception
     */

    public function index()
    {
        $skroutzProductsScrapList = config('skroutz_api_mappings', 'ref_eshop');

        return view('skroutz/products.twig', ['products' => $skroutzProductsScrapList]);
    }


}
