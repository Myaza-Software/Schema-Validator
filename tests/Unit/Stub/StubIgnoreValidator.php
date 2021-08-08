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
use SchemaValidator\Validator\Validator;

final class StubIgnoreValidator implements Validator
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
