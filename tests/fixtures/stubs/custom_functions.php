<?php

function barBar(string $a) : string
{
}

/**
 * @param string ...$bar
 */
function variadic()
{
}

function variadic2() : array
{
    return func_get_args();
}
