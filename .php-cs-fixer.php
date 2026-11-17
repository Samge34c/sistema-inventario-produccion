<?php

// Formato PHP del equipo: PSR-12 (ESTANDARES.md, sección 4.3).

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude(['vendor', 'node_modules', 'assets', 'pruebas/version-anterior']);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
    ])
    ->setFinder($finder);
