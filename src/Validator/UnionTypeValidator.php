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
use function SchemaValidator\formatValue;
use SchemaValidator\Schema;
use Symfony\Component\Validator\Util\PropertyPath;

final class UnionTypeValidator implements Validator
{
    /**
     * UnionTypeValidator constructor.
     *
     * @param iterable<Validator> $validators
     */
    public function __construct(
        private iterable $validators,
    ) {
    }

    public function isEnabledCircularReferenceStorage(): bool
    {
        return false;
    }

    public function support(\ReflectionType $type): bool
    {
        return $type instanceof \ReflectionUnionType;
    }

    public function validate(Argument $argument, Context $context): void
    {
        $unionType = $argument->type();
        $execution = $context->execution();

        if (!$unionType instanceof \ReflectionUnionType) {
            throw new \InvalidArgumentException('Type expected:' . \ReflectionUnionType::class);
        }

        $countErrors = 0;
        $types       = $unionType->getTypes();

        foreach ($types as $type) {
            $executionContext = clone $execution;

            foreach ($this->validators as $validator) {
                if (!$validator->support($type)) {
                    continue;
                }

                $validator->validate($argument->withType($type), $context->withExecution($executionContext));

                break;
            }

            if (count($executionContext->getViolations()) > 0) {
                ++$countErrors;
            }
        }

        if ($countErrors !== count($types)) {
            return;
        }

        $execution->buildViolation(Schema::INVALID_TYPE, [
            '{{ value }}' => formatValue($argument->currentValue()),
            '{{ type }}'  => $this->formatUnionType($unionType),
        ])
            ->atPath(PropertyPath::append($context->path(), $argument->name()))
            ->setInvalidValue($argument->currentValue())
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
