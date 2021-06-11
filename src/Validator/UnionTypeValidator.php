<?php

declare(strict_types=1);

namespace SchemaValidator\Validator;

use SchemaValidator\Argument;
use SchemaValidator\Context;
use SchemaValidator\Schema;

final class UnionTypeValidator implements ValidatorInterface
{
    /**
     * UnionTypeValidator constructor.
     *
     * @param iterable<ValidatorInterface> $validators
     */
    public function __construct(
        private iterable $validators,
    ) {
    }

    public function support(\ReflectionType $type): bool
    {
        return $type instanceof \ReflectionUnionType;
    }

    public function validate(Argument $argument, Context $context): void
    {
        $unionType = $argument->getType();

        if (!$unionType instanceof \ReflectionUnionType) {
            throw new \InvalidArgumentException('Invalid reflection union argument');
        }

        $countErrors  = 0;
        $types        = $unionType->getTypes();

        foreach ($types as $type) {
            $executionContext  = clone $context->getExecution();

            foreach ($this->validators as $validator) {
                if ($validator->support($type)) {
                    $validator->validate($argument->withType($type), $context->withExecution($executionContext));
                }
            }

            if (count($executionContext->getViolations()) > 0) {
                $countErrors++;
            }
        }

        if ($countErrors !== count($types)) {
            return;
        }


        $context->getExecution()->buildViolation(Schema::INVALID_TYPE)
            ->setParameter('{{ value }}', (string) $argument->getValueByArgumentName())
            ->setParameter('{{ type }}', $this->formatUnionType($unionType))
            ->setCode(Schema::INVALID_TYPE_ERROR)
            ->addViolation()
        ;
    }


    private function formatUnionType(\ReflectionUnionType $type): string
    {
        $types = array_map(fn (\ReflectionNamedType $type): string => $type->getName(), $type->getTypes());

        return implode('|', $types);
    }
}
