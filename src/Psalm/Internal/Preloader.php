<?php

declare(strict_types=1);

namespace Psalm\Internal;

use Psalm\Progress\Phase;
use Psalm\Progress\Progress;

use function class_exists;

/** @internal */
final class Preloader
{
    private static bool $preloaded = false;
    public static function preload(?Progress $progress = null, bool $hasJit = false): void
    {
        if (self::$preloaded) {
            return;
        }

        $progress?->startPhase($hasJit ? Phase::JIT_COMPILATION : Phase::PRELOADING);

        foreach (PreloaderList::CLASSES as $class) {
            class_exists($class);
        }

        $progress?->finish();
        self::$preloaded = true;
    }
}
