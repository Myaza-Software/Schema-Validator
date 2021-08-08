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
use function SchemaValidator\formatValue;
use SchemaValidator\Schema;
use SchemaValidator\Validator\Validator;
use Symfony\Component\Validator\Util\PropertyPath;

final class StubFailedValidator implements Validator
{
    public function support(\ReflectionType $type): bool
    {
        return true;
    }

    public function validate(Argument $argument, Context $context): void
    {
        $execution = $context->execution();
        $type      = $argument->type();

        assert($type instanceof \ReflectionNamedType);

        $execution->buildViolation(Schema::INVALID_TYPE, [
            '{{ value }}' => formatValue($argument->currentValue()),
            '{{ type }}'  => $type->getName(),
        ])
            ->atPath(PropertyPath::append($context->path(), $argument->name()))
            ->setCode(Schema::INVALID_TYPE_ERROR)
            ->addViolation()
        ;
    }
}
