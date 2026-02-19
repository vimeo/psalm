<?php
namespace Acme\SampleProject;

/** @psalm-pure */
function foo(string $_s): int
{
    return 'bar';
}

/** @psalm-pure */
function bar(string $s) : string {
    return $s;
}

/** @psalm-pure */
function baz(string $s) : string {
    return $s;
}

/** @psalm-pure */
function bat(string $s) : string {
    return $s;
}

/** @psalm-pure */
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
