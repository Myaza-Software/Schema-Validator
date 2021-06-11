<?php

declare(strict_types=1);

namespace SchemaValidator\Metadata;

use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;

final class ClassMetadataFactoryWrapper
{
    public function __construct(
        private PropertyAccessorInterface $propertyAccessor,
        private ClassMetadataFactoryInterface $classMetadataFactory,
        private ClassDiscriminatorResolverInterface $classDiscriminatorResolver,
    ) {
    }

    /**
     * @return ClassMetadataInterface
     */
    public function getMetadataFor(string $class, array $values): ?ClassMetadataInterface
    {
        $metadata       = $this->classMetadataFactory->getMetadataFor($class);
        $reflectionClass = $metadata->getReflectionClass();

        if ($reflectionClass->hasMethod('__construct')) {
            return new ClassMetadata(
                $metadata->getAttributesMetadata(),
                $reflectionClass->getMethod('__construct')->getParameters()
            );
        }

        $mapping = $this->classDiscriminatorResolver->getMappingForClass($class);

        if (null === $mapping) {
            throw new \RuntimeException('Not found constructor');
        }

        $propertyPath = $mapping->getTypeProperty();

        try {
            $value = $this->propertyAccessor->getValue($values, $propertyPath);
        } catch (InvalidArgumentException) {
            return null;
        }

        $class = $mapping->getClassForType($value);

        if (null === $class) {
            return null;
        }

        $metadata       = $this->classMetadataFactory->getMetadataFor($class);
        $reflectionClass = $metadata->getReflectionClass();

        if (!$reflectionClass->hasMethod('__construct')) {
            throw new \RuntimeException('Not found constructor');
        }

        return new ClassMetadata(
            $metadata->getAttributesMetadata(),
            $reflectionClass->getMethod('__construct')->getParameters()
        );
    }
}
