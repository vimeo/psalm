<?php

namespace Psalm\Issue;

final class TaintedEval extends TaintedInput
{
    public const SHORTCODE = 252;
    public const MESSAGE = 'Detected tainted code passed to eval or similar';
}
