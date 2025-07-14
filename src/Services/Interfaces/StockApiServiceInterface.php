<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

interface StockApiServiceInterface
{
    /**
     * Fetch stock data from API
     *
     * @param string $symbol
     * @return array|null
     */
    public function fetchStockData(string $symbol): ?array;
}