<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Test\Unit\CollectionInfoExtractor;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SchemaValidator\CollectionInfoExtractor\CollectionInfoExtractor;
use SchemaValidator\CollectionInfoExtractor\CollectionInfoExtractorInterface;
use SchemaValidator\CollectionInfoExtractor\ValueType;
use SchemaValidator\Test\Unit\Stub\Type as TypeStub;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

final class CollectionInfoExtractorTest extends TestCase
{
    private CollectionInfoExtractorInterface $collectionInfoExtractor;

    /**
     * @var PropertyInfoExtractorInterface&MockObject
     */
    private PropertyInfoExtractorInterface $propertyInfoExtractor;

    protected function setUp(): void
    {
        $this->propertyInfoExtractor   = $this->createMock(PropertyInfoExtractorInterface::class);
        $this->collectionInfoExtractor = new CollectionInfoExtractor($this->propertyInfoExtractor);
    }

    /**
     * @dataProvider nullTypeCollectionDataProvider
     */
    public function testNullTypeCollection(array | null $types): void
    {
        $this->propertyInfoExtractor->method('getTypes')
            ->willReturn($types);

        $valueType = $this->collectionInfoExtractor->getValueType(\stdClass::class, 'customers');

        $this->assertNull($valueType->getType());
        $this->assertTrue($valueType->isBuiltin());
    }

    /**
     * @return array{0: array<null>, 1: array<array>}
     */
    public function nullTypeCollectionDataProvider(): array
    {
        return [
            [null],
            [[]],
        ];
    }

    /**
     * @dataProvider getValueTypeDataProvider
     */
    public function testGetValueType(array $types, ValueType $valueType): void
    {
        $this->propertyInfoExtractor->method('getTypes')
            ->willReturn($types);

        $actualValueType = $this->collectionInfoExtractor->getValueType(\stdClass::class, 'customers');

        $this->assertEquals($valueType, $actualValueType);
    }

    /**
     * @psalm-suppress MoreSpecificReturnType
     *
     * @return \Generator<int, array{0: array{0: Type|TypeStub, 1: Type|TypeStub}, 1: ValueType}, mixed, void>
     */
    public function getValueTypeDataProvider(): iterable
    {
        yield [[
            new Type('int', collection: false),
            new Type('array', collection: true, collectionValueType: [new Type('string')]),
        ], new ValueType('string', true)];

        yield [
            [
                new Type('int', collection: false),
                new Type('array', collection: true, collectionValueType: [
                    new Type('object', class: \stdClass::class),
                ]),
            ],
            new ValueType(\stdClass::class, false),
        ];

        yield [
            [],
            new ValueType(null, true),
        ];

        yield [
            [
                new TypeStub('int', collection: false),
                new TypeStub('array', collection: true, collectionValueType: [
                    new TypeStub('object', class: \stdClass::class),
                ]),
            ],
            new ValueType(\stdClass::class, false),
        ];

        yield [
            [
                new TypeStub('int', collection: false),
                new TypeStub('array', collection: true, collectionValueType: [
                    new TypeStub('string'),
                ]),
            ],
            new ValueType('string', true),
        ];

        yield [
            [
                new TypeStub('int', collection: false),
                new TypeStub('array', collection: true, collectionValueType: []),
            ],
            new ValueType(null, true),
        ];
    }
}
