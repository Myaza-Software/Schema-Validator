<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Validator;

use Ramsey\Uuid\Uuid;
use SchemaValidator\Argument;
use SchemaValidator\Context;
use SchemaValidator\UuidExtractor\UuidExtractor;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Validator\Constraints\Uuid as SymfonyUuidConstraint;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidator;

final class UuidValidator implements ValidatorInterface, PriorityInterface
{
    private const INVALID_UUID_VERSION = 'This is not a valid UUID. Allowed Versions: %s';

    public function __construct(
        private SymfonyValidator $validator,
    ) {
    }

    public function support(\ReflectionType $type): bool
    {
        if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
            return false;
        }

        $typeName = $type->getName();

        if (!class_exists(Uuid::class) && !class_exists(AbstractUid::class)) {
            return false;
        }

        $isRamseyUuid  = is_subclass_of($typeName, Uuid::class);
        $isSymfonyUuid = is_subclass_of($typeName, AbstractUid::class);

        if (!$isRamseyUuid && !$isSymfonyUuid) {
            return false;
        }

        return true;
    }

    public function validate(Argument $argument, Context $context): void
    {
        $type = $argument->getType();

        if (!$type instanceof \ReflectionNamedType) {
            throw new \InvalidArgumentException('Invalid reflection named argument');
        }

        /** @var string $value */
        $value   = $argument->getValueByArgumentName();
        /** @var class-string $uuid */
        $uuid      = $type->getName();
        $execution = $context->getExecution();
        $version = UuidExtractor::findVersion($uuid);
        $options = [];

        if (null !== $version) {
            $options['versions'] = [$version];
            $options['message']  = sprintf(self::INVALID_UUID_VERSION, $version);
        }

        $this->validator->inContext($execution)->validate($value, [
            new SymfonyUuidConstraint($options),
        ]);
    }

    public static function getPriority(): int
    {
        return 2;
    }
}
