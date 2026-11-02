---
name: psalm
description: Use when running Psalm on a PHP project, fixing Psalm ERROR or SECURITY (taint) issues, writing Psalm type annotations (generics, array shapes, assertions), editing psalm.xml, or checking whether a Psalm result is a bug.
---

# Psalm

Notes for the Psalm version this skill ships with. The full docs of the installed version are in
`vendor/vimeo/psalm/docs/` (online: https://psalm.dev/docs/). Paths like `scripts/…` and `references/…`
are relative to this skill's directory.

## Running

```bash
vendor/bin/psalm --no-progress --output-format=compact src/Foo.php src/Bar.php
```

- `compact` prints one line per issue: `ERROR src/Foo.php:12:5 IssueType: message`.
- Run on the files you changed while iterating. Finish with one run on the whole project: only a full run
  reports unused code (`UnusedClass`, `PossiblyUnusedMethod`, …) and taint paths that cross files.
- Exit code `0`: no issues. `2`: issues found. `1`: Psalm itself failed (bad config, crash): read the
  message, it says nothing about the code.
- Many issues: `--report=psalm.json` writes a JSON report next to the normal output, then
  `php scripts/summary.php psalm.json` prints counts per type and file; `--type=…`, `--file=…`,
  `--changed` narrow it down (see `--help`).
- `php scripts/explain.php IssueType` prints the issue's documentation: what it means and how to fix it.
- `SECURITY` lines are taint findings. Taint analysis runs in the same run as type checking. `compact` shows
  only the sink; for the source → sink path run the files involved with `--output-format=console`.
- After changing `psalm.xml`, stubs or `composer.json`, add `--no-cache` once.

## Fixing issues

Fix the place where the type comes from, not the line that reports it. When the cause is unclear, ask Psalm
for the inferred type (see [When stuck](#when-stuck)) before editing.

- **Outside data** (`json_decode`, `$_GET`, `unserialize`, DB rows, `file_get_contents`): narrow `mixed` with
  runtime checks (`is_array`, `is_string`, `isset`, `instanceof`) and throw on invalid input. Check array
  elements in place: after `isset($data['dsn']) && is_string($data['dsn'])`, `$data['dsn']` itself is a
  `string`, so no intermediate `mixed` variable is needed. `@var` is an unchecked cast for values whose type
  a library guarantees but Psalm can't see; outside data needs a check, and `@var mixed` only renames the
  problem.
- **`null` / `false` results** (`find()`, `strpos`, `file_get_contents`, `preg_replace`, `json_encode`): handle
  them explicitly — throw, return early or use a default.
- **`list<T>`** after `array_filter`, `unset` or `array_diff`: the keys have gaps, wrap in `array_values()`.
- **Missing or loose types**: add native types first, then a docblock for what PHP can't express:
  `list<User>`, `array{sku: string, qty: int}`, `@template T`, `@psalm-assert`. Syntax:
  [references/annotations.md](references/annotations.md).
- **Unused code** (`Unused*`, `PossiblyUnused*`): decide per symbol. `@psalm-api` on the class is for code
  with a caller outside the analysed code — a framework, router, container config or the users of a
  library — and that caller should be visible somewhere (a config file, a docblock, an interface). A
  method or property of application code with no such caller is dead code: delete it. When a deletion
  reaches a constructor parameter or a field filled from the database or config, name it in your summary
  so the user can keep it.
- **SECURITY**: fix the data flow — bound parameters for SQL, `htmlspecialchars()` for HTML,
  `escapeshellarg()` for shell. See [references/taint.md](references/taint.md).
- **Suppression is the user's call.** `@psalm-suppress`, `issueHandlers`, `errorLevel`,
  `runTaintAnalysis="false"` and baseline changes hide issues; propose one with the reason and wait for a yes
  unless the user already asked for it. Scope an agreed suppression to one statement:
  `/** @psalm-suppress IssueType reason */`.
- **Many mechanical issues** (`MissingReturnType`, `MissingParamType`, `UnusedVariable`, …): Psalter fixes
  them. See [references/fixing.md](references/fixing.md), which also covers baselines.

A fix is done when the full run exits `0` with the original `psalm.xml` and no new suppressions.

## When stuck

- Check a hypothesis about a type: `/** @psalm-check-type $x = User */` above the statement. It is always
  reported. To print the type, use `/** @psalm-trace $x */` and run that one file with `--show-info=true`.
  Remove both afterwards. More in [references/debugging.md](references/debugging.md): crashes, hangs,
  stale cache.
- A class, function or extension Psalm doesn't know: [references/config.md](references/config.md) (stubs,
  plugins, `ext-*`, `projectFiles` vs `extraFiles`).
- Psalm looks wrong: [references/bug-report.md](references/bug-report.md).
