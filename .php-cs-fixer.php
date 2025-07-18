<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use johninamillion\ScriptureHeader\ScriptureHeaderFixer;

$finder = Finder::create()
    ->in(__DIR__ . '/src')
    ->name('*.php')
    ->exclude(['tests', 'vendor']);

return (new Config())
    ->setRules([
        '@PSR12' => true,
        'MillionVisions/scripture_header' => [
            'author' => 'MillionVisions',
        ],
    ])
    ->setFinder($finder)
    ->registerCustomFixers([
        'MillionVisions/scripture_header' => new ScriptureHeaderFixer()
    ]);