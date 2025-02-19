<?php

use Codexdelta\App\Http\Controllers\Auth\AuthenticationController;
use Codexdelta\App\Http\Controllers\DomCrawlerController;
use Codexdelta\App\Http\Controllers\HomeController;
use Codexdelta\App\Http\Controllers\Oxygen\OxygenProductController;
use Codexdelta\App\Http\Controllers\SkroutzController;
use Codexdelta\App\Http\Controllers\StockBalanceController;
use Codexdelta\Libs\Router\Router;

return function()
{
    Router::get('/login', [AuthenticationController::class, 'login']);
    Router::post('/auth', [AuthenticationController::class, 'authenticate']);

    Router::middleware('auth', function() {
            Router::get('/home', [HomeController::class, 'index']);
            Router::get('/welcome', [HomeController::class, 'welcome']);
            Router::get('/checkProductList', [HomeController::class, 'indexMissingProductsFromList']);
            Router::get('/stock/match-notify', [StockBalanceController::class, 'index']);
            Router::post('/logout', [AuthenticationController::class, 'logout']);
            Router::put('/products/:productRetailSystemId/price/update', [StockBalanceController::class, 'updateEshopPriceProductAction']);
            Router::put('/products/:productRetailSystemId/stock/update', [StockBalanceController::class, 'updateEshopStockProductAction']);
            Router::post('/products/crawl/skroutz/fetch', [DomCrawlerController::class, 'crawl']);
            Router::get('/warehouse/skroutz', [SkroutzController::class, 'index']);
    });
};
