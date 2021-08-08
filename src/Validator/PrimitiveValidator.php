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
use Symfony\Component\Validator\Constraints\Type as ConstraintType;
use Symfony\Component\Validator\Util\PropertyPath;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidator;

final class PrimitiveValidator implements Validator
{
    private const SUPPORT_CAST_TYPE = [
        'int'    => ['int', 'string', 'float'],
        'string' => ['string', 'int', 'float'],
        'float'  => ['float', 'int', 'string'],
    ];

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
        return $type instanceof \ReflectionNamedType && $type->isBuiltin();
    }

    public function validate(Argument $argument, Context $context): void
    {
        $type = $argument->type();

        if (!$type instanceof \ReflectionNamedType) {
            throw new \InvalidArgumentException('Type expected:' . \ReflectionNamedType::class);
        }

        /** @var string|int|float|array $value */
        $value    = $argument->currentValue();
        $typeName = $type->getName();
        $types    = [$typeName];

        if (!$context->strictTypes() && array_key_exists($typeName, self::SUPPORT_CAST_TYPE)) {
            $types = self::SUPPORT_CAST_TYPE[$typeName];
        }

        $constraint = new ConstraintType([
            'type' => $types,
        ]);

        $this->validator->inContext($context->execution())
            ->atPath(PropertyPath::append($context->path(), $argument->name()))
            ->validate($value, [$constraint])
        ;
    }
}
