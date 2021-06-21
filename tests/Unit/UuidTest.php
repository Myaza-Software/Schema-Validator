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
use function SchemaValidator\findUuidVersion;
use Symfony\Component\Uid\UuidV4;

final class UuidTest extends TestCase
{
    public function testSuccessFindVersionUuid(): void
    {
        $version = findUuidVersion(UuidV4::class);

        $this->assertIsInt($version);
        $this->assertEquals(4, $version);
    }

    public function testFailedFindVersionUuid(): void
    {
        $nullVersion = findUuidVersion('null');

        $this->assertNull($nullVersion);
    }
}
