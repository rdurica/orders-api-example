<?php

declare(strict_types=1);

require_once __DIR__ . '/tools/php-cs-fixer/SingleLineShortMethodSignatureFixer.php';

use App\Tools\PhpCsFixer\SingleLineShortMethodSignatureFixer;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->in(__DIR__ . '/migrations')
    ->exclude('var')
    ->exclude('vendor');

return (new PhpCsFixer\Config())
    ->registerCustomFixers([
        new SingleLineShortMethodSignatureFixer(),
    ])
    ->setRules([
        '@Symfony' => true,
        'App/single_line_short_method_signature' => true,
        'braces_position' => [
            'classes_opening_brace' => 'next_line_unless_newline_at_signature_end',
            'functions_opening_brace' => 'next_line_unless_newline_at_signature_end',
            'anonymous_functions_opening_brace' => 'next_line_unless_newline_at_signature_end',
            'control_structures_opening_brace' => 'next_line_unless_newline_at_signature_end',
            'anonymous_classes_opening_brace' => 'next_line_unless_newline_at_signature_end',
        ],
        'control_structure_continuation_position' => [
            'position' => 'next_line',
        ],
        'yoda_style' => false,
        'concat_space' => ['spacing' => 'one'],
        'binary_operator_spaces' => [
            'operators' => [
                '=>' => 'align_single_space_minimal',
            ],
        ],
        'phpdoc_summary' => false,
        'phpdoc_to_comment' => false,
        'single_line_throw' => false,
    ])
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/var/.php-cs-fixer.cache');
