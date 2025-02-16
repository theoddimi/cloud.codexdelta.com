<?php

namespace Codexdelta\Libs\Http;

use Codexdelta\Libs\Arr;
use Codexdelta\Libs\Str;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class CdxRequest extends SymfonyRequest
{
    protected $json;

    /**
     * The route resolver callback.
     *
     * @var \Closure
     */
    protected $routeResolver;

    public static function capture()
    {
        static::enableHttpMethodParameterOverride();
        $request = static::createFromBase(SymfonyRequest::createFromGlobals());
        if (!$request->hasSession()) {
            $request->setSession(CdxSession::init());
        }

        return $request;
    }

    /**
     * Return the Request instance.
     *
     * @return $this
     */
    public function instance()
    {
        return $this;
    }

    /**
     * Get the request method.
     *
     * @return string
     */
    public function method()
    {
        return $this->getMethod();
    }

    /**
     * Get the root URL for the application.
     *
     * @return string
     */
    public function root()
    {
        return rtrim($this->getSchemeAndHttpHost().$this->getBaseUrl(), '/');
    }

    /**
     * Get the URL (no query string) for the request.
     *
     * @return string
     */
    public function url()
    {
        return rtrim(preg_replace('/\?.*/', '', $this->getUri()), '/');
    }

    public function user()
    {
        return $this->getSession()->get('auth_user') ?? null;
    }

    /**
     * Get the full URL for the request.
     *
     * @return string
     */
    public function fullUrl()
    {
        $query = $this->getQueryString();

        $question = $this->getBaseUrl().$this->getPathInfo() === '/' ? '/?' : '?';

        return $query ? $this->url().$question.$query : $this->url();
    }

    /**
     * Get the current path info for the request.
     *
     * @return string
     */
    public function path()
    {
        $pattern = trim($this->getPathInfo(), '/');

        return $pattern === '' ? '/' : $pattern;
    }

    /**
     * Get the current decoded path info for the request.
     *
     * @return string
     */
    public function decodedPath()
    {
        return rawurldecode($this->path());
    }

    /**
     * Get a segment from the URI (1 based index).
     *
     * @param  int  $index
     * @param  string|null  $default
     * @return string|null
     */
    public function segment($index, $default = null)
    {
        return Arr::get($this->segments(), $index - 1, $default);
    }

    /**
     * Get all of the segments for the request path.
     *
     * @return array
     */
    public function segments()
    {
        $segments = explode('/', $this->decodedPath());

        return array_values(array_filter($segments, function ($value) {
            return $value !== '';
        }));
    }


    /**
     * Determine if the route name matches a given pattern.
     *
     * @param  mixed  ...$patterns
     * @return bool
     */
    public function routeIs(...$patterns)
    {
        return $this->route() && $this->route()->named(...$patterns);
    }

    /**
     * Get the route handling the request.
     *
     * @param  string|null  $param
     * @param  mixed  $default
     * @return \Illuminate\Routing\Route|object|string|null
     */
    public function route($param = null, $default = null)
    {
        $route = call_user_func($this->getRouteResolver());

        if (is_null($route) || is_null($param)) {
            return $route;
        }

        return $route->parameter($param, $default);
    }


    /**
     * Get the route resolver callback.
     *
     * @return \Closure
     */
    public function getRouteResolver()
    {
        return $this->routeResolver ?: function () {
            //
        };
    }

    /**
     * Determine if the current request URL and query string match a pattern.
     *
     * @param  mixed  ...$patterns
     * @return bool
     */
    public function fullUrlIs(...$patterns)
    {
        $url = $this->fullUrl();

        return collect($patterns)->contains(fn ($pattern) => Str::is($pattern, $url));
    }

    /**
     * Get the host name.
     *
     * @return string
     */
    public function host()
    {
        return $this->getHost();
    }

    /**
     * Get the HTTP host being requested.
     *
     * @return string
     */
    public function httpHost()
    {
        return $this->getHttpHost();
    }

    /**
     * Get the scheme and HTTP host.
     *
     * @return string
     */
    public function schemeAndHttpHost()
    {
        return $this->getSchemeAndHttpHost();
    }

    /**
     * Determine if the request is the result of an AJAX call.
     *
     * @return bool
     */
    public function ajax()
    {
        return $this->isXmlHttpRequest();
    }

    /**
     * Determine if the request is the result of a PJAX call.
     *
     * @return bool
     */
    public function pjax()
    {
        return $this->headers->get('X-PJAX') == true;
    }

    /**
     * Determine if the request is the result of a prefetch call.
     *
     * @return bool
     */
    public function prefetch()
    {
        return strcasecmp($this->server->get('HTTP_X_MOZ') ?? '', 'prefetch') === 0 ||
            strcasecmp($this->headers->get('Purpose') ?? '', 'prefetch') === 0 ||
            strcasecmp($this->headers->get('Sec-Purpose') ?? '', 'prefetch') === 0;
    }

    /**
     * Determine if the request is over HTTPS.
     *
     * @return bool
     */
    public function secure()
    {
        return $this->isSecure();
    }

    /**
     * Get the client IP address.
     *
     * @return string|null
     */
    public function ip()
    {
        return $this->getClientIp();
    }

    /**
     * Get the client IP addresses.
     *
     * @return array
     */
    public function ips()
    {
        return $this->getClientIps();
    }

    /**
     * Get the client user agent.
     *
     * @return string|null
     */
    public function userAgent()
    {
        return $this->headers->get('User-Agent');
    }

    /**
     * Merge new input into the current request's input array.
     *
     * @param  array  $input
     * @return $this
     */
    public function merge(array $input)
    {
        $this->getInputSource()->add($input);

        return $this;
    }

    /**
     * Get the input source for the request.
     *
     * @return InputBag
     */
    protected function getInputSource()
    {
        if ($this->isJson()) {
            return $this->json();
        }

        return in_array($this->getRealMethod(), ['GET', 'HEAD']) ? $this->query : $this->request;
    }


    /**
     * Replace the input values for the current request.
     *
     * @param  array  $input
     * @return $this
     */
    public function replace(array $input)
    {
        $this->getInputSource()->replace($input);

        return $this;
    }

    /**
     * This method belongs to Symfony HttpFoundation and is not usually needed when using Laravel.
     *
     * Instead, you may use the "input" method.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
        return parent::get($key, $default);
    }

    /**
     * Filter the given array of files, removing any empty values.
     *
     * @param  mixed  $files
     * @return mixed
     */
    protected function filterFiles($files)
    {
        if (! $files) {
            return;
        }

        foreach ($files as $key => $file) {
            if (is_array($file)) {
                $files[$key] = $this->filterFiles($files[$key]);
            }

            if (empty($files[$key])) {
                unset($files[$key]);
            }
        }

        return $files;
    }

    /**
     * Create an Illuminate request from a Symfony instance.
     *
     * @param SymfonyRequest $request
     * @return static
     */
    public static function createFromBase(SymfonyRequest $request): static
    {
        $newRequest = new static(
            $request->query->all(), $request->request->all(), $request->attributes->all(),
            $request->cookies->all(), (new static)->filterFiles($request->files->all()) ?? [], $request->server->all()
        );

        $newRequest->headers->replace($request->headers->all());

        $newRequest->content = $request->content;

        if ($newRequest->isJson()) {
            $newRequest->request = $newRequest->json();
        }

        return $newRequest;
    }

    public function isJson()
    {
        return Str::contains($this->header('CONTENT_TYPE') ?? '', ['/json', '+json']);
    }

    public function session()
    {
        return $this->session;
    }

    public function validateToken()
    {

    }

//    public function csrfToken()

    protected function retrieveItem($source, $key, $default)
    {
        if (is_null($key)) {
            return $this->$source->all();
        }

        if ($this->$source instanceof InputBag) {
            return $this->$source->all()[$key] ?? $default;
        }

        return $this->$source->get($key, $default);
    }

    /**
     * Retrieve a header from the request.
     *
     * @param  string|null  $key
     * @param  string|array|null  $default
     * @return string|array|null
     */
    public function header($key = null, $default = null)
    {
        return $this->retrieveItem('headers', $key, $default);
    }

    public function json($key = null, $default = null)
    {
        if (! isset($this->json)) {
            $this->json = new InputBag((array) json_decode($this->getContent(), true));
        }

        if (is_null($key)) {
            return $this->json;
        }

        return data_get($this->json->all(), $key, $default);
    }
}