<?php

declare(strict_types=1);

namespace Tests\ApiMocker\Example;

use Ranierif\AbstractApiMocker;

class ExampleApiMocker extends AbstractApiMocker
{
    protected function getProviderName(): string
    {
        return 'example';
    }

    /*
     * Add your mock methods here
     */
}
