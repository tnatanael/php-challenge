<?php

declare(strict_types=1);

namespace App\Database\Schema;

interface Seeder
{
    /**
     * Run the seeder
     */
    public static function run(): void;
    
    /**
     * Get the seeder name
     */
    public static function getName(): string;
}