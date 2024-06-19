<?php

namespace Psalm\Issue;

final class TaintedUserSecret extends TaintedInput
{
    public const SHORTCODE = 247;
    public const MESSAGE = 'Detected tainted user secret leaking';
}
