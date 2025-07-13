<?php

declare(strict_types=1);

namespace Tests;

use App\Models\User;
use Slim\Exception\HttpUnauthorizedException;

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
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->app = $this->getAppInstance();
        
        // Clean up test users from previous test runs
        User::where('email', 'test@example.com')->delete();
        User::where('email', 'updated@example.com')->delete();
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
        
        $userData = json_encode([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        
        $request = $this->createRequest('POST', '/users', $headers);
        $request->getBody()->write($userData);

        // Act
        $response = $this->app->handle($request);
        $payload = json_decode((string)$response->getBody(), true);

        // Assert
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertEquals('User created successfully', $payload['message']);
        $this->assertEquals('test@example.com', $payload['data']['email']);
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
        
        $userData = json_encode([
            'email' => 'test@example.com'
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
        
        $userData = json_encode([
            'email' => 'test@example.com',
            'password' => 'anotherpassword'
        ]);
        
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
        $user = User::where('email', 'test@example.com')->first();
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
        $this->assertEquals('test@example.com', $payload['data']['email']);
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
        $user = User::where('email', 'test@example.com')->first();
        $userId = $user->id;
        
        // Arrange
        $token = $this->getJwtToken();
        $headers = [
            'HTTP_ACCEPT' => 'application/json', 
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        ];
        
        $updateData = json_encode([
            'email' => 'updated@example.com',
            'password' => 'newpassword123'
        ]);
        
        $request = $this->createRequest('PUT', "/users/{$userId}", $headers);
        $request->getBody()->write($updateData);

        // Act
        $response = $this->app->handle($request);
        $payload = json_decode((string)$response->getBody(), true);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertEquals('User updated successfully', $payload['message']);
        $this->assertEquals('updated@example.com', $payload['data']['email']);
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
        
        $userData = json_encode([
            'email' => 'another@example.com',
            'password' => 'password123'
        ]);
        
        $request = $this->createRequest('POST', '/users', $headers);
        $request->getBody()->write($userData);
        $this->app->handle($request);
        
        // Get the second user ID
        $user2 = User::where('email', 'another@example.com')->first();
        $userId2 = $user2->id;
        
        // Try to update second user with first user's email
        $updateData = json_encode([
            'email' => 'test@example.com'
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
        
        // Clean up
        User::where('email', 'another@example.com')->delete();
    }

    /**
     * Test deleting a user with valid JWT token
     */
    public function testDeleteUserWithJwtAuth(): void
    {
        // First create a user
        $this->testCreateUserWithJwtAuth();
        
        // Get the user ID
        $user = User::where('email', 'test@example.com')->first();
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
}