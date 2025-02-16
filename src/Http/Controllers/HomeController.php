<?php

namespace Codexdelta\App\Http\Controllers;

use Codexdelta\Libs\Http\CdxRequest;
use Codexdelta\Libs\HttpApi\ApiHelpers\RequestContentType;
use Codexdelta\Libs\HttpApi\Oxygen\OxygenApi;
use Codexdelta\Libs\HttpApi\Woo\WoocommerceApi;
use Codexdelta\Libs\HttpApi\Woo\WoocommerceResourceEndpoint;
use Exception;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class HomeController
{
    const SKROUTZ_PROFIT_PERCENTAGE = 11.5;
    const PROFIT_PERCENTAGE_THRESHOLD = 20;

    const PROCESS_ITEMS_FROM_CONFIG_PER_PAGE = 20;
    /**
     * @throws Exception
     */

    public function welcome()
    {
        return view('/welcome.twig');
    }

    public function index()
    {
        $productCalculationsResults = [];
        $issuesReadingPrices = [];
        $page = 1;

        if (application()->getRequest()->query->has('page')) {
            $page = application()->getRequest()->query->get('page');
        }

        $productConfigListKeyRangeEnd = $page * self::PROCESS_ITEMS_FROM_CONFIG_PER_PAGE;
        $productConfigListKeyRangeStart = $productConfigListKeyRangeEnd - self::PROCESS_ITEMS_FROM_CONFIG_PER_PAGE;

        $productsNotFoundInSkroutzPage = [];
        $skroutzProductsScrapList = config('skroutz_api_mappings', 'ref_eshop');
        $allProductsInListCount = count($skroutzProductsScrapList);

        $lastBatchPaging = intval($allProductsInListCount/self::PROCESS_ITEMS_FROM_CONFIG_PER_PAGE);
        $currentBatchPaging = $page;
        if ($allProductsInListCount < self::PROCESS_ITEMS_FROM_CONFIG_PER_PAGE) {
            $skroutzProductsScrapListPaged = $skroutzProductsScrapList;
        } else {
            // Get the rows from json list based on the page query parameter and calculated start - end key
            $skroutzProductsScrapListPaged = array_filter(
                $skroutzProductsScrapList,
                function ($product, $key) use ($productConfigListKeyRangeStart, $productConfigListKeyRangeEnd) {
                    return $key > $productConfigListKeyRangeStart && $key < $productConfigListKeyRangeEnd;
                }, ARRAY_FILTER_USE_BOTH
            );
        }

        // Out of range, return empty page
        if (count($skroutzProductsScrapListPaged) === 0) {
            return view('home.twig', ['products_updated' => $productCalculationsResults, 'issuesReadingPrices' => $issuesReadingPrices]);
        }

        /**
         * BEGIN OXYGEN API
         */

        // Request all products from oxygen
        $oxygenApi = OxygenApi::init();
        $productsOxygenPage = 1;

        do {
            $productsResponse = $oxygenApi->getProducts($productsOxygenPage);
            $productsResponseBody = json_decode($productsResponse->getResponseBody(), true)["data"];
            $countResults = count($productsResponseBody);

            $oxygenProducts[$productsOxygenPage] = $productsResponseBody;
            $productsOxygenPage++;
        } while($countResults !== 0 && $productsOxygenPage<3);
        // END - Request products from oxygen



        /**
         * BEGIN LOOP TO PRODUCTS OF CURRENT PAGE AND MAKE THE CALCULATIONS FOR NEW COSTS
         */
        foreach ($skroutzProductsScrapListPaged as $productFromList) {
            // Get product from eshop
            $productEshop = $this->retrieveEshopProduct($productFromList);
            $productSku = $productEshop["sku"] ?? null;

            $foundProductInOxygen = $this->validateProductExistsInOxygenProductListResponse($oxygenProducts, $productSku);

            if (null === $foundProductInOxygen) {
                continue;
            }

            $skroutzProductPageHtml = $this->scrapProductPageInSkroutz($productFromList);
            if (false === $skroutzProductPageHtml || null === $skroutzProductPageHtml) {
                continue;
            }

            $skroutzPageMyPrice = $this->crawlAndFindMyShopPriceFromHtml($skroutzProductPageHtml);

            if (!is_numeric($skroutzPageMyPrice)) {
                $issuesReadingPrices[$foundProductInOxygen["code"]]['product_page_url'] =
                    data_get($productFromList, 'skroutz_page_url');
                $issuesReadingPrices[$foundProductInOxygen["code"]]['message'] =
                    'Could not crawl the price for my shop for the given URL. Possible issue: Product not listed to specific page.';
                continue;
            }

            $skroutzPageMerchantsPrices = $this->crawlAndFindMerchantPricesButNotMineFromHtml($skroutzProductPageHtml);

            if (count($skroutzPageMerchantsPrices) > 0) {
                ### Compare the results with my shop's price
                $lowestPriceInPage = min($skroutzPageMerchantsPrices);


                if ($lowestPriceInPage <= $skroutzPageMyPrice) {
                    $potentialNewPriceForProduct = $lowestPriceInPage - 0.01;

                    $profitPercentage =
                        $this->calculateProductProfitPercentageForPrice($foundProductInOxygen, $potentialNewPriceForProduct);

                    // Add skroutz profit from new potential price
                    $amountOfSkroutzCommissionForPrice = $this->calculatePercentageResultForValue(
                        self::SKROUTZ_PROFIT_PERCENTAGE,
                        $potentialNewPriceForProduct
                    );

                    $newPriceAfterSkroutzCommissionClearance = $potentialNewPriceForProduct - $amountOfSkroutzCommissionForPrice;
                    $profitPercentageIncludingSkroutzCommission =
                        $this->calculateProductProfitPercentageForPrice(
                            $foundProductInOxygen,
                            $newPriceAfterSkroutzCommissionClearance
                        );
                    // END - Add skroutz profit from new potential price

                    $lowestPriceToBe = $lowestPriceInPage - 0.01;

                    if (true === data_get($productFromList, 'auto_update')) {
                        // Update my price in eshop
                        $productCalculationsResults[] = array_column($this->updateEshopWithNewPriceForProduct($lowestPriceToBe, $productFromList), 'name');
                    } else {
                        $productCalculationsResults = $this->setupProductResultsResponse(
                            $foundProductInOxygen,
                            $productFromList,
                            $lowestPriceInPage,
                            $lowestPriceToBe,
                            $profitPercentage,
                            $profitPercentageIncludingSkroutzCommission
                        );
                    }
                }
            } else {
                $issuesReadingPrices[$foundProductInOxygen["code"]]['product_page_url'] =
                    data_get($productFromList, 'skroutz_page_url');
                $issuesReadingPrices[$foundProductInOxygen["code"]]['message'] = 'Could not retrieve prices for other merchants';
                // NOTIFY ISSUE WITH READING PRICES FROM MERCHANTS
            }
        }

        return view('home.twig', [
            'products_updated' => $productCalculationsResults,
            'issuesReadingPrices' => $issuesReadingPrices,
            'last_batch_paging' => $lastBatchPaging,
            'current_batch_paging' => $currentBatchPaging
        ]);
    }

    public function indexMissingProductsFromList(): Response
    {
        $skroutzProductsScrapList = config('skroutz_api_mappings', 'ref_eshop');
        $productsMissingFromList = $this->getAllProductsInEshopExistsInJsonProvided($skroutzProductsScrapList);

        return view('missing-products.twig', ['products' => $productsMissingFromList]);
    }

    public function updateProductAction()
    {
        $urlToScrap = escapeshellarg('https://www.skroutz.gr/s/55573265/Water-Revolution-Pagouri-500ml-Gri.html?adv_c=x4mgbg%3D%3D--vm%2FoK4CTaQBaJlFn--HQ85pR1eqeNh5igDdjSBsA%3D%3D&product_id=202693899&sponsored=cpc');
        $nodeCommand = $_SERVER['DOCUMENT_ROOT'] . '/../resources/js/crawl.cjs ' . $urlToScrap;

        dd( shell_exec('node ' . $nodeCommand));
        return view('skroutz/products.twig');
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
            endpoint: WoocommerceResourceEndpoint::RETRIEVE_PRODUCT,
            endpointParameters: [data_get($product, 'eshop_product_id')],
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

    /**
     * @param array $listProducts
     * @return array
     * @throws Exception
     */
    private function getAllProductsInEshopExistsInJsonProvided(array $listProducts): array
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
            $wooProductsResults[] = $pageResult;
            $page++;
        } while (count($pageResult) > 0);

        $eshopProductIdsInList = array_column($listProducts, 'eshop_product_id');
        $wooResultsKey = 0;

        do {
            foreach ($wooProductsResults[$wooResultsKey] as $wooProduct) {
                if (!in_array($wooProduct["id"], $eshopProductIdsInList) && $wooProduct['stock_quantity'] > 0) {
                    $productsMissingFromCompareList[] = $wooProduct;
                }
            }

            $wooResultsKey++;
        } while ($wooResultsKey < count($wooProductsResults));


        return $productsMissingFromCompareList;
    }
}
