<?php

namespace Psalm\Issue;

final class TaintedCallable extends TaintedInput
{
    public const SHORTCODE = 243;
    public const MESSAGE = 'Detected tainted text';
}
