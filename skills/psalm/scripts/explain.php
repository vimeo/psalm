<?php

declare(strict_types=1);

const USAGE = <<<'TXT'
Usage: php explain.php ISSUE

Prints the documentation of a Psalm issue: what it means, an example and how to fix it.
ISSUE is an issue type (PossiblyNullReference), a shortcode (78) or a link (https://psalm.dev/078).

TXT;

function fail(string $message): never
{
    fwrite(STDERR, rtrim($message) . "\n");
    exit(1);
}

/**
 * The Psalm package that the docs belong to: the one this script ships in, or the project's
 * vendor/vimeo/psalm when the skill was copied out of the package.
 */
function findPsalmRoot(): ?string
{
    $candidates = [dirname(__DIR__, 3)];
    $dir = getcwd();
    while (is_string($dir)) {
        $candidates[] = $dir . '/vendor/vimeo/psalm';
        $parent = dirname($dir);
        $dir = $parent === $dir ? null : $parent;
    }
    foreach ($candidates as $candidate) {
        if (is_dir($candidate . '/docs/running_psalm/issues')) {
            return $candidate;
        }
    }

    return null;
}

function typeForShortcode(string $root, int $shortcode): ?string
{
    $files = glob($root . '/src/Psalm/Issue/*.php');
    foreach ($files === false ? [] : $files as $file) {
        $code = (string) file_get_contents($file);
        if (preg_match('/const SHORTCODE = (\d+);/', $code, $m) && (int) $m[1] === $shortcode) {
            return basename($file, '.php');
        }
    }

    return null;
}

/** @var list<string> $argv */
$query = $argv[1] ?? '';
if ($query === '' || $query === '-h' || $query === '--help') {
    fwrite($query === '' ? STDERR : STDOUT, USAGE);
    exit($query === '' ? 1 : 0);
}

$root = findPsalmRoot();
if (preg_match('~^(?:https?://psalm\.dev/)?0*(\d+)$~', $query, $m)) {
    $type = $root === null ? null : typeForShortcode($root, (int) $m[1]);
    if ($type === null) {
        fail("Unknown shortcode $query. See https://psalm.dev/{$m[1]}");
    }
} else {
    $type = $query;
}

if (!preg_match('/^\w+$/', $type)) {
    fail("Not an issue type: $type");
}

$doc = $root === null ? null : $root . '/docs/running_psalm/issues/' . $type . '.md';
if ($doc === null || !is_file($doc)) {
    fail(
        ($root === null ? "Psalm's docs aren't installed (vendor/vimeo/psalm/docs). " : "No docs for $type. ")
        . "See https://psalm.dev/docs/running_psalm/issues/$type/",
    );
}

echo file_get_contents($doc);
