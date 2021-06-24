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
 * Whether to format {@link \DateTime} objects, either with the {@link \IntlDateFormatter}
 * (if it is available) or as RFC-3339 dates ("Y-m-d H:i:s").
 */
const PRETTY_DATE = 1;

/**
 * Whether to cast objects with a "__toString()" method to strings.
 */
const OBJECT_TO_STRING = 2;

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
    if (($format & PRETTY_DATE) && $value instanceof \DateTimeInterface) {
        return $value->format('Y-m-d H:i:s');
    }

    if (\is_object($value)) {
        if (($format & OBJECT_TO_STRING) && $value instanceof \Stringable) {
            return $value->__toString();
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
