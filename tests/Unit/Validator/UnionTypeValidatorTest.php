<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Test\Unit\Validator;

use SchemaValidator\Context;
use SchemaValidator\Schema;
use SchemaValidator\Test\Unit\ArgumentBuilder;
use SchemaValidator\Test\Unit\ReflectionClassWrapper;
use SchemaValidator\Test\Unit\Stub\StubFailedValidator;
use SchemaValidator\Test\Unit\Stub\StubSuccessValidator;
use SchemaValidator\Validator\UnionTypeValidator;

final class UnionTypeValidatorTest extends ValidatorTestCase
{
    /**
     * @dataProvider supportDataProvider
     */
    public function testSupport(\ReflectionType $type, bool $isSupport): void
    {
        $validator = new UnionTypeValidator([]);

        $this->assertEquals($isSupport, $validator->support($type));
    }

    /**
     * @throws \ReflectionException
     *
     * @return array<int, array{0: \ReflectionType, 1: bool}>
     */
    public function supportDataProvider(): array
    {
        [
            'dtoWithUnionType'    => $dtoWithUnionType,
            'dtoWithPrimitiveArg' => $dtoWithPrimitiveArg,
        ] = $this->getObjects();

        return [
            [new \ReflectionUnionType(), true],
            [ReflectionClassWrapper::analyze($dtoWithUnionType)->firstArgType(), true],
            [ReflectionClassWrapper::analyze($dtoWithPrimitiveArg)->firstArgType(), false],
        ];
    }

    /**
     * @throws \ReflectionException
     */
    public function testInvalidArgument(): void
    {
        $this->expectExceptionCode(0);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Type expected:' . \ReflectionUnionType::class);

        $validator            = new UnionTypeValidator([]);
        $dtoWithPrimitiveArgs = new class(1) {
            public function __construct(
                private int $id,
            ) {
            }
        };

        $argument = ArgumentBuilder::build($dtoWithPrimitiveArgs, ['id' => 50]);
        $context  = new Context('', $dtoWithPrimitiveArgs::class, false, $this->executionContext);

        $validator->validate($argument, $context);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSuccessValidate(): void
    {
        ['dtoWithUnionType' => $dtoWithUnionType] = $this->getObjects();
        $validator                                = new UnionTypeValidator([new StubSuccessValidator()]);

        $constraint = new Schema(['type' => $dtoWithUnionType::class]);
        $argument   = ArgumentBuilder::build($dtoWithUnionType, ['createdAt' => '12.01.2021']);
        $context    = new Context('', $dtoWithUnionType::class, false, $this->executionContext);

        $this->executionContext->setConstraint($constraint);

        $validator->validate($argument, $context);

        $this->assertNoViolation();
    }

    /**
     * @throws \ReflectionException
     */
    public function testFailedValidate(): void
    {
        ['dtoWithUnionType' => $dtoWithUnionType] = $this->getObjects();
        $validator                                = new UnionTypeValidator([new StubFailedValidator()]);

        $value      = [50];
        $constraint = new Schema(['type' => $dtoWithUnionType::class]);
        $argument   = ArgumentBuilder::build($dtoWithUnionType, ['createdAt' => $value]);
        $context    = new Context('', $dtoWithUnionType::class, false, $this->executionContext);

        $this->executionContext->setConstraint($constraint);

        $validator->validate($argument, $context);

        $this->buildViolation('This value should be of type {{ type }}.', $constraint)
            ->atPath('createdAt')
            ->setParameters([
                '{{ value }}' => 'array',
                '{{ type }}'  => 'DateTime|string',
            ])
            ->setInvalidValue($value)
            ->setCode('24231bed-2239-420e-add0-ae2a80ba360c')
            ->assertRaised()
        ;
    }

    /**
     * @return array<string,object>
     */
    private function getObjects(): array
    {
        return [
            'dtoWithUnionType' => new class(new \DateTime()) {
                public function __construct(
                    private \DateTime | string $createdAt,
                ) {
                }
            },
            'dtoWithPrimitiveArg' => new class (1) {
                public function __construct(
                    private int $id,
                ) {
                }
            },
        ];
    }
}
