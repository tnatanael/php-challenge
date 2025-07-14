<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\UserService;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    /** @var UserRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject */
    private UserRepositoryInterface $userRepository;

    /** @var UserService&\PHPUnit\Framework\MockObject\MockObject */
    private UserService $userService;
    
    protected function setUp(): void
    {
        /** @var UserRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject $userRepositoryMock */
        $userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $this->userRepository = $userRepositoryMock;
        $this->userService = new UserService($this->userRepository);
    }
    
    public function testCreateUserSuccess(): void
    {
        // Arrange
        $userData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];
        
        $user = new User();
        $user->email = $userData['email'];
        $user->password = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($userData['email'])
            ->willReturn(null);
            
        $this->userRepository->expects($this->once())
            ->method('create')
            ->willReturn($user);
        
        // Act
        $result = $this->userService->createUser($userData);
        
        // Assert
        $this->assertTrue($result->isSuccess());
        $this->assertEquals('User created successfully', $result->getMessage());
        $this->assertEquals($user, $result->getData());
    }
    
    public function testCreateUserWithExistingEmail(): void
    {
        // Arrange
        $userData = [
            'email' => 'existing@example.com',
            'password' => 'password123'
        ];
        
        $existingUser = new User();
        $existingUser->email = $userData['email'];
        
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($userData['email'])
            ->willReturn($existingUser);
            
        $this->userRepository->expects($this->never())
            ->method('create');
        
        // Act
        $result = $this->userService->createUser($userData);
        
        // Assert
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Email already in use', $result->getMessage());
        $this->assertEquals(400, $result->getCode());
    }
    
    public function testUpdateUserSuccess(): void
    {
        // Arrange
        $userId = 1;
        $userData = [
            'email' => 'updated@example.com'
        ];
        
        $user = new User();
        $user->id = $userId;
        $user->email = 'test@example.com';
        
        $updatedUser = new User();
        $updatedUser->id = $userId;
        $updatedUser->email = $userData['email'];
        
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);
            
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($userData['email'])
            ->willReturn(null);
            
        $this->userRepository->expects($this->once())
            ->method('update')
            ->with($user, $userData)
            ->willReturn($updatedUser);
        
        // Act
        $result = $this->userService->updateUser($userId, $userData);
        
        // Assert
        $this->assertTrue($result->isSuccess());
        $this->assertEquals('User updated successfully', $result->getMessage());
        $this->assertEquals($updatedUser, $result->getData());
    }
    
    public function testUpdateUserNotFound(): void
    {
        // Arrange
        $userId = 999;
        $userData = [
            'email' => 'updated@example.com'
        ];
        
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn(null);
            
        $this->userRepository->expects($this->never())
            ->method('update');
        
        // Act
        $result = $this->userService->updateUser($userId, $userData);
        
        // Assert
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('User not found', $result->getMessage());
        $this->assertEquals(404, $result->getCode());
    }
    
    public function testUpdateUserWithExistingEmail(): void
    {
        // Arrange
        $userId = 1;
        $userData = [
            'email' => 'existing@example.com'
        ];
        
        $user = new User();
        $user->id = $userId;
        $user->email = 'test@example.com';
        
        $existingUser = new User();
        $existingUser->id = 2; // Different user ID
        $existingUser->email = $userData['email'];
        
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);
            
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($userData['email'])
            ->willReturn($existingUser);
            
        $this->userRepository->expects($this->never())
            ->method('update');
        
        // Act
        $result = $this->userService->updateUser($userId, $userData);
        
        // Assert
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Email already in use', $result->getMessage());
        $this->assertEquals(400, $result->getCode());
    }
    
    public function testDeleteUserSuccess(): void
    {
        // Arrange
        $userId = 1;
        
        $user = new User();
        $user->id = $userId;
        
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);
            
        $this->userRepository->expects($this->once())
            ->method('delete')
            ->with($user)
            ->willReturn(true);
        
        // Act
        $result = $this->userService->deleteUser($userId);
        
        // Assert
        $this->assertTrue($result->isSuccess());
        $this->assertEquals('User deleted successfully', $result->getMessage());
    }
    
    public function testDeleteUserNotFound(): void
    {
        // Arrange
        $userId = 999;
        
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn(null);
            
        $this->userRepository->expects($this->never())
            ->method('delete');
        
        // Act
        $result = $this->userService->deleteUser($userId);
        
        // Assert
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('User not found', $result->getMessage());
        $this->assertEquals(404, $result->getCode());
    }
}