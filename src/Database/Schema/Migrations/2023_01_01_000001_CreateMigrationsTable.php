<?php

declare(strict_types=1);

use App\Database\Schema\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class implements Migration
{
    public function getName(): string
    {
        return 'create_migrations_table';
    }
    
    public function up(): void
    {
        if (!Capsule::schema()->hasTable('migrations')) {
            Capsule::schema()->create('migrations', function (Blueprint $table) {
                $table->id();
                $table->string('migration');
                $table->timestamp('executed_at');
            });
        }
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('migrations');
    }
};