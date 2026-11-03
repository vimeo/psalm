# Debugging types and Psalm runs

## What type does Psalm infer?

```php
/** @psalm-check-type $user = User */
$user = $repo->find($id);       // CheckType: Checked variable $user = User does not match $user = User|null

/** @psalm-check-type-exact $ids = list<int> */
$ids = array_map('intval', $raw);

/** @psalm-trace $data */
$data = json_decode($json, true);  // Trace: $data: mixed
```

- `@psalm-check-type` applies to the next statement and is reported at every `errorLevel`. Use it to test a
  hypothesis, `-exact` to rule out subtypes.
- `@psalm-trace` prints the type, but `Trace` is an info-level issue: run the one file with
  `--show-info=true`.
- Delete both annotations when done.

Workflow for a wrong or `mixed` type: put a check on the reported variable, then move it upstream (the
assignment, the function's return, its parameters) until the type is right. The first place where it goes
wrong is where the fix belongs: a missing return type, a missing template, an unchecked decode.

## Psalm says something that contradicts the code

- Stale cache, also after `vendor/` or `<extraFiles>` changed: `--clear-cache` once.
- A class or function is unknown: [config.md](config.md) (autoloader, `<extraFiles>`, extensions, plugins).
- The behaviour depends on the PHP version: `--php-version=8.3` or `phpVersion` in `psalm.xml`.

## Psalm crashes or hangs

- Exit code `1` with a PHP error or "Psalm crashed": rerun with `--threads=1 --debug` to see the last file it
  was working on.
- To find the statement: `--threads=1 --debug-by-line <file>`.
- Memory: `--memory-limit=4G`.
- Reduce it to a small repro: [bug-report.md](bug-report.md).
