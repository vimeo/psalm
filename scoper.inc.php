<?php

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
            if ($filePath === 'src/Psalm/Config.php') {
                return str_replace(
                    $prefix . '\Composer\Autoload\ClassLoader',
                    'Composer\Autoload\ClassLoader',
                    $contents
                );
            }

            return $contents;
        },
        function ($filePath, $prefix, $contents) {
            if (strpos($filePath, 'src/Psalm') === 0) {
                return str_replace(
                    [' \\Psalm\\', ' \\PhpParser\\'],
                    [' \\' . $prefix . '\\Psalm\\', ' \\' . $prefix . '\\PhpParser\\'],
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
            if ($filePath === 'src/Psalm/Internal/Analyzer/Statements/Expression/Call/MethodCallAnalyzer.php') {
                return str_replace(
                    'case \'Psalm\\\\',
                    'case \'' . $prefix . '\\\\Psalm\\\\',
                    $contents
                );
            }

            return $contents;
        },
        function ($filePath, $prefix, $contents) {
            if ($filePath === 'src/Psalm/Type.php') {
                return str_replace(
                    'get_class($type) === \'Psalm\\\\',
                    'get_class($type) === \'' . $prefix . '\\\\Psalm\\\\',
                    $contents
                );
            }

            return $contents;
        },
        function ($filePath, $prefix, $contents) {
            if ($filePath === 'src/psalm.php') {
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
                $prefix . '\Psalm\Plugin\\',
                'Psalm\Plugin\\',
                $contents
            );
            return $ret;
        },
    ],
    'whitelist' => [
        \Composer\Autoload\ClassLoader::class,
        'Psalm\Plugin\*',
    ],
    'files-whitelist' => [
        'src/Psalm/Internal/PropertyMap.php',
        'src/Psalm/Internal/CallMap.php',
        'src/Psalm/Internal/Stubs/CoreGenericFunctions.php',
        'src/Psalm/Internal/Stubs/CoreGenericClasses.php',
    ],
];
