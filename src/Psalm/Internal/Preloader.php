<?php

declare(strict_types=1);

namespace Psalm\Internal;

use Psalm\Progress\Progress;

use function class_exists;

use const PHP_EOL;

/** @internal */
final class Preloader
{
    private static bool $preloaded = false;
    public static function preload(?Progress $progress = null, bool $hasJit = false): void
    {
        if (self::$preloaded) {
            return;
        }

        if ($hasJit) {
            $progress?->write("JIT compilation in progress... ");
        }
        foreach (PreloaderList::CLASSES as $class) {
            class_exists($class);
        }
        if ($hasJit) {
            $progress?->write("Done.".PHP_EOL.PHP_EOL);
        }
        self::$preloaded = true;
    }
}
