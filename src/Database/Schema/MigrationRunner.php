<?php

declare(strict_types=1);

namespace App\Database\Schema;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

class MigrationRunner
{
    /**
     * @var array<class-string<Migration>>
     */
    private array $migrations = [];

    /**
     * Register a migration class
     *
     * @param class-string<Migration> $migrationClass
     * @return self
     */
    public function register(string $migrationClass): self
    {
        $this->migrations[] = $migrationClass;
        return $this;
    }

    /**
     * Create migrations table if it doesn't exist
     */
    private function createMigrationsTable(): void
    {
        if (!Capsule::schema()->hasTable('migrations')) {
            echo "Creating migrations table...\n";
            Capsule::schema()->create('migrations', function (Blueprint $table) {
                $table->id();
                $table->string('migration');
                $table->timestamp('executed_at');
            });
            echo "Migrations table created successfully.\n";
        } else {
            echo "Migrations table already exists.\n";
        }
    }

    /**
     * Check if a migration has been run
     */
    private function hasMigrationRun(string $migration): bool
    {
        return Capsule::table('migrations')
            ->where('migration', $migration)
            ->exists();
    }

    /**
     * Record that a migration has been run
     */
    private function recordMigration(string $migration): void
    {
        Capsule::table('migrations')->insert([
            'migration' => $migration,
            'executed_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Scan the Migrations directory for migration classes
     */
    public function scanMigrations(string $directory = null): self
    {
        if ($directory === null) {
            $directory = __DIR__ . '/Migrations';
        }

        if (!is_dir($directory)) {
            echo "Migration directory not found: {$directory}\n";
            return $this;
        }

        echo "Scanning for migrations in: {$directory}\n";
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );
        
        $phpFiles = new RegexIterator($iterator, '/^.+\.php$/i');
        $count = 0;
        
        foreach ($phpFiles as $phpFile) {
            $className = pathinfo($phpFile->getPathname(), PATHINFO_FILENAME);
            $namespace = 'App\\Database\\Schema\\Migrations\\' . $className;
            
            if (class_exists($namespace) && is_subclass_of($namespace, Migration::class)) {
                $this->register($namespace);
                $count++;
            }
        }
        
        echo "Found {$count} migration(s).\n";
        
        return $this;
    }

    /**
     * Run all registered migrations that haven't been run yet
     */
    public function up(): void
    {
        // First ensure migrations table exists
        $this->createMigrationsTable();
        
        echo "Running migrations...\n";
        $ranCount = 0;
        $skippedCount = 0;
        
        foreach ($this->migrations as $migration) {
            $migrationName = $migration::getName();
            $shortName = (new \ReflectionClass($migration))->getShortName();
            
            if (!$this->hasMigrationRun($migrationName)) {
                echo "Running migration: {$shortName}\n";
                $migration::up();
                $this->recordMigration($migrationName);
                echo "Migration completed: {$shortName}\n";
                $ranCount++;
            } else {
                echo "Skipping migration (already run): {$shortName}\n";
                $skippedCount++;
            }
        }
        
        echo "Migration summary: {$ranCount} run, {$skippedCount} skipped.\n";
    }

    /**
     * Reverse all registered migrations in reverse order
     */
    public function down(): void
    {
        echo "Reversing migrations...\n";
        $ranCount = 0;
        $skippedCount = 0;
        
        foreach (array_reverse($this->migrations) as $migration) {
            $migrationName = $migration::getName();
            $shortName = (new \ReflectionClass($migration))->getShortName();
            
            if ($this->hasMigrationRun($migrationName)) {
                echo "Reversing migration: {$shortName}\n";
                $migration::down();
                Capsule::table('migrations')
                    ->where('migration', $migrationName)
                    ->delete();
                echo "Migration reversed: {$shortName}\n";
                $ranCount++;
            } else {
                echo "Skipping reversal (not previously run): {$shortName}\n";
                $skippedCount++;
            }
        }
        
        echo "Migration reversal summary: {$ranCount} reversed, {$skippedCount} skipped.\n";
    }
}