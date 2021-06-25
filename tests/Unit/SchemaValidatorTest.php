<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Test\Unit;

use Ramsey\Uuid\Uuid;
use function SchemaValidator\formatValue;
use SchemaValidator\Metadata\ClassDiscriminatorMapping;
use SchemaValidator\Metadata\ClassMetadata;
use SchemaValidator\Metadata\ClassMetadataFactoryWrapperInterface;
use SchemaValidator\Metadata\Property;
use SchemaValidator\Schema;
use SchemaValidator\SchemaValidator;
use SchemaValidator\Test\Unit\Fixture\Credentials;
use SchemaValidator\Test\Unit\Fixture\User;
use SchemaValidator\Test\Unit\Fixture\Webhook\SuccessPaymentWebhook;
use SchemaValidator\Test\Unit\Fixture\Webhook\WebhookInterface;
use SchemaValidator\Test\Unit\Stub\StubFailedValidator;
use SchemaValidator\Test\Unit\Stub\StubIgnoreValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class SchemaValidatorTest extends ConstraintValidatorTestCase
{
    private bool $addValidators = false;

    /**
     * @var ClassMetadataFactoryWrapperInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private ClassMetadataFactoryWrapperInterface $classMetadataFactoryWrapper;

    protected function setUp(): void
    {
        $this->classMetadataFactoryWrapper = $this->createMock(ClassMetadataFactoryWrapperInterface::class);

        parent::setUp();
    }

    public function testInvalidConstraint(): void
    {
        $this->expectExceptionCode(0);
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "SchemaValidator\Schema');

        $this->validator->validate(null, new class() extends Constraint{});
    }

    public function testInvalidValue(): void
    {
        $this->expectExceptionCode(0);
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected argument of type "array", "null" given');

        $this->validator->validate(null, new Schema(['type' => Credentials::class]));
    }

    /**
     * @psalm-suppress MixedMethodCall
     */
    public function testFailedValidateMissingFiledDiscriminatorMapping(): void
    {
        $classMetadata = new ClassMetadata(
            [],
            [],
            new ClassDiscriminatorMapping([], new Property('type', null, false))
        );

        $this->classMetadataFactoryWrapper->method('getMetadataFor')
            ->willReturn($classMetadata)
        ;

        $this->validator->validate([], new Schema(['type' => WebhookInterface::class]));

        $this->buildViolation('This field is missing.')
            ->atPath('property.path.type')
            ->setParameter('{{ field }}', formatValue('type'))
            ->setInvalidValue(null)
            ->setCode('dddad9b7-b802-425d-a433-b56b39c5c3eb')
            ->assertRaised()
        ;
    }

    /**
     * @psalm-suppress MixedMethodCall
     */
    public function testFailedValidateUnknownResource(): void
    {
        $map = ['success_payment', 'refund_payment'];

        $classMetadata = new ClassMetadata(
            [],
            [],
            new ClassDiscriminatorMapping(
                $map,
                new Property('type', 'aza', true)
            )
        );

        $this->classMetadataFactoryWrapper->method('getMetadataFor')
            ->willReturn($classMetadata)
        ;

        $this->validator->validate(['type' => 'aza'], new Schema(['type' => WebhookInterface::class]));

        $this->buildViolation('Unknown resource. Allowed: {{ allowed }}')
            ->setParameter('{{ allowed }}', '"success_payment", "refund_payment"')
            ->atPath('property.path.type')
            ->setInvalidValue('aza')
            ->setCode('771fd9d5-ea63-4523-9b2d-c2977efc50e3')
            ->assertRaised()
        ;
    }

    /**
     * @psalm-suppress MixedMethodCall
     *
     * @throws \ReflectionException
     */
    public function testFailedValidateMissingFiled(): void
    {
        $dto           = new SuccessPaymentWebhook('53-24', new \DateTimeImmutable());
        $classMetadata = new ClassMetadata(
            [],
            ReflectionClassWrapper::analyze($dto)->getParametersConstructor(),
            null
        );

        $this->classMetadataFactoryWrapper->method('getMetadataFor')
            ->willReturn($classMetadata)
        ;

        $this->validator->validate([], new Schema(['type' => $dto::class]));

        $this->buildViolation('This field is missing.')
            ->atPath('property.path.id')
            ->setParameter('{{ field }}', formatValue('id'))
            ->setInvalidValue(null)
            ->setCode('dddad9b7-b802-425d-a433-b56b39c5c3eb')

            ->buildNextViolation('This field is missing.')
            ->atPath('property.path.createdAt')
            ->setParameter('{{ field }}', formatValue('createdAt'))
            ->setInvalidValue(null)
            ->setCode('dddad9b7-b802-425d-a433-b56b39c5c3eb')
            ->assertRaised()
        ;
    }

    /**
     * @throws \ReflectionException
     */
    public function testValidateOptionalParameter(): void
    {
        $dto           = new User('Vlad Shashkov', null);
        $classMetadata = new ClassMetadata(
            [],
            ReflectionClassWrapper::analyze($dto)->getParametersConstructor(),
            null
        );

        $this->classMetadataFactoryWrapper->method('getMetadataFor')
            ->willReturn($classMetadata)
        ;

        $this->validator->validate(['name' => 'aza', 'uuid' => null], new Schema(['type' => $dto::class]));

        $this->assertNoViolation();
    }

    /**
     * @dataProvider validateNullableParameterDataProvider
     */
    public function testValidateNullableParameter(object $dto, array $values): void
    {
        $classMetadata = new ClassMetadata(
            [],
            ReflectionClassWrapper::analyze($dto)->getParametersConstructor(),
            null
        );

        $this->classMetadataFactoryWrapper->method('getMetadataFor')
            ->willReturn($classMetadata)
        ;

        $this->validator->validate($values, new Schema(['type' => $dto::class]));

        $this->assertNoViolation();
    }

    /**
     * @return array<int,array{0: object, 1: array}>
     */
    public function validateNullableParameterDataProvider(): array
    {
        return [
            [
                new class (null) {
                    public function __construct(
                        private ?Uuid $uuid
                    ) {
                    }
                },
                ['uuid' => null],
            ],
            [
                new class (null) {
                    public function __construct(
                        private ?Uuid $uuid = null
                    ) {
                    }
                },
                [],
            ],
        ];
    }

    /**
     * @psalm-suppress MixedMethodCall
     *
     * @throws \ReflectionException
     */
    public function testFailedValidate(): void
    {
        $this->addValidators = true;
        self::setUp();

        $dto           = new User('Vlad Shashkov', null);
        $classMetadata = new ClassMetadata(
            [],
            ReflectionClassWrapper::analyze($dto)->getParametersConstructor(),
            null
        );

        $this->classMetadataFactoryWrapper->method('getMetadataFor')
            ->willReturn($classMetadata)
        ;

        $this->validator->validate(['name' => 1, 'uuid' => null], new Schema(['type' => $dto::class, 'strictTypes' => true]));

        $this->buildViolation('This value should be of type {{ type }}.')
            ->atPath('property.path.name')
            ->setParameters([
                '{{ type }}'  => 'string',
                '{{ value }}' => '1',
            ])
            ->setCode('24231bed-2239-420e-add0-ae2a80ba360c')

            ->buildNextViolation('This value should be of type {{ type }}.')
            ->atPath('property.path.isActive')
            ->setParameters([
                '{{ type }}'  => 'bool',
                '{{ value }}' => 'null',
            ])
            ->setCode('24231bed-2239-420e-add0-ae2a80ba360c')
            ->assertRaised()
        ;
    }

    protected function createValidator(): ConstraintValidator
    {
        $validators = $this->addValidators ? [
            new StubIgnoreValidator(),
            new StubFailedValidator(),
            new StubFailedValidator(),
        ] : [];

        return new SchemaValidator($validators, $this->classMetadataFactoryWrapper);
    }
}
