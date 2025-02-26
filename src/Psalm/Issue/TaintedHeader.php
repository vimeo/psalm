<?php

namespace Psalm\Issue;

final class TaintedHeader extends TaintedInput
{
    public const SHORTCODE = 256;
    public const MESSAGE = 'Detected tainted header';
}
