<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__.'/extractors')
    ->in(__DIR__.'/parsers')
    ->in(__DIR__.'/serializers')
    ->in(__DIR__.'/sparqlscript')
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/store')
    ->in(__DIR__.'/tests')
    ->in(__DIR__.'/')
    ->name('*.php')
;

$config = new Config();
$config
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'phpdoc_summary' => false,
    ])
;

return $config;
