<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Test\Unit\Stub;

use SchemaValidator\Argument;
use SchemaValidator\Context;
use SchemaValidator\Validator\ValidatorInterface;

final class StubIgnoreValidator implements ValidatorInterface
{
    public function support(\ReflectionType $type): bool
    {
        return false;
    }

    public function validate(Argument $argument, Context $context): void
    {
        // TODO: Implement validate() method.
    }
}
