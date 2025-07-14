<?php

declare(strict_types=1);

namespace Tests\Validators;

use App\Validators\UserValidator;
use PHPUnit\Framework\TestCase;

class UserValidatorTest extends TestCase
{
    private UserValidator $validator;
    
    protected function setUp(): void
    {
        $this->validator = new UserValidator();
    }
    
    public function testValidateForCreationWithValidData(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];
        
        $result = $this->validator->validateForCreation($data);
        
        $this->assertTrue($result->isValid());
        $this->assertNull($result->getFirstError());
    }
    
    public function testValidateForCreationWithMissingEmail(): void
    {
        $data = [
            'password' => 'password123'
        ];
        
        $result = $this->validator->validateForCreation($data);
        
        $this->assertFalse($result->isValid());
        $this->assertEquals('Email is required', $result->getFirstError());
    }
    
    public function testValidateForCreationWithMissingPassword(): void
    {
        $data = [
            'email' => 'test@example.com'
        ];
        
        $result = $this->validator->validateForCreation($data);
        
        $this->assertFalse($result->isValid());
        $this->assertEquals('Password is required', $result->getFirstError());
    }
    
    public function testValidateForCreationWithInvalidEmail(): void
    {
        $data = [
            'email' => 'invalid-email',
            'password' => 'password123'
        ];
        
        $result = $this->validator->validateForCreation($data);
        
        $this->assertFalse($result->isValid());
        $this->assertEquals('Invalid email format', $result->getFirstError());
    }
    
    public function testValidateForCreationWithShortPassword(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'short'
        ];
        
        $result = $this->validator->validateForCreation($data);
        
        $this->assertFalse($result->isValid());
        $this->assertEquals('Password must be at least 6 characters', $result->getFirstError());
    }
    
    public function testValidateForUpdateWithValidData(): void
    {
        $data = [
            'email' => 'updated@example.com',
            'password' => 'newpassword123'
        ];
        
        $result = $this->validator->validateForUpdate($data);
        
        $this->assertTrue($result->isValid());
        $this->assertNull($result->getFirstError());
    }
    
    public function testValidateForUpdateWithInvalidEmail(): void
    {
        $data = [
            'email' => 'invalid-email'
        ];
        
        $result = $this->validator->validateForUpdate($data);
        
        $this->assertFalse($result->isValid());
        $this->assertEquals('Invalid email format', $result->getFirstError());
    }
    
    public function testValidateForUpdateWithShortPassword(): void
    {
        $data = [
            'password' => 'short'
        ];
        
        $result = $this->validator->validateForUpdate($data);
        
        $this->assertFalse($result->isValid());
        $this->assertEquals('Password must be at least 6 characters', $result->getFirstError());
    }
    
    public function testValidateForUpdateWithEmptyData(): void
    {
        $data = [];
        
        $result = $this->validator->validateForUpdate($data);
        
        $this->assertTrue($result->isValid());
        $this->assertNull($result->getFirstError());
    }
}