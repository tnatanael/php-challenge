<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Services\Interfaces\HttpClientInterface;
use App\Services\StooqApiService;
use PHPUnit\Framework\TestCase;
use Tests\Factories\StockFactory;

class StooqApiServiceTest extends TestCase
{
    private StooqApiService $stooqApiService;
    private HttpClientInterface $httpClient;
    private StockFactory $stockFactory;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->stockFactory = StockFactory::new();
        
        // Create mock for HttpClientInterface
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        
        // Create StooqApiService with mocked dependencies
        $this->stooqApiService = new StooqApiService($this->httpClient);
    }

    public function testFetchStockDataReturnsFormattedDataForValidSymbol(): void
    {
        // Arrange
        $symbol = StockFactory::APPLE_SYMBOL;
        $csvData = $this->stockFactory->toCsv();
        $expectedData = $this->stockFactory->make();
        
        $this->httpClient->expects($this->once())
            ->method('get')
            ->with($this->stringContains($symbol))
            ->willReturn($csvData);
        
        // Act
        $result = $this->stooqApiService->fetchStockData($symbol);
        
        // Assert
        $this->assertEquals($expectedData['symbol'], $result['symbol']);
        $this->assertEquals($expectedData['name'], $result['name']);
        $this->assertEquals($expectedData['open'], $result['open']);
        $this->assertEquals($expectedData['high'], $result['high']);
        $this->assertEquals($expectedData['low'], $result['low']);
        $this->assertEquals($expectedData['close'], $result['close']);
    }
    
    public function testFetchStockDataReturnsNullForInvalidSymbol(): void
    {
        // Arrange
        $symbol = StockFactory::INVALID_SYMBOL;
        $csvData = $this->stockFactory->toInvalidCsv();
        
        $this->httpClient->expects($this->once())
            ->method('get')
            ->with($this->stringContains($symbol))
            ->willReturn($csvData);
        
        // Act
        $result = $this->stooqApiService->fetchStockData($symbol);
        
        // Assert
        $this->assertNull($result);
    }
    
    public function testFetchStockDataReturnsNullWhenHttpClientReturnsNull(): void
    {
        // Arrange
        $symbol = StockFactory::APPLE_SYMBOL;
        
        $this->httpClient->expects($this->once())
            ->method('get')
            ->with($this->stringContains($symbol))
            ->willReturn(null);
        
        // Act
        $result = $this->stooqApiService->fetchStockData($symbol);
        
        // Assert
        $this->assertNull($result);
    }
    
    public function testFetchStockDataReturnsNullForInvalidCsvFormat(): void
    {
        // Arrange
        $symbol = StockFactory::APPLE_SYMBOL;
        $invalidCsv = "Invalid,CSV,Format\nWithout,Proper,Headers";
        
        $this->httpClient->expects($this->once())
            ->method('get')
            ->with($this->stringContains($symbol))
            ->willReturn($invalidCsv);
        
        // Act
        $result = $this->stooqApiService->fetchStockData($symbol);
        
        // Assert
        $this->assertNull($result);
    }
}