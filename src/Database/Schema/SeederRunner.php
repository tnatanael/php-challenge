<?php

declare(strict_types=1);

namespace App\Database\Schema;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

class SeederRunner
{
    /**
     * @var array<class-string<Seeder>>
     */
    private array $seeders = [];

    /**
     * Register a seeder class
     *
     * @param class-string<Seeder> $seederClass
     * @return self
     */
    public function register(string $seederClass): self
    {
        $this->seeders[] = $seederClass;
        return $this;
    }

    /**
     * Scan the Seeders directory for seeder classes
     */
    public function scanSeeders(string $directory = null): self
    {
        if ($directory === null) {
            $directory = __DIR__ . '/Seeders';
        }

        if (!is_dir($directory)) {
            echo "Seeder directory not found: {$directory}\n";
            return $this;
        }

        echo "Scanning for seeders in: {$directory}\n";
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );
        
        $phpFiles = new RegexIterator($iterator, '/^.+\.php$/i');
        $count = 0;
        
        foreach ($phpFiles as $phpFile) {
            $className = pathinfo($phpFile->getPathname(), PATHINFO_FILENAME);
            $namespace = 'App\\Database\\Schema\\Seeders\\' . $className;
            
            if (class_exists($namespace) && is_subclass_of($namespace, Seeder::class)) {
                $this->register($namespace);
                $count++;
            }
        }
        
        echo "Found {$count} seeder(s).\n";
        
        return $this;
    }

    /**
     * Run all registered seeders
     */
    public function run(): void
    {
        echo "Running seeders...\n";
        $ranCount = 0;
        
        foreach ($this->seeders as $seeder) {
            $seederName = $seeder::getName();
            $shortName = (new \ReflectionClass($seeder))->getShortName();
            
            echo "Running seeder: {$shortName}\n";
            $seeder::run();
            echo "Seeder completed: {$shortName}\n";
            $ranCount++;
        }
        
        echo "Seeder summary: {$ranCount} run.\n";
    }
}