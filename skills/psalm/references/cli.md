# Psalm CLI

Every option: `vendor/bin/psalm --help`.

Run Psalm on the PHP version CI uses: a Psalm installed in a tool directory (`tools/psalm`,
`bamarni/composer-bin-plugin`) has its own lock file and may need a newer PHP than the package.

## Output formats

| `--output-format=` | For |
|---|---|
| `compact` | iterating: one line per issue |
| `console` | snippets and the source → sink path of `SECURITY` findings |
| `count` | issue type → count, the size of a cleanup |
| `json-summary` | counts per type plus mixed / total expressions, as JSON |
| `by-issue-level` | sorted by the `errorLevel` from which each type is an error, for raising the level |
| `json` | every field of every issue, `taint_trace` included |
| `github`, `sarif`, `checkstyle`, `junit`, `codeclimate`, … | CI integrations |

`--report=FILE` writes a second report in the format of the file's extension (`.json`, `.sarif`,
`checkstyle.xml`, …) next to the output on stdout; `scripts/summary.php` reads the JSON one.

## Cache and speed

- Psalm caches results and re-analyses only the files that changed and the files that depend on them.
  `--no-diff` re-analyses every file, `--no-cache` runs without the cache, `--clear-cache` deletes it.
- Threads: one per CPU core by default, `--threads=N` to change it. Always one on Windows and without the
  `pcntl` extension, whatever the flag says.

## Seeing more

- `--show-info=true` adds the issues the configured `errorLevel` doesn't count as errors, and `Trace` output.
  Useful for one file or before raising the level; for a whole project it is mostly noise.
- `--stats` (with `--output-format=console`) prints the share of expressions with a known type per file:
  the files with the most `mixed` are where typing pays off first.
- `--php-version=8.3` analyses for another PHP version than the one in `composer.json`.

## Finding code

- `--find-references-to=App\Foo` or `--find-references-to='App\Foo::$bar'` lists where a class or a
  property is used.
- `--find-unused-psalm-suppress` reports suppressions that hide nothing.
- Unused code is reported by default (`findUnusedCode`); a run on single files skips it.
