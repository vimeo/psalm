<?php

namespace Psalm\Issue;

final class TaintedShell extends TaintedInput
{
    public const SHORTCODE = 246;
    public const MESSAGE = 'Detected tainted shell code';
}
