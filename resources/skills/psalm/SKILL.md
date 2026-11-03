---
name: psalm
description: Use when running Psalm, fixing its ERROR or SECURITY (taint) issues, writing Psalm type annotations (generics, array shapes, assertions), editing psalm.xml, or checking whether a Psalm result is a bug.
---

# Psalm

Notes for the Psalm version this skill ships with. The full docs of the installed version are in
`vendor/vimeo/psalm/docs/` (online: https://psalm.dev/docs/). Paths like `scripts/…` and `references/…`
are relative to this skill's directory.

## Running

```bash
vendor/bin/psalm --no-progress --output-format=compact src/Foo.php src/Bar.php
```

- Run on the files you changed while iterating. Finish with one run on the whole project: only a full run
  reports unused code (`UnusedClass`, `PossiblyUnusedMethod`, …) and taint paths that cross files.
- Exit code `0`: no issues. `2`: issues found. `1`: Psalm itself failed (bad config, crash): read the
  message, it says nothing about the code.
- Many issues: `--report=psalm.json` writes a JSON report next to the normal output, then
  `php scripts/summary.php psalm.json` prints counts per type and file; `--type=…`, `--file=…`,
  `--changed` narrow it down (see `--help`).
- `php scripts/explain.php IssueType` prints the issue's documentation: what it means and how to fix it.
- `SECURITY` lines are taint findings, reported in the same run as type errors. Reading their source → sink
  path: [references/taint.md](references/taint.md).
- After changing `psalm.xml`, stubs or `composer.json`, add `--no-cache` once.
- Other output formats (`count`, `json-summary`, `by-issue-level`), cache and threads, `--stats`,
  `--show-info`, `--find-references-to`: [references/cli.md](references/cli.md).

## Fixing issues

Fix the place where the type comes from, not the line that reports it. When the cause is unclear, ask Psalm
for the inferred type with `@psalm-check-type` before editing: [references/debugging.md](references/debugging.md).

- **Outside data** (`json_decode`, `$_GET`, `unserialize`, DB rows, `file_get_contents`): narrow `mixed` with
  runtime checks (`is_array`, `is_string`, `isset`, `instanceof`) and throw on invalid input. Check array
  elements in place: after `isset($data['dsn']) && is_string($data['dsn'])`, `$data['dsn']` itself is a
  `string`, so no intermediate variable is needed. `@var` is an unchecked cast, meant for values whose type
  a library guarantees but Psalm can't see; outside data gets a check.
- **`null` / `false` results** (`find()`, `strpos`, `file_get_contents`, `preg_replace`, `json_encode`): handle
  them explicitly — throw, return early or use a default.
- **`list<T>`** after `array_filter`, `unset` or `array_diff`: the keys have gaps, wrap in `array_values()`.
- **Missing or loose types**: add native types first, then a docblock for what PHP can't express:
  `list<User>`, `array{sku: string, qty: int}`, `@template T`, `@psalm-assert`. Syntax and which tags take
  the `@psalm-` prefix: [references/annotations.md](references/annotations.md).
- **Unused code** (`Unused*`, `PossiblyUnused*`): decide per symbol. `@api` on the class is for code
  with a caller outside the analysed code — a framework, router, container config or the users of a
  library — and that caller should be visible somewhere (a config file, a docblock, an interface). A
  method or property of application code with no such caller is dead code: delete it. When a deletion
  reaches a constructor parameter or a field filled from the database or config, name it in your summary
  so the user can keep it. `ClassMustBeFinal` is the same decision: `@api` on a class others may extend,
  such as a library's public classes; `final` otherwise.
- **SECURITY**: fix the data flow — bound parameters for SQL, `htmlspecialchars()` for HTML,
  `escapeshellarg()` for shell. Psalm can't follow every check: after one it can't see (a path resolved
  and kept inside an allowed directory, a project's own escaping function), declare it with
  `@psalm-taint-escape` for exactly the taint kinds the check removes. Output of command-line code:
  `@psalm-cli-only`. More sinks and sanitisers: [references/taint.md](references/taint.md).
- **Suppression is the user's call.** `@psalm-suppress`, `issueHandlers`, `errorLevel`,
  `runTaintAnalysis="false"` and baseline changes hide issues; propose one with the reason and wait for a yes
  unless the user already asked for it. Scope an agreed suppression to one statement:
  `/** @psalm-suppress IssueType reason */`.
- **Many mechanical issues** (`MissingReturnType`, `MissingParamType`, `UnusedVariable`, …): Psalter fixes
  them. See [references/fixing.md](references/fixing.md), which also covers baselines.

Done when a run on the whole project after your last edit exits `0`, and every change to `psalm.xml`, the
baseline or a `@psalm-suppress` in the diff is one the user agreed to.

## When stuck

- A wrong or `mixed` type you can't place, a crash or a hang: [references/debugging.md](references/debugging.md).
- A class, function or extension Psalm doesn't know (`UndefinedClass` for code that exists, `mixed` from an
  extension): code Psalm should know but not check goes in `<extraFiles>`; stubs, plugins and `ext-*` in
  [references/config.md](references/config.md).
- Psalm looks wrong: [references/bug-report.md](references/bug-report.md).
