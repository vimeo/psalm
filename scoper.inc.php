<?php

use Composer\Autoload\ClassLoader;

return [
    'prefix' => 'PsalmPhar',
    'patchers' => [
        function (string $filePath, string $prefix, string $contents): string {
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
        function (string $_filePath, string $prefix, string $contents): string {
            return str_replace(
                '\\' . $prefix . '\Composer\Autoload\ClassLoader',
                '\Composer\Autoload\ClassLoader',
                $contents
            );
        },
        function (string $filePath, string $prefix, string $contents): string {
            if (strpos($filePath, 'src/Psalm') === 0) {
                return str_replace(
                    [' \\PhpParser\\'],
                    [' \\' . $prefix . '\\PhpParser\\'],
                    $contents
                );
            }

            return $contents;
        },
        function (string $filePath, string $prefix, string $contents): string {
            if (strpos($filePath, 'vendor/openlss') === 0) {
                return str_replace(
                    $prefix . '\\DomDocument',
                    'DomDocument',
                    $contents
                );
            }

            return $contents;
        },
    ],
    'exclude-classes' => [
        ClassLoader::class,
        Stringable::class,
    ],
    'exclude-namespaces' => [
        'Psalm',
        // PHP's own since 8.6 (Io\IoException, Io\Poll\*, used by revolt/event-loop's IoPollDriver): php-scoper only
        // leaves alone the internal symbols it knows, and the phar is built on an older PHP. A regex: a plain name
        // excludes every namespace containing it (str_contains), 'Io' would exclude Amp\Serialization too
        '/^Io(\\\\|$)/i',
    ],
    'exclude-functions' => [
        '/array_/',
    ],
    'exclude-constants' => [
        'PSALM_VERSION',
        'PHP_PARSER_VERSION',
    ],
    'exclude-files' => [
        'src/spl_object_id.php',
        '/vendor\/symfony\/polyfill-php8\d/',
    ],
];
