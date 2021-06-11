<?php

declare(strict_types=1);

namespace SchemaValidator\Validator;

use SchemaValidator\Argument;
use SchemaValidator\Context;
use SchemaValidator\Schema;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Util\PropertyPath;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidator;

final class ArrayItemValidator implements ValidatorInterface
{
    public function __construct(
        private SymfonyValidator $validator,
        private PropertyInfoExtractorInterface $propertyAccessor,
    ) {
    }

    public function support(\ReflectionType $type): bool
    {
        return $type instanceof \ReflectionNamedType && $type->isBuiltin() && 'array' === $type->getName();
    }

    public function validate(Argument $argument, Context $context): void
    {
        $argumentName = $argument->getName();
        [
            'class'       => $class,
            'builtinType' => $builtinType
        ] = $this->findArrayValueType($context->getRootType(), $argumentName);


        if (null === $class && null === $builtinType) {
            return;
        }

        $propertyPath = PropertyPath::append($context->getRootPath(), $argumentName);
        $validator    = $this->validator->inContext($context->getExecution());
        $value        = $argument->getValueByArgumentName();


        if ($class !== null){
            foreach ($value as $key => $item) {
                $validator->validate($item, [
                    new Schema([
                        'class'    => $class,
                        'rootPath' => $propertyPath . '[' . $key . ']',
                    ]),
                ]);
            }

            return;
        }

        $validator->atPath($propertyPath)
            ->validate($value, new Collection([
                new Type($builtinType)
            ]))
        ;
    }

    private function findArrayValueType(string $class, string $propertyName): array
    {
        if (null !== $typesProperty = $this->propertyAccessor->getTypes($class, $propertyName)) {
            foreach ($typesProperty as $type) {
                if ($type->isCollection() && ($valueType = $type->getCollectionValueType()) !== null) {
                    return [
                        'class'       => $valueType->getClassName(),
                        'builtinType' => $valueType->getBuiltinType()
                    ];
                }
            }
        }

        return [
            'class'       => null,
            'builtinType' => null
        ];
    }
}
