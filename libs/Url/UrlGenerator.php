<?php

namespace Codexdelta\Libs\Url;

use Codexdelta\Libs\Http\CdxRequest;
use Codexdelta\Libs\Router\RouterInterface;

class UrlGenerator
{
    private RouterInterface $routes;
    private CdxRequest $request;
    public function __construct(RouterInterface $routes, CdxRequest $request)
    {
        $this->routes = $routes;
        $this->request = $request;
    }

    public static function init()
    {
//        dd(application()->getRouter()->routes());
//        $urlGenerator = new self();
    }
    public function route(string $name, $parameters = [])
    {
        // @TODO Add name method to router
       // dD($this->routes);//->getByName($name)
    }

}