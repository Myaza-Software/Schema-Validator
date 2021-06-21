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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Context\ExecutionContextFactory;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class ValidatorTestCase extends TestCase
{
    protected ValidatorInterface $validator;
    protected ExecutionContextInterface $executionContext;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidator();
        $translator      = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnArgument(0)
        ;

        $factory = new ExecutionContextFactory($translator);

        $this->executionContext = $factory->createContext($this->validator, '');
    }

    protected function buildViolation(string $message, Constraint $constraint): ConstraintViolationAssertion
    {
        return new ConstraintViolationAssertion($this->executionContext, $message, $constraint);
    }

    protected function assertNoViolation(): void
    {
        $violations = $this->executionContext->getViolations();
        $message    = sprintf('0 violation expected. Got %u.', $violations->count());

        $this->assertCount(0, $violations, $message);
    }

    /**
     * @psalm-suppress UndefinedInterfaceMethod
     */
    protected function assertEqualsConstraint(Constraint $constraint): void
    {
        $this->assertEquals($constraint, $this->executionContext->getConstraint());
    }
}

/**
 * @internal
 */
final class ConstraintViolationAssertion
{
    private array  $parameters   = [];
    private mixed $invalidValue  = 'InvalidValue';
    private string $propertyPath = 'property.path';
    private ?int      $plural    = null;
    private ?string $code        = null;
    private mixed $cause         = null;

    /**
     * @param ConstraintViolationAssertion[] $assertions
     */
    public function __construct(
        private ExecutionContextInterface $context,
        private string $message,
        private ?Constraint $constraint = null,
        private array $assertions = []
    ) {
    }

    public function atPath(string $path): self
    {
        $this->propertyPath = $path;

        return $this;
    }

    public function setParameter(string $key, string $value): self
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function setInvalidValue(mixed $invalidValue): self
    {
        $this->invalidValue = $invalidValue;

        return $this;
    }

    public function setPlural(int $number): self
    {
        $this->plural = $number;

        return $this;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function setCause(mixed $cause): self
    {
        $this->cause = $cause;

        return $this;
    }

    public function buildNextViolation(string $message): self
    {
        $assertions   = $this->assertions;
        $assertions[] = $this;

        return new self($this->context, $message, $this->constraint, $assertions);
    }

    public function assertRaised(): void
    {
        $expected = [];
        foreach ($this->assertions as $assertion) {
            $expected[] = $assertion->getViolation();
        }
        $expected[] = $this->getViolation();

        $violations      = iterator_to_array($this->context->getViolations());
        $expectedCount   = \count($expected);
        $violationsCount = \count($violations);
        $message         = sprintf('%u violation(s) expected. Got %u.', $expectedCount, $violationsCount);

        Assert::assertSame($expectedCount, $violationsCount, $message);

        reset($violations);

        foreach ($expected as $violation) {
            Assert::assertEquals($violation, current($violations));
            next($violations);
        }
    }

    private function getViolation(): ConstraintViolation
    {
        return new ConstraintViolation(
            $this->message,
            $this->message,
            $this->parameters,
            $this->context->getRoot(),
            $this->propertyPath,
            $this->invalidValue,
            $this->plural,
            $this->code,
            $this->constraint,
            $this->cause
        );
    }
}
