<?php

namespace Codexdelta\Libs\Router;

use Closure;
use Codexdelta\App\App;
use Codexdelta\Libs\Exceptions\FourOhFourException;
use Codexdelta\Libs\Http\CdxRequest;
use Exception;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Response;

class Router
{
    protected $routes = []; // stores routes

    protected static $instance;
    protected function __construct()
    {}

    public static function singleton()
    {
        if (static::$instance instanceof Router) {
            return static::$instance;
        }

        static::$instance = new self();

        return static::$instance;
    }

    /**
    * add routes to the $routes
    */
    public function addRoute(string $method, string $url, Closure|array $target)
    {
        $this->routes[$method][$url] = $target;
    }

    public function routes()
    {
        return $this->routes;
    }

    public static function get(string $url, $target = null)
    {
        $router = static::singleton();

        if ($target instanceof Closure) {
            $router->addRoute('GET', $url, $target);
        } elseif (is_array($target) && count($target) === 2) {
            $router->addRoute('GET', $url, $target);
        } else {
            throw new Exception('Invalid format for route');
        }

//        return $router;
    }

    public function resolve(CdxRequest $request) {
        $method = $request->method();
        $url = $request->getPathInfo();

        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $routeUrl => $target) {
                // Use named subpatterns in the regular expression pattern to capture each parameter value separately
                $pattern = preg_replace('/\/:([^\/]+)/', '/(?P<$1>[^/]+)', $routeUrl);
                if (preg_match('#^' . $pattern . '$#', $url, $matches)) {
                    // Pass the captured parameter values as named arguments to the target function
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY); // Only keep named subpattern matches
//                    $controller = new$request;
                    if ($target instanceof Closure) {
                        return call_user_func_array($target, $params);
                    } elseif (is_array($target) && count($target) === 2) {

                        if (method_exists($target[0], $target[1])) {
                            $method = new ReflectionMethod($target[0], $target[1]);
                            $methodParameters = $method->getParameters();

                            foreach ($methodParameters as $parameter) {
                                if ($parameter->getType()->getName() === CdxRequest::class) {
                                    $params[$parameter->getName()] = $request;
                                }
                            };

                            return call_user_func_array(array(new $target[0], $target[1]), $params);
//                            return call_user_func_array(array(new $target[0], $target[1]), $params);
                        }

                        return new Response('', 404);
                    }

                    return new Response('', 404);
                }
            }
        }
        throw new FourOhFourException('test', 404);
    }
}