<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Test\Unit\Stub;

final class Type
{
    /**
     * Type constructor.
     *
     * @param string|null $builtinType
     * @param string|null $class
     * @param bool        $collection
     * @param self[]      $collectionValueType
     */
    public function __construct(
        private ?string $builtinType = null,
        private ?string $class = null,
        private bool $collection = false,
        private array $collectionValueType = [],
    ) {
    }

    public function getBuiltinType(): ?string
    {
        return $this->builtinType;
    }

    public function getClassName(): ?string
    {
        return $this->class;
    }

    public function isCollection(): bool
    {
        return $this->collection;
    }

    public function getCollectionValueType(): ?self
    {
        return $this->collectionValueType[0] ?? null;
    }
}
