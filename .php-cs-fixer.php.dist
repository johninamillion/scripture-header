<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use johninamillion\ScriptureHeader\ScriptureHeaderFixer;

if (!class_exists(ScriptureHeaderFixer::class)) {
    require_once __DIR__ . '/src/ScriptureHeaderFixer.php';
}

$finder = Finder::create()
    ->in(__DIR__ . '/src')
    ->name('*.php')
    ->exclude(['tests', 'vendor']);

return (new Config())
    ->setRules([
        '@PSR12' => true,
        'MillionVisions/scripture_header' => true,
    ])
    ->setFinder($finder)
    ->registerCustomFixers([
        'MillionVisions/scripture_header' => new ScriptureHeaderFixer()
    ]);