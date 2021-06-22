<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\CollectionInfoExtractor;

interface CollectionInfoExtractorInterface
{
    /**
     * @param class-string $class
     */
    public function getValueType(string $class, string $propertyName): ValueType;
}
