<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Interfaces\NotificationServiceInterface;

class EmailNotificationService implements NotificationServiceInterface
{
    private MessageQueue $messageQueue;
    private TemplateRenderer $templateRenderer;
    private array $config;
    
    public function __construct(
        MessageQueue $messageQueue, 
        TemplateRenderer $templateRenderer,
        array $config
    ) {
        $this->messageQueue = $messageQueue;
        $this->templateRenderer = $templateRenderer;
        $this->config = $config;
    }
    
    /**
     * Send email notification
     *
     * @param string $recipient
     * @param string $subject
     * @param array $data
     * @return bool
     */
    public function send(string $recipient, string $subject, array $data): bool
    {
        $emailBody = $this->templateRenderer->render('emails/stock_quote', ['stockData' => $data]);
        
        $emailData = [
            'to' => $recipient,
            'subject' => $subject,
            'body' => $emailBody,
            'from_email' => $this->config['from_email'] ?? 'stock-api@example.com',
            'from_name' => $this->config['from_name'] ?? 'Stock API'
        ];
        
        return $this->messageQueue->publish('email_queue', $emailData);
    }
}