<?php

declare(strict_types=1);

use App\Database\Schema\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class implements Migration
{
    public function getName(): string
    {
        return 'create_stock_queries_table';
    }
    
    public function up(): void
    {
        if (!Capsule::schema()->hasTable('stock_queries')) {
            Capsule::schema()->create('stock_queries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('symbol', 20);
                $table->string('name', 100)->nullable();
                $table->decimal('open', 10, 2)->nullable();
                $table->decimal('high', 10, 2)->nullable();
                $table->decimal('low', 10, 2)->nullable();
                $table->decimal('close', 10, 2)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('stock_queries');
    }
};