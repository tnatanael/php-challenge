<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Interfaces\HttpClientInterface;
use App\Services\Interfaces\StockApiServiceInterface;

class StooqApiService implements StockApiServiceInterface
{
    private HttpClientInterface $httpClient;
    private string $baseUrl;
    
    public function __construct(HttpClientInterface $httpClient, string $baseUrl = "https://stooq.com/q/l/")
    {
        $this->httpClient = $httpClient;
        $this->baseUrl = $baseUrl;
    }
    
    /**
     * Fetch stock data from API
     *
     * @param string $symbol
     * @return array|null
     */
    public function fetchStockData(string $symbol): ?array
    {
        $url = "{$this->baseUrl}?s={$symbol}&f=sd2t2ohlcvn&h&e=csv";
        $csvData = $this->httpClient->get($url);
        
        if (!$csvData) {
            return null;
        }
        
        return $this->parseStockData($csvData);
    }
    
    /**
     * Parse CSV stock data
     *
     * @param string $csvData
     * @return array|null
     */
    private function parseStockData(string $csvData): ?array
    {
        $lines = explode("\n", $csvData);
        if (count($lines) < 2) {
            return null;
        }
        
        $headers = str_getcsv($lines[0]);
        $values = str_getcsv($lines[1]);
        
        $data = array_combine($headers, $values);

        // Check if we have valid data
        if (!isset($data['Symbol']) || 
            !isset($data['Open']) || $data['Open'] === 'N/D' || 
            !isset($data['High']) || $data['High'] === 'N/D' || 
            !isset($data['Low']) || $data['Low'] === 'N/D' || 
            !isset($data['Close']) || $data['Close'] === 'N/D') {
            return null;
        }
        
        return [
            'symbol' => $data['Symbol'],
            'name' => $data['Name'],
            'open' => (float) $data['Open'],
            'high' => (float) $data['High'],
            'low' => (float) $data['Low'],
            'close' => (float) $data['Close'],
        ];
    }
}