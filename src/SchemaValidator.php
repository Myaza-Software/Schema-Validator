<?php

declare(strict_types=1);

namespace SchemaValidator;

use SchemaValidator\Metadata\ClassMetadataFactoryWrapper;
use SchemaValidator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Util\PropertyPath;

final class SchemaValidator extends ConstraintValidator
{
    /**
     * SchemaValidator constructor.
     *
     * @param iterable<ValidatorInterface> $validators
     */
    public function __construct(
        private iterable $validators,
        private ClassMetadataFactoryWrapper $classMetadataFactory
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Schema) {
            throw new UnexpectedTypeException($constraint, Schema::class);
        }

        if (!is_array($value)) {
            throw new UnexpectedValueException($value, 'array');
        }

        $metadata = $this->classMetadataFactory->getMetadataFor($constraint->class, $value);

        if (null === $metadata) {
            $this->context->buildViolation($constraint::UNKNOWN_RESOURCE)
                ->setCode($constraint::UNKNOWN_RESOURCE_CODE)
                ->addViolation()
            ;
        }

        $attributes  = $metadata->getAttributes();

        foreach ($metadata->getParameters() as $parameter) {
            $reflectionType    = $parameter->getType();
            $rootPropertyName = ($attributes[$parameter->name] ?? null)?->getSerializedName() ?? $parameter->name;

            if ($parameter->isOptional() || $reflectionType === null) {
                return;
            }

            if (!array_key_exists($rootPropertyName, $value)) {
                $this->context->buildViolation($constraint::MESSAGE_FILED_MISSING)
                    ->atPath(PropertyPath::append($constraint->rootPath ?? '', $rootPropertyName))
                    ->setCode($constraint::MISSING_FILED_CODE)
                    ->setParameter('{{ field }}', $this->formatValue($rootPropertyName))
                    ->setInvalidValue(null)
                    ->addViolation()
                ;
            }

            foreach ($this->validators as $validator) {
                if ($validator->support($reflectionType)) {
                    $argument = new Argument($rootPropertyName, $value, $reflectionType);
                    $context  = new Context(
                        $constraint->rootPath ?? $rootPropertyName,
                        $constraint->class,
                        $this->context
                    );

                    $validator->validate($argument, $context);
                }
            }
        }
    }
}
