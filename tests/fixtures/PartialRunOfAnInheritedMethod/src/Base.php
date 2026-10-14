<?php

namespace Acme\SampleProject;

abstract class Base
{
    public function render(string $text): string
    {
        // Other is named only here: a run that doesn't deep-scan this file doesn't scan it
        if ($this instanceof Other) {
            return $this->label();
        }

        return $text;
    }
}
