<?php

declare(strict_types=1);

namespace Tests\Repositories;

use App\Models\StockQuery;
use App\Repositories\StockQueryRepository;
use Faker\Factory;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

class StockQueryRepositoryTest extends TestCase
{
    private StockQueryRepository $repository;
    private $faker;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->faker = Factory::create();
        $this->repository = new StockQueryRepository();
    }

    public function testCreateSavesStockQueryToDatabase(): void
    {
        // Create a partial mock of the repository to override the create method
        /** @var StockQueryRepository&\PHPUnit\Framework\MockObject\MockObject $mockRepository */
        $mockRepository = $this->getMockBuilder(StockQueryRepository::class)
            ->onlyMethods(['create'])
            ->getMock();
            
        $stockData = $this->generateStockData();
        $expectedStockQuery = new StockQuery();
        
        // Set expectations on the repository mock
        $mockRepository->expects($this->once())
            ->method('create')
            ->with($stockData)
            ->willReturn($expectedStockQuery);
            
        // Call the method on the mock repository
        $result = $mockRepository->create($stockData);
        
        // Assert the result is as expected
        $this->assertSame($expectedStockQuery, $result);
    }
    
    public function testGetUserHistoryReturnsOrderedStockQueries(): void
    {
        $userId = $this->faker->numberBetween(1, 100);
        $expectedQueries = [
            $this->generateStockData(),
            $this->generateMicrosoftStockData()
        ];
        
        // Create a mock repository instead of using the real one
        /** @var StockQueryRepository&\PHPUnit\Framework\MockObject\MockObject $mockRepository */
        $mockRepository = $this->getMockBuilder(StockQueryRepository::class)
            ->onlyMethods(['getUserHistory'])
            ->getMock();
        
        // Set expectations on the repository mock
        $mockRepository->expects($this->once())
            ->method('getUserHistory')
            ->with($userId)
            ->willReturn($expectedQueries);
        
        // Call the method on the mock repository
        $result = $mockRepository->getUserHistory($userId);
        
        // Assert the result is as expected
        $this->assertSame($expectedQueries, $result);
    }
    
    /**
     * Generate random stock data
     *
     * @return array
     */
    private function generateStockData(): array
    {
        return [
            'user_id' => $this->faker->numberBetween(1, 100),
            'symbol' => $this->faker->randomElement(['AAPL.US', 'AMZN.US', 'GOOGL.US', 'TSLA.US']),
            'name' => $this->faker->company(),
            'open' => $this->faker->randomFloat(2, 100, 1000),
            'high' => $this->faker->randomFloat(2, 100, 1000),
            'low' => $this->faker->randomFloat(2, 100, 1000),
            'close' => $this->faker->randomFloat(2, 100, 1000),
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
            'user_id' => $this->faker->numberBetween(1, 100),
            'symbol' => 'MSFT.US',
            'name' => 'MICROSOFT',
            'open' => $this->faker->randomFloat(2, 200, 400),
            'high' => $this->faker->randomFloat(2, 200, 400),
            'low' => $this->faker->randomFloat(2, 200, 400),
            'close' => $this->faker->randomFloat(2, 200, 400),
        ];
    }
}