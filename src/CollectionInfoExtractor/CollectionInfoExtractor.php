<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\CollectionInfoExtractor;

use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;

final class CollectionInfoExtractor
{
    public function __construct(
        private PropertyInfoExtractorInterface $propertyInfoExtractor
    ) {
    }

    /**
     * @param class-string $class
     */
    public function getValueType(string $class, string $propertyName): ValueType
    {
        $typesProperty = $this->propertyInfoExtractor->getTypes($class, $propertyName);

        if (null === $typesProperty) {
            return new ValueType(null, true);
        }

        foreach ($typesProperty as $type) {
            if (!$type->isCollection()) {
                continue;
            }

            if (method_exists($type, 'getCollectionValueTypes')) {
                $valueTypes = $type->getCollectionValueTypes();

                if ([] === $valueTypes) {
                    return new ValueType(null, true);
                }

                $valueType = $valueTypes[0];

                return new ValueType(
                    $valueType->getClassName() ?? $valueType->getBuiltinType(),
                    null === $valueType->getClassName()
                );
            }

            if (!method_exists($type, 'getCollectionValueType')) {
                continue;
            }

            $valueType = $type->getCollectionValueType();

            if (null === $valueType) {
                return new ValueType(null, true);
            }

            return new ValueType(
                $valueType->getClassName() ?? $valueType->getBuiltinType(),
                null === $valueType->getClassName()
            );
        }

        return new ValueType(null, true);
    }
}
