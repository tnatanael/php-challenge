<?php

declare(strict_types=1);

namespace App\Validators;

class StockValidator
{
    /**
     * Validate stock symbol
     *
     * @param string|null $symbol
     * @return bool
     */
    public function validateSymbol(?string $symbol): bool
    {
        return !empty($symbol);
    }
}