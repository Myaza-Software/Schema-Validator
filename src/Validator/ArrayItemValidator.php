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
use SchemaValidator\CollectionInfoExtractor\CollectionInfoExtractor;
use SchemaValidator\Context;
use SchemaValidator\Schema;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidator;

final class ArrayItemValidator implements ValidatorInterface
{
    public function __construct(
        private SymfonyValidator $validator,
        private CollectionInfoExtractor $extractor,
    ) {
    }

    public function support(\ReflectionType $type): bool
    {
        return $type instanceof \ReflectionNamedType && $type->isBuiltin() && 'array' === $type->getName();
    }

    public function validate(Argument $argument, Context $context): void
    {
        $type = $context->getRootType();

        if (!class_exists($type) && !interface_exists($type)) {
            return;
        }

        $argumentName = $argument->getName();
        $valueType    = $this->extractor->getValueType($type, $argumentName);

        if (null === $valueType->getType()) {
            return;
        }

        /** @var array<string,array<string,mixed>|int|string|float> $value */
        $value        = is_array($argument->getValueByArgumentName()) ? $argument->getValueByArgumentName() : [];
        $rootPath     = $context->getRootPath();
        $propertyPath = $argument->getName();
        $validator    = $this->validator->inContext($context->getExecution())->atPath($rootPath);

        if (!$valueType->isBuiltin()) {
            if ([] === $value || !array_is_list($value)) {
                $validator->validate($value, new Schema([
                    'type'        => $valueType->getType(),
                    'rootPath'    => $propertyPath . '[]',
                    'strictTypes' => $context->isStrictTypes(),
                ]));

                return;
            }

            foreach ($value as $key => $item) {
                $validator->validate(is_array($item) ? $item : [$item], new Schema([
                    'type'        => $valueType->getType(),
                    'rootPath'    => $propertyPath . '[' . $key . ']',
                    'strictTypes' => $context->isStrictTypes(),
                ]));
            }

            return;
        }

        $validator->validate($value, new Collection([
            new Type($valueType->getType()),
        ]));
    }
}
