<?php

declare(strict_types=1);

namespace Tests\Unit\Commands;

use PHPUnit\Framework\TestCase;
use Ranierif\Commands\MakeApiMockerCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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

        $this->command = new MakeApiMockerCommand();

        $this->setBaseDir($this->command, $this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        \Mockery::close();
        parent::tearDown();
    }

    public function testExecuteThrowsExceptionForEmptyName(): void
    {
        // Arrange
        $input = $this->createMockedInput('');
        $output = $this->createMockedOutput();

        // Act
        $result = $this->command->execute($input, $output);

        // Assert
        $this->assertEquals(Command::FAILURE, $result);
    }

    public function testExecuteCreatesDirectories(): void
    {
        // Arrange
        $input = $this->createMockedInput('test');
        $output = $this->createMockedOutput();

        // Act
        $this->command->execute($input, $output);

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

        $input = $this->createMockedInput($providerName);
        $output = $this->createMockedOutput();

        // Act
        $this->command->execute($input, $output);

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
        // Arrange
        $input = $this->createMockedInput('TEST');
        $output = $this->createMockedOutput();

        // Act
        $this->command->execute($input, $output);

        // Assert
        $this->assertDirectoryExists($this->tempDir . '/tests/ApiMocker/TEST');
        $this->assertFileExists($this->tempDir . '/tests/ApiMocker/TEST/TESTApiMocker.php');
    }

    public function testExecuteOutputsSuccessMessage(): void
    {
        // Arrange
        $providerName = 'test';
        $expectedMessage = sprintf(
            '<info>ApiMocker created successfully: tests/ApiMocker/%s/%sApiMocker.php</info>',
            'Test',
            'test'
        );

        $input = $this->createMockedInput($providerName);
        $output = $this->createMockedOutput($expectedMessage);

        // Act
        $result = $this->command->execute($input, $output);

        // Assert
        $this->assertEquals(Command::SUCCESS, $result);
    }

    public function testGetProjectRootFallsBackToAutoloadPathsWhenNoComposerJsonInCwd(): void
    {
        $originalCwd = getcwd();
        if ($originalCwd === false) {
            $this->markTestSkipped('getcwd() retornou false');
        }

        $tempCwd = sys_get_temp_dir() . '/api-mocker-cwd-' . uniqid();
        mkdir($tempCwd, 0777, true);
        chdir($tempCwd);

        try {
            $command = new MakeApiMockerCommand();

            $ref = new \ReflectionClass($command);
            $method = $ref->getMethod('getProjectRoot');
            $method->setAccessible(true);
            $result = $method->invoke($command);

            $cmdFile = (new \ReflectionClass(MakeApiMockerCommand::class))->getFileName();
            if ($cmdFile === false) {
                $this->fail('It was not possible to obtain the file path of the MakeApiMockerCommand class.');
            }
            $cmdDir = dirname($cmdFile);

            $autoloadCandidate = realpath($cmdDir . '/../../vendor/autoload.php');
            if ($autoloadCandidate === false) {
                $this->markTestSkipped('vendor/autoload.php not found for the expected calculation');
            }

            $expectedProjectRoot = dirname($autoloadCandidate, 2);

            $this->assertSame($expectedProjectRoot, $result);
        } finally {
            if ($originalCwd !== false) {
                chdir($originalCwd);
            }
            if (is_dir($tempCwd)) {
                @rmdir($tempCwd);
            }
        }
    }

    private function createMockedInput(string $name): InputInterface
    {
        $input = \Mockery::mock(InputInterface::class);
        $input->allows('getArgument')->with('name')->andReturn($name);

        return $input;
    }

    private function createMockedOutput(?string $expectedMessage = null): OutputInterface
    {
        $output = \Mockery::mock(OutputInterface::class);

        if ($expectedMessage !== null) {
            $output->expects('writeln')->with($expectedMessage);
        } else {
            $output->allows('writeln');
        }

        return $output;
    }

    private function setBaseDir(MakeApiMockerCommand $command, string $baseDir): void
    {
        $reflection = new \ReflectionClass($command);
        $property = $reflection->getProperty('baseDir');
        $property->setAccessible(true);
        $property->setValue($command, $baseDir);
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
