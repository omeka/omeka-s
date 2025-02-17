<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('var')
    ->exclude('vendor')
;

$header = <<<EOF
This file is part of the doctrine spatial extension.

PHP 7.4 | 8.0 | 8.1

(c) Alexandre Tranchant <alexandre.tranchant@gmail.com> 2017 - 2022
(c) Longitude One 2020 - 2022 
(c) 2015 Derek J. Lambert

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.

EOF;

return (new PhpCsFixer\Config())
//    ->setCacheFile(__DIR__.'/.php_cs.cache')
    ->setRules([
        '@DoctrineAnnotation' => true,
        '@PhpCsFixer' => true,
        '@Symfony' => true,
        '@PHPUnit75Migration:risky' => true,
        '@PHP70Migration' => true,
        '@PHP71Migration' => true,
        '@PHP73Migration' => true,
//        'strict_param' => true,
        'array_syntax' => ['syntax' => 'short'],
        'dir_constant' => true,
        'ereg_to_preg' => true,
        'header_comment' => [
            'comment_type' => 'PHPDoc',
            'header' => $header,
            'location' => 'after_open',
            'separate' => 'bottom'
        ],
//        'date_time_immutable' => true,
//        'declare_strict_types' => true,
        'is_null' => true,
        'mb_str_functions' => true,
        'modernize_types_casting' => true,
        'no_unneeded_final_method' => true,
//        'no_alias_functions' =>true,
        'ordered_interfaces' => [
            'direction' => 'ascend',
            'order' => 'alpha',
        ],
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'constant_public', 'constant_protected', 'constant_private', 'constant',
                'property_public_static', 'property_protected_static', 'property_private_static', 'property_static',
                'property_public', 'property_protected', 'property_private',  'property',
                'construct', 'destruct',
                'phpunit',
                'method_public_static', 'method_protected_static', 'method_private_static', 'method_static',
                'method_public', 'method_protected', 'method_private', 'method', 'magic'
            ],
            'sort_algorithm' => 'alpha'
        ],
        'php_unit_test_case_static_method_calls' => true,
        'single_line_throw' => false
    ])
    ->setFinder($finder)
    ;