<?php

declare(strict_types=1);

namespace Tests\Database\Schema\Seeders;

use App\Database\Schema\Seeders\UserSeeder;
use App\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase;

class UserSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up in-memory database
        $capsule = new Capsule;
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        
        // Set environment variables for testing
        $_ENV['DEFAULT_USERNAME'] = 'test@example.com';
        $_ENV['DEFAULT_PASSWORD'] = 'password123';
    }
    
    public function testGetName(): void
    {
        $this->assertEquals('user_seeder', UserSeeder::getName());
    }
    
    public function testRunWithNoUsers(): void
    {
        // Create users table
        if (!Capsule::schema()->hasTable('users')) {
            Capsule::schema()->create('users', function ($table) {
                $table->id();
                $table->string('email')->unique();
                $table->string('password');
                $table->timestamps();
            });
        }
        
        // Ensure no users exist
        User::query()->delete();
        
        // Capture output
        ob_start();
        
        // Run the seeder
        UserSeeder::run();
        
        // Discard output
        ob_end_clean();
        
        // Verify a user was created
        $this->assertEquals(1, User::count());
        $user = User::first();
        $this->assertEquals('test@example.com', $user->email);
    }
    
    public function testRunWithExistingUsers(): void
    {
        // Create users table
        if (!Capsule::schema()->hasTable('users')) {
            Capsule::schema()->create('users', function ($table) {
                $table->id();
                $table->string('email')->unique();
                $table->string('password');
                $table->timestamps();
            });
        }
        
        // Create a user
        User::create([
            'email' => 'existing@example.com',
            'password' => password_hash('existing123', PASSWORD_DEFAULT)
        ]);
        
        // Capture output
        ob_start();
        
        // Run the seeder
        UserSeeder::run();
        
        // Discard output
        ob_end_clean();
        
        // Verify no additional users were created
        $this->assertEquals(1, User::count());
        $user = User::first();
        $this->assertEquals('existing@example.com', $user->email);
    }
    
    public function testRunWithNoUsersTable(): void
    {
        // Drop users table if it exists
        if (Capsule::schema()->hasTable('users')) {
            Capsule::schema()->drop('users');
        }
        
        // Capture output
        ob_start();
        
        // Run the seeder
        UserSeeder::run();
        
        // Discard output
        ob_end_clean();
        
        // Verify the seeder didn't try to create a user
        // (no exception should be thrown)
        $this->assertTrue(true);
    }
}