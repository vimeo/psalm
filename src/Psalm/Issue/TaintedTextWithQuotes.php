<?php

namespace Psalm\Issue;

final class TaintedTextWithQuotes extends TaintedInput
{
    public const SHORTCODE = 274;
    public const MESSAGE = 'Detected tainted text with possible quotes';
}
