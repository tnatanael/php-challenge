<?php

declare(strict_types=1);

namespace Tests;

use App\Models\StockQuery;
use App\Models\User;
use Slim\Exception\HttpUnauthorizedException;

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
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->app = $this->getAppInstance();
        
        // Clean up test stock queries from previous test runs
        StockQuery::where('symbol', 'AAPL.US')->delete();
        StockQuery::where('symbol', 'MSFT.US')->delete();
        
        // Set up function mocking
        $this->setUpFunctionMocks();
    }

    /**
     * Set up function mocks for the entire test class
     */
    private function setUpFunctionMocks(): void
    {
        if (self::$functionsMocked) return;
    
        // Mock for App\Controllers namespace
        eval('namespace App\\Controllers;');
        function file_get_contents($url) {
            if (strpos($url, 's=INVALID') !== false) {
                return "Symbol,Name,Open,High,Low,Close\nN/D,N/D,N/D,N/D,N/D,N/D";
            }
            return "Symbol,Name,Open,High,Low,Close\nAAPL.US,APPLE INC,150.25,152.43,149.92,151.60";
        }
        
        eval('namespace Tests;');
        self::$functionsMocked = true;
    }

    /**
     * Test that accessing stock endpoint without JWT token throws unauthorized exception
     */
    public function testGetStockThrowsUnauthorized(): void
    {
        // Arrange
        $request = $this->createRequest('GET', '/stock?q=AAPL.US');

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
        $request = $this->createRequest('GET', '/stock?q=AAPL.US', $headers);

        // Act
        $response = $this->app->handle($request);
        $payload = json_decode((string)$response->getBody(), true);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertEquals('Stock quote retrieved successfully', $payload['message']);
        
        // Verify the stock data in the response
        $stockData = $payload['data'];
        $this->assertEquals('AAPL.US', $stockData['symbol']);
        $this->assertEquals('APPLE INC', $stockData['name']);
        $this->assertIsFloat($stockData['open']);
        $this->assertIsFloat($stockData['high']);
        $this->assertIsFloat($stockData['low']);
        $this->assertIsFloat($stockData['close']);
        
        // Verify that the stock query was saved to the database
        $stockQuery = StockQuery::where('symbol', 'AAPL.US')->first();
        $this->assertNotNull($stockQuery);
        $this->assertEquals('AAPL.US', $stockQuery->symbol);
        $this->assertEquals('APPLE INC', $stockQuery->name);
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
        $request = $this->createRequest('GET', '/stock?q=INVALID', $headers);

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
        $this->assertEquals('AAPL.US', $firstQuery['symbol']);
        $this->assertEquals('APPLE INC', $firstQuery['name']);
    }
}