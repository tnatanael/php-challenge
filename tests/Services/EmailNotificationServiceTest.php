<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Services\EmailNotificationService;
use App\Services\MessageQueue;
use App\Services\TemplateRenderer;
use Faker\Factory;
use PHPUnit\Framework\TestCase;

class EmailNotificationServiceTest extends TestCase
{
    /** @var EmailNotificationService */
    private EmailNotificationService $emailNotificationService;
    
    /** @var MessageQueue&\PHPUnit\Framework\MockObject\MockObject */
    private MessageQueue $messageQueue;
    
    /** @var TemplateRenderer&\PHPUnit\Framework\MockObject\MockObject */
    private TemplateRenderer $templateRenderer;
    
    private $faker;
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->faker = Factory::create();
        
        // Create mock for MessageQueue
        /** @var MessageQueue&\PHPUnit\Framework\MockObject\MockObject $messageQueueMock */
        $messageQueueMock = $this->createMock(MessageQueue::class);
        $this->messageQueue = $messageQueueMock;
        
        // Create mock for TemplateRenderer
        /** @var TemplateRenderer&\PHPUnit\Framework\MockObject\MockObject $templateRendererMock */
        $templateRendererMock = $this->createMock(TemplateRenderer::class);
        $this->templateRenderer = $templateRendererMock;
        
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
    
    /**
     * Generate stock data for email template
     *
     * @return array
     */
    private function getEmailTemplateData(): array
    {
        return [
            'symbol' => $this->faker->randomElement(['AAPL.US', 'MSFT.US', 'GOOGL.US', 'AMZN.US']),
            'name' => $this->faker->company(),
            'date' => $this->faker->date('Y-m-d'),
            'open' => $this->faker->randomFloat(2, 100, 1000),
            'high' => $this->faker->randomFloat(2, 100, 1000),
            'low' => $this->faker->randomFloat(2, 100, 1000),
            'close' => $this->faker->randomFloat(2, 100, 1000)
        ];
    }

    public function testSendReturnsSuccessWhenMessageQueuePublishesSuccessfully(): void
    {
        // Arrange
        $recipient = 'recipient@example.com';
        $subject = 'Stock Quote';
        $data = $this->getEmailTemplateData();
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
        $data = $this->getEmailTemplateData();
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
        $data = $this->getEmailTemplateData();
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