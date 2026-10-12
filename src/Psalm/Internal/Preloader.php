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

    /**
     * The phase isn't finished here: the scan that follows starts the next one, so that the Preload row is followed
     * by the other rows rather than by the blank line that ends the progress output
     */
    public static function preload(?Progress $progress = null, bool $hasJit = false): void
    {
        if (self::$preloaded) {
            return;
        }

        $progress?->startPhase($hasJit ? Phase::JIT_COMPILATION : Phase::PRELOADING);
        $progress?->expand(count(PreloaderList::CLASSES));

        foreach (PreloaderList::CLASSES as $class) {
            $progress?->taskDone(0);
            class_exists($class);
        }

        self::$preloaded = true;
    }
}
