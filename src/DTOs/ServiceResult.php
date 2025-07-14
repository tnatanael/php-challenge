<?php

declare(strict_types=1);

namespace App\DTOs;

class ServiceResult
{
    private bool $success;
    private string $message;
    private $data;
    private ?int $code;
    
    /**
     * ServiceResult constructor
     *
     * @param bool $success
     * @param string $message
     * @param mixed $data
     * @param int|null $code
     */
    public function __construct(bool $success, string $message, $data = null, ?int $code = null)
    {
        $this->success = $success;
        $this->message = $message;
        $this->data = $data;
        $this->code = $code;
    }
    
    /**
     * Check if the operation was successful
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }
    
    /**
     * Get the message
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }
    
    /**
     * Get the data
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
    
    /**
     * Get the code
     *
     * @return int|null
     */
    public function getCode(): ?int
    {
        return $this->code;
    }
}