<?php

namespace Codexdelta\App\Http\Controllers;

use Codexdelta\Libs\Http\CdxRequest;
use Codexdelta\Libs\HttpApi\Request;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DomCrawlerController
{
    public function crawl(CdxRequest $request)
    {
        $issuesReadingPrices = [];
        $skroutzProductPageHtml = $request->get('skroutz_product_html');
        $skroutzProductsScrapList = config('skroutz_api_mappings', 'ref_eshop');

        $skroutzPageMerchantsPrices = $this->crawlAndFindMerchantPricesButNotMineFromHtml($skroutzProductPageHtml);
        $skroutzPageMyPrice = $this->crawlAndFindMyShopPriceFromHtml($skroutzProductPageHtml);

        if (count($skroutzPageMerchantsPrices) > 0) {
            ### Compare the results with my shop's price
            $lowestPriceInPage = min($skroutzPageMerchantsPrices);
        } else {
            return new JsonResponse([
                'success' => false,
                'message' => 'Issue reading prices',
            ], Response::HTTP_OK);
        }

        return new JsonResponse([
            'my_price' => $skroutzPageMyPrice,
            'lowest_price' => $lowestPriceInPage,
            'success' => true,
            'message' => 'Success',
        ], Response::HTTP_OK);
    }

    public function proxy(CdxRequest $request)
    {
        $skroutzProductPageUrl = $request->get('skroutz_product_url');

        if($skroutzProductPageUrl === null) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Empty request body',
            ], Response::HTTP_OK);
        }

        $skroutzProductsScrapList = config('skroutz_api_mappings', 'ref_eshop');

        $url = 'http://127.0.0.1:8080/run-crawl'; // Call the Node.js route

        // Initialize cURL session
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url); // Node.js script URL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response as a string
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects if any
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['crawl_url' => $skroutzProductPageUrl])); // Send JSON data
        // Execute the cURL request and capture the response
        $response = curl_exec($ch);
        curl_close($ch);
        // Check for errors in the cURL request
        if ($response === false) {
            echo "Error: " . curl_error($ch);
        } else {
            // You can parse the response here (for example, decode JSON)
            [$skroutzPageMerchantsPrices, $skroutzPageMyPrice] = $this->crawl(json_decode($response, true)['output']);

            if (count($skroutzPageMerchantsPrices) > 0) {
                ### Compare the results with my shop's price
                $lowestPriceInPage = min($skroutzPageMerchantsPrices);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Issue reading prices',
                ], Response::HTTP_OK);
            }


            return new JsonResponse([
                'my_price' => $skroutzPageMyPrice,
                'lowest_price' => $lowestPriceInPage,
                'success' => true,
                'message' => 'Success',
            ], Response::HTTP_OK);
        }

        // Close cURL session

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
}