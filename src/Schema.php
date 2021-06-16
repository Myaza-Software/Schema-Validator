<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator;

use Symfony\Component\Validator\Constraint;

final class Schema extends Constraint
{
    public const MISSING_FILED_CODE    = 'dddad9b7-b802-425d-a433-b56b39c5c3eb';
    public const UNKNOWN_RESOURCE_CODE = '771fd9d5-ea63-4523-9b2d-c2977efc50e3';
    public const INVALID_TYPE_ERROR    = '24231bed-2239-420e-add0-ae2a80ba360c';

    public const MESSAGE_FILED_MISSING = 'This field is missing.';
    public const UNKNOWN_RESOURCE      = 'Unknown resource. Allowed: {{ allowed }}';
    public const INVALID_TYPE          = 'This value should be of type {{ type }}.';

    /**
     * @var class-string
     */
    public string $class;

    public string $rootPath = '';

    /**
     * @return array<string>
     */
    public function getRequiredOptions(): array
    {
        return ['class'];
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
