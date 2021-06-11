<?php

declare(strict_types=1);

namespace SchemaValidator\Validator;

use SchemaValidator\Argument;
use SchemaValidator\Context;
use Symfony\Component\Validator\Constraints\Type as ConstraintType;
use Symfony\Component\Validator\Util\PropertyPath;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidator;

final class PrimitiveValidator implements ValidatorInterface
{
    private const SUPPORT_CAST_TYPE = [
        'int'    => ['string', 'float'],
        'string' => ['int', 'float'],
        'float'  => ['int', 'string'],
    ];

    public function __construct(
        private SymfonyValidator $validator,
    ) {
    }

    public function support(\ReflectionType $type): bool
    {
        return $type instanceof \ReflectionNamedType && $type->isBuiltin();
    }

    public function validate(Argument $argument, Context $context): void
    {
        $type = $argument->getType();

        if (!$type instanceof \ReflectionNamedType) {
            throw new \InvalidArgumentException('Invalid reflection named argument');
        }

        $value    = $argument->getValueByArgumentName();
        $typeName = $type->getName();
        $types    = [$typeName];

        if (($castTypes = self::SUPPORT_CAST_TYPE[$typeName] ?? null) !== null) {
            $types = $types + $castTypes;
        }

        $constraint = new ConstraintType([
            'type' => $types + ($type->allowsNull() ? ['null'] : []),
        ]);

        $rootPath = $context->getRootPath() === $argument->getName() ? '' : $context->getRootPath();

        $this->validator->inContext($context->getExecution())
            ->atPath(PropertyPath::append($rootPath, $argument->getName()))
            ->validate($value, [$constraint])
        ;
    }
}
