<?php

declare(strict_types=1);

namespace Tests\Unit\Commands;

use Ranierif\Commands\MakeApiMockerCommand;
use Tests\TestCase;

/**
 * @internal
 */
class MakeApiMockerCommandTest extends TestCase
{
    private string $tempDir;

    private MakeApiMockerCommand $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/api-mocker-test-' . uniqid();
        mkdir($this->tempDir, 0777, true);

        $this->command = new MakeApiMockerCommand($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);

        \Mockery::close();
        parent::tearDown();
    }

    public function testConstructorSetsBaseDir(): void
    {
        // Arrange
        $baseDir = '/custom/path';

        // Act
        $command = new MakeApiMockerCommand($baseDir);

        // Assert
        $this->assertInstanceOf(MakeApiMockerCommand::class, $command);
    }

    public function testExecuteThrowsExceptionForEmptyName(): void
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Provider name is required');

        // Act
        $this->command->execute('');
    }

    public function testExecuteCreatesDirectories(): void
    {
        // Act
        ob_start();
        $this->command->execute('test');
        ob_end_clean();

        // Assert
        $this->assertDirectoryExists($this->tempDir . '/tests');
        $this->assertDirectoryExists($this->tempDir . '/tests/ApiMocker');
        $this->assertDirectoryExists($this->tempDir . '/tests/ApiMocker/Test');
        $this->assertDirectoryExists($this->tempDir . '/tests/ApiMocker/Test/json');
    }

    public function testExecuteCreatesFileWithCorrectContent(): void
    {
        // Arrange
        $providerName = 'test';
        $expectedFilePath = $this->tempDir . '/tests/ApiMocker/Test/testApiMocker.php';

        // Act
        ob_start();
        $this->command->execute($providerName);
        ob_end_clean();

        // Assert
        $this->assertFileExists($expectedFilePath);

        $fileContent = file_get_contents($expectedFilePath);
        $this->assertNotFalse($fileContent, 'Failed to read file contents');
        $this->assertStringContainsString('namespace Tests\ApiMocker\Test;', $fileContent);
        $this->assertStringContainsString('class testApiMocker extends AbstractApiMocker', $fileContent);
        $this->assertStringContainsString("return 'Test';", $fileContent);
    }

    public function testExecuteHandlesCapitalization(): void
    {
        // Act
        ob_start();
        $this->command->execute('TEST');
        ob_end_clean();

        // Assert
        $this->assertDirectoryExists($this->tempDir . '/tests/ApiMocker/TEST');
        $this->assertFileExists($this->tempDir . '/tests/ApiMocker/TEST/TESTApiMocker.php');
    }

    public function testExecuteOutputsSuccessMessage(): void
    {
        // Arrange
        $providerName = 'test';
        $expectedMessage = "ApiMocker created successfully: tests/ApiMocker/Test/testApiMocker.php\n";

        // Act
        ob_start();
        $this->command->execute($providerName);
        $output = ob_get_clean();

        // Assert
        $this->assertEquals($expectedMessage, $output);
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $scanned = scandir($dir);
        if ($scanned === false) {
            return;
        }

        $files = array_diff($scanned, ['.', '..']);
        foreach ($files as $file) {
            $path = "{$dir}/{$file}";
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }
}
