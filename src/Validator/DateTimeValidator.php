<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Validator;

use SchemaValidator\Argument;
use SchemaValidator\Context;
use Symfony\Component\Validator\Util\PropertyPath;

final class DateTimeValidator implements Validator
{
    private const INVALID_DATE_MESSAGE = 'This is not a valid date.';
    private const INVALID_DATE_CODE    = '780e721d-ce3c-42f3-854d-f990ebffdc4f';

    public function isEnabledCircularReferenceStorage(): bool
    {
        return false;
    }

    public function support(\ReflectionType $type): bool
    {
        return $type instanceof \ReflectionNamedType && is_subclass_of($type->getName(), \DateTimeInterface::class);
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function validate(Argument $argument, Context $context): void
    {
        $execution = $context->execution();
        $value     = $argument->currentValue();

        if (is_string($value) && is_int(strtotime($value))) {
            return;
        }

        $execution->buildViolation(self::INVALID_DATE_MESSAGE)
            ->atPath(PropertyPath::append($context->path(), $argument->name()))
            ->setInvalidValue($value)
            ->setCode(self::INVALID_DATE_CODE)
            ->addViolation()
        ;
    }
}
