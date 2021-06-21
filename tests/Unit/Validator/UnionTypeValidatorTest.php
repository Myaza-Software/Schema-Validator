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
use SchemaValidator\Test\Unit\ArgumentBuilder;
use SchemaValidator\Test\Unit\ReflectionClassWrapper;
use SchemaValidator\Validator\DateTimeValidator;
use SchemaValidator\Validator\MyCLabsEnumValidator;
use SchemaValidator\Validator\ObjectValidator;
use SchemaValidator\Validator\PrimitiveValidator;
use SchemaValidator\Validator\UnionTypeValidator;
use SchemaValidator\Validator\UuidValidator;

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

//    /**
//     * @dataProvider successValidateDataProvider
//     */
//    public function testSuccessValidate(Argument $argument, array $other): void
//    {
//
//    }
//
//    /**
//     * @throws \ReflectionException
//     */
//    public function successValidateDataProvider(): iterable
//    {
//
//    }
//
//
//    /**
//     * @dataProvider failedValidateDataProvider
//     */
//    public function testFailedValidate(Argument $argument, array $other): void
//    {
//
//    }
//
//    /**
//     * @throws \ReflectionException
//     */
//    public function failedValidateDataProvider(): iterable
//    {
//
//    }

    private function getValidators(): iterable
    {
        yield new DateTimeValidator();
        yield new PrimitiveValidator($this->validator);
        yield new UuidValidator($this->validator);
        yield new MyCLabsEnumValidator($this->validator);
        yield new ObjectValidator($this->validator);
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
