<?php

declare(strict_types=1);

namespace Ranierif;

use Ranierif\Contracts\ApiMockerInterface;

class ApiMockerFactory
{
    /** @var ApiMockerInterface[] */
    private array $mockers;

    /**
     * @param ApiMockerInterface[] $mockers
     */
    public function __construct(array $mockers = [])
    {
        $this->mockers = $mockers;
    }

    public function create(string $provider): ApiMockerInterface
    {
        foreach ($this->mockers as $mocker) {
            if ($mocker->supports($provider)) {
                return $mocker;
            }
        }

        throw new \InvalidArgumentException(
            sprintf('No mocker found for provider: %s', $provider)
        );
    }
}
