<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Test\Unit;

use SchemaValidator\Argument;

final class ArgumentBuilder
{
    /**
     * @throws \ReflectionException
     */
    public static function build(object $object, array $values): Argument
    {
        $reflection = ReflectionClassWrapper::analyze($object);
        $parameter  = $reflection->firstArg();
        $type       = $reflection->firstArgType();

        return new Argument($parameter->getName(), $values, $type);
    }
}
