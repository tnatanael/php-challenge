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
     * @var array<Migration>
     */
    private array $migrations = [];

    /**
     * Register a migration instance
     *
     * @param Migration $migration
     * @param string $filename
     * @return self
     */
    public function registerInstance(Migration $migration, string $filename): self
    {
        $this->migrations[] = [
            'instance' => $migration,
            'filename' => $filename
        ];
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
     * Extract timestamp from migration filename
     * 
     * @param string $filename The migration filename
     * @return string The timestamp or '0' if no timestamp found
     */
    private function extractTimestamp(string $filename): string
    {
        // Extract timestamp from format like "YYYY_MM_DD_HHMMSS_MigrationName"
        if (preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})_/', $filename, $matches)) {
            return str_replace('_', '', $matches[1]);
        }
        
        return '0'; // Default timestamp for migrations without timestamp prefix
    }

    /**
     * Scan the Migrations directory for migration files and require them
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
            $filename = pathinfo($phpFile->getPathname(), PATHINFO_FILENAME);
            $filepath = $phpFile->getPathname();
            
            // Require the file which should return a Migration instance
            $migration = require $filepath;
            
            if ($migration instanceof Migration) {
                $this->registerInstance($migration, $filename);
                $count++;
            }
        }
        
        echo "Found {$count} migration(s).\n";
        
        // Sort migrations by timestamp
        usort($this->migrations, function ($a, $b) {
            $timestampA = $this->extractTimestamp($a['filename']);
            $timestampB = $this->extractTimestamp($b['filename']);
            
            return $timestampA <=> $timestampB;
        });
        
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
            $instance = $migration['instance'];
            $filename = $migration['filename'];
            $migrationName = $instance->getName();
            
            if (!$this->hasMigrationRun($migrationName)) {
                echo "Running migration: {$filename}\n";
                $instance->up();
                $this->recordMigration($migrationName);
                echo "Migration completed: {$filename}\n";
                $ranCount++;
            } else {
                echo "Skipping migration (already run): {$filename}\n";
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
            $instance = $migration['instance'];
            $filename = $migration['filename'];
            $migrationName = $instance->getName();
            
            if ($this->hasMigrationRun($migrationName)) {
                echo "Reversing migration: {$filename}\n";
                $instance->down();
                Capsule::table('migrations')
                    ->where('migration', $migrationName)
                    ->delete();
                echo "Migration reversed: {$filename}\n";
                $ranCount++;
            } else {
                echo "Skipping reversal (not previously run): {$filename}\n";
                $skippedCount++;
            }
        }
        
        echo "Migration reversal summary: {$ranCount} reversed, {$skippedCount} skipped.\n";
    }
}