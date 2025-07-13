<?php

declare(strict_types=1);

namespace App\Database\Schema;

interface Migration
{
    /**
     * Get the migration name
     */
    public function getName(): string;
    
    /**
     * Run the migration
     */
    public function up(): void;

    /**
     * Reverse the migration
     */
    public function down(): void;
}