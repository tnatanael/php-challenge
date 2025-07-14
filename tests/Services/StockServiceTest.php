<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Models\StockQuery;
use App\Repositories\StockQueryRepository;
use App\Services\Interfaces\StockApiServiceInterface;
use App\Services\StockService;
use Faker\Factory;
use PHPUnit\Framework\TestCase;

class StockServiceTest extends TestCase
{
    private StockService $stockService;
    
    /** @var StockApiServiceInterface&\PHPUnit\Framework\MockObject\MockObject */
    private StockApiServiceInterface $stockApiService;
    
    /** @var StockQueryRepository&\PHPUnit\Framework\MockObject\MockObject */
    private StockQueryRepository $stockQueryRepository;
    
    private $faker;
    
    // Define constants for stock symbols
    private const APPLE_SYMBOL = 'AAPL.US';
    private const INVALID_SYMBOL = 'INVALID';

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->faker = Factory::create();
        
        // Create mock for StockApiServiceInterface
        /** @var StockApiServiceInterface&\PHPUnit\Framework\MockObject\MockObject $stockApiServiceMock */
        $stockApiServiceMock = $this->createMock(StockApiServiceInterface::class);
        $this->stockApiService = $stockApiServiceMock;
        
        // Create mock for StockQueryRepository
        /** @var StockQueryRepository&\PHPUnit\Framework\MockObject\MockObject $stockQueryRepositoryMock */
        $stockQueryRepositoryMock = $this->createMock(StockQueryRepository::class);
        $this->stockQueryRepository = $stockQueryRepositoryMock;
        
        // Create StockService with mocked dependencies
        $this->stockService = new StockService(
            $this->stockApiService,
            $this->stockQueryRepository
        );
    }

    public function testGetStockDataReturnsDataFromApi(): void
    {
        // Arrange
        $symbol = self::APPLE_SYMBOL;
        $expectedData = $this->generateAppleStockData();
        
        $this->stockApiService->expects($this->once())
            ->method('fetchStockData')
            ->with($symbol)
            ->willReturn($expectedData);
        
        // Act
        $result = $this->stockService->getStockData($symbol);
        
        // Assert
        $this->assertSame($expectedData, $result);
    }
    
    public function testGetStockDataReturnsNullWhenApiReturnsNull(): void
    {
        // Arrange
        $symbol = self::INVALID_SYMBOL;
        
        $this->stockApiService->expects($this->once())
            ->method('fetchStockData')
            ->with($symbol)
            ->willReturn(null);
        
        // Act
        $result = $this->stockService->getStockData($symbol);
        
        // Assert
        $this->assertNull($result);
    }
    
    public function testSaveStockQueryCreatesStockQueryRecord(): void
    {
        // Arrange
        $userId = $this->faker->numberBetween(1, 100);
        $stockData = $this->generateRandomStockData();
        $expectedStockQuery = new StockQuery();
        
        // Set properties on the mock StockQuery
        $expectedStockQuery->user_id = $userId;
        $expectedStockQuery->symbol = $stockData['symbol'];
        $expectedStockQuery->name = $stockData['name'];
        $expectedStockQuery->open = $stockData['open'];
        $expectedStockQuery->high = $stockData['high'];
        $expectedStockQuery->low = $stockData['low'];
        $expectedStockQuery->close = $stockData['close'];
        
        $this->stockQueryRepository->expects($this->once())
            ->method('create')
            ->with([
                'user_id' => $userId,
                'symbol' => $stockData['symbol'],
                'name' => $stockData['name'],
                'open' => $stockData['open'],
                'high' => $stockData['high'],
                'low' => $stockData['low'],
                'close' => $stockData['close']
            ])
            ->willReturn($expectedStockQuery);
        
        // Act
        $result = $this->stockService->saveStockQuery($userId, $stockData);
        
        // Assert
        $this->assertSame($expectedStockQuery, $result);
    }
    
    public function testGetUserHistoryReturnsHistoryFromRepository(): void
    {
        // Arrange
        $userId = $this->faker->numberBetween(1, 100);
        $expectedHistory = [
            $this->generateRandomStockData(),
            $this->generateMicrosoftStockData()
        ];
        
        $this->stockQueryRepository->expects($this->once())
            ->method('getUserHistory')
            ->with($userId)
            ->willReturn($expectedHistory);
        
        // Act
        $result = $this->stockService->getUserStockHistory($userId);
        
        // Assert
        $this->assertSame($expectedHistory, $result);
    }
    
    /**
     * Generate random stock data
     *
     * @return array
     */
    private function generateRandomStockData(): array
    {
        return [
            'symbol' => $this->faker->randomElement(['AAPL.US', 'AMZN.US', 'GOOGL.US', 'TSLA.US']),
            'name' => $this->faker->company(),
            'open' => $this->faker->randomFloat(2, 100, 1000),
            'high' => $this->faker->randomFloat(2, 100, 1000),
            'low' => $this->faker->randomFloat(2, 100, 1000),
            'close' => $this->faker->randomFloat(2, 100, 1000),
        ];
    }
    
    /**
     * Generate Apple stock data
     *
     * @return array
     */
    private function generateAppleStockData(): array
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
     * Generate Microsoft stock data
     *
     * @return array
     */
    private function generateMicrosoftStockData(): array
    {
        return [
            'symbol' => 'MSFT.US',
            'name' => 'MICROSOFT',
            'open' => $this->faker->randomFloat(2, 200, 400),
            'high' => $this->faker->randomFloat(2, 200, 400),
            'low' => $this->faker->randomFloat(2, 200, 400),
            'close' => $this->faker->randomFloat(2, 200, 400),
        ];
    }
}