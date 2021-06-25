<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Test\Unit\Fixture;

final class User
{
    public function __construct(
        private string $name,
        private ?string $uuid,
        private bool $isActive = false,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }
}
