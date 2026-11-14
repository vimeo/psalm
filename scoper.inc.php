<?php

use Composer\Autoload\ClassLoader;

// Every file in the directories matching $pattern: exclude-files only takes file paths
$filesIn = static function (string $pattern): array {
    $files = [];
    foreach (glob($pattern, GLOB_BRACE | GLOB_ONLYDIR) ?: [] as $dir) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $file) {
            $files[] = $file->getPathname();
        }
    }

    return $files;
};

// Names the phar ships under the prefix (lowercase, single backslashes): every non-dev package's
// psr-4/psr-0 namespaces plus its classmapped classes (e.g. JsonMapper, sebastian/diff, Composer\InstalledVersions).
// $shipped is a set of those names, $partial a set of their parent namespaces.
[$shipped, $partial] = (static function (): array {
    $composerDir = __DIR__ . '/vendor/composer';
    $installed = json_decode(file_get_contents($composerDir . '/installed.json'), true, 512, JSON_THROW_ON_ERROR);
    $dev = array_flip($installed['dev-package-names'] ?? []);
    $names = [];
    // Composer's own runtime classes (InstalledVersions) ship too
    $paths = [realpath($composerDir) . '/'];

    foreach ($installed['packages'] as $package) {
        if (isset($dev[$package['name']])) {
            continue;
        }

        if (isset($package['install-path']) && ($path = realpath($composerDir . '/' . $package['install-path']))) {
            $paths[] = $path . '/';
        }

        foreach (['psr-4', 'psr-0'] as $type) {
            foreach (array_keys($package['autoload'][$type] ?? []) as $namespace) {
                $names[] = $namespace;
            }
        }
    }

    foreach (require $composerDir . '/autoload_classmap.php' as $class => $file) {
        $file = (string) realpath($file);
        foreach ($paths as $path) {
            if (str_starts_with($file, $path)) {
                $names[] = $class;
                break;
            }
        }
    }

    $shipped = [];
    $partial = [];
    foreach ($names as $name) {
        $name = strtolower(trim(str_replace('\\\\', '\\', $name), '\\'));
        if ($name === '') {
            continue;
        }

        $shipped[$name] = true;
        for ($pos = strpos($name, '\\'); $pos !== false; $pos = strpos($name, '\\', $pos + 1)) {
            $partial[substr($name, 0, $pos)] = true;
        }
    }

    return [$shipped, $partial];
})();

// Whether $name (lowercase, single backslashes) is a shipped name, a namespace of one, or lives inside one.
$isShipped = static function (string $name) use ($shipped, $partial): bool {
    if (isset($shipped[$name]) || isset($partial[$name])) {
        return true;
    }

    for ($pos = strpos($name, '\\'); $pos !== false; $pos = strpos($name, '\\', $pos + 1)) {
        if (isset($shipped[substr($name, 0, $pos)])) {
            return true;
        }
    }

    return false;
};

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
        // Psalm's code and the bundled plugins also name the analysed code's classes and functions (Decimal\Decimal,
        // lz4_compress(), PHPUnit\Framework\TestCase), and scoper prefixes those too. Strip the prefix again from names
        // the phar doesn't ship, and from everything inside string literals, which name analysed code
        // (bin/ci/build-phar.sh checks the result).
        function (string $filePath, string $prefix, string $contents) use ($isShipped): string {
            $plugin = str_starts_with($filePath, 'vendor/psalm/plugin-');
            if (!$plugin && !str_starts_with($filePath, 'src/Psalm/')) {
                return $contents;
            }

            // Matches `PsalmPhar\Foo\Bar` and the escaped `PsalmPhar\\Foo\\Bar`; group 1 is the separator.
            $name = '/(?<![A-Za-z0-9_])' . preg_quote($prefix, '/')
                . '(\\\\{1,2})([A-Za-z_][A-Za-z0-9_]*(?:\1[A-Za-z_][A-Za-z0-9_]*)*)/';

            $result = '';
            foreach (token_get_all($contents) as $token) {
                if (!is_array($token)) {
                    $result .= $token;
                    continue;
                }

                [$id, $text] = $token;
                $inString = $id === T_CONSTANT_ENCAPSED_STRING || $id === T_ENCAPSED_AND_WHITESPACE;

                $result .= preg_replace_callback(
                    $name,
                    static function (array $m) use ($inString, $isShipped, $plugin): string {
                        $unprefixed = strtolower(str_replace($m[1], '\\', $m[2]));
                        $keep = !$inString
                            && $isShipped($unprefixed)
                            // Symfony names in the bundled plugins are the project's: plugin-symfony analyses Symfony
                            // code, and plugin-laravel works on objects of the booted application (e.g. the
                            // InputArgument instances Laravel's console Parser returns)
                            && !($plugin && str_starts_with($unprefixed, 'symfony\\'));

                        return $keep ? $m[0] : $m[2];
                    },
                    $text
                );
            }

            return $result;
        },
    ],
    'exclude-classes' => [
        ClassLoader::class,
        Stringable::class,
    ],
    'exclude-namespaces' => [
        'Psalm',
    ],
    'exclude-functions' => [
        '/array_/',
        // Functions the phar calls but doesn't define. Prefixed, the calls fail, and the global aliases exposed for
        // them replace the project's own functions, since the phar's autoloader runs first.
        // Extensions php-scoper has no stubs for:
        '/^lz4_/',
        '/^uv_/',
        // Laravel helpers plugin-laravel calls on the analysed application:
        'class_basename',
        'class_uses_recursive',
        'now',
        'today',
    ],
    'exclude-constants' => [
        'PSALM_VERSION',
        'PHP_PARSER_VERSION',
    ],
    'exclude-files' => [
        'src/spl_object_id.php',
        '/vendor\/symfony\/polyfill-php8\d/',
        // Scripts of the dependencies (CI, tooling) never run inside the phar. Scoped, the functions they declare
        // would be exposed as global aliases to functions the phar never loads.
        ...$filesIn(__DIR__ . '/vendor/*/*/{bin,tools}'),
        // Stubs of the analysed libraries that bundled plugins register must keep the libraries' names.
        ...$filesIn(__DIR__ . '/vendor/psalm/plugin-*/stubs'),
    ],
];
