<?php

namespace Codexdelta\App\Clients\Contracts;

interface NotificationClientContract
{
    public function setup();
    public function send(string $subject, string $message);
}