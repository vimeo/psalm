<?php

declare(strict_types=1);

namespace Psalm\Tests\fixtures\Issue;

use Psalm\Issue\TaintedInput;

class TaintedTestingAnything extends TaintedInput
{
    // changing SHORTCODE not useful for custom taint issues
    // (e.g. `205` would lead to `https://psalm.dev/205` in the error output)
    public const SHORTCODE = TaintedInput::SHORTCODE;
    public const MESSAGE = 'Detected anything used in some example test case';
}
