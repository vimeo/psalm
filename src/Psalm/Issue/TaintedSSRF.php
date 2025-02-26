<?php

namespace Psalm\Issue;

final class TaintedSSRF extends TaintedInput
{
    public const SHORTCODE = 253;
    public const MESSAGE = 'Detected tainted network request';
}
