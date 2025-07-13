<?php

declare(strict_types=1);

namespace App\Database\Schema\Commands;

use App\Config\Database;
use App\Database\Schema\MigrationRunner;
use App\Database\Schema\SeederRunner;

class MigrateCommand
{
    /**
     * Run all migrations
     */
    public static function run(): void
    {
        // Initialize Eloquent
        Database::boot();

        // Run migrations
        $migrationRunner = new MigrationRunner();

        // Scan for migrations
        $migrationRunner->scanMigrations(__DIR__ . '/../../Schema/Migrations');

        // Run all migrations
        $migrationRunner->up();
        
        echo "Migration process completed.\n";
        
        // Run seeders
        self::seed();
    }
    
    /**
     * Run all seeders
     */
    public static function seed(): void
    {
        // Initialize Eloquent if not already initialized
        Database::boot();

        // Run seeders
        $seederRunner = new SeederRunner();

        // Scan for seeders
        $seederRunner->scanSeeders(__DIR__ . '/../../Schema/Seeders');

        // Run all seeders
        $seederRunner->run();
        
        echo "Seeding process completed.\n";
    }
    
    /**
     * Rollback all migrations
     */
    public static function rollback(): void
    {
        // Initialize Eloquent
        Database::boot();

        // Run migrations
        $migrationRunner = new MigrationRunner();

        // Scan for migrations
        $migrationRunner->scanMigrations(__DIR__ . '/../../Schema/Migrations');

        // Rollback all migrations
        $migrationRunner->down();
        
        echo "Migration rollback completed.\n";
    }
    
    /**
     * Refresh all migrations (rollback then run)
     */
    public static function refresh(): void
    {
        self::rollback();
        self::run();
        
        echo "Migration refresh completed.\n";
    }
}