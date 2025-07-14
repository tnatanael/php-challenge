<?php

declare(strict_types=1);

namespace Tests\Mocks;

use Tests\Factories\StockFactory;

class StockApiMock
{
    private StockFactory $stockFactory;
    
    public function __construct()
    {
        $this->stockFactory = StockFactory::new();
    }
    
    /**
     * Mock the file_get_contents function for stock API calls
     */
    public function setupMocks(): void
    {
        if (function_exists('\\App\\Controllers\\file_get_contents')) {
            return; // Already mocked
        }
        
        // Mock for App\Controllers namespace
        eval('namespace App\\Controllers;');
        function file_get_contents($url) {
            $mock = new \Tests\Mocks\StockApiMock();
            return $mock->getApiResponse($url);
        }
        
        eval('namespace Tests;');
    }
    
    /**
     * Get mocked API response based on URL
     */
    public function getApiResponse(string $url): string
    {
        // Use the constant from StockFactory for invalid symbol
        if (strpos($url, 's=' . StockFactory::INVALID_SYMBOL) !== false) {
            return $this->stockFactory->toInvalidCsv();
        }
        
        // Use the constant from StockFactory for Microsoft symbol
        if (strpos($url, 's=' . StockFactory::MICROSOFT_SYMBOL) !== false) {
            return $this->stockFactory->toMicrosoftCsv();
        }
        
        // Default to Apple stock
        return $this->stockFactory->toCsv();
    }
}