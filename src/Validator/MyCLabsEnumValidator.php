<?php
/**
 * Schema Validator
 *
 * @author    Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Validator;

use MyCLabs\Enum\Enum;
use SchemaValidator\Argument;
use SchemaValidator\Context;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Util\PropertyPath;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidator;

final class MyCLabsEnumValidator implements Validator
{
    public function __construct(
        private SymfonyValidator $validator,
    ) {
    }

    public function isEnabledCircularReferenceStorage(): bool
    {
        return false;
    }

    public function support(\ReflectionType $type): bool
    {
        if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
            return false;
        }

        if (!class_exists(Enum::class) || !is_subclass_of($type->getName(), Enum::class)) {
            return false;
        }

        return true;
    }

    public function validate(Argument $argument, Context $context): void
    {
        $type = $argument->type();

        if (!$type instanceof \ReflectionNamedType) {
            throw new \InvalidArgumentException('Type expected:' . \ReflectionNamedType::class);
        }

        /** @var string $value */
        $value = $argument->currentValue();
        /** @var class-string<Enum> $enum */
        $enum      = $type->getName();
        $execution = $context->execution();

        $this->validator->inContext($execution)
            ->atPath(PropertyPath::append($context->path(), $argument->name()))
            ->validate($value, [
                new Choice([
                    'choices' => forward_static_call_array([$enum, 'toArray'], []),
                ]),
            ])
        ;
    }
}
