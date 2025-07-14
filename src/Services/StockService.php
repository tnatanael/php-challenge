<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\StockQuery;
use App\Repositories\StockQueryRepository;
use App\Services\Interfaces\StockApiServiceInterface;

class StockService
{
    private StockApiServiceInterface $stockApiService;
    private StockQueryRepository $stockQueryRepository;
    
    public function __construct(
        StockApiServiceInterface $stockApiService,
        StockQueryRepository $stockQueryRepository
    ) {
        $this->stockApiService = $stockApiService;
        $this->stockQueryRepository = $stockQueryRepository;
    }
    
    /**
     * Get stock data by symbol
     *
     * @param string $symbol
     * @return array|null
     */
    public function getStockData(string $symbol): ?array
    {
        return $this->stockApiService->fetchStockData($symbol);
    }
    
    /**
     * Save stock query to database
     *
     * @param int $userId
     * @param array $stockData
     * @return StockQuery
     */
    public function saveStockQuery(int $userId, array $stockData): StockQuery
    {
        return $this->stockQueryRepository->create([
            'user_id' => $userId,
            'symbol' => $stockData['symbol'],
            'name' => $stockData['name'],
            'open' => $stockData['open'],
            'high' => $stockData['high'],
            'low' => $stockData['low'],
            'close' => $stockData['close'],
        ]);
    }
    
    /**
     * Get stock query history for a user
     *
     * @param int $userId
     * @return array
     */
    public function getUserStockHistory(int $userId): array
    {
        return $this->stockQueryRepository->getUserHistory($userId);
    }
}