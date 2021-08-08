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
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ArrayItemValidator implements Validator
{
    public function __construct(
        private ValidatorInterface $validator,
        private CollectionInfoExtractor $extractor,
    ) {
    }

    public function isEnabledCircularReferenceStorage(): bool
    {
        return false;
    }

    public function support(\ReflectionType $type): bool
    {
        return $type instanceof \ReflectionNamedType && $type->isBuiltin() && 'array' === $type->getName();
    }

    public function validate(Argument $argument, Context $context): void
    {
        /** @var class-string $type */
        $type         = $context->type();
        $argumentName = $argument->name();
        $valueType    = $this->extractor->getValueType($type, $argumentName);

        if (null === $valueType->type()) {
            return;
        }

        /** @var array<string,array<string,mixed>|int|string|float> $value */
        $value        = is_array($argument->currentValue()) ? $argument->currentValue() : [];
        $rootPath     = $context->path();
        $propertyPath = $argument->name();
        $validator    = $this->validator->inContext($context->execution())->atPath($rootPath);

        if ($valueType->isBuiltin()) {
            $validator->validate($value, new All([
                new Type($valueType->type()),
            ]));

            return;
        }

        if ([] === $value || !array_is_list($value)) {
            $validator->validate($value, new Schema([
                'type'        => $valueType->type(),
                'rootPath'    => $propertyPath . '[]',
                'strictTypes' => $context->strictTypes(),
            ]));

            return;
        }

        foreach ($value as $key => $item) {
            $validator->validate(is_array($item) ? $item : [$item], new Schema([
                'type'        => $valueType->type(),
                'rootPath'    => $propertyPath . '[' . $key . ']',
                'strictTypes' => $context->strictTypes(),
            ]));
        }
    }
}
