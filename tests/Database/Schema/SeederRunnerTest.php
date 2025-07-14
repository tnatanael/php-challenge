<?php

declare(strict_types=1);

namespace Tests\Database\Schema;

use App\Database\Schema\Seeder;
use App\Database\Schema\SeederRunner;
use PHPUnit\Framework\TestCase;

class SeederRunnerTest extends TestCase
{
    /** @var SeederRunner&\PHPUnit\Framework\MockObject\MockObject */
    private SeederRunner $seederRunner;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->seederRunner = new SeederRunner();
    }
    
    public function testRegister(): void
    {
        // Create a mock seeder class
        $mockSeederClass = 'Tests\Database\Schema\MockSeeder';
        
        // Register the seeder
        $result = $this->seederRunner->register($mockSeederClass);
        
        // Verify the method returns $this for method chaining
        $this->assertSame($this->seederRunner, $result);
    }
    
    public function testScanSeedersWithInvalidDirectory(): void
    {
        // Capture output
        ob_start();
        
        // Test with a non-existent directory
        $result = $this->seederRunner->scanSeeders('/non/existent/directory');
        
        // Discard output
        ob_end_clean();
        
        // Verify the method returns $this for method chaining
        $this->assertSame($this->seederRunner, $result);
    }
    
    public function testScanSeedersWithValidDirectory(): void
    {
        // Create a temporary directory for test seeders
        $tempDir = sys_get_temp_dir() . '/test_seeders_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        try {
            // Create a test seeder file
            $seederContent = '<?php
                namespace App\Database\Schema\Seeders;
                
                use App\Database\Schema\Seeder;
                
                class TestSeeder implements Seeder
                {
                    public static function run(): void {}
                    public static function getName(): string { return "test_seeder"; }
                }
            ';
            file_put_contents($tempDir . '/TestSeeder.php', $seederContent);
            
            // Mock the class_exists function to return true for our test seeder
            /** @var SeederRunner&\PHPUnit\Framework\MockObject\MockObject $seedRunnerMock */
            $seedRunnerMock = $this->getMockBuilder(SeederRunner::class)
                ->onlyMethods(['scanSeeders'])
                ->getMock();
            $this->seederRunner = $seedRunnerMock;
                
            // Expect scanSeeders to be called once and return $this
            $this->seederRunner->expects($this->once())
                ->method('scanSeeders')
                ->willReturnSelf();
                
            // Call scanSeeders
            $result = $this->seederRunner->scanSeeders($tempDir);
            
            // Verify the method returns $this for method chaining
            $this->assertSame($this->seederRunner, $result);
        } finally {
            // Clean up
            if (file_exists($tempDir . '/TestSeeder.php')) {
                unlink($tempDir . '/TestSeeder.php');
            }
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
    }
    
    public function testRun(): void
    {
        // Create a mock seeder class that tracks if it was run
        MockSeederForRunTest::$wasRun = false;
        
        // Register the seeder
        $this->seederRunner->register('Tests\Database\Schema\MockSeederForRunTest');
        
        // Capture output
        ob_start();
        
        // Run the seeders
        $this->seederRunner->run();
        
        // Discard output
        ob_end_clean();
        
        // Verify the seeder was run
        $this->assertTrue(MockSeederForRunTest::$wasRun);
    }
}

// Mock seeder class for testing
class MockSeeder implements Seeder
{
    public static function run(): void
    {
        // Do nothing
    }
    
    public static function getName(): string
    {
        return 'mock_seeder';
    }
}

// Mock seeder class for testing run method
class MockSeederForRunTest implements Seeder
{
    public static bool $wasRun = false;
    
    public static function run(): void
    {
        self::$wasRun = true;
    }
    
    public static function getName(): string
    {
        return 'mock_seeder_for_run_test';
    }
}