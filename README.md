<p align="center">
    <a href="https://github.com/Myaza-Software" target="_blank">
        <img src="https://myaza-software.github.io/storage/schema/icon.svg" height="300px">
    </a>
    <h1 align="center">Schema Validator</h1>
</p>

[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FMyaza-Software%2FSchema-Validator%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/Myaza-Software/Schema-Validator/master)
[![Code Coverage](https://scrutinizer-ci.com/g/Myaza-Software/Schema-Validator/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Myaza-Software/Schema-Validator/?branch=master)
[![Lint CI](https://github.com/Myaza-Software/Schema-Validator/actions/workflows/lint.yml/badge.svg)](https://github.com/Myaza-Software/Schema-Validator/actions/workflows/lint.yml)
[![Stats-Analysis CI](https://github.com/Myaza-Software/Schema-Validator/actions/workflows/stats-analysis.yml/badge.svg)](https://github.com/Myaza-Software/Schema-Validator/actions/workflows/stats-analysis.yml)


Description
-------------------------
Check mapping array to object


Requirements
-------------------------
- php >= 8.0
- symfony >= 4.0
- symfony/serializer >= 4.0

## Installation

The package could be installed with composer:

```
composer install myaza-software/schema-validator
```

## Supported type:
- String,Float,Int,Array
- Union type
- Array of object/string
- Uuid symfony/ramsey
- Enum MyCLabs
- DateTimeInterface


## Example

```php
<?php

declare(strict_types=1);

namespace App;

use App\DTO\RefundWebhook;
use SchemaValidator\Schema;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ValidateDtoCommand extends Command
{
    protected static $defaultName = 'validate:dto';

    public function __construct(
        private ValidatorInterface  $validator,
        private SerializerInterface $serializer,
    ){
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $errors = $this->validator->validate([
            'type' => 123123,
            'uuid' => '',
            'price' => [
                'vault' => [
                    'value' => 1,
                    'names' => [
                        ['value' => 1, 'createdAt' => '']
                    ]
                ],'value' => 1]
        ], new Schema([
            'type'       => RefundWebhook::class,
            'strictTypes' => true
        ]));


        $output->write($this->serializer->serialize($errors,'json'));

        return self::SUCCESS;
    }
}
```

### Output:

```json
{
  "type": "https:\/\/symfony.com\/errors\/validation",
  "title": "Validation Failed",
  "detail": "price.vault.value: This value should be of type string.\nprice.vault.names[0].value: This value should be of type string.\nprice.vault.names[0].createdAt: This is not a valid date.\nprice.value: This value should be of type float.\nuuid: This value should not be blank.\ndate: This is not a valid date.\ntype: This value should be of type string.",
  "violations": [
    {
      "propertyPath": "price.vault.value",
      "title": "This value should be of type string.",
      "parameters": {
        "{{ value }}": "1",
        "{{ type }}": "string"
      },
      "type": "urn:uuid:ba785a8c-82cb-4283-967c-3cf342181b40"
    },
    {
      "propertyPath": "price.vault.names[0].value",
      "title": "This value should be of type string.",
      "parameters": {
        "{{ value }}": "1",
        "{{ type }}": "string"
      },
      "type": "urn:uuid:ba785a8c-82cb-4283-967c-3cf342181b40"
    },
    {
      "propertyPath": "price.vault.names[0].createdAt",
      "title": "This is not a valid date.",
      "parameters": [],
      "type": "urn:uuid:780e721d-ce3c-42f3-854d-f990ebffdc4f"
    },
    {
      "propertyPath": "price.value",
      "title": "This value should be of type float.",
      "parameters": {
        "{{ value }}": "1",
        "{{ type }}": "float"
      },
      "type": "urn:uuid:ba785a8c-82cb-4283-967c-3cf342181b40"
    },
    {
      "propertyPath": "uuid",
      "title": "This value should not be blank.",
      "parameters": {
        "{{ value }}": "\"\""
      },
      "type": "urn:uuid:c1051bb4-d103-4f74-8988-acbcafc7fdc3"
    },
    {
      "propertyPath": "date",
      "title": "This is not a valid date.",
      "parameters": [],
      "type": "urn:uuid:780e721d-ce3c-42f3-854d-f990ebffdc4f"
    },
    {
      "propertyPath": "type",
      "title": "This value should be of type string.",
      "parameters": {
        "{{ value }}": "123123",
        "{{ type }}": "string"
      },
      "type": "urn:uuid:ba785a8c-82cb-4283-967c-3cf342181b40"
    }
  ]
}
```
