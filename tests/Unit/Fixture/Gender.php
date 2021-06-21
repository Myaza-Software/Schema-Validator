<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Test\Unit\Fixture;

use MyCLabs\Enum\Enum;

/**
 * @psalm-immutable
 */
final class Gender extends Enum
{
    private const MAN   = 'man';
    private const WOMEN = 'women';
    private const GAY   = 'gay';

    public static function man(): self
    {
        return new self(self::MAN);
    }

    public static function women(): self
    {
        return new self(self::WOMEN);
    }

    public static function gay(): self
    {
        return new self(self::GAY);
    }
}
