<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Interfaces\HttpClientInterface;

class HttpClient implements HttpClientInterface
{
    /**
     * Make a GET request
     *
     * @param string $url
     * @return string|null
     */
    public function get(string $url): ?string
    {
        $result = @file_get_contents($url);
        
        if ($result === false) {
            return null;
        }
        
        return $result;
    }
}