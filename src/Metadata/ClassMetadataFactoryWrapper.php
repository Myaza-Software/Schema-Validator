<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Metadata;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;

final class ClassMetadataFactoryWrapper implements ClassMetadataFactory
{
    public function __construct(
        private PropertyAccessorInterface $propertyAccessor,
        private ClassMetadataFactoryInterface $classMetadataFactory,
    ) {
    }

    public function getMetadataFor(string $type, array $values): ClassMetadata
    {
        $metadata        = $this->classMetadataFactory->getMetadataFor($type);
        $reflectionClass = $metadata->getReflectionClass();

        if ($reflectionClass->hasMethod('__construct')) {
            return new ClassMetadata(
                $metadata->getAttributesMetadata(),
                $reflectionClass->getMethod('__construct')->getParameters()
            );
        }

        $mapping = $metadata->getClassDiscriminatorMapping();

        if (null === $mapping) {
            throw new \RuntimeException('Not found constructor class:' . $type);
        }

        $propertyPath = $mapping->getTypeProperty();
        /** @var string|null $value */
        $value = $this->propertyAccessor->getValue($values, sprintf('[%s]', $propertyPath));

        if (null === $value) {
            /** @var array<string> $mapValue */
            $mapValue = array_keys($mapping->getTypesMapping());
            $property = new Property($propertyPath, null);
            $mapping  = new ClassDiscriminatorMapping($mapValue, $property);

            return new ClassMetadata([], [], $mapping);
        }

        $type = $mapping->getClassForType($value);

        if (null === $type) {
            /** @var array<string> $mapValue */
            $mapValue = array_keys($mapping->getTypesMapping());
            $property = new Property($propertyPath, $value, true);
            $mapping  = new ClassDiscriminatorMapping($mapValue, $property);

            return new ClassMetadata([], [], $mapping);
        }

        $metadata        = $this->classMetadataFactory->getMetadataFor($type);
        $reflectionClass = $metadata->getReflectionClass();

        if (!$reflectionClass->hasMethod('__construct')) {
            throw new \RuntimeException('Not found constructor class:' . $type);
        }

        return new ClassMetadata(
            $metadata->getAttributesMetadata(),
            $reflectionClass->getMethod('__construct')->getParameters()
        );
    }
}
