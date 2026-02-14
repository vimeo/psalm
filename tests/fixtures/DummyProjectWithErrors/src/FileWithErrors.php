<?php
namespace Acme\SampleProject;

/** @psalm-mutation-free */
function foo(string $_s): int
{
    return 'bar';
}

/** @psalm-external-mutation-free */
function bar(string $s) : string {
    return $s;
}

/** @psalm-mutation-free */
function baz(string $s) : string {
    return $s;
}

/** @psalm-mutation-free */
function bat(string $s) : string {
    return $s;
}

/** @psalm-mutation-free */
function bang(string $s) : string {
    return $s;
}

function boom(): void
{
    echo (string) ($GLOBALS['abc'] ?? 'z');
}

function booom(): void
{
    echo isset($_GET['abc']) && is_string($_GET['abc']) ? $_GET['abc'] : 'z';
}
