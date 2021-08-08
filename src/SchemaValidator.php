<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator;

use SchemaValidator\CircularReference\CircularReferenceStorage;
use SchemaValidator\Metadata\ClassMetadataFactory;
use SchemaValidator\Validator\Validator;
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
     * @param iterable<Validator> $validators
     */
    public function __construct(
        private iterable $validators,
        private ClassMetadataFactory $classMetadataFactory
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
            $property = $mapping->property();

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
                ->setParameter('{{ allowed }}', $this->formatValues($mapping->mapValue()))
                ->setCode(Schema::UNKNOWN_RESOURCE_CODE)
                ->setInvalidValue($property->getInvalidValue())
                ->addViolation()
            ;

            return;
        }

        $attributes = $metadata->getAttributes();

        foreach ($metadata->getParameters() as $parameter) {
            $reflectionType = $parameter->getType();
            $attribute      = $attributes[$parameter->name] ?? null;
            $propertyName   = $attribute?->getSerializedName() ?? $parameter->name;

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

                $storage  = $validator->isEnabledCircularReferenceStorage() ? $constraint->circularReferenceStorage ?? new CircularReferenceStorage() : null;
                $argument = new Argument($propertyName, $value, $reflectionType);
                $context  = new Context(
                    $constraint->rootPath,
                    $constraint->type,
                    $constraint->strictTypes,
                    $this->context,
                    $attribute?->getMaxDepth(),
                    $storage
                );

                $validator->validate($argument, $context);

                break;
            }
        }
    }
}
