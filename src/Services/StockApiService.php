<?php

declare(strict_types=1);

namespace App\Services;

class StockApiService
{
    /**
     * Fetch data from the stock API
     */
    public function fetchFromApi(string $url): string
    {
        return file_get_contents($url);
    }
}