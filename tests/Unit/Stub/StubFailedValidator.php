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
use SchemaValidator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Util\PropertyPath;

final class StubFailedValidator implements ValidatorInterface
{
    public function support(\ReflectionType $type): bool
    {
        return true;
    }

    public function validate(Argument $argument, Context $context): void
    {
        $execution = $context->getExecution();

        $execution->buildViolation(Schema::INVALID_TYPE, [
            '{{ value }}' => formatValue($argument->getValueByArgumentName()),
            '{{ type }}'  => 'string',
        ])
            ->atPath(PropertyPath::append($context->getRootPath(), $argument->getName()))
            ->setCode(Schema::INVALID_TYPE_ERROR)
            ->addViolation()
        ;
    }
}
