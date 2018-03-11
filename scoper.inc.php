<?php

use Isolated\Symfony\Component\Finder\Finder;

return [
    'finders' => [
        Finder::create()->files()->exclude(['Psalm/Stubs'])->in('src'),
        Finder::create()->files()->in('assets'),
        Finder::create()
            ->files()
            ->ignoreVCS(true)
            ->notName('/LICENSE|.*\\.md|.*\\.dist|Makefile|composer\\.json|composer\\.lock/')
            ->exclude([
                'doc',
                'test',
                'test_old',
                'tests',
                'Tests',
                'vendor-bin',
            ])
            ->in('vendor'),
        Finder::create()->append([
            'composer.json',
            'composer.lock',
            'config.xsd',
            'psalm'
        ]),
    ],
    'whitelist' => [

    ],
    'patchers' => [
        function ($filePath, $prefix, $contents) {
            //
            // PHP-Parser patch
            //
            if ($filePath === realpath(__DIR__ . '/vendor/nikic/php-parser/lib/PhpParser/NodeAbstract.php')) {
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
            if ($filePath === realpath(__DIR__ . '/src/Psalm/Config.php')) {
                return str_replace(
                    $prefix . '\Composer\Autoload\ClassLoader',
                    'Composer\Autoload\ClassLoader',
                    $contents
                );
            }

            return $contents;
        },
        function ($filePath, $prefix, $contents) {
            if ($filePath === realpath(__DIR__ . '/src/Psalm/PropertyMap.php')) {
                return str_replace(
                    [$prefix . '\\\\', $prefix . '\\'],
                    '',
                    $contents
                );
            }

            return $contents;
        },
    ],
];
