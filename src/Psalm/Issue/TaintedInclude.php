<?php

namespace Psalm\Issue;

final class TaintedInclude extends TaintedInput
{
    public const SHORTCODE = 251;
    public const MESSAGE = 'Detected tainted code passed to include or similar';
}
