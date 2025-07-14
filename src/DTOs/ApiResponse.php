<?php

declare(strict_types=1);

namespace App\DTOs;

use OpenApi\Attributes as OA;

class ApiResponse implements \JsonSerializable
{
    // Add OpenAPI schema definition at the top of the class
    #[OA\Schema(
        schema: "ApiResponse",
        title: "API Response",
        description: "Standard API response format"
    )]
    
    #[OA\Property(
        property: "success",
        type: "boolean",
        description: "Indicates if the operation was successful",
        example: true
    )]
    
    #[OA\Property(
        property: "message",
        type: "string",
        description: "Response message",
        example: "Operation successful"
    )]
    
    #[OA\Property(
        property: "data",
        type: "object",
        description: "Response data"
    )]

    /**
     * @var bool
     */
    private bool $success;

    /**
     * @var string|null
     */
    private ?string $message;

    /**
     * @var mixed|null
     */
    private $data;

    /**
     * @param bool $success
     * @param string|null $message
     * @param mixed|null $data
     */
    public function __construct(bool $success, ?string $message = null, $data = null)
    {
        $this->success = $success;
        $this->message = $message;
        $this->data = $data;
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @return mixed|null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }
    
    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
    
    
}