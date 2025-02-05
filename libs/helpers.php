<?php

use Codexdelta\App\App;
use Codexdelta\Libs\Arr;
use Codexdelta\Libs\Exceptions\ExceptionCase;
use Codexdelta\Libs\Exceptions\MissingEnvironmentVariableException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\VarDumper\Caster\ScalarStub;
use Symfony\Component\VarDumper\VarDumper;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;


if (! function_exists('head')) {
    /**
     * Get the first element of an array. Useful for method chaining.
     *
     * @param  array  $array
     * @return mixed
     */
    function head($array)
    {
        return reset($array);
    }
}

if (! function_exists('last')) {
    /**
     * Get the last element from an array.
     *
     * @param  array  $array
     * @return mixed
     */
    function last($array)
    {
        return end($array);
    }
}

if (! function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param  mixed  $value
     * @param  mixed  ...$args
     * @return mixed
     */
    function value($value, ...$args)
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}

if (! function_exists('data_get')) {
    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param  mixed  $target
     * @param  string|array|int|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    function data_get($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        foreach ($key as $i => $segment) {
            unset($key[$i]);

            if (is_null($segment)) {
                return $target;
            }

            if ($segment === '*') {
                if (! is_iterable($target)) {
                    return value($default);
                }

                $result = [];

                foreach ($target as $item) {
                    $result[] = data_get($item, $key);
                }

                return in_array('*', $key) ? Arr::collapse($result) : $result;
            }

            $segment = match ($segment) {
                '\*' => '*',
                '\{first}' => '{first}',
                '{first}' => array_key_first($target),
                '\{last}' => '{last}',
                '{last}' => array_key_last($target),
                default => $segment,
            };

            if (Arr::accessible($target) && Arr::exists($target, $segment)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return value($default);
            }
        }

        return $target;
    }
}

if (!function_exists('dd')) {
    function dd(mixed ...$vars): never
    {
        if (!\in_array(\PHP_SAPI, ['cli', 'phpdbg', 'embed'], true) && !headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
        }

        if (!$vars) {
            VarDumper::dump(new ScalarStub('🐛'));

            exit(1);
        }

        if (array_key_exists(0, $vars) && 1 === count($vars)) {
            VarDumper::dump($vars[0]);
        } else {
            foreach ($vars as $k => $v) {
                VarDumper::dump($v, is_int($k) ? 1 + $k : $k);
            }
        }

        exit(1);
    }
}

if (! function_exists('view')) {
    function view($view = null, $data = [], $mergeData = [])
    {
        return new Response(application()->getTwig()->render($view, $data), RESPONSE::HTTP_OK);
    }
}

if (! function_exists('application')) {
    function application(): App
    {
        return App::get();
    }
}

if (! function_exists('env')) {
    function env(string $environmentParameter, $default = null): int|string|null
    {
        if (null === $env = $_ENV[$environmentParameter] ?? null && null === $default) {
            throw new MissingEnvironmentVariableException(
                ExceptionCase::MISSING_ENVIRONMENT_VARIABLE_500,
                $environmentParameter . ' missing from environment file'
            );
        }

        if (null === $env) {
            return $default;
        }

        return is_numeric($env) ? intval($env) : $env;
    }
}

if (! function_exists('config')) {
    function config(string $configName, string $key): int|string|array|null
    {
        $configAbsPath = $_SERVER['DOCUMENT_ROOT'] . '/../config/';
        $configFileOnDemand = $configAbsPath . $configName . '.php';

        if (file_exists($configFileOnDemand)) {
            $configData = require_once $configFileOnDemand;
            return data_get($configData, $key);
        } else {
            throw new Exception('Configuration file does not exist.');
        }
    }
}
