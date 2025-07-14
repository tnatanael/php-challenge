<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Models\StockQuery;
use App\Repositories\StockQueryRepository;
use App\Services\Interfaces\StockApiServiceInterface;
use App\Services\StockService;
use PHPUnit\Framework\TestCase;
use Tests\Factories\StockFactory;

class StockServiceTest extends TestCase
{
    private StockService $stockService;
    private StockApiServiceInterface $stockApiService;
    private StockQueryRepository $stockQueryRepository;
    private StockFactory $stockFactory;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->stockFactory = StockFactory::new();
        
        // Create mock for StockApiServiceInterface
        $this->stockApiService = $this->createMock(StockApiServiceInterface::class);
        
        // Create mock for StockQueryRepository
        $this->stockQueryRepository = $this->createMock(StockQueryRepository::class);
        
        // Create StockService with mocked dependencies
        $this->stockService = new StockService(
            $this->stockApiService,
            $this->stockQueryRepository
        );
    }

    public function testGetStockDataReturnsDataFromApi(): void
    {
        // Arrange
        $symbol = StockFactory::APPLE_SYMBOL;
        $expectedData = $this->stockFactory->make();
        
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
        $symbol = StockFactory::INVALID_SYMBOL;
        
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
        $userId = 1;
        $stockData = $this->stockFactory->make();
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
        $userId = 1;
        $expectedHistory = [
            $this->stockFactory->make(),
            $this->stockFactory->makeMicrosoft()
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
}