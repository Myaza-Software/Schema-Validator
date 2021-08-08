<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator;

/**
 * @codeCoverageIgnore
 */
final class Argument
{
    public function __construct(
        private string $name,
        private array $rootValues,
        private \ReflectionType $type,
    ) {
    }

    public function rootValues(): array
    {
        return $this->rootValues;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): \ReflectionType
    {
        return $this->type;
    }

    /**
     * @psalm-suppress MixedInferredReturnType,MixedReturnStatement
     */
    public function currentValue(): bool | int | float | string | array | null
    {
        return array_key_exists($this->name, $this->rootValues) ? $this->rootValues[$this->name] : null;
    }

    public function withType(\ReflectionType $type): self
    {
        $new       = clone $this;
        $new->type = $type;

        return $new;
    }
}
