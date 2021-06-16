<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Validator;

use SchemaValidator\Argument;
use SchemaValidator\Context;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type as ConstraintType;
use Symfony\Component\Validator\Util\PropertyPath;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidator;

final class DateValidator implements ValidatorInterface
{
    public function __construct(
        private SymfonyValidator $validator,
    ) {
    }

    public function support(\ReflectionType $type): bool
    {
        return $type instanceof \ReflectionNamedType && is_subclass_of($type->getName(), \DateTimeInterface::class);
    }

    public function validate(Argument $argument, Context $context): void
    {
        $this->validator->inContext($context->getExecution())
            ->atPath(PropertyPath::append($context->getRootPath(), $argument->getName()))
            ->validate($argument->getValueByArgumentName(), [
                new ConstraintType([
                    'type' => ['string'] + ($argument->getType()->allowsNull() ? ['null'] : []),
                ]),
                new NotBlank(['allowNull' => $argument->getType()->allowsNull()]),
                new DateTime(),
            ])
        ;
    }
}
