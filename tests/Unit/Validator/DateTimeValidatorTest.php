<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Test\Unit\Validator;

use SchemaValidator\Argument;
use SchemaValidator\Context;
use SchemaValidator\Schema;
use SchemaValidator\Test\Unit\ArgumentBuilder;
use SchemaValidator\Test\Unit\ReflectionClassWrapper;
use SchemaValidator\Validator\DateTimeValidator;

final class DateTimeValidatorTest extends ValidatorTestCase
{
    /**
     * @dataProvider supportDataProvider
     */
    public function testSupport(\ReflectionType $type, bool $isSupport): void
    {
        $validator = new DateTimeValidator();

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
            'dtoWithDateTimeArg'          => $dtoWithDateTimeArg,
            'dtoWithDatetimeImmutableArg' => $dtoWithDatetimeImmutableArg,
            'dtoWithPrimitiveArg'         => $dtoWithPrimitiveArg,
        ] = $this->getObjects();

        return [
            [new \ReflectionUnionType(), false],
            [ReflectionClassWrapper::analyze($dtoWithPrimitiveArg)->firstArgType(), false],
            [ReflectionClassWrapper::analyze($dtoWithDateTimeArg)->firstArgType(), true],
            [ReflectionClassWrapper::analyze($dtoWithDatetimeImmutableArg)->firstArgType(), true],
        ];
    }

    /**
     * @dataProvider successValidateDataProvider
     * @psalm-suppress MixedArgument
     *
     * @param array{path:string, type: string, strictTypes: bool} $other
     */
    public function testSuccessValidate(Argument $argument, array $other): void
    {
        $validator = new DateTimeValidator();
        $context   = new Context(...$other + ['execution' => $this->executionContext]);
        $this->executionContext->setConstraint(new Schema(['type' => $context->type()]));

        $validator->validate($argument, $context);

        $this->assertNoViolation();
    }

    /**
     * @throws \ReflectionException
     *
     * @return \Generator<int,array{0: Argument, 1: array{path:string, type: string, strictTypes: bool}}>
     */
    public function successValidateDataProvider(): iterable
    {
        [
            'dtoWithDateTimeArg'          => $dtoWithDateTimeArg,
            'dtoWithDatetimeImmutableArg' => $dtoWithDatetimeImmutableArg,
        ] = $this->getObjects();

        foreach ($this->getFormatsDate() as $format) {
            foreach ([$dtoWithDateTimeArg, $dtoWithDatetimeImmutableArg] as $dto) {
                yield [
                    ArgumentBuilder::build($dto, ['createdAt' => date($format)]),
                    ['path' => '', 'type' => $dto::class, 'strictTypes' => true],
                ];
            }
        }
    }

    /**
     * @dataProvider failedValidateDataProvider
     * @psalm-suppress MixedArgument
     *
     * @param array{path:string, type: string, strictTypes: bool} $other
     */
    public function testFailedValidate(Argument $argument, array $other): void
    {
        $context    = new Context(...$other + ['execution' => $this->executionContext]);
        $constraint = new Schema(['type' => $context->type()]);
        $validator  = new DateTimeValidator();

        $this->executionContext->setConstraint($constraint);

        $validator->validate($argument, $context);

        $this->buildViolation('This is not a valid date.', $constraint)
            ->atPath($argument->name())
            ->setInvalidValue($argument->currentValue())
            ->setCode('780e721d-ce3c-42f3-854d-f990ebffdc4f')
            ->assertRaised()
        ;
    }

    /**
     * @throws \ReflectionException
     *
     * @return \Generator<int,array{0: Argument, 1: array{path:string, type: string, strictTypes: bool}}>
     */
    public function failedValidateDataProvider(): iterable
    {
        [
            'dtoWithDateTimeArg'          => $dtoWithDateTimeArg,
            'dtoWithDatetimeImmutableArg' => $dtoWithDatetimeImmutableArg,
        ] = $this->getObjects();

        foreach ([null, '', 'aza', [], 1, 1.33] as $value) {
            foreach ([$dtoWithDateTimeArg, $dtoWithDatetimeImmutableArg] as $dto) {
                yield [
                    ArgumentBuilder::build($dto, ['createdAt' => $value]),
                    ['path' => '', 'type' => $dto::class, 'strictTypes' => true],
                ];
            }
        }
    }

    /**
     * @return array<string,object>
     */
    private function getObjects(): array
    {
        return [
            'dtoWithDateTimeArg' => new class(new \DateTime()) {
                public function __construct(
                    private \DateTime $createdAt,
                ) {
                }
            },
            'dtoWithDatetimeImmutableArg' => new class(new \DateTimeImmutable()) {
                public function __construct(
                    private \DateTimeImmutable $createdAt
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

    /**
     * @return string[]
     */
    private function getFormatsDate(): array
    {
        return [
            'm.d.y',
            'd.m.Y',
            'Y-m-d H:i:s',
        ];
    }
}
