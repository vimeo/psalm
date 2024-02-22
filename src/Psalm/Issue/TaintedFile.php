<?php

namespace Psalm\Issue;

final class TaintedFile extends TaintedInput
{
    public const SHORTCODE = 255;
    public const MESSAGE = 'Detected tainted file handling';
}
