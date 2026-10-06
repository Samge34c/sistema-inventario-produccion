<?php

declare(strict_types=1);

$buscador = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude(['vendor', 'node_modules']);

return (new PhpCsFixer\Config())
    ->setRules(['@PSR12' => true])
    ->setFinder($buscador);
