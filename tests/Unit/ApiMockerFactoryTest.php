<?php

declare(strict_types=1);

namespace Tests\Unit;

use Ranierif\AbstractApiMocker;
use Ranierif\ApiMockerFactory;
use Ranierif\Contracts\ApiMockerInterface;
use Tests\TestCase;

/**
 * @internal
 */
class ApiMockerFactoryTest extends TestCase
{
    private ApiMockerFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $testMocker = new class extends AbstractApiMocker {
            protected function getProviderName(): string
            {
                return 'test';
            }
        };

        $this->factory = new ApiMockerFactory([$testMocker]);
    }

    public function testShouldCreateApiMockerInstance(): void
    {
        $provider = 'test';

        $mocker = $this->factory->create($provider);

        $this->assertInstanceOf(ApiMockerInterface::class, $mocker);
    }

    public function testShouldThrowExceptionWhenProviderNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No mocker found for provider: invalid');

        $this->factory->create('invalid');
    }

    public function testShouldCreateFactoryWithEmptyMockersArray(): void
    {
        $factory = new ApiMockerFactory([]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No mocker found for provider: test');

        $factory->create('test');
    }
}
