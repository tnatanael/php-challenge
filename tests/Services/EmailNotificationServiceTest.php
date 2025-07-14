<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Services\EmailNotificationService;
use App\Services\MessageQueue;
use App\Services\TemplateRenderer;
use PHPUnit\Framework\TestCase;
use Tests\Factories\StockFactory;

class EmailNotificationServiceTest extends TestCase
{
    private EmailNotificationService $emailNotificationService;
    private MessageQueue $messageQueue;
    private TemplateRenderer $templateRenderer;
    private StockFactory $stockFactory;
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->stockFactory = StockFactory::new();
        
        // Create mock for MessageQueue
        $this->messageQueue = $this->createMock(MessageQueue::class);
        
        // Create mock for TemplateRenderer
        $this->templateRenderer = $this->createMock(TemplateRenderer::class);
        
        // Set up config
        $this->config = [
            'from_email' => 'test@example.com',
            'from_name' => 'Test Sender'
        ];
        
        // Create EmailNotificationService with mocked dependencies
        $this->emailNotificationService = new EmailNotificationService(
            $this->messageQueue,
            $this->templateRenderer,
            $this->config
        );
    }

    public function testSendReturnsSuccessWhenMessageQueuePublishesSuccessfully(): void
    {
        // Arrange
        $recipient = 'recipient@example.com';
        $subject = 'Stock Quote';
        $data = $this->stockFactory->getEmailTemplateData();
        $renderedTemplate = '<html>Rendered Email Template</html>';
        
        $this->templateRenderer->expects($this->once())
            ->method('render')
            ->with('emails/stock_quote', ['stockData' => $data])
            ->willReturn($renderedTemplate);
        
        $expectedEmailData = [
            'to' => $recipient,
            'subject' => $subject,
            'body' => $renderedTemplate,
            'from_email' => $this->config['from_email'],
            'from_name' => $this->config['from_name']
        ];
        
        $this->messageQueue->expects($this->once())
            ->method('publish')
            ->with('email_queue', $expectedEmailData)
            ->willReturn(true);
        
        // Act
        $result = $this->emailNotificationService->send($recipient, $subject, $data);
        
        // Assert
        $this->assertTrue($result);
    }
    
    public function testSendReturnsFailureWhenMessageQueuePublishFails(): void
    {
        // Arrange
        $recipient = 'recipient@example.com';
        $subject = 'Stock Quote';
        $data = $this->stockFactory->getEmailTemplateData();
        $renderedTemplate = '<html>Rendered Email Template</html>';
        
        $this->templateRenderer->expects($this->once())
            ->method('render')
            ->with('emails/stock_quote', ['stockData' => $data])
            ->willReturn($renderedTemplate);
        
        $this->messageQueue->expects($this->once())
            ->method('publish')
            ->willReturn(false);
        
        // Act
        $result = $this->emailNotificationService->send($recipient, $subject, $data);
        
        // Assert
        $this->assertFalse($result);
    }
    
    public function testSendUsesDefaultConfigValuesWhenNotProvided(): void
    {
        // Arrange
        $recipient = 'recipient@example.com';
        $subject = 'Stock Quote';
        $data = $this->stockFactory->getEmailTemplateData();
        $renderedTemplate = '<html>Rendered Email Template</html>';
        
        // Create service with empty config
        $serviceWithEmptyConfig = new EmailNotificationService(
            $this->messageQueue,
            $this->templateRenderer,
            []
        );
        
        $this->templateRenderer->expects($this->once())
            ->method('render')
            ->with('emails/stock_quote', ['stockData' => $data])
            ->willReturn($renderedTemplate);
        
        $expectedEmailData = [
            'to' => $recipient,
            'subject' => $subject,
            'body' => $renderedTemplate,
            'from_email' => 'stock-api@example.com', // Default value
            'from_name' => 'Stock API' // Default value
        ];
        
        $this->messageQueue->expects($this->once())
            ->method('publish')
            ->with('email_queue', $expectedEmailData)
            ->willReturn(true);
        
        // Act
        $result = $serviceWithEmptyConfig->send($recipient, $subject, $data);
        
        // Assert
        $this->assertTrue($result);
    }
}