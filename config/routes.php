<?php

use Codexdelta\App\Http\Controllers\HomeController;
use Codexdelta\App\Http\Controllers\StockBalanceController;
use Codexdelta\Libs\Router\Router;

return function()
{
    Router::get('/home', [HomeController::class, 'index']);
    Router::get('/checkProductList', [HomeController::class, 'indexMissingProductsFromList']);
    Router::get('/stock/match-notify', [StockBalanceController::class, 'index']);
};
