<?php

declare(strict_types=1);

namespace Tests\Factories;

use App\Models\User;

class UserFactory
{
    private array $attributes = [];
    
    /**
     * Define default attribute values
     *
     * @return array
     */
    public function getDefaults(): array
    {
        return [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];
    }
    
    /**
     * Define updated attribute values
     *
     * @return array
     */
    public function getUpdatedDefaults(): array
    {
        return [
            'email' => 'updated@example.com',
            'password' => 'newpassword123'
        ];
    }
    
    /**
     * Define another user's attribute values
     *
     * @return array
     */
    public function getAnotherDefaults(): array
    {
        return [
            'email' => 'another@example.com',
            'password' => 'password123'
        ];
    }
    
    /**
     * Set custom email
     *
     * @param string $email
     * @return self
     */
    public function withEmail(string $email): self
    {
        $this->attributes['email'] = $email;
        return $this;
    }
    
    /**
     * Set custom password
     *
     * @param string $password
     * @return self
     */
    public function withPassword(string $password): self
    {
        $this->attributes['password'] = $password;
        return $this;
    }
    
    /**
     * Create a user in the database
     *
     * @return User
     */
    public function create(): User
    {
        $attributes = array_merge($this->getDefaults(), $this->attributes);
        return User::create($attributes);
    }
    
    /**
     * Make a user model without persisting to database
     *
     * @return array
     */
    public function make(): array
    {
        return array_merge($this->getDefaults(), $this->attributes);
    }
    
    /**
     * Make an updated user model without persisting to database
     *
     * @return array
     */
    public function makeUpdated(): array
    {
        return array_merge($this->getUpdatedDefaults(), $this->attributes);
    }
    
    /**
     * Make another user model without persisting to database
     *
     * @return array
     */
    public function makeAnother(): array
    {
        return array_merge($this->getAnotherDefaults(), $this->attributes);
    }
    
    /**
     * Get JSON representation of user data
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->make());
    }
    
    /**
     * Get JSON representation of updated user data
     *
     * @return string
     */
    public function toUpdatedJson(): string
    {
        return json_encode($this->makeUpdated());
    }
    
    /**
     * Get JSON representation of another user data
     *
     * @return string
     */
    public function toAnotherJson(): string
    {
        return json_encode($this->makeAnother());
    }
    
    /**
     * Create a new factory instance
     *
     * @return self
     */
    public static function new(): self
    {
        return new self();
    }
}