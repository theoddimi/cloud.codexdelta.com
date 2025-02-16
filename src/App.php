<?php

namespace Codexdelta\App;

use Codexdelta\Libs\Http\CdxRequest;
use Codexdelta\Libs\Http\CdxSession;
use Codexdelta\Libs\Router\Router;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class App
{
    protected $routes;
    protected Router $router;

    protected Environment $twig;

    protected CdxRequest $request;
    protected EntityManager $entityManager;

    protected static $app;

    protected function __construct(string $routesPath, Environment $twig, EntityManager $entityManager)
    {
        if (realpath($routesPath) !== false) {
            $this->routes = require $routesPath;
            $this->router = Router::singleton();
            call_user_func($this->routes);
        }

        $this->twig = $twig;
        $this->entityManager = $entityManager;
    }

    public static function getInstance(string $routesPath, Environment $twig, EntityManager $entityManager)
    {
        if (is_null(static::$app)) {
            static::$app = new static($routesPath, $twig, $entityManager);
        }

        return static::$app;
    }


    /**
     * @param CdxRequest $request
     * @return Response
     */
    public function handle(CdxRequest $request): Response
    {
        $this->request = $request;

        return $this->router->resolve($request);
    }

    public function getRequest(): CdxRequest
    {
//        if ($this->request->isMethod('POST')) {
//            if ($this->request->hasSession() && $this->request->session()->get('csrf_token') && $this->request->get('csrf_token')) {
//                if ( hash_equals($this->request->session()->get('csrf_token'), $this->request->get('csrf_token')))
//                {
//                    return $this->request;
//                } else {
//                    throw new \Exception('No authenticated');
//                }
//            } else {
//                throw new \Exception('No authenticated');
//                // @TODO Check if request carries the bearer token and check for user
//            }
//        }

        return $this->request;
    }

    public function getTwig(): Environment
    {
        return $this->twig;
    }

    public static function get()
    {
        if (static::$app instanceof App) {
            return static::$app;
        }

        throw new \Exception('App has not been initialized');
    }

    public function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }
}