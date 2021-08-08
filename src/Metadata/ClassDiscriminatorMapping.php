<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Metadata;

/**
 * @codeCoverageIgnore
 */
final class ClassDiscriminatorMapping
{
    /**
     * ClassDiscriminatorMapping constructor.
     *
     * @param array<string> $mapValue
     */
    public function __construct(
        private array $mapValue,
        private Property $property,
    ) {
    }

    /**
     * @return string[]
     */
    public function mapValue(): array
    {
        return $this->mapValue;
    }

    public function property(): Property
    {
        return $this->property;
    }
}
