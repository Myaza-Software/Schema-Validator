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

    public function getMapping(): ?ClassDiscriminatorMapping;

    public function isEmpty(): bool;
}
