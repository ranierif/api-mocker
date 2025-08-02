<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ranierif\Contracts\ApiMockerInterface;
use Ranierif\Exceptions\MockFileNotFoundException;
use Ranierif\Traits\ApiMockerTrait;

/**
 * @internal
 */
class ApiMockerTraitTest extends TestCase
{
    use ApiMockerTrait;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test class that doesn't implement ApiMockerInterface
        if (! class_exists('Tests\ApiMocker\Badmock\BadmockApiMocker')) {
            eval('
                namespace Tests\ApiMocker\Badmock;
                class BadmockApiMocker {
                    // This class intentionally does not implement ApiMockerInterface
                }
            ');
        }
    }

    public function testShouldGetApiMockerInstance(): void
    {
        // Act
        $mocker = $this->getApiMocker('example');

        // Assert
        $this->assertInstanceOf(ApiMockerInterface::class, $mocker);
    }

    public function testShouldGetMockResponse(): void
    {
        // Arrange
        $expectedResponse = ['status' => 'success'];

        // Act
        $response = $this->getMockResponseAsArray('example', 'customers', 'success');

        // Assert
        $this->assertEquals($expectedResponse, $response);
    }

    public function testShouldThrowExceptionForInvalidProvider(): void
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Mocker class not found');

        // Act
        $this->getApiMocker('invalid');
    }

    public function testShouldThrowRuntimeExceptionWhenClassDoesNotImplementInterface(): void
    {
        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Mocker class Tests\ApiMocker\Badmock\BadmockApiMocker must implement ApiMockerInterface');

        // Act
        $this->getApiMocker('badmock');
    }

    public function testShouldThrowMockFileNotFoundExceptionWhenFileDoesNotExist(): void
    {
        // Assert
        $this->expectException(MockFileNotFoundException::class);
        $this->expectExceptionMessage('Mock file not found:');

        // Act
        $this->getMockResponse('example', 'nonexistent', 'endpoint');
    }

    public function testShouldThrowRuntimeExceptionWhenFailsToReadFile(): void
    {
        // Arrange
        $testDir = __DIR__ . '/../../tests/ApiMocker/Example/json/testdir';
        if (! is_dir($testDir)) {
            mkdir($testDir, 0777, true);
        }

        $problematicFile = $testDir . '/unreadable.json';
        file_put_contents($problematicFile, 'test content');
        chmod($problematicFile, 0000);

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to read mock file:');

        $originalErrorReporting = error_reporting();
        error_reporting($originalErrorReporting & ~E_WARNING);

        try {
            // Act
            $this->getMockResponse('example', 'testdir', 'unreadable');
        } finally {
            error_reporting($originalErrorReporting);

            if (file_exists($problematicFile)) {
                chmod($problematicFile, 0777);
                unlink($problematicFile);
            }
            if (is_dir($testDir)) {
                rmdir($testDir);
            }
        }
    }
}
