<?php
namespace Acme\SampleProject;

function foo(string $s): int
{
    return 'bar';
}

function bar(string $s) : string {
    return $s;
}

function baz(string $s) : string {
    return $s;
}

function bat(string $s) : string {
    return $s;
}

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
