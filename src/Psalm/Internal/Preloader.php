<?php

declare(strict_types=1);

namespace Psalm\Internal;

use Psalm\Progress\Phase;
use Psalm\Progress\Progress;

use function class_exists;

use function count;

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
        $progress?->expand(count(PreloaderList::CLASSES)+1);

        foreach (PreloaderList::CLASSES as $class) {
            $progress?->taskDone(0);
            class_exists($class);
        }

        $progress?->finish();
        self::$preloaded = true;
    }
}
