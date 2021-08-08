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
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ObjectValidator implements Validator, Priority
{
    private const MAX_DEPTH = 5;

    /**
     * ObjectValidator constructor.
     */
    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    public function isEnabledCircularReferenceStorage(): bool
    {
        return true;
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
        $type                     = $argument->type();
        $circularReferenceStorage = $context->circularReferenceStorage();

        if (!$type instanceof \ReflectionNamedType) {
            throw new \InvalidArgumentException('Type expected:' . \ReflectionNamedType::class);
        }

        if (null === $circularReferenceStorage) {
            throw new \RuntimeException('Not found circular reference storage');
        }

        $value    = $argument->currentValue();
        $maxDepth = $context->maxDepth();

        if (null === $maxDepth && $circularReferenceStorage->hasLimit($type->getName(), self::MAX_DEPTH)) {
            throw new \RuntimeException('Detected circular reference, please set maxDepth');
        }

        if (null !== $maxDepth && $circularReferenceStorage->hasLimit($type->getName(), $maxDepth)) {
            return;
        }

        $circularReferenceStorage->calculate($type->getName());

        $this->validator->inContext($context->execution())
            ->validate(is_array($value) ? $value : [$value], [
                new Schema([
                    'type'                     => $type->getName(),
                    'rootPath'                 => PropertyPath::append($context->path(), $argument->name()),
                    'strictTypes'              => $context->strictTypes(),
                    'circularReferenceStorage' => $circularReferenceStorage,
                ]),
            ])
        ;
    }

    public static function priority(): int
    {
        return -1;
    }
}
