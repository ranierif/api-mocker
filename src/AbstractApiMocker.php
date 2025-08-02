<?php

declare(strict_types=1);

namespace Ranierif;

use Ranierif\Contracts\ApiMockerInterface;
use Ranierif\Exceptions\MockFileNotFoundException;

abstract class AbstractApiMocker implements ApiMockerInterface
{
    protected string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? dirname(__DIR__) . '/tests/ApiMocker';
    }

    public function getResponse(string $endpoint, string $type): string
    {
        $filePath = $this->resolveFilePath($endpoint, $type);

        if (! file_exists($filePath)) {
            throw new MockFileNotFoundException(
                sprintf('Mock file not found: %s', $filePath)
            );
        }

        $contents = @file_get_contents($filePath);

        if ($contents === false) {
            throw new \RuntimeException(sprintf('Failed to read mock file: %s', $filePath));
        }

        return $contents;
    }

    public function getMockPath(): string
    {
        return sprintf('%s/%s/json', $this->basePath, ucfirst($this->getProviderName()));
    }

    public function supports(string $provider): bool
    {
        return strtolower($provider) === $this->getProviderName();
    }

    protected function resolveFilePath(string $endpoint, string $type): string
    {
        return sprintf(
            '%s/%s/%s.json',
            $this->getMockPath(),
            $endpoint,
            $type
        );
    }

    abstract protected function getProviderName(): string;
}
