<?php

declare(strict_types=1);

namespace Tests;

use App\Models\User;
use Slim\Exception\HttpUnauthorizedException;
use Faker\Factory;

class AuthTest extends BaseTestCase
{
    protected $app;
    private $faker;
    private $defaultPassword;
    private $testUserEmail; // Store the test user email
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize the app
        $this->app = $this->getAppInstance();
        $this->faker = Factory::create();
        $this->defaultPassword = 'Password123'; // Using a fixed password for test consistency
        $this->testUserEmail = $this->faker->email(); // Generate and store a test email
        
        // Create a test user for authentication tests
        $user = new User();
        $user->email = $this->testUserEmail;
        $user->password = password_hash($this->defaultPassword, PASSWORD_DEFAULT);
        $user->save();
    }
    
    /**
     * Generate user data for testing
     */
    private function generateUserData(): array
    {
        return [
            'email' => $this->testUserEmail, // Use the stored test email
            'password' => $this->defaultPassword,
        ];
    }
    
    /**
     * Test login with valid credentials
     */
    public function testLoginWithValidCredentials(): void
    {
        // Arrange
        $credentials = $this->generateUserData();
        
        $request = $this->createRequest(
            'POST',
            '/auth/login',
            ['HTTP_ACCEPT' => 'application/json'],
        );
        $request = $request->withParsedBody($credentials);
        
        // Act
        $response = $this->app->handle($request);
        $payload = json_decode((string)$response->getBody(), true);
        
        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertEquals('Login successful', $payload['message']);
        $this->assertArrayHasKey('token', $payload['data']);
        $this->assertArrayHasKey('user', $payload['data']);
        $this->assertEquals($credentials['email'], $payload['data']['user']['email']);
    }
    
    /**
     * Test login with wrong credentials
     */
    public function testLoginWithInvalidCredentials(): void
    {
        // Arrange
        $credentials = [
            'email' => $this->testUserEmail,
            'password' => 'wrongpassword'
        ];
        
        $request = $this->createRequest(
            'POST',
            '/auth/login',
            ['HTTP_ACCEPT' => 'application/json'],
        );
        $request = $request->withParsedBody($credentials);
        
        // Act & Assert
        $this->expectException(\Slim\Exception\HttpUnauthorizedException::class);
        $this->app->handle($request);
    }
    
    /**
     * Test login with missing fields
     */
    public function testLoginWithMissingFields(): void
    {
        // Test with missing email
        $request1 = $this->createRequest(
            'POST',
            '/auth/login',
            ['HTTP_ACCEPT' => 'application/json'],
        );
        $request1 = $request1->withParsedBody(['password' => 'password123']);
        
        $response1 = $this->app->handle($request1);
        $payload1 = json_decode((string)$response1->getBody(), true);
        
        $this->assertEquals(400, $response1->getStatusCode());
        $this->assertFalse($payload1['success']);
        $this->assertEquals('Email and password are required', $payload1['message']);
        
        // Test with missing password
        $request2 = $this->createRequest(
            'POST',
            '/auth/login',
            ['HTTP_ACCEPT' => 'application/json'],
        );
        $request2 = $request2->withParsedBody(['email' => $this->testUserEmail]);
        
        $response2 = $this->app->handle($request2);
        $payload2 = json_decode((string)$response2->getBody(), true);
        
        $this->assertEquals(400, $response2->getStatusCode());
        $this->assertFalse($payload2['success']);
        $this->assertEquals('Email and password are required', $payload2['message']);
        
        // Test with empty request body
        $request3 = $this->createRequest(
            'POST',
            '/auth/login',
            ['HTTP_ACCEPT' => 'application/json'],
        );
        
        $response3 = $this->app->handle($request3);
        $payload3 = json_decode((string)$response3->getBody(), true);
        
        $this->assertEquals(400, $response3->getStatusCode());
        $this->assertFalse($payload3['success']);
        $this->assertEquals('Email and password are required', $payload3['message']);
    }
}