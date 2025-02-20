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
//        $allProductsInListCount = count($skroutzProductsScrapList);

//        $productFromFile = array_filter($skroutzProductsScrapList, fn($product) => $product['skroutz_page_url'] == $skroutzProductPageHtml);
//        $productFromFile = reset($productFromFile);
//        dd($productFromFile);
//        if (false === $productFromFile) {
//            return new JsonResponse([
//                'success' => false,
//            ], Response::HTTP_OK);
//        }

//        $urlToScrap = escapeshellarg($productFromFile['skroutz_page_url']);
//        $nodeCommand = $_SERVER['DOCUMENT_ROOT'] . '/../resources/js/crawl.cjs ' . $urlToScrap;
//        $skroutzProductPageHtml = shell_exec('node ' . $nodeCommand);

        $skroutzPageMyPrice = $this->crawlAndFindMyShopPriceFromHtml($skroutzProductPageHtml);

//        if (!is_numeric($skroutzPageMyPrice)) {
//            $issuesReadingPrices[$productFromFile["eshop_product_id"]]['name'] =
//                $productFromFile['skroutz_page_url'];
//            $issuesReadingPrices[$productFromFile["eshop_product_id"]]['message'] =
//                'Could not crawl the price for my shop for the given URL. Possible issue: Product not listed to specific page.';
//        }

        $skroutzPageMerchantsPrices = $this->crawlAndFindMerchantPricesButNotMineFromHtml($skroutzProductPageHtml);

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