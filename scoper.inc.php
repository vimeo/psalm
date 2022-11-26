<?php

use Composer\Autoload\ClassLoader;

return [
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
    ],
    'exclude-constants' => [
        'PSALM_VERSION',
        'PHP_PARSER_VERSION',
    ],
    'exclude-files' => [
        'src/spl_object_id.php',
        'vendor/symfony/polyfill-php80/Php80.php',
        'vendor/symfony/polyfill-php80/PhpToken.php',
        'vendor/symfony/polyfill-php80/Resources/stubs/Attribute.php',
        'vendor/symfony/polyfill-php80/Resources/stubs/PhpToken.php',
        'vendor/symfony/polyfill-php80/Resources/stubs/Stringable.php',
    ],
];
