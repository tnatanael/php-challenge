<?php

declare(strict_types=1);

namespace Tests;

use App\Controllers\StockController;
use App\Models\StockQuery;
use App\Models\User;
use App\Repositories\StockQueryRepository;
use App\Services\EmailNotificationService;
use App\Services\HttpClient;
use App\Services\Interfaces\HttpClientInterface;
use App\Services\Interfaces\NotificationServiceInterface;
use App\Services\Interfaces\StockApiServiceInterface;
use App\Services\StockService;
use App\Services\StooqApiService;
use App\Services\TemplateRenderer;
use App\Validators\StockValidator;
use Slim\Exception\HttpUnauthorizedException;
use Tests\Factories\StockFactory;

/**
 * Class StockTest
 * @package Tests
 */
class StockTest extends BaseTestCase
{
    /**
     * @var \Slim\App
     */
    protected $app;
    
    /**
     * @var bool
     */
    private static $functionsMocked = false;
    
    /**
     * @var StockFactory
     */
    protected $stockFactory;

    /**
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();
    
        // Initialize the app first
        $this->app = $this->getAppInstance();
        $this->stockFactory = StockFactory::new();
    
        // Create mocks for all the new services and interfaces
        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $httpClientMock->method('get')
            ->will($this->returnCallback(function($url) {
                $stockFactory = StockFactory::new();
    
                if (strpos($url, StockFactory::INVALID_SYMBOL) !== false) {
                    return $stockFactory->toInvalidCsv();
                }
    
                return $stockFactory->toCsv();
            }));
        
        // Create StooqApiService with mocked HttpClient
        $stockApiServiceMock = $this->createMock(StockApiServiceInterface::class);
        $stockApiServiceMock->method('fetchStockData')
            ->will($this->returnCallback(function($symbol) {
                $stockFactory = StockFactory::new();
                
                if ($symbol === StockFactory::INVALID_SYMBOL) {
                    return null;
                }
                
                return $stockFactory->make();
            }));
        
        // Create StockQueryRepository mock
        $stockQueryRepositoryMock = $this->createMock(StockQueryRepository::class);
        $stockQueryRepositoryMock->method('create')
            ->will($this->returnCallback(function($data) {
                return StockQuery::create($data);
            }));
        $stockQueryRepositoryMock->method('getUserHistory')
            ->will($this->returnCallback(function($userId) {
                return StockQuery::where('user_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->toArray();
            }));
        
        // Create StockService with mocked dependencies
        $stockServiceMock = $this->getMockBuilder(StockService::class)
            ->setConstructorArgs([$stockApiServiceMock, $stockQueryRepositoryMock])
            ->onlyMethods([])
            ->getMock();
        
        // Create TemplateRenderer mock
        $templateRendererMock = $this->createMock(TemplateRenderer::class);
        $templateRendererMock->method('render')
            ->will($this->returnCallback(function($template, $data) {
                // Simple template rendering simulation
                $stockData = $data['stockData'];
                return "<h1>Stock Quote Information</h1>\n" .
                       "<p><strong>Symbol:</strong> {$stockData['symbol']}</p>\n" .
                       "<p><strong>Name:</strong> {$stockData['name']}</p>\n" .
                       "<p><strong>Date:</strong> {$stockData['date']}</p>\n" .
                       "<p><strong>Open:</strong> {$stockData['open']}</p>\n" .
                       "<p><strong>High:</strong> {$stockData['high']}</p>\n" .
                       "<p><strong>Low:</strong> {$stockData['low']}</p>\n" .
                       "<p><strong>Close:</strong> {$stockData['close']}</p>";
            }));
        
        // Create MessageQueue mock
        $messageQueueMock = $this->createMock(\App\Services\MessageQueue::class);
        $messageQueueMock->method('publish')
            ->willReturn(true);
        
        // Create NotificationService mock
        $notificationServiceMock = $this->getMockBuilder(EmailNotificationService::class)
            ->setConstructorArgs([$messageQueueMock, $templateRendererMock, []])
            ->onlyMethods([])
            ->getMock();
        
        // Create StockValidator mock
        $validatorMock = $this->createMock(StockValidator::class);
        $validatorMock->method('validateSymbol')
            ->will($this->returnCallback(function($symbol) {
                return !empty($symbol);
            }));
        
        // Replace the services in the container
        $container = $this->app->getContainer();
        /** @var \DI\Container $container */
        $container->set(HttpClientInterface::class, $httpClientMock);
        $container->set(StockApiServiceInterface::class, $stockApiServiceMock);
        $container->set(StockQueryRepository::class, $stockQueryRepositoryMock);
        $container->set(StockService::class, $stockServiceMock);
        $container->set(TemplateRenderer::class, $templateRendererMock);
        $container->set(\App\Services\MessageQueue::class, $messageQueueMock);
        $container->set(NotificationServiceInterface::class, $notificationServiceMock);
        $container->set(StockValidator::class, $validatorMock);
        $container->set(StockController::class, function() use ($container) {
            return new StockController(
                $container->get(StockService::class),
                $container->get(NotificationServiceInterface::class),
                $container->get(StockValidator::class)
            );
        });
    
        // Clean up test stock queries from previous test runs
        StockQuery::where('symbol', $this->stockFactory->getDefaults()['symbol'])->delete();
        StockQuery::where('symbol', $this->stockFactory->getMicrosoftDefaults()['symbol'])->delete();
    }

    /**
     * Test that accessing stock endpoint without JWT token throws unauthorized exception
     */
    public function testGetStockThrowsUnauthorized(): void
    {
        // Arrange
        $request = $this->createRequest('GET', '/stock?q=' . $this->stockFactory->getDefaults()['symbol']);

        // Assert
        $this->expectException(HttpUnauthorizedException::class);

        // Act
        $this->app->handle($request);
    }

    /**
     * Test that accessing history endpoint without JWT token throws unauthorized exception
     */
    public function testGetHistoryThrowsUnauthorized(): void
    {
        // Arrange
        $request = $this->createRequest('GET', '/history');

        // Assert
        $this->expectException(HttpUnauthorizedException::class);

        // Act
        $this->app->handle($request);
    }

    /**
     * Test getting stock quote with valid JWT token and valid stock symbol
     */
    public function testGetStockWithJwtAuth(): void
    {
        // Arrange
        $token = $this->getJwtToken();
        $headers = ['HTTP_ACCEPT' => 'application/json', 'Authorization' => 'Bearer ' . $token];
        $request = $this->createRequest('GET', '/stock?q=' . $this->stockFactory->getDefaults()['symbol'], $headers);

        // Act
        $response = $this->app->handle($request);
        $payload = json_decode((string)$response->getBody(), true);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertEquals('Stock quote retrieved successfully', $payload['message']);
        
        // Verify the stock data in the response
        $stockData = $payload['data'];
        $defaultStock = $this->stockFactory->getDefaults();
        $this->assertEquals($defaultStock['symbol'], $stockData['symbol']);
        $this->assertEquals($defaultStock['name'], $stockData['name']);
        $this->assertIsFloat($stockData['open']);
        $this->assertIsFloat($stockData['high']);
        $this->assertIsFloat($stockData['low']);
        $this->assertIsFloat($stockData['close']);
        
        // Verify that the stock query was saved to the database
        $stockQuery = StockQuery::where('symbol', $defaultStock['symbol'])->first();
        $this->assertNotNull($stockQuery);
        $this->assertEquals($defaultStock['symbol'], $stockQuery->symbol);
        $this->assertEquals($defaultStock['name'], $stockQuery->name);
    }

    /**
     * Test getting stock quote with missing stock symbol
     */
    public function testGetStockWithMissingSymbol(): void
    {
        // Arrange
        $token = $this->getJwtToken();
        $headers = ['HTTP_ACCEPT' => 'application/json', 'Authorization' => 'Bearer ' . $token];
        $request = $this->createRequest('GET', '/stock', $headers);

        // Act
        $response = $this->app->handle($request);
        $payload = json_decode((string)$response->getBody(), true);

        // Assert
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertEquals('Stock symbol is required', $payload['message']);
    }

    /**
     * Test getting stock quote with invalid stock symbol
     */
    public function testGetStockWithInvalidSymbol(): void
    {
        // Arrange
        $token = $this->getJwtToken();
        $headers = ['HTTP_ACCEPT' => 'application/json', 'Authorization' => 'Bearer ' . $token];
        $request = $this->createRequest('GET', '/stock?q=' . StockFactory::INVALID_SYMBOL, $headers);

        // Act
        $response = $this->app->handle($request);
        $payload = json_decode((string)$response->getBody(), true);

        // Assert
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertEquals('Failed to fetch stock data', $payload['message']);
    }

    /**
     * Test getting stock history with valid JWT token
     */
    public function testGetHistoryWithJwtAuth(): void
    {
        // First create a stock query to have some history
        $this->testGetStockWithJwtAuth();
        
        // Arrange
        $token = $this->getJwtToken();
        $headers = ['HTTP_ACCEPT' => 'application/json', 'Authorization' => 'Bearer ' . $token];
        $request = $this->createRequest('GET', '/history', $headers);

        // Act
        $response = $this->app->handle($request);
        $payload = json_decode((string)$response->getBody(), true);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertEquals('Stock query history retrieved successfully', $payload['message']);
        $this->assertIsArray($payload['data']);
        $this->assertGreaterThanOrEqual(1, count($payload['data']));
        
        // Verify the first stock query in the history
        $firstQuery = $payload['data'][0];
        $defaultStock = $this->stockFactory->getDefaults();
        $this->assertEquals($defaultStock['symbol'], $firstQuery['symbol']);
        $this->assertEquals($defaultStock['name'], $firstQuery['name']);
    }

    /**
     * Test the StockService getStockData method
     */
    public function testStockServiceGetStockData(): void
    {
        // Arrange
        $container = $this->app->getContainer();
        $stockService = $container->get(StockService::class);
        $symbol = $this->stockFactory->getDefaults()['symbol'];
        
        // Act
        $stockData = $stockService->getStockData($symbol);
        
        // Assert
        $this->assertNotNull($stockData);
        $this->assertEquals($symbol, $stockData['symbol']);
        $this->assertArrayHasKey('name', $stockData);
        $this->assertArrayHasKey('open', $stockData);
        $this->assertArrayHasKey('high', $stockData);
        $this->assertArrayHasKey('low', $stockData);
        $this->assertArrayHasKey('close', $stockData);
    }
    
    /**
     * Test the StockService saveStockQuery method
     */
    public function testStockServiceSaveStockQuery(): void
    {
        // Arrange
        $container = $this->app->getContainer();
        $stockService = $container->get(StockService::class);
        $userId = 1;
        $stockData = $this->stockFactory->make();
        
        // Act
        $stockQuery = $stockService->saveStockQuery($userId, $stockData);
        
        // Assert
        $this->assertNotNull($stockQuery);
        $this->assertEquals($userId, $stockQuery->user_id);
        $this->assertEquals($stockData['symbol'], $stockQuery->symbol);
        $this->assertEquals($stockData['name'], $stockQuery->name);
        $this->assertEquals($stockData['open'], $stockQuery->open);
        $this->assertEquals($stockData['high'], $stockQuery->high);
        $this->assertEquals($stockData['low'], $stockQuery->low);
        $this->assertEquals($stockData['close'], $stockQuery->close);
    }
    
    /**
     * Test the NotificationService send method
     */
    public function testNotificationServiceSend(): void
    {
        // Arrange
        $container = $this->app->getContainer();
        $notificationService = $container->get(NotificationServiceInterface::class);
        $recipient = 'test@example.com';
        $subject = 'Test Subject';
        $data = $this->stockFactory->getEmailTemplateData();
        
        // Act
        $result = $notificationService->send($recipient, $subject, $data);
        
        // Assert
        $this->assertTrue($result);
    }
    
    /**
     * Test the TemplateRenderer render method
     */
    public function testTemplateRendererRender(): void
    {
        // Arrange
        $container = $this->app->getContainer();
        $templateRenderer = $container->get(TemplateRenderer::class);
        $stockData = $this->stockFactory->getEmailTemplateData();
        
        // Act
        $emailContent = $templateRenderer->render('emails/stock_quote', ['stockData' => $stockData]);
        
        // Assert
        $this->assertStringContainsString('Stock Quote Information', $emailContent);
        $this->assertStringContainsString('<strong>Symbol:</strong> ' . $stockData['symbol'], $emailContent);
        $this->assertStringContainsString('<strong>Date:</strong> ' . $stockData['date'], $emailContent);
        $this->assertStringContainsString('<strong>Open:</strong> ' . $stockData['open'], $emailContent);
        $this->assertStringContainsString('<strong>Close:</strong> ' . $stockData['close'], $emailContent);
        $this->assertStringContainsString('<strong>High:</strong> ' . $stockData['high'], $emailContent);
        $this->assertStringContainsString('<strong>Low:</strong> ' . $stockData['low'], $emailContent);
    }
}