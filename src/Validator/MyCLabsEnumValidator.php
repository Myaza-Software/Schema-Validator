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
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidator;

final class MyCLabsEnumValidator implements ValidatorInterface, PriorityInterface
{
    public function __construct(
        private SymfonyValidator $validator,
    ) {
    }

    public function support(\ReflectionType $type): bool
    {
        if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
            return false;
        }

        if (!class_exists(Enum::class) && !is_subclass_of($type->getName(), Enum::class)) {
            return false;
        }

        return true;
    }

    public function validate(Argument $argument, Context $context): void
    {
        $type = $argument->getType();

        if (!$type instanceof \ReflectionNamedType) {
            throw new \InvalidArgumentException('Invalid reflection named argument');
        }

        /** @var string $value */
        $value = $argument->getValueByArgumentName();
        /** @var class-string<Enum> $enum */
        $enum      = $type->getName();
        $execution = $context->getExecution();


        $this->validator->inContext($execution)
            ->validate($value, [
                new Choice([
                    'choices' => forward_static_call_array([$enum, 'toArray'], []),
                ]),
            ])
        ;
    }

    public static function getPriority(): int
    {
        return 2;
    }
}
