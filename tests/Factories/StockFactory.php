<?php

declare(strict_types=1);

namespace Tests\Factories;

use App\Models\StockQuery;

class StockFactory
{
    /**
     * Symbol used for invalid stock queries
     */
    public const INVALID_SYMBOL = 'INVALID';
    
    /**
     * Symbol used for Apple stock queries
     */
    public const APPLE_SYMBOL = 'AAPL.US';
    
    /**
     * Symbol used for Microsoft stock queries
     */
    public const MICROSOFT_SYMBOL = 'MSFT.US';
    
    private array $attributes = [];
    
    /**
     * Define default attribute values for AAPL stock
     *
     * @return array
     */
    public function getDefaults(): array
    {
        return [
            'user_id' => 1,
            'symbol' => self::APPLE_SYMBOL,
            'name' => 'APPLE INC',
            'open' => 150.25,
            'high' => 152.43,
            'low' => 149.92,
            'close' => 151.60
        ];
    }
    
    /**
     * Define attribute values for MSFT stock
     *
     * @return array
     */
    public function getMicrosoftDefaults(): array
    {
        return [
            'user_id' => 1,
            'symbol' => self::MICROSOFT_SYMBOL,
            'name' => 'MICROSOFT CORP',
            'open' => 245.30,
            'high' => 248.75,
            'low' => 244.85,
            'close' => 247.65
        ];
    }
    
    /**
     * Define invalid stock data (N/D values)
     *
     * @return array
     */
    public function getInvalidDefaults(): array
    {
        return [
            'symbol' => 'N/D',
            'name' => 'N/D',
            'open' => 'N/D',
            'high' => 'N/D',
            'low' => 'N/D',
            'close' => 'N/D'
        ];
    }
    
    /**
     * Set custom user ID
     *
     * @param int $userId
     * @return self
     */
    public function withUserId(int $userId): self
    {
        $this->attributes['user_id'] = $userId;
        return $this;
    }
    
    /**
     * Set custom symbol
     *
     * @param string $symbol
     * @return self
     */
    public function withSymbol(string $symbol): self
    {
        $this->attributes['symbol'] = $symbol;
        return $this;
    }
    
    /**
     * Set custom name
     *
     * @param string $name
     * @return self
     */
    public function withName(string $name): self
    {
        $this->attributes['name'] = $name;
        return $this;
    }
    
    /**
     * Set custom open price
     *
     * @param float $open
     * @return self
     */
    public function withOpen(float $open): self
    {
        $this->attributes['open'] = $open;
        return $this;
    }
    
    /**
     * Set custom high price
     *
     * @param float $high
     * @return self
     */
    public function withHigh(float $high): self
    {
        $this->attributes['high'] = $high;
        return $this;
    }
    
    /**
     * Set custom low price
     *
     * @param float $low
     * @return self
     */
    public function withLow(float $low): self
    {
        $this->attributes['low'] = $low;
        return $this;
    }
    
    /**
     * Set custom close price
     *
     * @param float $close
     * @return self
     */
    public function withClose(float $close): self
    {
        $this->attributes['close'] = $close;
        return $this;
    }
    
    /**
     * Create a stock query in the database
     *
     * @return StockQuery
     */
    public function create(): StockQuery
    {
        $attributes = array_merge($this->getDefaults(), $this->attributes);
        return StockQuery::create($attributes);
    }
    
    /**
     * Make a stock query model without persisting to database
     *
     * @return array
     */
    public function make(): array
    {
        return array_merge($this->getDefaults(), $this->attributes);
    }
    
    /**
     * Make a Microsoft stock query model without persisting to database
     *
     * @return array
     */
    public function makeMicrosoft(): array
    {
        return array_merge($this->getMicrosoftDefaults(), $this->attributes);
    }
    
    /**
     * Make an invalid stock query model
     *
     * @return array
     */
    public function makeInvalid(): array
    {
        return array_merge($this->getInvalidDefaults(), $this->attributes);
    }
    
    /**
     * Get CSV representation of stock data (for mocking API responses)
     *
     * @return string
     */
    public function toCsv(): string
    {
        $data = $this->make();
        return "Symbol,Name,Open,High,Low,Close\n{$data['symbol']},{$data['name']},{$data['open']},{$data['high']},{$data['low']},{$data['close']}";
    }
    
    /**
     * Get CSV representation of Microsoft stock data
     *
     * @return string
     */
    public function toMicrosoftCsv(): string
    {
        $data = $this->makeMicrosoft();
        return "Symbol,Name,Open,High,Low,Close\n{$data['symbol']},{$data['name']},{$data['open']},{$data['high']},{$data['low']},{$data['close']}";
    }
    
    /**
     * Get CSV representation of invalid stock data
     *
     * @return string
     */
    public function toInvalidCsv(): string
    {
        return "Symbol,Name,Open,High,Low,Close\nN/D,N/D,N/D,N/D,N/D,N/D";
    }
    
    /**
     * Create a new factory instance
     *
     * @return self
     */
    public static function new(): self
    {
        return new self();
    }
    
    /**
     * Get stock data formatted for email template testing
     * This includes a date field which is needed for the email template
     *
     * @param string $date Optional date string, defaults to '2023-01-01'
     * @return array
     */
    public function getEmailTemplateData(string $date = '2023-01-01'): array
    {
        return [
            'symbol' => 'AAPL',
            'name' => 'APPLE INC',  // Added the missing name key
            'date' => $date,
            'open' => '150.00',
            'high' => '155.00',
            'low' => '149.00',
            'close' => '153.00'
        ];
    }
}