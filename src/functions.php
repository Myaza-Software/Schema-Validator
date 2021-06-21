<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator;

function findUuidVersion(string $class): ?int
{
    $class   = basename(str_replace('\\', '/', $class));
    $matches = [];

    preg_match('/(?<version>\d+)/', $class, $matches);

    if ([] === $matches) {
        return null;
    }

    ['version' => $version] = $matches;

    return (int) $version;
}

function formatValue(mixed $value, int $format = 0): string
{
    if (($format & 1) && $value instanceof \DateTimeInterface) {
        return $value->format('Y-m-d H:i:s');
    }

    if (\is_object($value)) {
        if (($format & 2) && method_exists($value, '__toString')) {
            /** @var string $value */
            $value = $value->__toString();

            return $value;
        }

        return 'object';
    }

    if (\is_array($value)) {
        return 'array';
    }

    if (\is_string($value)) {
        return '"' . $value . '"';
    }

    if (\is_resource($value)) {
        return 'resource';
    }

    if (null === $value) {
        return 'null';
    }

    if (false === $value) {
        return 'false';
    }

    if (true === $value) {
        return 'true';
    }

    return (string) $value;
}
