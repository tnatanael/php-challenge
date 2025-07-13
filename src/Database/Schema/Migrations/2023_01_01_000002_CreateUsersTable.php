<?php

declare(strict_types=1);

use App\Database\Schema\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class implements Migration
{
    public function getName(): string
    {
        return 'create_users_table';
    }
    
    public function up(): void
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

    public function down(): void
    {
        Capsule::schema()->dropIfExists('users');
    }
};