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
use function SchemaValidator\formatValue;
use SchemaValidator\Test\Unit\ArgumentBuilder;
use SchemaValidator\Test\Unit\Fixture\Gender;
use SchemaValidator\Test\Unit\ReflectionClassWrapper;
use SchemaValidator\Validator\MyCLabsEnumValidator;
use Symfony\Component\Validator\Constraints\Choice;

final class MyCLabsEnumValidatorTest extends ValidatorTestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testInvalidArgument(): void
    {
        $this->expectExceptionCode(0);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Type expected:' . \ReflectionNamedType::class);

        $validator            = new MyCLabsEnumValidator($this->validator);
        $dtoWithUnionTypeArgs = new class(1) {
            public function __construct(
                private string | int $id,
            ) {
            }
        };

        $argument = ArgumentBuilder::build($dtoWithUnionTypeArgs, ['id' => 50]);
        $context  = new Context('', $dtoWithUnionTypeArgs::class, false, $this->executionContext);

        $validator->validate($argument, $context);
    }

    /**
     * @dataProvider supportDataProvider
     */
    public function testSupport(\ReflectionType $type, bool $isSupport): void
    {
        $validator = new MyCLabsEnumValidator($this->validator);

        $this->assertEquals($isSupport, $validator->support($type));
    }

    /**
     * @throws \ReflectionException
     *
     * @return array<int,array{0: \ReflectionType, 1: bool}>
     */
    public function supportDataProvider(): array
    {
        ['dtoWithEnumArg' => $dtoWithEnumArg, 'dtoWithObjectArg' => $dtoWithObjectArg] = $this->getObjects();

        return [
            [new \ReflectionUnionType(), false],
            [ReflectionClassWrapper::analyze($dtoWithObjectArg)->firstArgType(), false],
            [ReflectionClassWrapper::analyze($dtoWithEnumArg)->firstArgType(), true],
        ];
    }

    /**
     * @throws \ReflectionException
     */
    public function testSuccessValidate(): void
    {
        ['dtoWithEnumArg' => $dto] = $this->getObjects();

        $validator = new MyCLabsEnumValidator($this->validator);
        $argument  = ArgumentBuilder::build($dto, ['gender' => Gender::man()->getValue()]);
        $context   = new Context('', $dto::class, true, $this->executionContext);

        $validator->validate($argument, $context);

        $this->assertNoViolation();
    }

    /**
     * @throws \ReflectionException
     *
     * @dataProvider failedValidateDataProvider
     */
    public function testFailedValidate(string $value): void
    {
        ['dtoWithEnumArg' => $dto] = $this->getObjects();

        $choices    = Gender::toArray();
        $constraint = new Choice(['choices' => $choices], groups: ['Default']);
        $validator  = new MyCLabsEnumValidator($this->validator);
        $argument   = ArgumentBuilder::build($dto, ['gender' => $value]);
        $context    = new Context('', $dto::class, true, $this->executionContext);

        $validator->validate($argument, $context);

        $this->buildViolation('The value you selected is not a valid choice.', $constraint)
            ->setCode('8e179f1b-97aa-4560-a02f-2a8b42e49df7')
            ->setParameter('{{ value }}', formatValue($value))
            ->setParameter('{{ choices }}', $this->formatValues($choices))
            ->atPath($argument->getName())
            ->setInvalidValue($value)
            ->assertRaised()
        ;
    }

    /**
     * @return iterable<int,array<string>>
     */
    public function failedValidateDataProvider(): iterable
    {
        return [
            ['1'],
            ['test'],
            ['aza'],
            ['green'],
        ];
    }

    /**
     * @return array<string,object>
     */
    private function getObjects(): array
    {
        return [
            'dtoWithEnumArg' => new class (Gender::man()) {
                public function __construct(
                    private Gender $gender
                ) {
                }
            },
            'dtoWithObjectArg' => new class(new \stdClass()) {
                public function __construct(
                    private \stdClass $vo
                ) {
                }
            },
        ];
    }

    /**
     * @param array<string,mixed> $values
     */
    private function formatValues(array $values, int $format = 0): string
    {
        /** @var mixed $value */
        foreach ($values as $key => $value) {
            $values[$key] = formatValue($value, $format);
        }

        return implode(', ', $values);
    }
}
