<?php

declare(strict_types=1);

namespace App\DTOs;

class SuccessResponse extends ApiResponse
{
    /**
     * @param mixed|null $data
     * @param string|null $message
     */
    public function __construct($data = null, ?string $message = 'Operation successful')
    {
        parent::__construct(true, $message, $data);
    }
}