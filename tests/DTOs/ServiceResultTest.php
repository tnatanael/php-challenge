<?php

declare(strict_types=1);

namespace Tests\DTOs;

use App\DTOs\ServiceResult;
use Faker\Factory;
use PHPUnit\Framework\TestCase;

class ServiceResultTest extends TestCase
{
    private $faker;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create();
    }
    
    public function testSuccessfulResult(): void
    {
        $id = $this->faker->randomNumber(2);
        $email = $this->faker->email();
        $message = $this->faker->sentence();
        
        $data = ['id' => $id, 'email' => $email];
        $result = new ServiceResult(true, $message, $data);
        
        $this->assertTrue($result->isSuccess());
        $this->assertEquals($message, $result->getMessage());
        $this->assertEquals($data, $result->getData());
        $this->assertNull($result->getCode());
    }
    
    public function testFailedResult(): void
    {
        $message = $this->faker->sentence();
        $code = $this->faker->randomElement([400, 401, 403, 404, 422, 500]);
        
        $result = new ServiceResult(false, $message, null, $code);
        
        $this->assertFalse($result->isSuccess());
        $this->assertEquals($message, $result->getMessage());
        $this->assertNull($result->getData());
        $this->assertEquals($code, $result->getCode());
    }
}