<?php

namespace Acme\SampleProject;

/** @psalm-taint-source input */
function input(): string
{
    return '';
}

echo (new Child())->render(input());
