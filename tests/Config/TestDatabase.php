<?php

declare(strict_types=1);

namespace Tests\Config;

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