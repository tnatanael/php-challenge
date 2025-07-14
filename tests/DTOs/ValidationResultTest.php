<?php

declare(strict_types=1);

namespace Tests\DTOs;

use App\DTOs\ValidationResult;
use PHPUnit\Framework\TestCase;

class ValidationResultTest extends TestCase
{
    public function testValidResult(): void
    {
        $result = new ValidationResult(true);
        
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
        $this->assertNull($result->getFirstError());
    }
    
    public function testInvalidResultWithSingleError(): void
    {
        $result = new ValidationResult(false, 'Validation error');
        
        $this->assertFalse($result->isValid());
        $this->assertEquals(['Validation error'], $result->getErrors());
        $this->assertEquals('Validation error', $result->getFirstError());
    }
    
    public function testAddingErrors(): void
    {
        $result = new ValidationResult(true);
        
        $result->addError('First error');
        $result->addError('Second error');
        
        $this->assertFalse($result->isValid());
        $this->assertEquals(['First error', 'Second error'], $result->getErrors());
        $this->assertEquals('First error', $result->getFirstError());
    }
}