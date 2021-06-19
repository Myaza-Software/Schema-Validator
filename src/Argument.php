<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator;

final class Argument
{
    public function __construct(
        private string $name,
        private array $rootValues,
        private \ReflectionType $type,
    ) {
    }

    public function getRootValues(): array
    {
        return $this->rootValues;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): \ReflectionType
    {
        return $this->type;
    }

    public function getValueByArgumentName(): mixed
    {
        return array_key_exists($this->name, $this->rootValues) ? $this->rootValues[$this->name] : null;
    }

    public function withRootValues(array $values): self
    {
        $new             = clone $this;
        $new->rootValues = $values;

        return $new;
    }

    public function withType(\ReflectionType $type): self
    {
        $new       = clone $this;
        $new->type = $type;

        return $new;
    }
}
