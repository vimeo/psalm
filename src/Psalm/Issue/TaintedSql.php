<?php

namespace Psalm\Issue;

final class TaintedSql extends TaintedInput
{
    public const SHORTCODE = 244;
    public const MESSAGE = 'Detected tainted SQL';
}
