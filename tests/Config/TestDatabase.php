<?php

declare(strict_types=1);

namespace Tests\Config;

use App\Database\Schema\MigrationRunner;
use App\Database\Schema\SeederRunner;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;

class TestDatabase
{
    /**
     * Boot an in-memory SQLite database for testing
     */
    public static function boot(): void
    {
        $capsule = new Capsule;

        $capsule->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Set the event dispatcher used by Eloquent models
        $capsule->setEventDispatcher(new Dispatcher(new Container));

        // Make this Capsule instance available globally
        $capsule->setAsGlobal();

        // Setup the Eloquent ORM
        $capsule->bootEloquent();
        
        // Suppress output during migrations and seeding
        ob_start();
        
        try {
            // Run migrations using the MigrationRunner
            $migrationRunner = new MigrationRunner();
            $migrationRunner->scanMigrations(__DIR__ . '/../../src/Database/Schema/Migrations');
            $migrationRunner->up();
            
            // Run seeders using the SeederRunner
            $seederRunner = new SeederRunner();
            $seederRunner->scanSeeders(__DIR__ . '/../../src/Database/Schema/Seeders');
            $seederRunner->run();
        } finally {
            // Discard the output
            ob_end_clean();
        }
    }
    
    /**
     * Reset the database by dropping all tables and re-running migrations and seeders
     * This can be called in tearDown methods to ensure a clean state between tests
     */
    public static function reset(): void
    {
        // Suppress output during reset
        ob_start();
        
        try {
            // Drop all tables
            $tables = Capsule::select('SELECT name FROM sqlite_master WHERE type="table"');
            
            foreach ($tables as $table) {
                if ($table->name !== 'sqlite_sequence') {
                    Capsule::schema()->drop($table->name);
                }
            }
            
            // Re-run migrations and seeders
            self::boot();
        } finally {
            // Discard the output
            ob_end_clean();
        }
    }
}