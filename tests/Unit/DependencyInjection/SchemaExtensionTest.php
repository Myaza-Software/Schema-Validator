<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

namespace SchemaValidator\Test\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use SchemaValidator\CollectionInfoExtractor\CollectionInfoExtractor;
use SchemaValidator\DependencyInjection\SchemaExtension;
use SchemaValidator\Metadata\ClassMetadataFactoryWrapper;
use SchemaValidator\SchemaValidator;
use SchemaValidator\Validator\ArrayItemValidator;
use SchemaValidator\Validator\DateTimeValidator;
use SchemaValidator\Validator\MyCLabsEnumValidator;
use SchemaValidator\Validator\ObjectValidator;
use SchemaValidator\Validator\PrimitiveValidator;
use SchemaValidator\Validator\UnionTypeValidator;
use SchemaValidator\Validator\UuidValidator;

final class SchemaExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [
           new SchemaExtension(),
        ];
    }

    /**
     * @dataProvider loadedServicesDataProvider
     */
    public function testLoadedServices(string $serviceId, string $class): void
    {
        $this->load();

        $this->assertContainerBuilderHasService($serviceId, $class);
    }

    public function loadedServicesDataProvider(): iterable
    {
        return [
            ['schema.collection_info_extractor', CollectionInfoExtractor::class],
            ['schema.object_validator', ObjectValidator::class],
            ['schema.my_clabs_validator', MyCLabsEnumValidator::class],
            ['schema.uuid_validator', UuidValidator::class],
            ['schema.array_item_validator', ArrayItemValidator::class],
            ['schema.primitive_validator', PrimitiveValidator::class],
            ['schema.date_time_validator', DateTimeValidator::class],
            ['schema.union_type_validator', UnionTypeValidator::class],
            ['schema.class_metadata_factory', ClassMetadataFactoryWrapper::class],
            ['schema.validator', SchemaValidator::class],
        ];
    }
}
