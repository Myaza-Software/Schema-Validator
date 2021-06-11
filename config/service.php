<?php

declare(strict_types=1);

use SchemaValidator\Metadata\ClassMetadataFactoryWrapper;
use SchemaValidator\SchemaValidator;
use SchemaValidator\Validator\DateValidator;
use SchemaValidator\Validator\ObjectValidator;
use SchemaValidator\Validator\PrimitiveValidator;
use SchemaValidator\Validator\UnionTypeValidator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $di): void {
    $services = $di->services();

    $services
        ->set('schema.object_validator', ObjectValidator::class)
            ->args([service('validator')])
            ->tag('schema.validator')

        ->set('schema.primitive_validator', PrimitiveValidator::class)
            ->args([service('validator')])
            ->tag('schema.validator')

        ->set('schema.date_validator', DateValidator::class)
            ->args([service('validator')])
            ->tag('schema.validator')

        ->set('schema.union_type_validator', UnionTypeValidator::class)
            ->args([tagged_iterator('schema.validator')])
            ->tag('schema.validator')

        ->set('schema.class_metadata_factory', ClassMetadataFactoryWrapper::class)
            ->args([
                service('property_accessor'),
                service('serializer.mapping.class_metadata_factory'),
                service('serializer.mapping.class_discriminator_resolver')
            ])

        ->set('schema.validator', SchemaValidator::class)
            ->args([
                tagged_iterator('schema.validator'),
                service('schema.class_metadata_factory')
            ])
            ->tag('validator.constraint_validator')
            ->alias(SchemaValidator::class,'schema.validator')
    ;
};