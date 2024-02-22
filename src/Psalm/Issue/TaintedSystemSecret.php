<?php

namespace Psalm\Issue;

final class TaintedSystemSecret extends TaintedInput
{
    public const SHORTCODE = 248;
    public const MESSAGE = 'Detected tainted system secret leaking';
}
