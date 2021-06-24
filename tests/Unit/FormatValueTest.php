<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Test\Unit;

use PHPUnit\Framework\TestCase;
use function SchemaValidator\formatValue;
use const SchemaValidator\OBJECT_TO_STRING;
use const SchemaValidator\PRETTY_DATE;

final class FormatValueTest extends TestCase
{
    /**
     * @dataProvider formatValueDataProvider
     */
    public function testFormatValue(mixed $value, string $result, int $format = 0): void
    {
        $this->assertEquals($result, formatValue($value, $format));
    }

    /**
     * @return array{0: array{0: array<empty, empty>, 1: string}, 1: array{0: string, 1: string}, 2: array{0: null, 1: string}, 3: array{0: true, 1: string}, 4: array{0: false, 1: string}, 5: array{0: false|resource, 1: string}, 6: array{0: \stdClass, 1: string}, 7: array{0: StubStringable, 1: string, 2: int}, 8: array{0: \DateTimeImmutable, 1: string, 2: int}}
     */
    public function formatValueDataProvider(): array
    {
        return [
            [[], 'array'],
            ['aza', '"' . 'aza' . '"'],
            [null, 'null'],
            [true, 'true'],
            [false, 'false'],
            [fopen('php://memory', 'r+'), 'resource'],
            [new \stdClass(), 'object'],
            [new StubStringable(), StubStringable::class, OBJECT_TO_STRING],
            [new \DateTimeImmutable('12.01.2021 00:01:00'), '2021-01-12 00:01:00', PRETTY_DATE],
        ];
    }
}

final class StubStringable implements \Stringable
{
    public function __toString(): string
    {
        return $this::class;
    }
}
