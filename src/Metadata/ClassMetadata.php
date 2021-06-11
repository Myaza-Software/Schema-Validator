<?php

declare(strict_types=1);

namespace SchemaValidator\Metadata;

use Symfony\Component\Serializer\Mapping\AttributeMetadataInterface;

final class ClassMetadata implements ClassMetadataInterface
{
    /**
     * ClassMetaData constructor.
     *
     * @param AttributeMetadataInterface[] $attributes
     * @param \ReflectionParameter[]   $parameters
     */
    public function __construct(
        private array $attributes,
        private array $parameters
    ) {}

    /**
     * @return ClassMetadataInterface[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return \ReflectionParameter[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}