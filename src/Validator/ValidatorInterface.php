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

interface ValidatorInterface
{
    public function support(\ReflectionType $type): bool;

    public function validate(Argument $argument, Context $context): void;
}
