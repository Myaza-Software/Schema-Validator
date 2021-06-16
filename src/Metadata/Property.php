<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Metadata;

final class Property
{
    public function __construct(
        private string $name,
        private ?string $invalidValue,
        private bool $exits = false,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getInvalidValue(): ?string
    {
        return $this->invalidValue;
    }

    public function isExits(): bool
    {
        return $this->exits;
    }
}
