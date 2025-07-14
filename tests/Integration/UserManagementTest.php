<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\UserServiceInterface;
use App\Validators\UserValidator;
use Tests\BaseTestCase;
use Faker\Factory;

class UserManagementTest extends BaseTestCase
{
    private $app;
    private $faker;
    private $defaultUserData;
    private $updatedUserData;
    
    protected function setUp(): void
    {  
        parent::setUp();
        
        $this->app = $this->getAppInstance();
        $this->faker = Factory::create();
        
        // Generate default user data
        $this->defaultUserData = [
            'email' => $this->faker->email(),
            'password' => $this->faker->password(8, 12)
        ];
        
        // Generate updated user data with a different email
        $this->updatedUserData = [
            'email' => $this->faker->email(),
            'password' => $this->faker->password(8, 12)
        ];
    }
    
    public function testUserLifecycleWithRefactoredComponents(): void
    {  
        // 1. Create a user
        $token = $this->getJwtToken();
        $headers = [
            'HTTP_ACCEPT' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        ];
        
        $userData = json_encode($this->defaultUserData);
        
        $request = $this->createRequest('POST', '/users', $headers);
        $request->getBody()->write($userData);
        
        $response = $this->app->handle($request);
        $payload = json_decode((string)$response->getBody(), true);
        
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertEquals('User created successfully', $payload['message']);
        
        $userId = $payload['data']['id'];
        
        // 2. Get the user
        $request = $this->createRequest('GET', "/users/{$userId}", $headers);
        
        $response = $this->app->handle($request);
        $payload = json_decode((string)$response->getBody(), true);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertEquals('User retrieved successfully', $payload['message']);
        $this->assertEquals($this->defaultUserData['email'], $payload['data']['email']);
        
        // 3. Update the user
        $updateData = json_encode($this->updatedUserData);
        
        $request = $this->createRequest('PUT', "/users/{$userId}", $headers);
        $request->getBody()->write($updateData);
        
        $response = $this->app->handle($request);
        $payload = json_decode((string)$response->getBody(), true);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertEquals('User updated successfully', $payload['message']);
        $this->assertEquals($this->updatedUserData['email'], $payload['data']['email']);
        
        // 4. Delete the user
        $request = $this->createRequest('DELETE', "/users/{$userId}", $headers);
        
        $response = $this->app->handle($request);
        $payload = json_decode((string)$response->getBody(), true);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertEquals('User deleted successfully', $payload['message']);
        
        // 5. Verify user is deleted
        $request = $this->createRequest('GET', "/users/{$userId}", $headers);
        
        $response = $this->app->handle($request);
        $payload = json_decode((string)$response->getBody(), true);
        
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertEquals('User not found', $payload['message']);
    }
}