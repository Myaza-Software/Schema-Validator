<?php

$finder = (new PhpCsFixer\Finder())
    ->in(['src','tests'])
    ->exclude('var')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony'       => true,
        '@Symfony:risky' => true,
        '@PSR12'         => true,
        '@PSR12:risky'   => true,
        'declare_strict_types' => true,

        'no_superfluous_phpdoc_tags' => true,

        // exceptions
        'single_line_throw' => false,

        // php file
        'concat_space' => ['spacing' => 'one'],

        // namespace and imports
        'ordered_imports'         => true,
        'global_namespace_import' => [
            'import_classes'   => false,
            'import_constants' => false,
            'import_functions' => false,
        ],

        // standard functions and operators
        'native_constant_invocation' => false,
        'native_function_invocation' => false,
        'modernize_types_casting'    => true,
        'is_null'                    => true,
        // arrays
        'array_syntax' => [
            'syntax' => 'short',
        ],

        // phpdoc
        'phpdoc_annotation_without_dot' => false,
        'phpdoc_summary'                => false,

        // logical operators
        'logical_operators' => true,

        'final_class'            => true,
        'class_definition'       => false,
        'binary_operator_spaces' => ['operators' => ['=>' => 'align_single_space_minimal', '=' => 'align_single_space_minimal']],

        '@PHP74Migration'       => true,
        '@PHP74Migration:risky' => true,
        '@PHP80Migration'       => true,
        '@PHP80Migration:risky' => true,
    ])
    ->setFinder($finder)
;
