<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

$finder = (new Finder())
    ->files()
    ->name('/\.php$/')
    ->contains('<?php')
    ->in(['bin', 'src', 'tests']);

return (new PhpCsFixer\Config())
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    // https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/master/doc/rules/index.rst
    ->setRules([
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],
        'no_empty_phpdoc' => true,
        'phpdoc_trim' => true,
    ]);
