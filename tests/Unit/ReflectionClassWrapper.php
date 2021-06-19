<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Test\Unit;

final class ReflectionClassWrapper
{
    private \ReflectionClass $reflection;

    private function __construct(object $object)
    {
        $this->reflection = new \ReflectionClass($object);
    }

    public static function analyze(object $object): self
    {
        return new self($object);
    }

    /**
     * @throws \ReflectionException
     */
    public function firstArgType(): \ReflectionType
    {
        return $this->firstArg()->getType() ?? throw new \ReflectionException('Not found type first arg');
    }

    /**
     * @throws \ReflectionException
     */
    public function firstArg(): \ReflectionParameter
    {
        $parameters = $this->reflection->getConstructor()?->getParameters() ?? [];

        if ([] === $parameters) {
            throw new \ReflectionException('Not found __constructor');
        }

        return $parameters[0];
    }
}
