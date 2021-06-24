<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Metadata;

use Symfony\Component\Serializer\Mapping\AttributeMetadataInterface;

/**
 * @codeCoverageIgnore
 */
final class ClassMetadata
{
    /**
     * ClassMetaData constructor.
     *
     * @param AttributeMetadataInterface[] $attributes
     * @param \ReflectionParameter[]       $parameters
     */
    public function __construct(
        private array $attributes,
        private array $parameters,
        private ?ClassDiscriminatorMapping $mapping = null,
    ) {
    }

    /**
     * @return AttributeMetadataInterface[]
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

    public function getMapping(): ?ClassDiscriminatorMapping
    {
        return $this->mapping;
    }

    public function isEmpty(): bool
    {
        return [] === $this->parameters && [] === $this->attributes;
    }
}
