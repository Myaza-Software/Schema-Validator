<?php

declare(strict_types=1);

namespace SchemaValidator\Metadata;

use Symfony\Component\Serializer\Mapping\AttributeMetadataInterface;

interface ClassMetadataInterface
{
    /**
     * @return AttributeMetadataInterface[]
     */
    public function getAttributes(): array;

    /**
     * @return \ReflectionParameter[]
     */
    public function getParameters(): array;
}