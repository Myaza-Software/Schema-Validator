<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\CollectionInfoExtractor;

final class ValueType
{
    public function __construct(
        private ?string $type,
        private bool $isBuiltin,
    ) {
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function isBuiltin(): bool
    {
        return $this->isBuiltin;
    }
}
