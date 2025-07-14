<?php

declare(strict_types=1);

namespace App\Database\Schema\Seeders;

use App\Database\Schema\Seeder;
use App\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;

class UserSeeder implements Seeder
{
    public static function getName(): string
    {
        return 'user_seeder';
    }
    
    public static function run(): void
    {
        // Check if users table exists
        if (!Capsule::schema()->hasTable('users')) {
            echo "Users table does not exist. Skipping UserSeeder.\n";
            return;
        }
        
        // Check if any users exist
        $userCount = User::count();
        
        if ($userCount === 0) {
            echo "No users found. Creating default admin user...\n";
            
            // Create default admin user
            User::create([
                'email' => $_ENV['DEFAULT_USERNAME'] ?? 'user@example.com',
                'password' => password_hash($_ENV['DEFAULT_PASSWORD'] ?? 'user123', PASSWORD_DEFAULT)
            ]);
            
            echo "Default admin user created successfully.\n";
        } else {
            echo "Users already exist. Skipping UserSeeder.\n";
        }
    }
}