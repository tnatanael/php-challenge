<?php

declare(strict_types=1);

namespace App\DTOs;

class ErrorResponse extends ApiResponse
{
    /**
     * @var int
     */
    private int $errorCode;

    /**
     * @param string $message
     * @param int $errorCode
     * @param mixed|null $data
     */
    public function __construct(string $message, int $errorCode = 400, $data = null)
    {
        parent::__construct(false, $message, $data);
        $this->errorCode = $errorCode;
    }

    /**
     * @return int
     */
    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        $data['error_code'] = $this->errorCode;
        return $data;
    }
}