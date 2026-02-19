<?php

declare(strict_types=1);

namespace Psalm\Progress;

/**
 * Note: this enum is not covered by the backwards compatibility promise,
 * when implementing progress make sure to always include a fallback based on the name of the case.
 */
enum Phase
{
    case SCAN;
    case ANALYSIS;
    case ALTERING;
    case TAINT_GRAPH_RESOLUTION;
    case JIT_COMPILATION;
    case PRELOADING;
    case MERGING_THREAD_RESULTS;
}
