<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Metadata;

interface ClassMetadataFactoryWrapperInterface
{
    public function getMetadataFor(string $type, array $values): ClassMetadataInterface;
}
