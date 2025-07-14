<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

interface NotificationServiceInterface
{
    /**
     * Send notification
     *
     * @param string $recipient
     * @param string $subject
     * @param array $data
     * @return bool
     */
    public function send(string $recipient, string $subject, array $data): bool;
}