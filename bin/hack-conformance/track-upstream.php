<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Tracks the Hack (facebook/hhvm) test directories that are relevant to Psalm's
 * type-variable feature, so upstream changes surface for review.
 *
 * It does NOT auto-translate Hack tests into Psalm tests: Hack's surface syntax
 * (native generics, `<_>`, `vec`/`dict`/`nothing`, `==>`) and builtins don't map
 * 1:1 to PHP + Psalm docblocks, and the tests that would map are scattered
 * through a 7500-file corpus rather than a clean directory. Instead this hashes
 * the tracked directories and reports which files were added/removed/changed
 * since the pinned commit — a signal to hand-translate or re-verify (with
 * fixtures/ + run.php as the oracle).
 *
 * Usage:
 *   php bin/hack-conformance/track-upstream.php            # report drift
 *   php bin/hack-conformance/track-upstream.php --write     # accept current state
 *
 * Requires: git. Clones a blobless sparse checkout into ./.hack-cache (gitignored).
 */

declare(strict_types=1);

$root = __DIR__;
$cache = "$root/.hack-cache";
$hashesFile = "$root/upstream-hashes.json";
$manifest = json_decode((string) file_get_contents("$root/manifest.json"), true);
$repo = $manifest['hack_repo'];
$paths = $manifest['tracked_paths'];
$write = in_array('--write', $argv, true);

function run(string $cmd, ?string $cwd = null): array
{
    $full = ($cwd !== null ? 'cd ' . escapeshellarg($cwd) . ' && ' : '') . $cmd;
    exec($full . ' 2>&1', $out, $rc);
    return [$rc, implode("\n", $out)];
}

// Blobless sparse checkout of just the tracked paths, at origin HEAD.
if (!is_dir("$cache/.git")) {
    @mkdir($cache, 0777, true);
    run('git init -q', $cache);
    run('git remote add origin ' . escapeshellarg($repo), $cache);
    run('git config core.sparseCheckout true', $cache);
}
file_put_contents(
    "$cache/.git/info/sparse-checkout",
    implode("\n", array_map(static fn(string $p): string => "$p/", $paths)) . "\n",
);
fwrite(STDERR, "Fetching tracked Hack test paths (blobless, shallow) ...\n");
[$rc] = run('git fetch --depth 1 --filter=blob:none origin master', $cache);
if ($rc !== 0) {
    fwrite(STDERR, "git fetch failed\n");
    exit(2);
}
run('git checkout -q -f FETCH_HEAD', $cache);
[, $head] = run('git rev-parse FETCH_HEAD', $cache);
$head = trim($head);

// Hash every tracked file.
$current = [];
foreach ($paths as $p) {
    $dir = "$cache/$p";
    if (!is_dir($dir)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if ($file->isFile()) {
            $rel = substr($file->getPathname(), strlen("$cache/"));
            $current[$rel] = hash_file('sha256', $file->getPathname());
        }
    }
}
ksort($current);

$previous = is_file($hashesFile)
    ? (json_decode((string) file_get_contents($hashesFile), true) ?: [])
    : ['commit' => null, 'files' => []];
$prevFiles = $previous['files'] ?? [];

$added = array_diff_key($current, $prevFiles);
$removed = array_diff_key($prevFiles, $current);
$changed = [];
foreach ($current as $f => $h) {
    if (isset($prevFiles[$f]) && $prevFiles[$f] !== $h) {
        $changed[$f] = true;
    }
}

printf("Pinned commit : %s\n", $manifest['hack_commit']);
printf("Fetched HEAD  : %s\n", $head);
printf("Tracked files : %d\n\n", count($current));
printf("  added:   %d\n  removed: %d\n  changed: %d\n", count($added), count($removed), count($changed));

foreach (['added' => $added, 'removed' => $removed, 'changed' => $changed] as $label => $set) {
    foreach (array_keys($set) as $f) {
        printf("    [%s] %s\n", $label, $f);
    }
}

if ($write) {
    file_put_contents(
        $hashesFile,
        json_encode(['commit' => $head, 'files' => $current], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
    );
    // Keep manifest.json's recorded commit in step with the accepted snapshot.
    $manifest['hack_commit'] = $head;
    file_put_contents(
        "$root/manifest.json",
        json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
    );
    printf("\nAccepted current state (commit %s) into upstream-hashes.json and manifest.json\n", $head);
    exit(0);
}

$drift = count($added) + count($removed) + count($changed);
if ($drift > 0) {
    printf("\n%d change(s) upstream. Review, hand-translate/verify as needed, then rerun with --write.\n", $drift);
}
exit($drift > 0 ? 1 : 0);
