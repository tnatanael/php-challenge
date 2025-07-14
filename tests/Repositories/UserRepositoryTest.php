<?php

declare(strict_types=1);

namespace Tests\Repositories;

use App\Models\User;
use App\Repositories\UserRepository;
use Faker\Factory;
use Tests\BaseTestCase;

class UserRepositoryTest extends BaseTestCase
{
    private UserRepository $repository;
    private $faker;
    private array $testEmails = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = new UserRepository();
        $this->faker = Factory::create();
        
        // Create test users in the database with faker data
        for ($i = 0; $i < 2; $i++) {
            $email = $this->faker->unique()->safeEmail();
            $this->testEmails[] = $email;
            
            User::create([
                'email' => $email,
                'password' => $this->faker->password(8, 12)
            ]);
        }
    }

    public function testFindAllReturnsAllUsersAsArray(): void
    {
        // Call the findAll method
        $result = $this->repository->findAll();
        
        // Assert the result is an array with our test users plus the default admin user
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        
        // Check that the emails match our test data
        $emails = array_column($result, 'email');
        foreach ($this->testEmails as $email) {
            $this->assertContains($email, $emails);
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up test data
        foreach ($this->testEmails as $email) {
            User::where('email', $email)->delete();
        }
        
        parent::tearDown();
    }
}