<?php

namespace Codexdelta\App\Http\Controllers;

class DomCrawlerController
{
    public function crawl()
    {
        $urlToScrap = escapeshellarg('https://www.skroutz.gr/s/55573265/Water-Revolution-Pagouri-500ml-Gri.html?adv_c=x4mgbg%3D%3D--vm%2FoK4CTaQBaJlFn--HQ85pR1eqeNh5igDdjSBsA%3D%3D&product_id=202693899&sponsored=cpc');
        $nodeCommand = $_SERVER['DOCUMENT_ROOT'] . '/../resources/js/crawl.cjs ' . $urlToScrap;

        dd( shell_exec('node ' . $nodeCommand));
        return view('skroutz/products.twig');
    }
}