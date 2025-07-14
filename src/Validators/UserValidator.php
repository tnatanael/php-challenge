<?php

declare(strict_types=1);

namespace App\Validators;

use App\DTOs\ValidationResult;

class UserValidator
{
    /**
     * Validate user data for creation
     *
     * @param array $data
     * @return ValidationResult
     */
    public function validateForCreation(array $data): ValidationResult
    {
        // Check required fields
        if (!isset($data['email']) || empty($data['email'])) {
            return new ValidationResult(false, 'Email is required');
        }
        
        if (!isset($data['password']) || empty($data['password'])) {
            return new ValidationResult(false, 'Password is required');
        }
        
        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return new ValidationResult(false, 'Invalid email format');
        }
        
        // Validate password length
        if (strlen($data['password']) < 6) {
            return new ValidationResult(false, 'Password must be at least 6 characters');
        }
        
        return new ValidationResult(true);
    }
    
    /**
     * Validate user data for update
     *
     * @param array $data
     * @return ValidationResult
     */
    public function validateForUpdate(array $data): ValidationResult
    {
        // If email is provided, validate format
        if (isset($data['email']) && !empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return new ValidationResult(false, 'Invalid email format');
            }
        }
        
        // If password is provided, validate length
        if (isset($data['password']) && !empty($data['password'])) {
            if (strlen($data['password']) < 6) {
                return new ValidationResult(false, 'Password must be at least 6 characters');
            }
        }
        
        return new ValidationResult(true);
    }
}