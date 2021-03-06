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

        $metadata = $this->classMetadataFactory->getMetadataFor($constraint->type, $value);
        $mapping  = $metadata->getMapping();

        if ($metadata->isEmpty() && null !== $mapping) {
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
            $reflectionType = $parameter->getType();
            $propertyName   = ($attributes[$parameter->name] ?? null)?->getSerializedName() ?? $parameter->name;

            if (null === $reflectionType) {
                continue;
            }

            if (!array_key_exists($propertyName, $value) && !$parameter->isOptional()) {
                $this->context->buildViolation(Schema::MESSAGE_FILED_MISSING)
                    ->setParameter('{{ field }}', $this->formatValue($propertyName))
                    ->atPath(PropertyPath::append($constraint->rootPath, $propertyName))
                    ->setCode(Schema::MISSING_FILED_CODE)
                    ->setInvalidValue(null)
                    ->addViolation()
                ;

                continue;
            }

            if (!$parameter->isOptional() && $parameter->allowsNull() && null === $value[$propertyName]) {
                continue;
            }

            foreach ($this->validators as $validator) {
                if (!$validator->support($reflectionType)) {
                    continue;
                }

                $argument = new Argument($propertyName, $value, $reflectionType);
                $context  = new Context($constraint->rootPath, $constraint->type, $constraint->strictTypes, $this->context);

                $validator->validate($argument, $context);

                break;
            }
        }
    }
}
