<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Test\Unit\Validator;

use Ramsey\Uuid\FeatureSet;
use Ramsey\Uuid\Nonstandard\UuidV6 as RamseyUuidV6;
use Ramsey\Uuid\Rfc4122\UuidV1 as RamseyUuidV1;
use Ramsey\Uuid\Rfc4122\UuidV2 as RamseyUuidV2;
use Ramsey\Uuid\Rfc4122\UuidV3 as RamseyUuidV3;
use Ramsey\Uuid\Rfc4122\UuidV4 as RamseyUuidV4;
use Ramsey\Uuid\Rfc4122\UuidV5 as RamseyUuidV5;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Uuid as RamseyUuid;
use Ramsey\Uuid\UuidFactory;
use SchemaValidator\Argument;
use SchemaValidator\Context;
use function SchemaValidator\findUuidVersion;
use function SchemaValidator\formatValue;
use SchemaValidator\Test\Unit\ArgumentBuilder;
use SchemaValidator\Test\Unit\ReflectionClassWrapper;
use SchemaValidator\Validator\UuidValidator;
use Symfony\Component\Uid\AbstractUid as AbstractSymfonyUuid;
use Symfony\Component\Uid\Uuid as SymfonyUuid;
use Symfony\Component\Uid\UuidV1 as SymfonyUuidV1;
use Symfony\Component\Uid\UuidV3 as SymfonyUuidV3;
use Symfony\Component\Uid\UuidV4 as SymfonyUuidV4;
use Symfony\Component\Uid\UuidV5 as SymfonyUuidV5;
use Symfony\Component\Uid\UuidV6 as SymfonyUuidV6;
use Symfony\Component\Validator\Constraints\Uuid as SymfonyUuidConstraint;

final class UuidValidatorTest extends ValidatorTestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testInvalidArgument(): void
    {
        $this->expectExceptionCode(0);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Type expected:' . \ReflectionNamedType::class);

        $validator            = new UuidValidator($this->validator);
        $dtoWithUnionTypeArgs = new class(1) {
            public function __construct(
                private string | int $id,
            ) {
            }
        };

        $argument = ArgumentBuilder::build($dtoWithUnionTypeArgs, ['uuid' => Uuid::uuid4()->toString()]);
        $context  = new Context('', $dtoWithUnionTypeArgs::class, false, $this->executionContext);

        $validator->validate($argument, $context);
    }

    /**
     * @dataProvider supportDataProvider
     */
    public function testSupport(\ReflectionType $type, bool $isSupport): void
    {
        $validator = new UuidValidator($this->validator);

        $this->assertEquals($isSupport, $validator->support($type));
    }

    /**
     * @throws \ReflectionException
     *
     * @return \Generator<int, array{0:\ReflectionType, 1: bool}>
     */
    public function supportDataProvider(): \Generator
    {
        yield [new \ReflectionUnionType(), false];

        foreach ($this->getObjectsWithUuid() as $dto) {
            yield [ReflectionClassWrapper::analyze($dto)->firstArgType(), true];
        }
    }

    /**
     * @psalm-suppress MixedArgument
     * @dataProvider successValidateDataProvider
     *
     * @param array{path:string, type: string, strictTypes: bool} $other
     */
    public function testSuccessValidate(Argument $argument, array $other): void
    {
        $context   = new Context(...$other + ['execution' => $this->executionContext]);
        $validator = new UuidValidator($this->validator);

        $validator->validate($argument, $context);

        $this->assertNoViolation();
    }

    /**
     * @throws \ReflectionException
     *
     * @return \Generator<int,array{0: Argument, 1: array{path:string, type: string, strictTypes: bool}}>
     */
    public function successValidateDataProvider(): \Generator
    {
        foreach ($this->getObjectsWithUuid() as $dto) {
            yield [
                ArgumentBuilder::build($dto, ['id' => (string) $dto->id]),
                ['path' => '', 'type' => $dto::class, 'strictTypes' => true],
            ];
        }
    }

    /**
     * @psalm-suppress MixedArgument
     * @dataProvider failedValidateDataProvider
     *
     * @param array{path:string, type: string, strictTypes: bool} $other
     */
    public function testFailedValidate(Argument $argument, array $other, ?int $version): void
    {
        $message   = 'This is not a valid UUID.';
        $options   = ['versions' => SymfonyUuidConstraint::ALL_VERSIONS];
        $context   = new Context(...$other + ['execution' => $this->executionContext]);
        $validator = new UuidValidator($this->validator);

        if (null !== $version) {
            $message             = sprintf('This is not a valid UUID. Allowed Versions: %s', $version);
            $options['versions'] = [$version];
            $options['message']  = $message;
        }

        $constraint = new SymfonyUuidConstraint($options, groups: ['Default']);

        $validator->validate($argument, $context);

        $this->buildViolation($message, $constraint)
            ->atPath($argument->name())
            ->setParameter('{{ value }}', formatValue($argument->currentValue()))
            ->setInvalidValue($argument->currentValue())
            ->setCode($version ? '21ba13b4-b185-4882-ac6f-d147355987eb' : '51120b12-a2bc-41bf-aa53-cd73daf330d0')
            ->assertRaised()
        ;
    }

    /**
     * @throws \ReflectionException
     *
     * @return \Generator<int,array{0: Argument, 1: array{path:string, type: string, strictTypes: bool}, 2: ?int}>
     */
    public function failedValidateDataProvider(): iterable
    {
        foreach ($this->getObjectsWithUuid() as $dto) {
            $uuid    = ReflectionClassWrapper::analyze($dto)->firstArgType();
            $version = findUuidVersion((string) $uuid);

            if (null == $version) {
                yield [
                    ArgumentBuilder::build($dto, ['id' => 'aza']),
                    ['path' => '', 'type' => $dto::class, 'strictTypes' => true],
                    $version,
                ];

                continue;
            }

            yield [
                ArgumentBuilder::build($dto, ['id' => (string) (6 !== $version ? SymfonyUuid::v6() : SymfonyUuid::v4())]),
                ['path' => '', 'type' => $dto::class, 'strictTypes' => true],
                $version,
            ];
        }
    }

    /**
     * @psalm-suppress UnusedVariable
     *
     * @return \Generator<int, object{id:AbstractSymfonyUuid|RamseyUuid}, mixed, void>
     */
    private function getObjectsWithUuid(): \Generator
    {
        RamseyUuid::setFactory(new UuidFactory(new FeatureSet()));

        $class = '$dto =  new class($id){
            public function __construct(public %s $id)
            {}
          };
         ';

        $uuids = [
            SymfonyUuid::class   => SymfonyUuid::v1(),
            SymfonyUuidV1::class => SymfonyUuid::v1(),
            SymfonyUuidV3::class => SymfonyUuid::v3(SymfonyUuid::v1(), 'aza'),
            SymfonyUuidV4::class => SymfonyUuid::v4(),
            SymfonyUuidV5::class => SymfonyUuidV5::v5(SymfonyUuid::v1(), 'aza'),
            SymfonyUuidV6::class => SymfonyUuid::v6(),
            RamseyUuid::class    => RamseyUuid::uuid4(),
            RamseyUuidV1::class  => RamseyUuid::uuid1(),
            RamseyUuidV2::class  => RamseyUuid::uuid2(1),
            RamseyUuidV3::class  => RamseyUuid::uuid3(RamseyUuid::uuid1(), 'aza'),
            RamseyUuidV4::class  => RamseyUuid::uuid4(),
            RamseyUuidV5::class  => RamseyUuid::uuid5(RamseyUuid::uuid1(), 'aza'),
            RamseyUuidV6::class  => RamseyUuid::uuid6(),
        ];

        foreach ($uuids as $type => $id) {
            /** @var object{id: RamseyUuid|AbstractSymfonyUuid} $dto */
            $dto     = null;
            $newCode = sprintf($class, $type);

            eval($newCode);
            yield $dto;
        }
    }
}
