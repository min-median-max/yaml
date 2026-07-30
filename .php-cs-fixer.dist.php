<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__)
    ->exclude(['var', 'vendor', 'coverage'])
    ->ignoreDotFiles(false)
    ->ignoreVCS(true)
;

$config = new Config;

return $config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PhpCsFixer' => true,
        '@PHP81Migration' => true,
        'declare_strict_types' => true,
        'void_return' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => true,
        'no_unused_imports' => true,
        'global_namespace_import' => false,
        'no_empty_comment' => true,
        'not_operator_with_successor_space' => false,
        'trailing_comma_in_multiline' => true,
        'phpdoc_scalar' => true,
        'unary_operator_spaces' => true,
        'binary_operator_spaces' => true,
        'blank_line_before_statement' => [
            'statements' => ['break', 'continue', 'declare', 'return', 'throw', 'try'],
        ],
        'phpdoc_single_line_var_spacing' => true,
        'blank_line_after_opening_tag' => false,
        'phpdoc_var_without_name' => false,
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => true,
        ],
        'single_trait_insert_per_statement' => true,
        'new_with_parentheses' => [
            'named_class' => false,
            'anonymous_class' => false,
        ],
        'general_phpdoc_annotation_remove' => ['annotations' => ['internal']],
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => false, 'remove_inheritdoc' => false],
        'php_unit_internal_class' => false,
        'php_unit_method_casing' => false,
    ])
    ->setFinder($finder)
;
