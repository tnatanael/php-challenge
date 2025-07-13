<?php

declare(strict_types=1);

namespace App\Database\Schema\Migrations;

use App\Database\Schema\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CreateMigrationsTable implements Migration
{
    public static function getName(): string
    {
        return 'create_migrations_table';
    }
    
    public static function up(): void
    {
        if (!Capsule::schema()->hasTable('migrations')) {
            Capsule::schema()->create('migrations', function (Blueprint $table) {
                $table->id();
                $table->string('migration');
                $table->timestamp('executed_at');
            });
        }
    }

    public static function down(): void
    {
        Capsule::schema()->dropIfExists('migrations');
    }
}