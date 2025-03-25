<?php

declare(strict_types=1);

use danog\ClassFinder\ClassFinder;

chdir(__DIR__.'/../../');

require 'vendor/autoload.php';

const EXCLUDE = [
    'vendor/amphp/parallel/src/Context/functions.php',
    'vendor/amphp/sync/src/functions.php',
    'vendor/amphp/socket/src/functions.php',
    'vendor/amphp/socket/src/Internal/functions.php',
    'vendor/amphp/socket/src/SocketAddress/functions.php',
    'vendor/amphp/serialization/src/functions.php',
    'vendor/amphp/parallel/src/Context/Internal/functions.php',
    'vendor/amphp/parallel/src/Ipc/functions.php',
    'vendor/amphp/parallel/src/Worker/functions.php',
    'vendor/amphp/dns/src/functions.php',
    'vendor/amphp/byte-stream/src/functions.php',
    'vendor/amphp/byte-stream/src/Internal/functions.php',
    'vendor/amphp/process/src/functions.php',
    'vendor/amphp/amp/src/functions.php',
    'vendor/amphp/amp/src/Future/functions.php',
    'vendor/amphp/amp/src/Internal/functions.php',
];

$excludes = [];
foreach (EXCLUDE as $f) {
    $excludes[$f] = file_get_contents($f);
    file_put_contents($f, '');
    clearstatcache(true, $f);
}

$classes = array_fill_keys(
    ClassFinder::getClassesInNamespace('PhpParser', ClassFinder::ALLOW_ALL|ClassFinder::RECURSIVE_MODE),
    true,
);
$classes += array_fill_keys(
    ClassFinder::getClassesInNamespace('Amp', ClassFinder::ALLOW_ALL|ClassFinder::RECURSIVE_MODE),
    true,
);

foreach ($excludes as $f => $content) {
    file_put_contents($f, $content);
}

foreach (new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(
        'src',
        FilesystemIterator::CURRENT_AS_PATHNAME|FilesystemIterator::SKIP_DOTS,
    ),
    RecursiveIteratorIterator::LEAVES_ONLY,
) as $f
) {
    if (str_ends_with($f, '.php')) {
        $f = str_replace(['/', '.php', 'src\\'], ['\\', '', ''], $f);
        $classes[$f] = true;
    }
}

foreach ($classes as $class => $_) {
    $class = new ReflectionClass($class);
    $class = file_get_contents($class->getFileName());
    if (preg_match_all('/^use (\S+);/m', $class, $matches)) {
        foreach ($matches[1] as $sub) {
            if (!class_exists($sub) || str_starts_with($sub, 'PHPUnit')) {
                continue;
            }
            $refl = new ReflectionClass($sub);
            if ($refl->isUserDefined()) {
                $classes[$sub] = true;
            }
        }
    }
}

$classes = array_keys($classes);
sort($classes);

$ff = "<?php

declare(strict_types=1);

namespace Psalm\Internal;

/** @internal */
final class PreloaderList {
    public const CLASSES = [
";

foreach ($classes as $f) {
    $ff .= '        \\'.$f."::class,\n";
}

$ff .= "
    ];
}
";

file_put_contents('src/Psalm/Internal/PreloaderList.php', $ff);
