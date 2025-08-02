# API Mocker

A simple and flexible package to mock API responses for testing in PHP applications. This package helps you create and manage mock responses for your API integrations, making your tests more reliable and faster.

## Introduction

API Mocker provides an easy way to mock external API responses in your PHP tests. Instead of making real HTTP requests during testing, you can use predefined JSON responses, making your tests:

- Faster (no actual HTTP requests)
- More reliable (no dependency on external services)
- Deterministic (you control the responses)
- Offline-capable (no internet connection required)

## Requirements

- PHP 8.2 or higher
- ext-json
- Composer

## Installation

You can install the package via composer:

```shell
bash composer require --dev ranierif/api-mocker
```

## Usage

### 1. Creating a New API Mocker

First, create a new API mocker for your provider using the command:

```shell
bash vendor/bin/make-api-mocker YourProviderName
```

For example, to create a Stripe API mocker:

```shell
bash vendor/bin/make-api-mocker Stripe
```

This will create:

```markdown
tests/ 
└── ApiMocker/ 
    └── Stripe/ 
        ├── StripeApiMocker.php 
        └── json/
```

### 2. Adding Mock Responses

Create JSON files in the `json` directory for your mock responses. The structure should be:

```markdown
tests/ApiMocker/Stripe/json/
├── customers/
│   ├── success.json
│   └── error.json
└── payments/
    ├── success.json
    └── error.json
```

Example JSON response file (`tests/ApiMocker/Stripe/json/customers/success.json`):

```json
{
  "id": "cus_123456",
  "object": "customer",
  "email": "test@example.com",
  "name": "Test Customer"
}
```

### 3. Using in Tests

Here's how to use the API mocker in your tests:

```php
<?php

namespace Tests\Feature\Payment;

use Illuminate\Http\Response;
use Mockery;
use Ranierif\Traits\ApiMockerTrait;
use Tests\TestCase;
use Tests\ApiMocker\stripe\StripeApiMocker;

class PaymentTest extends TestCase
{
    use ApiMockerTrait;

    public function test_successful_customer_creation(): void
    {
        // Arrange
        $mockResponseBody = $this->getMockResponseAsArray('stripe', 'customers', 'success');
                
        $this->instance(
            StripeApiService::class,
            Mockery::mock(StripeApiService::class, function (MockInterface $mock) use ($mockResponseBody) {
                return $mock->shouldReceive('requestCreateCustomer')->andReturn([
                    'status_code' => Response::HTTP_OK,
                    'body' => $mockResponseBody,
                ]);
            })
        );
        
        // Act
        $response = $this
            ->postJson(
                '/api/customers',
                [
                    'name' => 'Test Customer',
                    'email' => 'test@example.com',
                ]
            );
        
        // Assert
        $this->assertJson($response);
        $this->assertEquals($mockResponseBody['id'], $response['id']);
    }
}
```

### 4. Creating Custom Responses

You can extend the base ApiMocker class to add custom methods for your specific needs:

```php
<?php

namespace Tests\ApiMocker\Stripe;

use Ranierif\ApiMocker\AbstractApiMocker;

class StripeApiMocker extends AbstractApiMocker
{
    protected function getProviderName(): string
    {
        return 'stripe';
    }

    public function getCustomerSuccessResponse(): string
    {
        return $this->getResponse('customers', 'success');
    }

    public function getCustomerErrorResponse(): string
    {
        return $this->getResponse('customers', 'error');
    }
}
```

## Testing Different Scenarios

You can create different JSON files for various scenarios:

- Success responses
- Error responses
- Validation failures
- Special cases

Simply create a new JSON file with an appropriate name and use it in your tests:

```php
// Test success scenario
$successResponse = $mocker->getResponse('payments', 'success');

// Test error scenario
$errorResponse = $mocker->getResponse('payments', 'error');

// Test validation failure
$validationResponse = $mocker->getResponse('payments', 'validation_error');
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
