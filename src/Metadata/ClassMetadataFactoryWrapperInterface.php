<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

namespace SchemaValidator\Metadata;

interface ClassMetadataFactoryWrapperInterface
{
    public function getMetadataFor(string $class, array $values): ClassMetadataInterface;
}