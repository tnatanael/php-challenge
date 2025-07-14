<?php

declare(strict_types=1);

namespace Tests;

use App\Models\User;
use Slim\Exception\HttpUnauthorizedException;
use Tests\Factories\UserFactory;

/**
 * Class UserTest
 * @package Tests
 */
class UserTest extends BaseTestCase
{
    /**
     * @var \Slim\App
     */
    protected $app;
    
    /**
     * @var UserFactory
     */
    protected $userFactory;

    /**
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->app = $this->getAppInstance();
        $this->userFactory = UserFactory::new();
        
        // Clean up test users from previous test runs
        User::where('email', $this->userFactory->getDefaults()['email'])->delete();
        User::where('email', $this->userFactory->getUpdatedDefaults()['email'])->delete();
        User::where('email', $this->userFactory->getAnotherDefaults()['email'])->delete();
    }

    /**
     * Test that accessing users endpoint without JWT token throws unauthorized exception
     */
    public function testGetAllUsersThrowsUnauthorized(): void
    {
        // Arrange
        $request = $this->createRequest('GET', '/users');

        // Assert
        $this->expectException(HttpUnauthorizedException::class);

        // Act
        $this->app->handle($request);
    }

    /**
     * Test getting all users with valid JWT token
     */
    public function testGetAllUsersWithJwtAuth(): void
    {
        // Arrange
        $token = $this->getJwtToken();
        $headers = ['HTTP_ACCEPT' => 'application/json', 'Authorization' => 'Bearer ' . $token];
        $request = $this->createRequest('GET', '/users', $headers);

        // Act
        $response = $this->app->handle($request);
        $payload = json_decode((string)$response->getBody(), true);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertIsArray($payload['data']);
        $this->assertEquals('Users retrieved successfully', $payload['message']);
    }

    /**
     * Test creating a new user with valid JWT token
     */
    public function testCreateUserWithJwtAuth(): void
    {
        // Arrange
        $token = $this->getJwtToken();
        $headers = [
            'HTTP_ACCEPT' => 'application/json', 
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        ];
        
        $userData = $this->userFactory->toJson();
        
        $request = $this->createRequest('POST', '/users', $headers);
        $request->getBody()->write($userData);

        // Act
        $response = $this->app->handle($request);
        $payload = json_decode((string)$response->getBody(), true);

        // Assert
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertEquals('User created successfully', $payload['message']);
        $this->assertEquals($this->userFactory->getDefaults()['email'], $payload['data']['email']);
        $this->assertArrayNotHasKey('password', $payload['data']); // Password should be hidden
    }

    /**
     * Test creating a user with missing required fields
     */
    public function testCreateUserWithMissingFields(): void
    {
        // Arrange
        $token = $this->getJwtToken();
        $headers = [
            'HTTP_ACCEPT' => 'application/json', 
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        ];
        
        // Create a user with only email (missing password)
        $userData = json_encode([
            'email' => $this->userFactory->getDefaults()['email']
            // Missing password
        ]);
        
        $request = $this->createRequest('POST', '/users', $headers);
        $request->getBody()->write($userData);

        // Act
        $response = $this->app->handle($request);
        $payload = json_decode((string)$response->getBody(), true);

        // Assert
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertEquals('Email and password are required', $payload['message']);
    }

    /**
     * Test creating a user with an email that already exists
     */
    public function testCreateUserWithDuplicateEmail(): void
    {
        // First create a user
        $this->testCreateUserWithJwtAuth();
        
        // Arrange - Try to create another user with the same email
        $token = $this->getJwtToken();
        $headers = [
            'HTTP_ACCEPT' => 'application/json', 
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        ];
        
        $userData = $this->userFactory
            ->withPassword('anotherpassword')
            ->toJson();
        
        $request = $this->createRequest('POST', '/users', $headers);
        $request->getBody()->write($userData);

        // Act
        $response = $this->app->handle($request);
        $payload = json_decode((string)$response->getBody(), true);

        // Assert
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertEquals('Email already in use', $payload['message']);
    }

    /**
     * Test getting a single user by ID with valid JWT token
     */
    public function testGetOneUserWithJwtAuth(): void
    {
        // First create a user
        $this->testCreateUserWithJwtAuth();
        
        // Get the user ID
        $user = User::where('email', $this->userFactory->getDefaults()['email'])->first();
        $userId = $user->id;
        
        // Arrange
        $token = $this->getJwtToken();
        $headers = ['HTTP_ACCEPT' => 'application/json', 'Authorization' => 'Bearer ' . $token];
        $request = $this->createRequest('GET', "/users/{$userId}", $headers);

        // Act
        $response = $this->app->handle($request);
        $payload = json_decode((string)$response->getBody(), true);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertEquals('User retrieved successfully', $payload['message']);
        $this->assertEquals($this->userFactory->getDefaults()['email'], $payload['data']['email']);
        $this->assertEquals($userId, $payload['data']['id']);
    }

    /**
     * Test getting a non-existent user
     */
    public function testGetNonExistentUser(): void
    {
        // Arrange - Use a very large ID that's unlikely to exist
        $token = $this->getJwtToken();
        $headers = ['HTTP_ACCEPT' => 'application/json', 'Authorization' => 'Bearer ' . $token];
        $request = $this->createRequest('GET', '/users/99999', $headers);

        // Act
        $response = $this->app->handle($request);
        $payload = json_decode((string)$response->getBody(), true);

        // Assert
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertEquals('User not found', $payload['message']);
    }

    /**
     * Test updating a user with valid JWT token
     */
    public function testUpdateUserWithJwtAuth(): void
    {
        // First create a user
        $this->testCreateUserWithJwtAuth();
        
        // Get the user ID
        $user = User::where('email', $this->userFactory->getDefaults()['email'])->first();
        $userId = $user->id;
        
        // Arrange
        $token = $this->getJwtToken();
        $headers = [
            'HTTP_ACCEPT' => 'application/json', 
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        ];
        
        $updateData = $this->userFactory->toUpdatedJson();
        
        $request = $this->createRequest('PUT', "/users/{$userId}", $headers);
        $request->getBody()->write($updateData);

        // Act
        $response = $this->app->handle($request);
        $payload = json_decode((string)$response->getBody(), true);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertEquals('User updated successfully', $payload['message']);
        $this->assertEquals($this->userFactory->getUpdatedDefaults()['email'], $payload['data']['email']);
        $this->assertEquals($userId, $payload['data']['id']);
    }

    /**
     * Test updating a user with an email that already exists
     */
    public function testUpdateUserWithDuplicateEmail(): void
    {
        // Create first user
        $this->testCreateUserWithJwtAuth();
        
        // Create second user with different email
        $token = $this->getJwtToken();
        $headers = [
            'HTTP_ACCEPT' => 'application/json', 
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        ];
        
        $anotherUser = $this->userFactory->toAnotherJson();
        
        $request = $this->createRequest('POST', '/users', $headers);
        $request->getBody()->write($anotherUser);
        $this->app->handle($request);
        
        // Get the second user ID
        $user2 = User::where('email', $this->userFactory->getAnotherDefaults()['email'])->first();
        $userId2 = $user2->id;
        
        // Try to update second user with first user's email
        $updateData = json_encode([
            'email' => $this->userFactory->getDefaults()['email']
        ]);
        
        $request = $this->createRequest('PUT', "/users/{$userId2}", $headers);
        $request->getBody()->write($updateData);

        // Act
        $response = $this->app->handle($request);
        $payload = json_decode((string)$response->getBody(), true);

        // Assert
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertEquals('Email already in use', $payload['message']);
    }

    /**
     * Test deleting a user with valid JWT token
     */
    public function testDeleteUserWithJwtAuth(): void
    {
        // First create a user
        $this->testCreateUserWithJwtAuth();
        
        // Get the user ID
        $user = User::where('email', $this->userFactory->getDefaults()['email'])->first();
        $userId = $user->id;
        
        // Arrange
        $token = $this->getJwtToken();
        $headers = ['HTTP_ACCEPT' => 'application/json', 'Authorization' => 'Bearer ' . $token];
        $request = $this->createRequest('DELETE', "/users/{$userId}", $headers);

        // Act
        $response = $this->app->handle($request);
        $payload = json_decode((string)$response->getBody(), true);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertEquals('User deleted successfully', $payload['message']);
        
        // Verify user is deleted
        $this->assertNull(User::find($userId));
    }

    /**
     * Test deleting a non-existent user
     */
    public function testDeleteNonExistentUser(): void
    {
        // Arrange - Use a very large ID that's unlikely to exist
        $token = $this->getJwtToken();
        $headers = ['HTTP_ACCEPT' => 'application/json', 'Authorization' => 'Bearer ' . $token];
        $request = $this->createRequest('DELETE', '/users/99999', $headers);

        // Act
        $response = $this->app->handle($request);
        $payload = json_decode((string)$response->getBody(), true);

        // Assert
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertEquals('User not found', $payload['message']);
    }

    /**
     * Test user login with valid credentials
     */
    public function testLoginWithValidCredentials(): void
    {
        // First create a user
        $this->testCreateUserWithJwtAuth();
        
        // Arrange
        $headers = [
            'HTTP_ACCEPT' => 'application/json',
            'Content-Type' => 'application/json'
        ];
        
        $loginData = json_encode([
            'email' => $this->userFactory->getDefaults()['email'],
            'password' => $this->userFactory->getDefaults()['password']
        ]);
        
        $request = $this->createRequest('POST', '/auth/login', $headers);
        $request->getBody()->write($loginData);

        // Act
        $response = $this->app->handle($request);
        $payload = json_decode((string)$response->getBody(), true);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertEquals('Login successful', $payload['message']);
        $this->assertArrayHasKey('token', $payload['data']);
        $this->assertNotEmpty($payload['data']['token']);
    }

    /**
     * Test user login with invalid credentials
     */
    public function testLoginWithInvalidCredentials(): void
    {
        // First create a user
        $this->testCreateUserWithJwtAuth();
        
        // Arrange
        $headers = [
            'HTTP_ACCEPT' => 'application/json',
            'Content-Type' => 'application/json'
        ];
        
        $loginData = json_encode([
            'email' => $this->userFactory->getDefaults()['email'],
            'password' => 'wrongpassword'
        ]);
        
        $request = $this->createRequest('POST', '/auth/login', $headers);
        $request->getBody()->write($loginData);
    
        // Assert that the request throws an HttpUnauthorizedException
        $this->expectException(\Slim\Exception\HttpUnauthorizedException::class);
    
        // Act
        $this->app->handle($request);
    }
}