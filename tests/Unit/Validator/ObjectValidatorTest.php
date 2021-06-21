<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Test\Unit\Validator;

use PHPUnit\Framework\Assert;
use SchemaValidator\Context;
use SchemaValidator\Schema;
use SchemaValidator\Test\Unit\ArgumentBuilder;
use SchemaValidator\Test\Unit\Fixture\Credentials;
use SchemaValidator\Test\Unit\ReflectionClassWrapper;
use SchemaValidator\Validator\ObjectValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Context\ExecutionContextFactory;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ObjectValidatorTest extends ValidatorTestCase
{
    /**
     * @dataProvider validateDataProvider
     *
     * @throws \ReflectionException
     */
    public function testValidate(float | string | array $value): void
    {
        $this->mockingValidator($value);

        ['dtoWithVoArg' => $dtoWithVoArg] = $this->getObjects();

        $constraint = new Schema([
            'type'        => Credentials::class,
            'rootPath'    => 'credentials',
            'strictTypes' => true,
        ], groups: ['Default']);

        $validator = new ObjectValidator($this->validator);
        $argument  = ArgumentBuilder::build($dtoWithVoArg, ['credentials' => $value]);
        $context   = new Context('', $dtoWithVoArg::class, true, $this->executionContext);

        $validator->validate($argument, $context);

        $this->assertEqualsConstraint($constraint);
    }

    /**
     * @return array{0: array<string>, 1: array<int>, 2: array<float>, 3: array<int, array{value: string}>}
     */
    public function validateDataProvider(): array
    {
        return [
            ['value'],
            [1],
            [1.33],
            [['value' => 'value']],
        ];
    }

    private function mockingValidator(mixed $expected): void
    {
        $constraintValidator = new class($expected) extends ConstraintValidator {
            public function __construct(
                private mixed $expected
            ) {
            }

            public function validate(mixed $value, Constraint $constraint): void
            {
                Assert::assertEquals(is_array($this->expected) ? $this->expected : [$this->expected], $value);
            }
        };
        $constraintValidatorFactory = $this->createMock(ConstraintValidatorFactory::class);
        $constraintValidatorFactory
            ->method('getInstance')
            ->willReturn($constraintValidator)
        ;

        $this->validator = Validation::createValidatorBuilder()
            ->setConstraintValidatorFactory($constraintValidatorFactory)
            ->getValidator()
        ;

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnArgument(0)
        ;

        $factory = new ExecutionContextFactory($translator);

        $this->executionContext = $factory->createContext($this->validator, '');

        $constraintValidator->initialize($this->executionContext);
    }

    /**
     * @throws \ReflectionException
     */
    public function testInvalidArgument(): void
    {
        $this->expectExceptionCode(0);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Type expected:' . \ReflectionNamedType::class);

        $validator            = new ObjectValidator($this->validator);
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
        $validator = new ObjectValidator($this->validator);

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
            'dtoWithPrimitiveArg' => $dtoWithPrimitiveArg,
            'dtoWithVoArg'        => $dtoWithVoArg,
        ] = $this->getObjects();

        return [
            [new \ReflectionUnionType(), false],
            [ReflectionClassWrapper::analyze($dtoWithPrimitiveArg)->firstArgType(), false],
            [ReflectionClassWrapper::analyze($dtoWithVoArg)->firstArgType(), true],
        ];
    }

    /**
     * @return array<string,object>
     */
    private function getObjects(): array
    {
        return [
            'dtoWithPrimitiveArg' => new class(1) {
                public function __construct(
                    private int $id,
                ) {
                }
            },
            'dtoWithVoArg' => new class(new Credentials('aza', 'root', 'root@myaza.info')) {
                public function __construct(
                    private Credentials $credentials
                ) {
                }

                public function getCredentials(): Credentials
                {
                    return $this->credentials;
                }
            },
        ];
    }
}
