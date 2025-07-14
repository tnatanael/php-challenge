<?php

declare(strict_types=1);

namespace Tests\DTOs;

use App\DTOs\ErrorResponse;
use Faker\Factory;
use PHPUnit\Framework\TestCase;

class ErrorResponseTest extends TestCase
{
    private $faker;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create();
    }
    
    public function testErrorResponse(): void
    {
        $message = $this->faker->sentence();
        $errorCode = $this->faker->randomElement([400, 401, 403, 404, 422, 500]);
        $data = ['error' => $this->faker->word()];
        
        $response = new ErrorResponse($message, $errorCode, $data);
        
        // Test that the constructor properly sets values
        $this->assertFalse($response->isSuccess());
        $this->assertEquals($message, $response->getMessage());
        $this->assertEquals($data, $response->getData());
        
        // Test the getErrorCode method that's currently not covered
        $this->assertEquals($errorCode, $response->getErrorCode());
        
        // Test the toArray method
        $array = $response->toArray();
        $this->assertFalse($array['success']);
        $this->assertEquals($message, $array['message']);
        $this->assertEquals($data, $array['data']);
        $this->assertEquals($errorCode, $array['error_code']);
    }
    
    public function testErrorResponseWithDefaultErrorCode(): void
    {
        $message = $this->faker->sentence();
        
        $response = new ErrorResponse($message);
        
        // Test that the default error code is 400
        $this->assertEquals(400, $response->getErrorCode());
    }
}