<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ServiceResult;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\UserServiceInterface;

class UserService implements UserServiceInterface
{
    private UserRepositoryInterface $userRepository;
    
    /**
     * UserService constructor
     *
     * @param UserRepositoryInterface $userRepository
     */
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    
    /**
     * Create a new user
     *
     * @param array $data
     * @return ServiceResult
     */
    public function createUser(array $data): ServiceResult
    {
        // Check if email already exists
        $existingUser = $this->userRepository->findByEmail($data['email']);
        if ($existingUser) {
            return new ServiceResult(false, 'Email already in use', null, 400);
        }
        
        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Create user
        $user = $this->userRepository->create([
            'email' => $data['email'],
            'password' => $data['password']
        ]);
        
        return new ServiceResult(true, 'User created successfully', $user);
    }
    
    /**
     * Update a user
     *
     * @param int $id
     * @param array $data
     * @return ServiceResult
     */
    public function updateUser(int $id, array $data): ServiceResult
    {
        // Find user
        $user = $this->userRepository->findById($id);
        if (!$user) {
            return new ServiceResult(false, 'User not found', null, 404);
        }
        
        $updateData = [];
        
        // Update email if provided
        if (isset($data['email'])) {
            // Check if email already exists for another user
            $existingUser = $this->userRepository->findByEmail($data['email']);
            if ($existingUser && $existingUser->id !== $id) {
                return new ServiceResult(false, 'Email already in use', null, 400);
            }
            
            $updateData['email'] = $data['email'];
        }
        
        // Update password if provided
        if (isset($data['password'])) {
            $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        // Update user
        $updatedUser = $this->userRepository->update($user, $updateData);
        
        return new ServiceResult(true, 'User updated successfully', $updatedUser);
    }
    
    /**
     * Delete a user
     *
     * @param int $id
     * @return ServiceResult
     */
    public function deleteUser(int $id): ServiceResult
    {
        // Find user
        $user = $this->userRepository->findById($id);
        if (!$user) {
            return new ServiceResult(false, 'User not found', null, 404);
        }
        
        // Delete user
        $result = $this->userRepository->delete($user);
        
        return new ServiceResult($result, 'User deleted successfully');
    }
}