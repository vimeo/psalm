<?php

namespace Psalm\Issue;

final class TaintedCookie extends TaintedInput
{
    public const SHORTCODE = 257;
    public const MESSAGE = 'Detected tainted cookie';
}
