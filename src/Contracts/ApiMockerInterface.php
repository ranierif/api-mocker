<?php

declare(strict_types=1);

namespace Ranierif\Contracts;

interface ApiMockerInterface
{
    public function getResponse(string $endpoint, string $type): string;

    public function getMockPath(): string;

    public function supports(string $provider): bool;
}
