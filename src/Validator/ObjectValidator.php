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
use Symfony\Component\Validator\Util\PropertyPath;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidator;

final class ObjectValidator implements ValidatorInterface, PriorityInterface
{
    /**
     * ObjectValidator constructor.
     *
     * @param SymfonyValidator $validator
     */
    public function __construct(
        private SymfonyValidator $validator,
    ) {
    }

    public function support(\ReflectionType $type): bool
    {
        return $type instanceof \ReflectionNamedType && !$type->isBuiltin();
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function validate(Argument $argument, Context $context): void
    {
        $type = $argument->getType();

        if (!$type instanceof \ReflectionNamedType) {
            throw new \InvalidArgumentException('Type expected:' . \ReflectionNamedType::class);
        }

        $value = $argument->getValueByArgumentName();

        $this->validator->inContext($context->getExecution())
            ->validate(is_array($value) ? $value : [$value], [
                new Schema([
                    'type'        => $type->getName(),
                    'rootPath'    => PropertyPath::append($context->getRootPath(), $argument->getName()),
                    'strictTypes' => $context->isStrictTypes(),
                ]),
            ])
        ;
    }

    public static function priority(): int
    {
        return -1;
    }
}
