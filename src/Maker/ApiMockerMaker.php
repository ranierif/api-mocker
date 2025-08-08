<?php

declare(strict_types=1);

namespace Ranierif\Maker;

final class ApiMockerMaker
{
    public function make(string $providerName, ?string $baseDir = null): void
    {
        if ($providerName === '') {
            throw new \InvalidArgumentException('Provider name is required.');
        }

        $baseDir = $baseDir ?? (getcwd() ?: '');
        if ($baseDir === '') {
            throw new \RuntimeException('Failed to resolve current working directory.');
        }

        $testDir = $baseDir . '/tests/ApiMocker/' . $providerName;
        $jsonDir = $testDir . '/json';
        $classFile = $testDir . '/' . $providerName . 'ApiMocker.php';

        $stubPath = __DIR__ . '/stubs/api-mocker.stub';
        if (! file_exists($stubPath)) {
            throw new \RuntimeException('Stub file not found: api-mocker.stub');
        }

        if (file_exists($testDir) && ! is_dir($testDir)) {
            throw new \RuntimeException(sprintf('Failed to create directory: %s', $testDir));
        }
        if (! is_dir($testDir) && ! @mkdir($testDir, 0755, true) && ! is_dir($testDir)) {
            throw new \RuntimeException(sprintf('Failed to create directory: %s', $testDir));
        }

        if (file_exists($jsonDir) && ! is_dir($jsonDir)) {
            throw new \RuntimeException(sprintf('Failed to create directory: %s', $jsonDir));
        }
        if (! is_dir($jsonDir) && ! @mkdir($jsonDir, 0755, true) && ! is_dir($jsonDir)) {
            throw new \RuntimeException(sprintf('Failed to create directory: %s', $jsonDir));
        }

        $stubContent = file_get_contents($stubPath);
        if ($stubContent === false) {
            throw new \RuntimeException(sprintf('Failed to read stub file: %s', $stubPath));
        }

        $classContent = str_replace(
            ['{{providerName}}', '{{className}}'],
            [$providerName, $providerName . 'ApiMocker'],
            $stubContent
        );

        if (@file_put_contents($classFile, $classContent) === false) {
            throw new \RuntimeException(sprintf('Failed to write class file: %s', $classFile));
        }
    }
}
