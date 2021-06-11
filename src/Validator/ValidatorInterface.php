<?php

declare(strict_types=1);

namespace SchemaValidator\Validator;

use SchemaValidator\Argument;
use SchemaValidator\Context;

interface ValidatorInterface
{
    public function support(\ReflectionType $type): bool;

    public function validate(Argument $argument, Context $context): void;
}
