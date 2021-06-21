<?php
/**
 * Schema Validator
 *
 * @author    Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Test\Unit;

use PHPUnit\Framework\TestCase;
use SchemaValidator\Schema;

final class SchemaTest extends TestCase
{
    public function testNotFoundClassOrInterface(): void
    {
        $this->expectExceptionCode(0);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Not found class or interface: aza');

        new Schema(['type' => 'aza']);
    }

    /**
     * @dataProvider typeDataProvider
     */
    public function testSuccessCreateConstraint(string $type): void
    {
        $this->assertInstanceOf(Schema::class, new Schema(['type' => $type]));
    }

    public function testSettingsConstraints(): void
    {
        $constraint = new Schema(['type' => StubClass::class]);

        $this->assertEquals(['type'], $constraint->getRequiredOptions());
        $this->assertEquals('class', $constraint->getTargets());
    }

    /**
     * @return array<int, array<class-string>>
     */
    public function typeDataProvider(): array
    {
        return [
            [StubClass::class],
            [StubInterface::class],
        ];
    }
}

final class StubClass
{
}

interface StubInterface
{
}
