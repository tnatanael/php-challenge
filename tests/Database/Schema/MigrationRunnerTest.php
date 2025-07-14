<?php

declare(strict_types=1);

namespace Tests\Database\Schema;

use App\Database\Schema\Migration;
use App\Database\Schema\MigrationRunner;
use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase;

class MigrationRunnerTest extends TestCase
{
    private MigrationRunner $migrationRunner;
    private $mockMigration;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up in-memory database
        $capsule = new Capsule;
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        
        $this->migrationRunner = new MigrationRunner();
        
        // Create a mock migration
        $this->mockMigration = $this->createMock(Migration::class);
    }
    
    public function testRegisterInstance(): void
    {
        $this->mockMigration->method('getName')->willReturn('test_migration');
        
        $result = $this->migrationRunner->registerInstance($this->mockMigration, 'test_migration');
        
        $this->assertSame($this->migrationRunner, $result);
    }
    
    public function testExtractTimestamp(): void
    {
        $reflectionClass = new \ReflectionClass(MigrationRunner::class);
        $method = $reflectionClass->getMethod('extractTimestamp');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->migrationRunner, '2023_01_01_123456_TestMigration');
        $this->assertEquals('20230101123456', $result);
        
        $result = $method->invoke($this->migrationRunner, 'TestMigration');
        $this->assertEquals('0', $result);
    }
    
    public function testDown(): void
    {
        // Create migrations table first
        if (!Capsule::schema()->hasTable('migrations')) {
            Capsule::schema()->create('migrations', function ($table) {
                $table->id();
                $table->string('migration');
                $table->timestamp('executed_at');
            });
        }
        
        // Register a mock migration
        $this->mockMigration->method('getName')->willReturn('test_migration');
        $this->mockMigration->expects($this->once())->method('down');
        $this->migrationRunner->registerInstance($this->mockMigration, 'test_migration');
        
        // Record the migration as having been run
        Capsule::table('migrations')->insert([
            'migration' => 'test_migration',
            'executed_at' => date('Y-m-d H:i:s')
        ]);
        
        // Capture output to prevent it from being displayed in test results
        ob_start();
        
        // Run the down method
        $this->migrationRunner->down();
        
        // Discard output
        ob_end_clean();
        
        // Verify the migration was removed from the migrations table
        $count = Capsule::table('migrations')->where('migration', 'test_migration')->count();
        $this->assertEquals(0, $count);
    }
    
    public function testCreateMigrationsTable(): void
    {
        // Drop migrations table if it exists
        if (Capsule::schema()->hasTable('migrations')) {
            Capsule::schema()->drop('migrations');
        }
        
        // Use reflection to access private method
        $reflectionClass = new \ReflectionClass(MigrationRunner::class);
        $method = $reflectionClass->getMethod('createMigrationsTable');
        $method->setAccessible(true);
        
        // Capture output
        ob_start();
        
        // Call the method
        $method->invoke($this->migrationRunner);
        
        // Discard output
        ob_end_clean();
        
        // Verify the table was created
        $this->assertTrue(Capsule::schema()->hasTable('migrations'));
        
        // Call again to test the "already exists" branch
        ob_start();
        $method->invoke($this->migrationRunner);
        ob_end_clean();
    }
    
    public function testHasMigrationRun(): void
    {
        // Create migrations table
        if (!Capsule::schema()->hasTable('migrations')) {
            Capsule::schema()->create('migrations', function ($table) {
                $table->id();
                $table->string('migration');
                $table->timestamp('executed_at');
            });
        }
        
        // Insert a test migration
        Capsule::table('migrations')->insert([
            'migration' => 'existing_migration',
            'executed_at' => date('Y-m-d H:i:s')
        ]);
        
        // Use reflection to access private method
        $reflectionClass = new \ReflectionClass(MigrationRunner::class);
        $method = $reflectionClass->getMethod('hasMigrationRun');
        $method->setAccessible(true);
        
        // Test with existing migration
        $result = $method->invoke($this->migrationRunner, 'existing_migration');
        $this->assertTrue($result);
        
        // Test with non-existing migration
        $result = $method->invoke($this->migrationRunner, 'non_existing_migration');
        $this->assertFalse($result);
    }
    
    public function testRecordMigration(): void
    {
        // Create migrations table
        if (!Capsule::schema()->hasTable('migrations')) {
            Capsule::schema()->create('migrations', function ($table) {
                $table->id();
                $table->string('migration');
                $table->timestamp('executed_at');
            });
        }
        
        // Use reflection to access private method
        $reflectionClass = new \ReflectionClass(MigrationRunner::class);
        $method = $reflectionClass->getMethod('recordMigration');
        $method->setAccessible(true);
        
        // Call the method
        $method->invoke($this->migrationRunner, 'new_migration');
        
        // Verify the migration was recorded
        $exists = Capsule::table('migrations')
            ->where('migration', 'new_migration')
            ->exists();
        $this->assertTrue($exists);
    }
    
    public function testUp(): void
    {
        // Create migrations table
        if (!Capsule::schema()->hasTable('migrations')) {
            Capsule::schema()->create('migrations', function ($table) {
                $table->id();
                $table->string('migration');
                $table->timestamp('executed_at');
            });
        }
        
        // Register a mock migration
        $this->mockMigration->method('getName')->willReturn('test_migration');
        $this->mockMigration->expects($this->once())->method('up');
        $this->migrationRunner->registerInstance($this->mockMigration, 'test_migration');
        
        // Capture output
        ob_start();
        
        // Run the up method
        $this->migrationRunner->up();
        
        // Discard output
        ob_end_clean();
        
        // Verify the migration was recorded
        $exists = Capsule::table('migrations')
            ->where('migration', 'test_migration')
            ->exists();
        $this->assertTrue($exists);
    }
    
    public function testScanMigrations(): void
    {
        // Create a temporary directory for test migrations
        $tempDir = sys_get_temp_dir() . '/test_migrations_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        try {
            // Create a test migration file
            $migrationContent = '<?php return new class implements \App\Database\Schema\Migration {
                public function up(): void {}
                public function down(): void {}
                public function getName(): string { return "test_migration"; }
            };';
            file_put_contents($tempDir . '/2023_01_01_123456_TestMigration.php', $migrationContent);
            
            // Capture output
            ob_start();
            
            // Scan the directory
            $result = $this->migrationRunner->scanMigrations($tempDir);
            
            // Discard output
            ob_end_clean();
            
            // Verify the method returns $this for method chaining
            $this->assertSame($this->migrationRunner, $result);
            
            // Test with non-existent directory
            ob_start();
            $result = $this->migrationRunner->scanMigrations('/non/existent/directory');
            ob_end_clean();
            $this->assertSame($this->migrationRunner, $result);
        } finally {
            // Clean up
            if (file_exists($tempDir . '/2023_01_01_123456_TestMigration.php')) {
                unlink($tempDir . '/2023_01_01_123456_TestMigration.php');
            }
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
    }
}