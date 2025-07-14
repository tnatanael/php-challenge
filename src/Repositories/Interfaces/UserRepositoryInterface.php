<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\User;

interface UserRepositoryInterface
{
    /**
     * Find all users
     *
     * @return array
     */
    public function findAll(): array;
    
    /**
     * Find user by ID
     *
     * @param int $id
     * @return User|null
     */
    public function findById(int $id): ?User;
    
    /**
     * Find user by email
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User;
    
    /**
     * Create a new user
     *
     * @param array $data
     * @return User
     */
    public function create(array $data): User;
    
    /**
     * Update a user
     *
     * @param User $user
     * @param array $data
     * @return User
     */
    public function update(User $user, array $data): User;
    
    /**
     * Delete a user
     *
     * @param User $user
     * @return bool
     */
    public function delete(User $user): bool;
}