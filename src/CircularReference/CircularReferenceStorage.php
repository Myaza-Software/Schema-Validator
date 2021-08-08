<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\CircularReference;

final class CircularReferenceStorage
{
    /**
     * @var array<string, int>
     */
    private array $references = [];

    public function hasLimit(string $class, int $limit): bool
    {
        return ($this->references[$class] ?? 0) >= $limit;
    }

    public function calculate(string $class): void
    {
        if (array_key_exists($class, $this->references)) {
            ++$this->references[$class];

            return;
        }

        $this->references[$class] = 1;
    }
}
