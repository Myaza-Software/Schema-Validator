<?php
/**
 * Schema Validator
 *
 * @author Vlad Shashkov <root@myaza.info>
 * @copyright Copyright (c) 2021, The Myaza Software
 */

declare(strict_types=1);

use SchemaValidator\CollectionInfoExtractor\CollectionInfoExtractor;
use SchemaValidator\Metadata\ClassMetadataFactoryWrapper;
use SchemaValidator\SchemaValidator;
use SchemaValidator\Validator\ArrayItemValidator;
use SchemaValidator\Validator\DateTimeValidator;
use SchemaValidator\Validator\ObjectValidator;
use SchemaValidator\Validator\MyCLabsEnumValidator;
use SchemaValidator\Validator\PrimitiveValidator;
use SchemaValidator\Validator\UnionTypeValidator;
use SchemaValidator\Validator\UuidValidator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $di): void {
    $services = $di->services();

    $services
        ->set('schema.collection_info_extractor', CollectionInfoExtractor::class)
            ->args([service('property_info')])

        ->set('schema.object_validator', ObjectValidator::class)
            ->args([service('validator')])
            ->tag('schema.validator')

        ->set('schema.my_clabs_validator', MyCLabsEnumValidator::class)
            ->args([service('validator')])
            ->tag('schema.validator')

        ->set('schema.uuid_validator', UuidValidator::class)
            ->args([service('validator')])
            ->tag('schema.validator')

        ->set('schema.array_item_validator', ArrayItemValidator::class)
            ->args([
                service('validator'),
                service('schema.collection_info_extractor')
            ])
            ->tag('schema.validator')

        ->set('schema.primitive_validator', PrimitiveValidator::class)
            ->args([service('validator')])
            ->tag('schema.validator')

        ->set('schema.date_time_validator', DateTimeValidator::class)
            ->tag('schema.validator')

        ->set('schema.union_type_validator', UnionTypeValidator::class)
            ->args([tagged_iterator('schema.validator')])
            ->tag('schema.validator')

        ->set('schema.class_metadata_factory', ClassMetadataFactoryWrapper::class)
            ->args([
                service('property_accessor'),
                service('serializer.mapping.class_metadata_factory'),
            ])

        ->set('schema.validator', SchemaValidator::class)
            ->args([
                tagged_iterator('schema.validator', defaultPriorityMethod: 'getPriority'),
                service('schema.class_metadata_factory')
            ])
            ->tag('validator.constraint_validator')
            ->alias(SchemaValidator::class,'schema.validator')
    ;
};