<?php

declare(strict_types=1);

namespace App\Database\Schema\Migrations;

use App\Database\Schema\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CreateUsersTable implements Migration
{
    public static function getName(): string
    {
        return 'create_users_table';
    }
    
    public static function up(): void
    {
        if (!Capsule::schema()->hasTable('users')) {
            Capsule::schema()->create('users', function (Blueprint $table) {
                $table->id();
                $table->string('email')->unique();
                $table->string('password');
                $table->timestamps();
            });
        }
    }

    public static function down(): void
    {
        Capsule::schema()->dropIfExists('users');
    }
}