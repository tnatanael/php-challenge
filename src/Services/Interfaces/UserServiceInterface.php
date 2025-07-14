<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\DTOs\ServiceResult;

interface UserServiceInterface
{
    /**
     * Create a new user
     *
     * @param array $data
     * @return ServiceResult
     */
    public function createUser(array $data): ServiceResult;
    
    /**
     * Update a user
     *
     * @param int $id
     * @param array $data
     * @return ServiceResult
     */
    public function updateUser(int $id, array $data): ServiceResult;
    
    /**
     * Delete a user
     *
     * @param int $id
     * @return ServiceResult
     */
    public function deleteUser(int $id): ServiceResult;
}