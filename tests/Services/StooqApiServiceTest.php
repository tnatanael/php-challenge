<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Services\Interfaces\HttpClientInterface;
use App\Services\StooqApiService;
use Faker\Factory;
use PHPUnit\Framework\TestCase;

class StooqApiServiceTest extends TestCase
{
    private StooqApiService $stooqApiService;
    
    /** @var HttpClientInterface&\PHPUnit\Framework\MockObject\MockObject */
    private HttpClientInterface $httpClient;
    
    private $faker;
    
    // Define constants for stock symbols
    private const APPLE_SYMBOL = 'AAPL.US';
    private const INVALID_SYMBOL = 'INVALID';

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->faker = Factory::create();
        
        // Create mock for HttpClientInterface
        /** @var HttpClientInterface&\PHPUnit\Framework\MockObject\MockObject $httpClientMock */
        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->httpClient = $httpClientMock;
        
        // Create StooqApiService with mocked dependencies
        $this->stooqApiService = new StooqApiService($this->httpClient);
    }

    public function testFetchStockDataReturnsFormattedDataForValidSymbol(): void
    {
        // Arrange
        $symbol = self::APPLE_SYMBOL;
        $stockData = $this->generateStockData(); // Generate stock data first
        $csvData = $this->generateStockCsvFromData($stockData); // Use the same data for CSV
        
        $this->httpClient->expects($this->once())
            ->method('get')
            ->with($this->stringContains($symbol))
            ->willReturn($csvData);
        
        // Act
        $result = $this->stooqApiService->fetchStockData($symbol);
        
        // Assert
        $this->assertEquals($stockData['symbol'], $result['symbol']);
        $this->assertEquals($stockData['name'], $result['name']);
        $this->assertEquals($stockData['open'], $result['open']);
        $this->assertEquals($stockData['high'], $result['high']);
        $this->assertEquals($stockData['low'], $result['low']);
        $this->assertEquals($stockData['close'], $result['close']);
    }
    
    public function testFetchStockDataReturnsNullForInvalidSymbol(): void
    {
        // Arrange
        $symbol = self::INVALID_SYMBOL;
        $csvData = $this->generateInvalidStockCsv();
        
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
        $symbol = self::APPLE_SYMBOL;
        
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
        $symbol = self::APPLE_SYMBOL;
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
    
    public function testFetchStockDataReturnsNullForEmptyCsv(): void
    {
        // Arrange
        $symbol = self::APPLE_SYMBOL;
        $emptyCsv = "Symbol,Name,Date,Time,Open,High,Low,Close,Volume,Name"; // Only header, no data line
        
        $this->httpClient->expects($this->once())
            ->method('get')
            ->with($this->stringContains($symbol))
            ->willReturn($emptyCsv);
        
        // Act
        $result = $this->stooqApiService->fetchStockData($symbol);
        
        // Assert
        $this->assertNull($result);
    }
    
    /**
     * Generate stock data array
     *
     * @return array
     */
    private function generateStockData(): array
    {
        return [
            'symbol' => self::APPLE_SYMBOL,
            'name' => 'APPLE',
            'open' => $this->faker->randomFloat(2, 100, 200),
            'high' => $this->faker->randomFloat(2, 100, 200),
            'low' => $this->faker->randomFloat(2, 100, 200),
            'close' => $this->faker->randomFloat(2, 100, 200),
        ];
    }
    
    /**
     * Generate CSV data for valid stock using provided stock data
     *
     * @param array $stockData
     * @return string
     */
    private function generateStockCsvFromData(array $stockData): string
    {
        // Create CSV header and data rows
        $header = "Symbol,Name,Date,Time,Open,High,Low,Close,Volume,Name";
        $data = sprintf(
            "%s,%s,%s,%s,%.2f,%.2f,%.2f,%.2f,%d,%s",
            $stockData['symbol'],
            $stockData['name'],
            $this->faker->date('Y-m-d'),
            $this->faker->time('H:i:s'),
            $stockData['open'],
            $stockData['high'],
            $stockData['low'],
            $stockData['close'],
            $this->faker->numberBetween(1000, 1000000),
            $stockData['name']
        );
        
        return $header . "\n" . $data;
    }
    
    /**
     * Generate CSV data for valid stock
     * 
     * @deprecated Use generateStockCsvFromData instead
     * @return string
     */
    private function generateStockCsv(): string
    {
        $stockData = $this->generateStockData();
        return $this->generateStockCsvFromData($stockData);
    }
    
    /**
     * Generate CSV data for invalid stock
     *
     * @return string
     */
    private function generateInvalidStockCsv(): string
    {
        // Create CSV header and data rows with N/D values
        $header = "Symbol,Name,Date,Time,Open,High,Low,Close,Volume,Name";
        $data = sprintf(
            "%s,%s,%s,%s,N/D,N/D,N/D,N/D,%d,%s",
            self::INVALID_SYMBOL,
            "INVALID",
            $this->faker->date('Y-m-d'),
            $this->faker->time('H:i:s'),
            $this->faker->numberBetween(1000, 1000000),
            "INVALID"
        );
        
        return $header . "\n" . $data;
    }
}