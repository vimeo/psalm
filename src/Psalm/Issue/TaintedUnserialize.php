<?php

namespace Psalm\Issue;

final class TaintedUnserialize extends TaintedInput
{
    public const SHORTCODE = 250;
    public const MESSAGE = 'Detected tainted code passed to unserialize or similar';
}
