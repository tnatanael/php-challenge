<?php

declare(strict_types=1);

namespace Tests;

use App\Models\StockQuery;
use App\Models\User;
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
    
        // Create a mock of the StockApiService
        $stockApiServiceMock = $this->createMock(\App\Services\StockApiService::class);
    
        // Configure the mock
        $stockApiServiceMock->method('fetchFromApi')
            ->will($this->returnCallback(function($url) {
                $stockFactory = StockFactory::new();
    
                if (strpos($url, 's=' . StockFactory::INVALID_SYMBOL) !== false) {
                    return $stockFactory->toInvalidCsv();
                }
    
                return $stockFactory->toCsv();
            }));
    
        // Replace the service in the container
        $container = $this->app->getContainer();
        /** @var \DI\Container $container */
        $container->set(\App\Services\StockApiService::class, $stockApiServiceMock);
    
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
     * Test that email template is rendered correctly
     */
    public function testRenderEmailTemplate(): void
    {
        // Arrange
        $container = $this->getAppInstance()->getContainer();
        $stockController = $container->get(\App\Controllers\StockController::class);
        
        // Use reflection to access the private method
        $reflectionMethod = new \ReflectionMethod(\App\Controllers\StockController::class, 'renderEmailTemplate');
        $reflectionMethod->setAccessible(true);
        
        $stockData = $this->stockFactory->getEmailTemplateData();
        
        // Act
        $emailContent = $reflectionMethod->invoke($stockController, $stockData);
        
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