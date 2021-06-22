<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Test\Unit\Fixture;

/**
 * @psalm-immutable
 */
final class Customer
{
    public function __construct(
        private int $id,
        private string $username,
        private array $tags,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getTags(): array
    {
        return $this->tags;
    }
}
