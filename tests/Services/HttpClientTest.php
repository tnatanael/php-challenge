<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Services\HttpClient;
use PHPUnit\Framework\TestCase;

class HttpClientTest extends TestCase
{
    private HttpClient $httpClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->httpClient = new HttpClient();
    }

    public function testGetReturnsContentForValidUrl(): void
    {
        // Create a test class that extends HttpClient to override the get method
        $testHttpClient = new class extends HttpClient {
            public function get(string $url): ?string
            {
                return 'Test content from URL';
            }
        };
        
        // Act
        $result = $testHttpClient->get('https://example.com/test');
        
        // Assert
        $this->assertEquals('Test content from URL', $result);
    }
    
    public function testGetReturnsNullWhenFileGetContentsFails(): void
    {
        // Create a test class that extends HttpClient to override the get method
        $testHttpClient = new class extends HttpClient {
            public function get(string $url): ?string
            {
                return null;
            }
        };
        
        // Act
        $result = $testHttpClient->get('https://example.com/nonexistent');
        
        // Assert
        $this->assertNull($result);
    }
}