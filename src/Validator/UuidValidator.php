<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Validator;

use Ramsey\Uuid\UuidInterface;
use SchemaValidator\Argument;
use SchemaValidator\Context;
use function SchemaValidator\findUuidVersion;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Uuid as SymfonyUuidConstraint;
use Symfony\Component\Validator\Util\PropertyPath;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidator;

final class UuidValidator implements Validator
{
    private const INVALID_UUID_VERSION = 'This is not a valid UUID. Allowed Versions: %s';
    private const UUID_TYPES           = [
        UuidInterface::class,
        AbstractUid::class,
    ];

    /**
     * UuidValidator constructor.
     *
     * @param array<class-string|string> $uuids
     */
    public function __construct(
        private SymfonyValidator $validator,
        private array $uuids = self::UUID_TYPES,
    ) {
    }

    public function isEnabledCircularReferenceStorage(): bool
    {
        return false;
    }

    public function support(\ReflectionType $type): bool
    {
        if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
            return false;
        }

        $typeName = $type->getName();

        foreach ($this->uuids as $uuid) {
            if ((interface_exists($uuid) || class_exists($uuid)) && is_subclass_of($typeName, $uuid)) {
                return true;
            }
        }

        return false;
    }

    public function validate(Argument $argument, Context $context): void
    {
        $type = $argument->type();

        if (!$type instanceof \ReflectionNamedType) {
            throw new \InvalidArgumentException('Type expected:' . \ReflectionNamedType::class);
        }

        /** @var string $value */
        $value = $argument->currentValue();
        /** @var class-string $uuid */
        $uuid      = $type->getName();
        $execution = $context->execution();
        $version   = findUuidVersion($uuid);
        $options   = ['strict' => $context->strictTypes()];

        if (null !== $version) {
            $options['versions'] = [$version];
            $options['message']  = sprintf(self::INVALID_UUID_VERSION, $version);
        }

        $this->validator->inContext($execution)
            ->atPath(PropertyPath::append($context->path(), $argument->name()))
            ->validate($value, [
                new NotBlank(),
                new SymfonyUuidConstraint($options),
            ])
        ;
    }
}
