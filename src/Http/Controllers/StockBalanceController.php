<?php

namespace Codexdelta\App\Http\Controllers;

use Codexdelta\App\Clients\Notifications\NotificationClient;
use Codexdelta\App\Enums\NotificationType;
use Codexdelta\Libs\Exceptions\ExceptionCase;
use Codexdelta\Libs\Exceptions\InvalidArgumentException;
use Codexdelta\Libs\Exceptions\MissingEnvironmentVariableException;
use Codexdelta\Libs\HttpApi\ApiHelpers\RequestContentType;
use Codexdelta\Libs\HttpApi\Oxygen\OxygenApi;
use Codexdelta\Libs\HttpApi\Woo\WoocommerceApi;
use Codexdelta\Libs\HttpApi\Woo\WoocommerceResourceEndpoint;
use Codexdelta\Libs\Mailer\Mailer;
use Exception;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class StockBalanceController
{
    const PROCESS_ITEMS_FROM_CONFIG_PER_PAGE = 20;

    const IGNORE_OXYGEN_CODES_PREFIX = ["CDX"];
    /**
     * @throws Exception
     */

    public function index()
    {
        $notificationClient = new NotificationClient();
        $notification = $notificationClient->resolve(NotificationType::MAILER);

//        if (application()->getRequest()->query->has('page')) {
//            $page = application()->getRequest()->query->get('page');
//        }

        /**
         * BEGIN OXYGEN API
         */
        // Request all products from oxygen
        $oxygenProducts = [];
        // END - Request products from oxygen
        foreach ($this->parseOxygenProductsList() as $page => $oxygenProductGroup) {
            $oxygenProducts[$page-1] = $oxygenProductGroup;
        }

        /**
         * BEGIN WOO API
         */
        $wooProductsPages = [];
        foreach ($this->getAllProductsInEshop() as $page => $wooProduct) {
//            $wooProductKeyBySku = array_combine(array_column($wooProduct, 'sku'), $wooProduct);
            $wooProductsPages[$page-1] = $wooProduct;
        }

//        dd($oxygenProducts, $wooProductsPages);
        /**
         * BEGIN LOOP TO PRODUCTS OF CURRENT PAGE AND MAKE THE CALCULATIONS FOR NEW COSTS
         */
        $searching = [];
        $priceAlert = [];
        $stockMismatchAlert = [];
        foreach ($oxygenProducts as $groupKey => $oxygenProductGroup) {
            // Get product from eshop
            foreach ($oxygenProductGroup as $key => $oxygenProduct) {
                $searching[$groupKey][$key] = ['product_code' => $oxygenProduct['code'], 'found' => false];

                if (
                    false === $oxygenProduct['status'] ||
                    str_contains(mb_strtoupper($oxygenProduct['code']), implode('|', self::IGNORE_OXYGEN_CODES_PREFIX))
                ) {
                    unset($searching[$groupKey][$key]);
                    continue;
                }

                $timeToRetrieveFromWooStart = microtime(true);

                foreach ($wooProductsPages as $wooProductPage) {
                    foreach ($wooProductPage as $wooProduct) {
                        $found = mb_strtoupper($oxygenProduct['code']) === mb_strtoupper($wooProduct["sku"]);

                        if (false !== $found) {
                            $searching[$groupKey][$key]['found'] = true;
                            // COMPARE PRICES AND STOCK $this->comparePricesAndStock($wooProduct, $oxygenProduct);
                            $priceAlert[] = number_format((float)$wooProduct['price'], 2, '.', '')
                            === number_format((float)$oxygenProduct['sale_total_amount'], 2, '.', '') ||
                                number_format((float)$wooProduct['regular_price'], 2, '.', '')
                                === number_format((float)$oxygenProduct['sale_total_amount'], 2, '.', '')
                                ? null
                                : $oxygenProduct['code'];

                            $stockMismatchAlert[] = $wooProduct['stock_quantity'] === (int)$oxygenProduct['quantity'] ? null : $oxygenProduct['code'];

                            continue 3;
                        }
                    }
                }

                $timeToRetrieveFromWooEnd = microtime(true);
            }
        }

        $productsMissingFromEshop = [];
        foreach ($searching as $group) {
            $productsMissingFromEshop[] = array_filter($group, fn($item) => false === $item['found']);
        }

        $flattenMissingEshopProductList = [];
        foreach ($productsMissingFromEshop as $productGroup) {
            $flattenMissingEshopProductList = array_merge($flattenMissingEshopProductList, $productGroup);
        }

        $priceAlert = array_filter($priceAlert, fn($item) => null !== $item);
        $stockMismatchAlert = array_filter($stockMismatchAlert, fn($item) => null !== $item);

        // Send notification for missing products from eshop $this->notifyMissingProductsFromEshop();
        return view('stock-balance/info.twig',
            [
                'eshop_products_not_found' => $flattenMissingEshopProductList,
                'prices_mismatch' => $priceAlert,
                'stock_mismatch' => $stockMismatchAlert
            ]);
    }

    private function parseOxygenProductsList()
    {
        $oxygenApi = OxygenApi::init();
        $productsOxygenPage = 1;

        do {
            $productsResponse = $oxygenApi->getProducts($productsOxygenPage);
            $productsResponseBody = json_decode($productsResponse->getResponseBody(), true)["data"];
            $countResults = count($productsResponseBody);

//            $oxygenProducts[$productsOxygenPage] = $productsResponseBody;

            yield $productsOxygenPage => $productsResponseBody;
            $productsOxygenPage++;
        } while($countResults !== 0);

//        dd($oxygenProducts);
    }

    public function indexMissingProductsFromList(): Response
    {
        $skroutzProductsScrapList = config('skroutz_api_mappings', 'ref_eshop');
        $productsMissingFromList = $this->getAllProductsInEshopExistsInJsonProvided($skroutzProductsScrapList);

        return view('missing-products.twig', ['products' => $productsMissingFromList]);
    }

    /**
     * @param array $productProperties
     * @param float $price
     * @return float
     */
    private function calculateProductProfitPercentageForPrice(array $productProperties, float $price): float
    {
        $vatIndicator = round((floatval($productProperties["sale_vat_ratio"])/100), 2) + 1; // 1,24 - 1,06 etc
        $newSaleNetAmount = round(($price/$vatIndicator), 2);// - floatval($oxygenProduct["purchase_total_amount"])

        $netProfit = $newSaleNetAmount - floatval($productProperties["purchase_net_amount"]);

        try {
            return round($netProfit * 100 / floatval($productProperties["purchase_net_amount"]), 2);
        } catch (\Throwable $e) {
            throw new Exception($e->getMessage() . ' | Product: ' . $productProperties['code']);
        }

    }

    private function calculatePercentageResultForValue(float $percentage, float $value): float
    {
        return round($value*$percentage/100, 2);
    }

    /**
     * @param array $product
     * @return mixed
     * @throws Exception
     */
    private function retrieveEshopProduct(array $product): mixed
    {
        $wooApi = WoocommerceApi::initRequest(
            endpoint: WoocommerceResourceEndpoint::PRODUCTS,
            queryParameters: ['sku' => mb_strtoupper(data_get($product, 'code'))],
            contentType: RequestContentType::APPLICATION_JSON
        );

        return json_decode($wooApi->exec()->getResponseBody(), true);
    }

    /**
     * @param array $oxygenProducts
     * @param string|null $productSku
     * @return array|null
     */
    private function validateProductExistsInOxygenProductListResponse(array $oxygenProducts, ?string $productSku): null|array
    {
        $oxygenResultsKey = 1;

        do {
            foreach ($oxygenProducts[$oxygenResultsKey] as $oxygenProduct) {
                if (isset($oxygenProduct["code"]) && $oxygenProduct["code"] === $productSku) {
                    return $oxygenProduct;
                }
            }

            $oxygenResultsKey++;
        } while($oxygenResultsKey <= count($oxygenProducts));

        return null;
    }

    private function scrapProductPageInSkroutz(array $productFromList):  false|null|string
    {
        $urlToScrap = escapeshellarg(data_get($productFromList,'skroutz_page_url'));
        $nodeCommand = $_SERVER['DOCUMENT_ROOT'] . '/../resources/js/crawl.cjs ' . $urlToScrap;

        return shell_exec('node ' . $nodeCommand);
    }

    /**
     * @param string $html
     * @return float|null
     */
    private function crawlAndFindMyShopPriceFromHtml(string $html): null|float
    {
        $pattern = '/(\d+,\d+)/';

        # Use preg_match to find the first match of the pattern in the input string
        $crawler = new Crawler($html);
        # My shop in skroutz
        $myShopPriceNode = $crawler->filter('li#shop-' . env("SKROUTZ_SHY_BONSAI_SHOP_ID") . ' strong.dominant-price');
        $myShopPriceCount = $myShopPriceNode->count();

        $myShopPrice = null;

        if ($myShopPriceCount > 0) {
            $myShopPrice = $myShopPriceNode->first()->text();

            if (preg_match($pattern, $myShopPrice, $matches)) {
                // Return the matched number
                $myShopPrice = floatval(str_replace(',', '.', $matches[1]));
            } else {
                $myShopPrice = null;
            }
        }

        return $myShopPrice;
    }

    /**
     * @param string $html
     * @return array
     */
    private function crawlAndFindMerchantPricesButNotMineFromHtml(string $html): array
    {
        $pattern = '/(\d+,\d+)/';
        $crawler = new Crawler($html);

        $pricesNodes = $crawler->filter('li:not(#shop-' . env("SKROUTZ_SHY_BONSAI_SHOP_ID") . ') strong.dominant-price');
        $pricesCount = $pricesNodes->count();
        $prices = [];

        if ($pricesCount > 0) {
            foreach ($pricesNodes as $priceNode) {
                if (is_string($priceNode->textContent)) {
                    if (preg_match($pattern, $priceNode->textContent, $matches)) {
                        // Return the matched number
                        $prices[] = floatval(str_replace(',', '.', $matches[1]));
                    }
                }
            }
        }

        return $prices;
    }

    /**
     * @param float $price
     * @param array $product
     * @return array
     * @throws Exception
     */
    private function updateEshopWithNewPriceForProduct(float $price, array $product): array
    {
        $wooApi = WoocommerceApi::initRequest(
            endpoint: WoocommerceResourceEndpoint::UPDATE_PRODUCTS,
            endpointParameters: [data_get($product, 'eshop_product_id')],
            requestBody: ['sale_price' => strval($price)],
            contentType: RequestContentType::APPLICATION_JSON
        );

        return json_decode($wooApi->exec()->getResponseBody(), true);
    }

    /**
     * @param array $foundProductInOxygen
     * @param array $productFromList
     * @param string $lowestPriceInPage
     * @param string $lowestPriceToBe
     * @param string $profitPercentage
     * @param string $profitPercentageIncludingSkroutzCommission
     * @return array
     */
    private function setupProductResultsResponse(
        array $foundProductInOxygen,
        array $productFromList,
        string $lowestPriceInPage,
        string $lowestPriceToBe,
        string $profitPercentage,
        string $profitPercentageIncludingSkroutzCommission
    ): array {
        $results = [];
        $results[$foundProductInOxygen["code"]]['dry_run'] = true;
        $results[$foundProductInOxygen["code"]]['product_title'] = data_get($productFromList, 'title');
        $results[$foundProductInOxygen["code"]]['product_code'] = $foundProductInOxygen["code"];
        $results[$foundProductInOxygen["code"]]['product_lowest_price_skroutz'] = $lowestPriceInPage;
        $results[$foundProductInOxygen["code"]]['product_new_price'] = $lowestPriceToBe;
        $results[$foundProductInOxygen["code"]]['product_new_price_percentage_profit'] = $profitPercentage;
        $results[$foundProductInOxygen["code"]]['product_new_price_percentage_profit_after_commission'] = $profitPercentageIncludingSkroutzCommission;
        $results[$foundProductInOxygen["code"]]['product_page_url'] = data_get($productFromList, 'skroutz_page_url');

        return $results;
    }

    private function getAllEshopProducts()
    {
        $wooApi = WoocommerceApi::initRequest(
            endpoint: WoocommerceResourceEndpoint::PRODUCTS,
            contentType: RequestContentType::APPLICATION_JSON
        );

        $wooProductsResults = [];
        $page = 1;

        do {
            $pageResult = json_decode($wooApi->exec(page: $page)->getResponseBody(), true);
            $wooProductsResults[] = $pageResult;
            $page++;
        } while (count($pageResult) > 0);
    }


    private function getAllProductsInEshop(): \Generator
    {
        $wooApi = WoocommerceApi::initRequest(
            endpoint: WoocommerceResourceEndpoint::PRODUCTS,
            contentType: RequestContentType::APPLICATION_JSON
        );

        $productsMissingFromCompareList = [];
        $wooProductsResults = [];
        $page = 1;

        do {
            $pageResult = json_decode($wooApi->exec(page: $page)->getResponseBody(), true);
            yield $page => $pageResult;
            $page++;
        } while (count($pageResult) > 0);
//
//        $eshopProductIdsInList = array_column($listProducts, 'eshop_product_id');
//        $wooResultsKey = 0;
//
//        do {
//            foreach ($wooProductsResults[$wooResultsKey] as $wooProduct) {
//                if (!in_array($wooProduct["id"], $eshopProductIdsInList) && $wooProduct['stock_quantity'] > 0) {
//                    $productsMissingFromCompareList[] = $wooProduct;
//                }
//            }
//
//            $wooResultsKey++;
//        } while ($wooResultsKey < count($wooProductsResults));
//
//
//        return $productsMissingFromCompareList;
    }
}
