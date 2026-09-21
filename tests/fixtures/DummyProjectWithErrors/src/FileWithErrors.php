<?php
namespace Acme\SampleProject;

/**
 * @api
 * @psalm-pure
 */
function foo(string $_s): int
{
    return 'bar';
}

/**
 * @api
 * @psalm-pure
 */
function bar(string $s) : string {
    return $s;
}

/**
 * @api
 * @psalm-pure
 */
function baz(string $s) : string {
    return $s;
}

/**
 * @api
 * @psalm-pure
 */
function bat(string $s) : string {
    return $s;
}

/**
 * @api
 * @psalm-pure
 */
function bang(string $s) : string {
    return $s;
}

/** @api */
function boom(): void
{
    echo (string) ($GLOBALS['abc'] ?? 'z');
}

/** @api */
function booom(): void
{
    echo isset($_GET['abc']) && is_string($_GET['abc']) ? $_GET['abc'] : 'z';
}
