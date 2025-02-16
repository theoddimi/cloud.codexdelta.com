<?php

namespace Codexdelta\Libs\Http;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

class CdxSession extends Session
{
    public static function init(): CdxSession
    {
        $sessionStorage = new NativeSessionStorage();
        $session = new CdxSession($sessionStorage);
        $session->start();

        return $session;
    }

    public function setFlashMessage(string $key, string $message): void
    {
        $this->getFlashBag()->set($key, $message);
    }

    public function getFlashMessage(string $name): array
    {
        return $this->getFlashBag()->get($name);
    }
}