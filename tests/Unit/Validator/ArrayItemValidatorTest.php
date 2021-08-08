<?php
/**
 * Schema Validator
 *
 * @author    Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Test\Unit\Validator;

use SchemaValidator\Argument;
use SchemaValidator\CircularReference\InMemoryCircularReferenceDetector;
use SchemaValidator\CollectionInfoExtractor\CollectionInfoExtractor;
use SchemaValidator\CollectionInfoExtractor\ValueType;
use SchemaValidator\Context;
use SchemaValidator\Schema;
use SchemaValidator\Test\Unit\ArgumentBuilder;
use SchemaValidator\Test\Unit\Fixture\Customer;
use SchemaValidator\Test\Unit\ReflectionClassWrapper;
use SchemaValidator\Validator\ArrayItemValidator;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Context\ExecutionContextFactory;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ArrayItemValidatorTest extends ValidatorTestCase
{
    /**
     * @dataProvider supportDataProvider
     */
    public function testSupport(\ReflectionType $type, bool $isSupport): void
    {
        $collectionExtractor = $this->createMock(CollectionInfoExtractor::class);
        $validator           = new ArrayItemValidator($this->validator, $collectionExtractor, new InMemoryCircularReferenceDetector());

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
            'dtoWithIntArg'           => $dtoWithIntArg,
            'dtoWithArrayOfObjectArg' => $dtoWithArrayOfObjectArg,
            'dtoWithArrayOfStringArg' => $dtoWithArrayOfStringArg,
        ] = $this->getObjects();

        return [
            [new \ReflectionUnionType(), false],
            [ReflectionClassWrapper::analyze($dtoWithIntArg)->firstArgType(), false],
            [ReflectionClassWrapper::analyze($dtoWithArrayOfObjectArg)->firstArgType(), true],
            [ReflectionClassWrapper::analyze($dtoWithArrayOfStringArg)->firstArgType(), true],
        ];
    }

    /**
     * @throws \ReflectionException
     */
    public function testValidateArrayOfNullType(): void
    {
        ['dtoWithArrayOfStringArg' => $dtoWithArrayOfStringArg] = $this->getObjects();
        $collectionExtractor                                    = $this->createMock(CollectionInfoExtractor::class);
        $validator                                              = new ArrayItemValidator($this->validator, $collectionExtractor, new InMemoryCircularReferenceDetector());

        $collectionExtractor->method('getValueType')
            ->willReturn(new ValueType(null, true))
        ;

        $argument = ArgumentBuilder::build($dtoWithArrayOfStringArg, ['id' => 50]);
        $context  = new Context('', $dtoWithArrayOfStringArg::class, false, $this->executionContext);

        $validator->validate($argument, $context);

        $this->assertNoViolation();
    }

    /**
     * @dataProvider validateArrayOfStringDataProvider
     */
    public function testValidateArrayOfString(Argument $argument, string $type, bool $isSuccess): void
    {
        $collectionExtractor = $this->createMock(CollectionInfoExtractor::class);
        $validator           = new ArrayItemValidator($this->validator, $collectionExtractor, new InMemoryCircularReferenceDetector());
        $context             = new Context('', $type, true, $this->executionContext);

        $collectionExtractor->method('getValueType')
            ->willReturn(new ValueType('string', true))
        ;

        $validator->validate($argument, $context);

        if ($isSuccess) {
            $this->assertNoViolation();

            return;
        }

        $constraint = new Type(['type' => 'string'], groups: ['Default']);

        $this->buildViolation('This value should be of type {{ type }}.', $constraint)
            ->atPath('[0]')
            ->setParameters(['{{ value }}' => '1', '{{ type }}' => 'string'])
            ->setInvalidValue(1)
            ->setCode('ba785a8c-82cb-4283-967c-3cf342181b40')

            ->buildNextViolation('This value should be of type {{ type }}.')
            ->atPath('[1]')
            ->setParameters(['{{ value }}' => '2', '{{ type }}' => 'string'])
            ->setInvalidValue(2)
            ->setCode('ba785a8c-82cb-4283-967c-3cf342181b40')

            ->buildNextViolation('This value should be of type {{ type }}.')
            ->atPath('[2]')
            ->setParameters(['{{ value }}' => '3', '{{ type }}' => 'string'])
            ->setInvalidValue(3)
            ->setCode('ba785a8c-82cb-4283-967c-3cf342181b40')

            ->buildNextViolation('This value should be of type {{ type }}.')
            ->atPath('[3]')
            ->setParameters(['{{ value }}' => '4', '{{ type }}' => 'string'])
            ->setInvalidValue(4)
            ->setCode('ba785a8c-82cb-4283-967c-3cf342181b40')
            ->assertRaised()
        ;
    }

    /**
     * @throws \ReflectionException
     *
     * @return array<int,array{0: Argument, 1: string,2: bool}>
     */
    public function validateArrayOfStringDataProvider(): array
    {
        ['dtoWithArrayOfStringArg' => $dtoWithArrayOfStringArg] = $this->getObjects();

        return [
            [
                ArgumentBuilder::build($dtoWithArrayOfStringArg, ['tags' => [1, 2, 3, 4]]),
                $dtoWithArrayOfStringArg::class,
                false,
            ],
            [
                ArgumentBuilder::build($dtoWithArrayOfStringArg, ['tags' => ['aza', 'root', 'crm']]),
                $dtoWithArrayOfStringArg::class,
                true,
            ],
        ];
    }

    /**
     * @dataProvider validateArrayOfObjectDataProvider
     */
    public function testValidateArrayOfObject(Argument $argument, string $type, string $rootPath): void
    {
        $this->mockingValidator();

        $collectionExtractor = $this->createMock(CollectionInfoExtractor::class);
        $validator           = new ArrayItemValidator($this->validator, $collectionExtractor, new InMemoryCircularReferenceDetector());
        $context             = new Context('', $type, true, $this->executionContext);

        $collectionExtractor->method('getValueType')
            ->willReturn(new ValueType(Customer::class, false))
        ;

        $validator->validate($argument, $context);

        $constraint = new Schema([
            'strictTypes' => $context->strictTypes(),
            'rootPath'    => $rootPath,
            'type'        => Customer::class,
        ], groups: ['Default']);

        $this->assertEqualsConstraint($constraint);
    }

    /**
     * @throws \ReflectionException
     *
     * @return array<int,array{0: Argument, 1: string,2: string}>
     */
    public function validateArrayOfObjectDataProvider(): array
    {
        ['dtoWithArrayOfObjectArg' => $dtoWithArrayOfObjectArg] = $this->getObjects();

        return [
            [
                ArgumentBuilder::build($dtoWithArrayOfObjectArg, ['customers' => []]),
                $dtoWithArrayOfObjectArg::class,
                'customers[]',
            ],
            [
                ArgumentBuilder::build($dtoWithArrayOfObjectArg, ['customers' => ['nick' => 'aza', 'login' => 'root', 'work' => 'crm']]),
                $dtoWithArrayOfObjectArg::class,
                'customers[]',
            ],
            [
                ArgumentBuilder::build($dtoWithArrayOfObjectArg, ['customers' => [['aza'], ['root'], ['crm']]]),
                $dtoWithArrayOfObjectArg::class,
                'customers[0]',
            ],
        ];
    }

    private function mockingValidator(): void
    {
        $constraintValidator        = $this->createMock(ConstraintValidator::class);
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
     * @return array<string,object>
     */
    private function getObjects(): array
    {
        return [
            'dtoWithIntArg' => new class (1) {
                public function __construct(
                    private int $id,
                ) {
                }
            },
            'dtoWithArrayOfObjectArg' => new class ([]) {
                /**
                 * @param Customer[] $customers
                 */
                public function __construct(
                    private array $customers,
                ) {
                }
            },
            'dtoWithArrayOfStringArg' => new class ([]) {
                /**
                 * @param string[] $tags
                 */
                public function __construct(
                    private array $tags
                ) {
                }
            },
        ];
    }
}
