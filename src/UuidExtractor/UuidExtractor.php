<?php
/**
 * Schema Validator
 *
 * @author    Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\UuidExtractor;

final class UuidExtractor
{
    public static function findVersion(string $uuid): ?int
    {
        $uuid    = basename(str_replace('\\', '/', $uuid));
        $matches = [];

        preg_match('/(?<version>\d+)/', $uuid, $matches);

        if ([] === $matches) {
            return null;
        }

        ['version' => $version] = $matches;

        return (int) $version;
    }
}
