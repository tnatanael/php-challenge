<?php

declare(strict_types=1);

namespace Tests\Controllers;

use App\Controllers\UserController;
use App\DTOs\ServiceResult;
use App\DTOs\ValidationResult;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\UserServiceInterface;
use App\Validators\UserValidator;
use Faker\Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class UserControllerTest extends TestCase
{
    /** @var UserRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject */
    private UserRepositoryInterface $userRepository;
    
    /** @var UserServiceInterface&\PHPUnit\Framework\MockObject\MockObject */
    private UserServiceInterface $userService;
    
    /** @var UserValidator&\PHPUnit\Framework\MockObject\MockObject */
    private UserValidator $validator;
    
    private UserController $controller;
    
    private \Faker\Generator $faker;
    
    protected function setUp(): void
    {
        /** @var UserRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject $userRepositoryMock */
        $userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $this->userRepository = $userRepositoryMock;
        
        /** @var UserServiceInterface&\PHPUnit\Framework\MockObject\MockObject $userServiceMock */
        $userServiceMock = $this->createMock(UserServiceInterface::class);
        $this->userService = $userServiceMock;
        
        /** @var UserValidator&\PHPUnit\Framework\MockObject\MockObject $validatorMock */
        $validatorMock = $this->createMock(UserValidator::class);
        $this->validator = $validatorMock;
        
        $this->controller = new UserController(
            $this->userRepository,
            $this->userService,
            $this->validator
        );
        
        $this->faker = Factory::create();
    }
    
    /**
     * Gera dados de usuário para testes
     */
    private function generateUserData(array $overrides = []): array
    {
        return array_merge([
            'email' => $this->faker->email,
            'password' => $this->faker->password(8, 12),
        ], $overrides);
    }
    
    /**
     * Cria um objeto User a partir dos dados gerados
     */
    private function createUser(array $userData = [], ?int $id = null): User
    {
        $user = new User();
        $user->email = $userData['email'] ?? $this->faker->email;
        if ($id !== null) {
            $user->id = $id;
        }
        return $user;
    }
    
    public function testGetAll(): void
    {
        // Arrange
        $users = [
            ['id' => 1, 'email' => $this->faker->email],
            ['id' => 2, 'email' => $this->faker->email]
        ];
        
        $this->userRepository->expects($this->once())
            ->method('findAll')
            ->willReturn($users);
        
        /** @var ServerRequestInterface&\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(ServerRequestInterface::class);
        
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function($json) use ($users) {
                $data = json_decode($json, true);
                return $data['success'] === true 
                    && $data['message'] === 'Users retrieved successfully'
                    && $data['data'] === $users;
            }));
        
        /** @var ResponseInterface&\PHPUnit\Framework\MockObject\MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($stream);
        $response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();
        $response->expects($this->once())
            ->method('withStatus')
            ->with(200)
            ->willReturnSelf();
        
        // Act
        $result = $this->controller->getAll($request, $response);
        
        // Assert
        $this->assertSame($response, $result);
    }
    
    public function testCreateWithValidData(): void
    {
        // Arrange
        $userData = $this->generateUserData();
        
        $user = $this->createUser($userData);
        
        /** @var ServerRequestInterface&\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($userData);
        
        $this->validator->expects($this->once())
            ->method('validateForCreation')
            ->with($userData)
            ->willReturn(new ValidationResult(true));
        
        $this->userService->expects($this->once())
            ->method('createUser')
            ->with($userData)
            ->willReturn(new ServiceResult(true, 'User created successfully', $user));
        
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function($json) {
                $data = json_decode($json, true);
                return $data['success'] === true 
                    && $data['message'] === 'User created successfully';
            }));
        
        /** @var ResponseInterface&\PHPUnit\Framework\MockObject\MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($stream);
        $response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();
        $response->expects($this->once())
            ->method('withStatus')
            ->with(201)
            ->willReturnSelf();
        
        // Act
        $result = $this->controller->create($request, $response);
        
        // Assert
        $this->assertSame($response, $result);
    }
    
    public function testCreateWithInvalidData(): void
    {
        // Arrange
        $userData = [
            'email' => 'invalid-email'
        ];
        
        /** @var ServerRequestInterface&\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($userData);
        
        $this->validator->expects($this->once())
            ->method('validateForCreation')
            ->with($userData)
            ->willReturn(new ValidationResult(false, 'Invalid email format'));
        
        $this->userService->expects($this->never())
            ->method('createUser');
        
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function($json) {
                $data = json_decode($json, true);
                return $data['success'] === false 
                    && $data['message'] === 'Invalid email format';
            }));
        
        /** @var ResponseInterface&\PHPUnit\Framework\MockObject\MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($stream);
        $response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();
        $response->expects($this->once())
            ->method('withStatus')
            ->with(400)
            ->willReturnSelf();
        
        // Act
        $result = $this->controller->create($request, $response);
        
        // Assert
        $this->assertSame($response, $result);
    }
    
    public function testCreateWithServiceError(): void
    {
        // Arrange
        $userData = $this->generateUserData();
        
        /** @var ServerRequestInterface&\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($userData);
        
        $this->validator->expects($this->once())
            ->method('validateForCreation')
            ->with($userData)
            ->willReturn(new ValidationResult(true));
        
        $this->userService->expects($this->once())
            ->method('createUser')
            ->with($userData)
            ->willReturn(new ServiceResult(false, 'Email already exists', null, 400));
        
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function($json) {
                $data = json_decode($json, true);
                return $data['success'] === false 
                    && $data['message'] === 'Email already exists';
            }));
        
        /** @var ResponseInterface&\PHPUnit\Framework\MockObject\MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($stream);
        $response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();
        $response->expects($this->once())
            ->method('withStatus')
            ->with(400)
            ->willReturnSelf();
        
        // Act
        $result = $this->controller->create($request, $response);
        
        // Assert
        $this->assertSame($response, $result);
    }
    
    public function testGetOneWithExistingUser(): void
    {
        // Arrange
        $userId = 1;
        $userData = $this->generateUserData();
        
        // Create a User object
        $user = $this->createUser($userData, $userId);
        
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);
        
        /** @var ServerRequestInterface&\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(ServerRequestInterface::class);
        
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function($json) use ($user) {
                $data = json_decode($json, true);
                return $data['success'] === true 
                    && $data['message'] === 'User retrieved successfully'
                    && isset($data['data']); // Just check that data exists, as User object will be converted to array
            }));
        
        /** @var ResponseInterface&\PHPUnit\Framework\MockObject\MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($stream);
        $response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();
        $response->expects($this->once())
            ->method('withStatus')
            ->with(200)
            ->willReturnSelf();
        
        // Act
        $result = $this->controller->getOne($request, $response, ['id' => (string)$userId]);
        
        // Assert
        $this->assertSame($response, $result);
    }
    
    public function testGetOneWithNonExistingUser(): void
    {
        // Arrange
        $userId = 999;
        
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn(null);
        
        /** @var ServerRequestInterface&\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(ServerRequestInterface::class);
        
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function($json) {
                $data = json_decode($json, true);
                return $data['success'] === false 
                    && $data['message'] === 'User not found';
            }));
        
        /** @var ResponseInterface&\PHPUnit\Framework\MockObject\MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($stream);
        $response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();
        $response->expects($this->once())
            ->method('withStatus')
            ->with(404)
            ->willReturnSelf();
        
        // Act
        $result = $this->controller->getOne($request, $response, ['id' => (string)$userId]);
        
        // Assert
        $this->assertSame($response, $result);
    }
    
    public function testUpdateWithValidData(): void
    {
        // Arrange
        $userId = 1;
        $userData = $this->generateUserData(['email' => $this->faker->email]);
        
        $updatedUser = $this->createUser($userData, $userId);
        
        /** @var ServerRequestInterface&\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($userData);
        
        $this->validator->expects($this->once())
            ->method('validateForUpdate')
            ->with($userData)
            ->willReturn(new ValidationResult(true));
        
        $this->userService->expects($this->once())
            ->method('updateUser')
            ->with($userId, $userData)
            ->willReturn(new ServiceResult(true, 'User updated successfully', $updatedUser));
        
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function($json) {
                $data = json_decode($json, true);
                return $data['success'] === true 
                    && $data['message'] === 'User updated successfully';
            }));
        
        /** @var ResponseInterface&\PHPUnit\Framework\MockObject\MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($stream);
        $response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();
        $response->expects($this->once())
            ->method('withStatus')
            ->with(200)
            ->willReturnSelf();
        
        // Act
        $result = $this->controller->update($request, $response, ['id' => (string)$userId]);
        
        // Assert
        $this->assertSame($response, $result);
    }
    
    public function testUpdateWithInvalidData(): void
    {
        // Arrange
        $userId = 1;
        $userData = [
            'email' => 'invalid-email'
        ];
        
        /** @var ServerRequestInterface&\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($userData);
        
        $this->validator->expects($this->once())
            ->method('validateForUpdate')
            ->with($userData)
            ->willReturn(new ValidationResult(false, 'Invalid email format'));
        
        $this->userService->expects($this->never())
            ->method('updateUser');
        
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function($json) {
                $data = json_decode($json, true);
                return $data['success'] === false 
                    && $data['message'] === 'Invalid email format';
            }));
        
        /** @var ResponseInterface&\PHPUnit\Framework\MockObject\MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($stream);
        $response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();
        $response->expects($this->once())
            ->method('withStatus')
            ->with(400)
            ->willReturnSelf();
        
        // Act
        $result = $this->controller->update($request, $response, ['id' => (string)$userId]);
        
        // Assert
        $this->assertSame($response, $result);
    }
    
    public function testUpdateWithServiceError(): void
    {
        // Arrange
        $userId = 999;
        $userData = $this->generateUserData();
        
        /** @var ServerRequestInterface&\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($userData);
        
        $this->validator->expects($this->once())
            ->method('validateForUpdate')
            ->with($userData)
            ->willReturn(new ValidationResult(true));
        
        $this->userService->expects($this->once())
            ->method('updateUser')
            ->with($userId, $userData)
            ->willReturn(new ServiceResult(false, 'User not found', null, 404));
        
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function($json) {
                $data = json_decode($json, true);
                return $data['success'] === false 
                    && $data['message'] === 'User not found';
            }));
        
        /** @var ResponseInterface&\PHPUnit\Framework\MockObject\MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($stream);
        $response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();
        $response->expects($this->once())
            ->method('withStatus')
            ->with(404)
            ->willReturnSelf();
        
        // Act
        $result = $this->controller->update($request, $response, ['id' => (string)$userId]);
        
        // Assert
        $this->assertSame($response, $result);
    }
    
    public function testDeleteSuccess(): void
    {
        // Arrange
        $userId = 1;
        
        $this->userService->expects($this->once())
            ->method('deleteUser')
            ->with($userId)
            ->willReturn(new ServiceResult(true, 'User deleted successfully'));
        
        /** @var ServerRequestInterface&\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(ServerRequestInterface::class);
        
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function($json) {
                $data = json_decode($json, true);
                return $data['success'] === true 
                    && $data['message'] === 'User deleted successfully'
                    && $data['data'] === null;
            }));
        
        /** @var ResponseInterface&\PHPUnit\Framework\MockObject\MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($stream);
        $response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();
        $response->expects($this->once())
            ->method('withStatus')
            ->with(200)
            ->willReturnSelf();
        
        // Act
        $result = $this->controller->delete($request, $response, ['id' => (string)$userId]);
        
        // Assert
        $this->assertSame($response, $result);
    }
    
    public function testDeleteWithError(): void
    {
        // Arrange
        $userId = 999;
        
        $this->userService->expects($this->once())
            ->method('deleteUser')
            ->with($userId)
            ->willReturn(new ServiceResult(false, 'User not found', null, 404));
        
        /** @var ServerRequestInterface&\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(ServerRequestInterface::class);
        
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
            ->method('write')
            ->with($this->callback(function($json) {
                $data = json_decode($json, true);
                return $data['success'] === false 
                    && $data['message'] === 'User not found';
            }));
        
        /** @var ResponseInterface&\PHPUnit\Framework\MockObject\MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($stream);
        $response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();
        $response->expects($this->once())
            ->method('withStatus')
            ->with(404)
            ->willReturnSelf();
        
        // Act
        $result = $this->controller->delete($request, $response, ['id' => (string)$userId]);
        
        // Assert
        $this->assertSame($response, $result);
    }
}