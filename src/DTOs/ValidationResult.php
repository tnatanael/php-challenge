<?php

declare(strict_types=1);

namespace App\DTOs;

class ValidationResult
{
    private bool $valid;
    private array $errors;
    
    /**
     * ValidationResult constructor
     *
     * @param bool $valid
     * @param string|null $error
     */
    public function __construct(bool $valid, ?string $error = null)
    {
        $this->valid = $valid;
        $this->errors = [];
        
        if ($error) {
            $this->errors[] = $error;
        }
    }
    
    /**
     * Check if validation passed
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->valid;
    }
    
    /**
     * Add an error
     *
     * @param string $error
     * @return self
     */
    public function addError(string $error): self
    {
        $this->errors[] = $error;
        $this->valid = false;
        return $this;
    }
    
    /**
     * Get all errors
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Get the first error
     *
     * @return string|null
     */
    public function getFirstError(): ?string
    {
        return $this->errors[0] ?? null;
    }
}