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
use SchemaValidator\Context;
use SchemaValidator\Test\Unit\ArgumentBuilder;
use SchemaValidator\Test\Unit\ReflectionClassWrapper;
use SchemaValidator\Validator\PrimitiveValidator;
use Symfony\Component\Validator\Constraints\Type;

final class PrimitiveValidatorTest extends ValidatorTestCase
{
    public function testInvalidArgument(): void
    {
        $this->expectExceptionCode(0);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Type expected:' . \ReflectionNamedType::class);

        $validator            = new PrimitiveValidator($this->validator);
        $dtoWithUnionTypeArgs = new class(1) {
            public function __construct(
                private string | int $id,
            ) {
            }
        };

        $argument = ArgumentBuilder::build($dtoWithUnionTypeArgs, ['id' => 50]);
        $context  = new Context('', $dtoWithUnionTypeArgs::class, $this->executionContext, false);

        $validator->validate($argument, $context);
    }

    /**
     * @dataProvider successValidateDataProvider
     */
    public function testSuccessValidate(Argument $argument, array $other): void
    {
        $context   = new Context(...$other + ['execution' => $this->executionContext]);
        $validator = new PrimitiveValidator($this->validator);

        $validator->validate($argument, $context);

        $this->assertNoViolation();
    }

    /**
     * @throws \ReflectionException
     */
    public function successValidateDataProvider(): array
    {
        [
            'dtoWithIntArg'       => $dtoWithIntArg,
            'dtoWithFloatArg'     => $dtoWithFloatArg,
            'dtoWithStringArg'    => $dtoWithStringArg,
            'dtoWithNullableArgs' => $dtoWithNullableArgs,
        ] = $this->getDtos();

        return [
            [
                ArgumentBuilder::build($dtoWithIntArg, ['id' => 50]),
                ['rootPath' => '', 'rootType' => $dtoWithIntArg::class, 'strictTypes' => true],
            ],
            [
                ArgumentBuilder::build($dtoWithIntArg, ['id' => '50']),
                ['rootPath' => '', 'rootType' => $dtoWithIntArg::class, 'strictTypes' => false],
            ],
            [
                ArgumentBuilder::build($dtoWithFloatArg, ['value' => 1.333]),
                ['rootPath' => '', 'rootType' => $dtoWithFloatArg::class, 'strictTypes' => true],
            ],
            [
                ArgumentBuilder::build($dtoWithStringArg, ['name' => 'AZA']),
                ['rootPath' => '', 'rootType' => $dtoWithStringArg::class, 'strictTypes' => true],
            ],
            [
                ArgumentBuilder::build($dtoWithNullableArgs, ['id' => null]),
                ['rootPath' => '', 'rootType' => $dtoWithNullableArgs::class, 'strictTypes' => true],
            ],
        ];
    }

    /**
     * @dataProvider supportDataProvider
     */
    public function testSupport(\ReflectionType $type, bool $isSupport): void
    {
        $validator = new PrimitiveValidator($this->validator);

        $this->assertEquals($validator->support($type), $isSupport);
    }

    /**
     * @throws \ReflectionException
     */
    public function supportDataProvider(): array
    {
        ['dtoWithObjectArgs' => $dtoWithObjectArgs, 'dtoWithFloatArg' => $dtoWithPrimitiveArgs] = $this->getDtos();

        return [
            [new \ReflectionUnionType(), false],
            [ReflectionClassWrapper::analyze($dtoWithObjectArgs)->firstArgType(), false],
            [ReflectionClassWrapper::analyze($dtoWithPrimitiveArgs)->firstArgType(), true],
        ];
    }

    /**
     * @dataProvider failedValidateDataProvider
     */
    public function testFailedValidate(Argument $argument, array $other, string $type): void
    {
        $context    = new Context(...$other + ['execution' => $this->executionContext]);
        $constraint = new Type(['type' => [$type]], groups: ['Default']);
        $validator  = new PrimitiveValidator($this->validator);

        $validator->validate($argument, $context);

        $this->buildViolation('This value should be of type {{ type }}.', $constraint)
            ->atPath($argument->getName())
            ->setParameter('{{ value }}', $this->formatValue($argument->getValueByArgumentName()))
            ->setParameter('{{ type }}', $type)
            ->setInvalidValue($argument->getValueByArgumentName())
            ->setCode('ba785a8c-82cb-4283-967c-3cf342181b40')
            ->assertRaised()
        ;
    }

    /**
     * @throws \ReflectionException
     */
    public function failedValidateDataProvider(): array
    {
        [
            'dtoWithIntArg'    => $dtoWithIntArg,
            'dtoWithFloatArg'  => $dtoWithFloatArg,
            'dtoWithStringArg' => $dtoWithStringArg,
        ] = $this->getDtos();

        return [
            [
                ArgumentBuilder::build($dtoWithFloatArg, ['value' => 1]),
                ['rootPath' => '', 'rootType' => $dtoWithFloatArg::class, 'strictTypes' => true],
                'float',
            ],
            [
                ArgumentBuilder::build($dtoWithFloatArg, ['value' => 'toos']),
                ['rootPath' => '', 'rootType' => $dtoWithFloatArg::class, 'strictTypes' => true],
                'float',
            ],
            [
                ArgumentBuilder::build($dtoWithIntArg, ['id' => 'gavno']),
                ['rootPath' => '', 'rootType' => $dtoWithIntArg::class, 'strictTypes' => true],
                'int',
            ],
            [
                ArgumentBuilder::build($dtoWithIntArg, ['id' => 1.333]),
                ['rootPath' => '', 'rootType' => $dtoWithIntArg::class, 'strictTypes' => true],
                'int',
            ],
            [
                ArgumentBuilder::build($dtoWithStringArg, ['name' => 1]),
                ['rootPath' => '', 'rootType' => $dtoWithStringArg::class, 'strictTypes' => true],
                'string',
            ],
            [
                ArgumentBuilder::build($dtoWithStringArg, ['name' => 1.33]),
                ['rootPath' => '', 'rootType' => $dtoWithStringArg::class, 'strictTypes' => true],
                'string',
            ],
        ];
    }

    /**
     * @return array<string,object>
     */
    private function getDtos(): array
    {
        return [
            'dtoWithIntArg' => new class (1) {
                public function __construct(
                    private int $id,
                ) {
                }
            },
            'dtoWithFloatArg' => new class (1.333) {
                public function __construct(
                    private float $value
                ) {
                }
            },
            'dtoWithStringArg' => new class ('AZA') {
                public function __construct(
                    private string $name
                ) {
                }
            },
            'dtoWithObjectArgs' => new class (new \stdClass()) {
                public function __construct(
                    private \stdClass $vo
                ) {
                }
            },
            'dtoWithNullableArgs' => new class(1) {
                public function __construct(
                    private ?int $id,
                ) {
                }
            },
        ];
    }
}
