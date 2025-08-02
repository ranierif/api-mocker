<?php

declare(strict_types=1);

namespace Ranierif\Commands;

class MakeApiMockerCommand
{
    private string $baseDir;

    private string $stubPath;

    public function __construct(?string $baseDir = null)
    {
        $this->baseDir = $baseDir ?? (getcwd() ?: '');
        $this->stubPath = __DIR__ . '/stubs/api-mocker.stub';
    }

    public function execute(string $name): void
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Provider name is required');
        }

        $providerName = ucfirst($name);
        $className = "{$name}ApiMocker";

        $this->createDirectories($providerName);
        $this->createMockerFile($className, $providerName);

        echo "ApiMocker created successfully: tests/ApiMocker/{$providerName}/{$className}.php\n";
    }

    private function createDirectories(string $providerName): void
    {
        $directories = [
            $this->baseDir . '/tests',
            $this->baseDir . '/tests/ApiMocker',
            $this->baseDir . '/tests/ApiMocker/' . $providerName,
            $this->baseDir . '/tests/ApiMocker/' . $providerName . '/json',
        ];

        foreach ($directories as $directory) {
            if (! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
        }
    }

    private function createMockerFile(string $className, string $providerName): void
    {
        $stub = file_get_contents($this->stubPath) ?: '';
        $content = str_replace(
            ['{{className}}', '{{providerName}}'],
            [$className, $providerName],
            $stub
        );

        $filePath = "{$this->baseDir}/tests/ApiMocker/{$providerName}/{$className}.php";
        file_put_contents($filePath, $content);
    }
}
