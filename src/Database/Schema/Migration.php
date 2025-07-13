<?php

declare(strict_types=1);

namespace App\Database\Schema;

interface Migration
{
    /**
     * Get the migration name
     */
    public static function getName(): string;
    
    /**
     * Run the migration
     */
    public static function up(): void;

    /**
     * Reverse the migration
     */
    public static function down(): void;
}