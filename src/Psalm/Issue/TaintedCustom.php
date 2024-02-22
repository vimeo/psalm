<?php

namespace Psalm\Issue;

final class TaintedCustom extends TaintedInput
{
    public const SHORTCODE = 249;
    public const MESSAGE = 'Detected tainted %s';
}
