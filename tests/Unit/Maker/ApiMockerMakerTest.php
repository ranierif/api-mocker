<?php

declare(strict_types=1);

namespace Tests\Unit\Maker;

use PHPUnit\Framework\TestCase;
use Ranierif\Exceptions\ApiMockerMakerException;
use Ranierif\Maker\ApiMockerMaker;

/**
 * @internal
 */
final class ApiMockerMakerTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/api-mocker-maker-' . uniqid('', true);
        if (! is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    public function testMakeCreatesStructureAndClassFile(): void
    {
        // Arrange
        $provider = 'BankInter';

        // Verifica se o stub existe (para não ter falso negativo)
        $stubPath = dirname(__DIR__, 3) . '/src/Maker/stubs/api-mocker.stub';
        $rootStub = dirname(__DIR__, 3) . '/api-mocker.stub';
        if (! file_exists($stubPath) && ! file_exists($rootStub)) {
            $this->markTestSkipped('Stub file not found in expected locations for ApiMockerMaker.');
        }

        $maker = new ApiMockerMaker();

        // Act
        $maker->make($provider, $this->tempDir);

        // Assert: diretórios
        $base = $this->tempDir . '/tests/ApiMocker/' . $provider;
        $this->assertDirectoryExists($base, 'Provider directory not created');
        $this->assertDirectoryExists($base . '/json', 'JSON directory not created');

        // Assert: arquivo de classe
        $classFile = $base . '/' . $provider . 'ApiMocker.php';
        $this->assertFileExists($classFile, 'Class file not created');

        $content = file_get_contents($classFile);
        $this->assertNotFalse($content, 'Failed to read generated class file');

        // Verifica substituição dos placeholders
        $this->assertStringContainsString("namespace Tests\\ApiMocker\\{$provider};", $content);
        $this->assertStringContainsString("class {$provider}ApiMocker extends AbstractApiMocker", $content);
        $this->assertStringContainsString("return '{$provider}';", $content);
    }

    public function testThrowsWhenProviderIsEmpty(): void
    {
        $this->expectException(ApiMockerMakerException::class);
        $this->expectExceptionMessage('Provider name is required.');

        (new ApiMockerMaker())->make('', $this->tempDir);
    }

    public function testThrowsWhenBaseDirCannotBeResolved(): void
    {
        $this->expectException(ApiMockerMakerException::class);
        $this->expectExceptionMessage('Failed to resolve current working directory.');

        (new ApiMockerMaker())->make('AnyProvider', '');
    }

    public function testThrowsWhenCannotCreateProviderDirectory(): void
    {
        // Arrange
        $provider = 'CantCreateDir';
        $providerPath = $this->tempDir . '/tests/ApiMocker/' . $provider;

        $dirUp = dirname($providerPath);
        if (! is_dir($dirUp)) {
            mkdir($dirUp, 0777, true);
        }

        file_put_contents($providerPath, 'I am a file, not a directory');

        $this->expectException(ApiMockerMakerException::class);
        $this->expectExceptionMessage('Failed to create directory: ' . $providerPath);

        (new ApiMockerMaker())->make($provider, $this->tempDir);
    }

    public function testThrowsWhenCannotCreateJsonDirectory(): void
    {
        // Arrange
        $provider = 'CantCreateJson';
        $base = $this->tempDir . '/tests/ApiMocker/' . $provider;
        $jsonPath = $base . '/json';

        if (! is_dir($base)) {
            mkdir($base, 0777, true);
        }
        file_put_contents($jsonPath, 'file that blocks json dir creation');

        $this->expectException(ApiMockerMakerException::class);
        $this->expectExceptionMessage('Failed to create directory for json: ' . $jsonPath);

        (new ApiMockerMaker())->make($provider, $this->tempDir);
    }

    public function testThrowsWhenCannotWriteClassFile(): void
    {
        // Arrange
        $provider = 'WriteFails';
        $base = $this->tempDir . '/tests/ApiMocker/' . $provider;
        $json = $base . '/json';
        $classFile = $base . '/' . $provider . 'ApiMocker.php';

        mkdir($base, 0777, true);
        mkdir($json, 0777, true);

        $originalPerms = fileperms($base);
        chmod($base, 0555);

        $this->expectException(ApiMockerMakerException::class);
        $this->expectExceptionMessage('Failed to write class file: ' . $classFile);

        try {
            (new ApiMockerMaker())->make($provider, $this->tempDir);
        } finally {
            if ($originalPerms !== false) {
                chmod($base, $originalPerms & 0777 ?: 0777);
            } else {
                chmod($base, 0777);
            }
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach (array_diff($items, ['.', '..']) as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @chmod($path, 0777);
                @unlink($path);
            }
        }

        @chmod($dir, 0777);
        @rmdir($dir);
    }
}
