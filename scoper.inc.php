<?php

use Isolated\Symfony\Component\Finder\Finder;

$polyfillsBootstraps = array_map(
    static fn (SplFileInfo $fileInfo) => $fileInfo->getPathname(),
    iterator_to_array(
        Finder::create()
            ->files()
            ->in(__DIR__ . '/vendor/symfony/polyfill-*')
            ->name('bootstrap*.php'),
        false,
    ),
);
$polyfillsStubs = array_map(
    static fn (SplFileInfo $fileInfo) => $fileInfo->getPathname(),
    iterator_to_array(
        Finder::create()
            ->files()
            ->in(__DIR__ . '/vendor/symfony/polyfill-*/Resources/stubs')
            ->name('*.php'),
        false,
    ),
);

return [
    'patchers' => [
        function ($filePath, $prefix, $contents) {
            //
            // PHP-Parser patch
            //
            if ($filePath === 'vendor/nikic/php-parser/lib/PhpParser/NodeAbstract.php') {
                $length = 15 + strlen($prefix) + 1;

                return preg_replace(
                    '%strpos\((.+?)\) \+ 15%',
                    sprintf('strpos($1) + %d', $length),
                    $contents
                );
            }

            return $contents;
        },
        function ($filePath, $prefix, $contents) {
            return str_replace(
                '\\'.$prefix.'\Composer\Autoload\ClassLoader',
                '\Composer\Autoload\ClassLoader',
                $contents
            );
        },
        function ($filePath, $prefix, $contents) {
            if (strpos($filePath, 'src/Psalm') === 0) {
                return str_replace(
                    [' \\PhpParser\\'],
                    [' \\' . $prefix . '\\PhpParser\\'],
                    $contents
                );
            }

            return $contents;
        },
        function ($filePath, $prefix, $contents) {
            if (strpos($filePath, 'vendor/openlss') === 0) {
                return str_replace(
                    $prefix . '\\DomDocument',
                    'DomDocument',
                    $contents
                );
            }

            return $contents;
        },
        function ($filePath, $prefix, $contents) {
            if ($filePath === 'src/Psalm/Internal/Cli/Psalm.php') {
                return str_replace(
                    '\\' . $prefix . '\\PSALM_VERSION',
                    'PSALM_VERSION',
                    $contents
                );
            }

            return $contents;
        },
        function ($filePath, $prefix, $contents) {
            $ret = str_replace(
                $prefix . '\\Psalm\\',
                'Psalm\\',
                $contents
            );
            return $ret;
        },
    ],
    'exclude-namespaces' => [
        'Symfony\Polyfill',
        'Psalm',
    ],
    'exclude-constants' => [
        // Symfony global constants
        // TODO: switch to the following regex once regexes are supported here
        // https://github.com/humbug/php-scoper/issues/634
        '/^SYMFONY\_[\p{L}_]+$/',
        // Meanwhile:
        'SYMFONY_GRAPHEME_CLUSTER_RX',
        'PSALM_VERSION',
        'PHP_PARSER_VERSION',
    ],
    'exclude-files' => [
        'src/spl_object_id.php',
        ...$polyfillsBootstraps,
        ...$polyfillsStubs,
    ],
    'expose-classes' => [
        \Composer\Autoload\ClassLoader::class,
    ],
];
