<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->exclude('vendor')
    ->exclude('node_modules')
    ->exclude('public/bundles')
    ->exclude('public/build')
    ->notPath('bin/console')
    ->notPath('public/index.php');

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => true,
        'concat_space' => ['spacing' => 'one'],
        'trailing_comma_in_multiline' => true,
        'phpdoc_summary' => false,
        'no_unused_imports' => true,
        'blank_line_after_opening_tag' => true,
        'linebreak_after_opening_tag' => true,
        'declare_strict_types' => false,
        'phpdoc_order' => true,
        'single_line_throw' => false,
        'yoda_style' => false,
        'phpdoc_to_comment' => false,
        'phpdoc_separation' => false,
        'native_function_invocation' => false,
        'modernize_types_casting' => true,
        'use_arrow_functions' => true,
        'void_return' => true,
        'ordered_class_elements' => true,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setUsingCache(true)
    ->setCacheFile('.php-cs-fixer.cache');