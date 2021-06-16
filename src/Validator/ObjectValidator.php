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
use SchemaValidator\Schema;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidator;

final class ObjectValidator implements ValidatorInterface, PriorityInterface
{
    public function __construct(
        private SymfonyValidator $validator,
    ) {
    }

    public function support(\ReflectionType $type): bool
    {
        return $type instanceof \ReflectionNamedType && !$type->isBuiltin();
    }

    public function validate(Argument $argument, Context $context): void
    {
        $type = $argument->getType();

        if (!$type instanceof \ReflectionNamedType) {
            throw new \InvalidArgumentException('Invalid reflection named argument');
        }

        $this->validator->inContext($context->getExecution())
            ->validate($argument->getValueByArgumentName() ?? $argument->getRootValues(), [
                new Schema(['class' => $type->getName(), 'rootPath' => $context->getRootPath()]),
            ])
        ;
    }

    public static function getPriority(): int
    {
        return -1;
    }
}
