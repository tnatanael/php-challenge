<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

interface HttpClientInterface
{
    /**
     * Make a GET request
     *
     * @param string $url
     * @return string|null
     */
    public function get(string $url): ?string;
}