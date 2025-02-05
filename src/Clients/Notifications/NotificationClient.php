<?php

namespace Codexdelta\App\Clients\Notifications;

use Codexdelta\App\Clients\Contracts\NotificationClientContract;
use Codexdelta\App\Enums\NotificationType;
use PHPMailer\PHPMailer\Exception;

class NotificationClient implements NotificationClientContract
{
    protected mixed $client;

    /**
     * @throws Exception
     */
    public function resolve(NotificationType $notificationType): NotificationClientContract
    {
        $this->client = match ($notificationType) {
            default => (new MailerClient)->setup(),
        };

        return $this->client;
    }

    public function setup()
    {}
    public function send(string $subject, string $message)
    {}
}