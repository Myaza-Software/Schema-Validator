<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator;

use SchemaValidator\Metadata\ClassMetadataFactoryWrapperInterface;
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
        private ClassMetadataFactoryWrapperInterface $classMetadataFactory
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

        if ($metadata->isEmpty()) {
            $mapping  = $metadata->getMapping();
            $property = $mapping->getProperty();

            if (!$property->isExits()) {
                $this->context->buildViolation(Schema::MESSAGE_FILED_MISSING)
                    ->atPath(PropertyPath::append($constraint->rootPath, $property->getName()))
                    ->setCode(Schema::MISSING_FILED_CODE)
                    ->setParameter('{{ field }}', $this->formatValue($property->getName()))
                    ->setInvalidValue(null)
                    ->addViolation()
                ;

                return;
            }

            $this->context->buildViolation(Schema::UNKNOWN_RESOURCE)
                ->atPath(PropertyPath::append($constraint->rootPath, $property->getName()))
                ->setParameter('{{ allowed }}', $this->formatValues($mapping->getMapValue()))
                ->setCode(Schema::UNKNOWN_RESOURCE_CODE)
                ->setInvalidValue($property->getInvalidValue())
                ->addViolation()
            ;

            return;
        }

        $attributes = $metadata->getAttributes();

        foreach ($metadata->getParameters() as $parameter) {
            $reflectionType   = $parameter->getType();
            $rootPropertyName = ($attributes[$parameter->name] ?? null)?->getSerializedName() ?? $parameter->name;

            if ($parameter->isOptional() || null === $reflectionType) {
                return;
            }

            if (!array_key_exists($rootPropertyName, $value)) {
                $this->context->buildViolation(Schema::MESSAGE_FILED_MISSING)
                    ->atPath(PropertyPath::append($constraint->rootPath, $rootPropertyName))
                    ->setCode(Schema::MISSING_FILED_CODE)
                    ->setParameter('{{ field }}', $this->formatValue($rootPropertyName))
                    ->setInvalidValue(null)
                    ->addViolation()
                ;
            }

            foreach ($this->validators as $validator) {
                if (!$validator->support($reflectionType)) {
                    continue;
                }

                $rootPath = $constraint->rootPath === '' ? $rootPropertyName : $constraint->rootPath;
                $class    = $constraint->class;

                $argument = new Argument($rootPropertyName, $value, $reflectionType);
                $context  = new Context($rootPath, $class, $this->context);

                $validator->validate($argument, $context);

                return;
            }
        }
    }
}
