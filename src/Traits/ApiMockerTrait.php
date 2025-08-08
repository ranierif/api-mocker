<?php

declare(strict_types=1);

namespace Ranierif\Traits;

use Ranierif\ApiMockerFactory;
use Ranierif\Contracts\ApiMockerInterface;

trait ApiMockerTrait
{
    /**
     * Get a mocker instance for a specific provider.
     */
    protected function getApiMocker(string $provider): ApiMockerInterface
    {
        $factory = new ApiMockerFactory([
            $this->createMockerInstance($provider),
        ]);

        return $factory->create($provider);
    }

    /**
     * Create mocker instance based on provider name.
     * @throws \InvalidArgumentException
     */
    protected function createMockerInstance(string $provider): ApiMockerInterface
    {
        $className = sprintf(
            'Tests\ApiMocker\%s\%sApiMocker',
            ucfirst($provider),
            ucfirst($provider)
        );

        if (! class_exists($className)) {
            throw new \InvalidArgumentException(
                sprintf('Mocker class not found: %s', $className)
            );
        }

        $instance = new $className();

        if (! $instance instanceof ApiMockerInterface) {
            throw new \RuntimeException(sprintf(
                'Mocker class %s must implement ApiMockerInterface',
                $className
            ));
        }

        return $instance;
    }

    /**
     * Get mock response as string.
     */
    protected function getMockResponse(string $provider, string $endpoint, string $scenario): string
    {
        return $this->getApiMocker($provider)->getResponse($endpoint, $scenario);
    }

    /**
     * Get a mock response as an array.
     * @return array<string, mixed>
     * @throws \RuntimeException
     */
    protected function getMockResponseAsArray(string $provider, string $endpoint, string $scenario): array
    {
        $decoded = json_decode($this->getMockResponse($provider, $endpoint, $scenario), true);

        return is_array($decoded) ? $decoded : [];
    }
}
