<?php

declare(strict_types=1);

namespace App\Config;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use PDO;

class Database
{
    public static function boot(): void
    {
        // First check if database exists and create it if it doesn't
        self::createDatabaseIfNotExists();
        
        $capsule = new Capsule;

        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => $_ENV['DB_HOST'] ?? 'localhost',
            'database'  => $_ENV['DB_NAME'] ?? 'stock_app',
            'username'  => $_ENV['DB_USERNAME'] ?? 'root',
            'password'  => $_ENV['DB_PASSWORD'] ?? '',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ]);

        // Set the event dispatcher used by Eloquent models
        $capsule->setEventDispatcher(new Dispatcher(new Container));

        // Make this Capsule instance available globally
        $capsule->setAsGlobal();

        // Setup the Eloquent ORM
        $capsule->bootEloquent();
    }
    
    /**
     * Create the database if it doesn't exist
     */
    private static function createDatabaseIfNotExists(): void
    {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $username = $_ENV['DB_USERNAME'] ?? 'root';
        $password = $_ENV['DB_PASSWORD'] ?? '';
        $database = $_ENV['DB_NAME'] ?? 'stock_app';
        
        try {
            // Connect without specifying a database
            $pdo = new PDO("mysql:host={$host};port={$port}", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if database exists
            $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$database}'");
            $databaseExists = $stmt->fetchColumn();
            
            if (!$databaseExists) {
                echo "Database '{$database}' does not exist. Creating it now...\n";
                $pdo->exec("CREATE DATABASE `{$database}`");
                echo "Database '{$database}' created successfully.\n";
            }
        } catch (\PDOException $e) {
            echo "Database connection error: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Boot an in-memory SQLite database for testing
     */
    public static function bootTestDatabase(): void
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
        
        // Create the tables for testing
        $capsule->schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        // Create a default user for testing
        $capsule->table('users')->insert([
            'id' => 1,
            'email' => $_ENV['DEFAULT_USERNAME'] ?? 'user@example.com',
            'password' => password_hash($_ENV['DEFAULT_PASSWORD'] ?? 'secret', PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        $capsule->schema()->create('stock_queries', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('symbol');
            $table->string('name')->nullable();
            $table->decimal('open', 10, 2)->nullable();
            $table->decimal('high', 10, 2)->nullable();
            $table->decimal('low', 10, 2)->nullable();
            $table->decimal('close', 10, 2)->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users');
        });
    }
}