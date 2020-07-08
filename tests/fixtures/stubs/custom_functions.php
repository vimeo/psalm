<?php

function barBar(string $a) : string
{
}

function variadic(string ...$bar)
{
}

function variadic2() : array
{
    return func_get_args();
}
