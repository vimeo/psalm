<?php

declare(strict_types=1);

namespace Psalm\Progress;

enum Phase
{
    case SCAN;
    case ANALYSIS;
    case ALTERING;
    case TAINT_GRAPH_RESOLUTION;
}
