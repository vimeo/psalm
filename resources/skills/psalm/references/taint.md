# SECURITY findings (taint analysis)

Docs: `docs/security_analysis/`. Psalm follows values from taint sources (`$_GET`, `$_POST`, `$_COOKIE`,
request objects with plugins, …) to sinks (SQL queries, `echo`, shell, file paths, `include`, headers, …)
and reports a `Tainted*` issue at the sink: `TaintedSql`, `TaintedHtml`, `TaintedShell`, `TaintedFile`, …
Taint analysis runs in the same run as type checking; `runTaintAnalysis="false"` in `psalm.xml` turns it off.

## Reading a finding

`compact` shows only the sink. The path from source to sink comes with the console format; pass the files
on the path, or run the whole project:

```bash
vendor/bin/psalm --no-progress --output-format=console public/index.php src/UserRepository.php
```

```
SECURITY: TaintedHtml - src/a.php:4:1 - Detected tainted HTML
  $_GET - src/a.php:2:16
  $n - src/a.php:2:1
  concat - src/a.php:4:6
  call to echo - src/a.php:4:6
```

In a JSON report the same path is in `taint_trace`. A path that crosses files is only found when all of them
are analysed: run the whole project before calling a finding fixed.

## Fixing

Fix the flow at the sink, for the kind of sink:

| Issue | Fix |
|---|---|
| `TaintedSql` | prepared statement with bound parameters (`$pdo->prepare('… WHERE name = ?')`, `execute([$name])`) |
| `TaintedHtml`, `TaintedTextWithQuotes` | `htmlspecialchars($value)` where the value is put into HTML |
| `TaintedShell` | `escapeshellarg()` per argument, or pass an array command to `proc_open()` |
| `TaintedFile`, `TaintedInclude` | resolve against a fixed directory and check the result, or map input to a whitelist |
| `TaintedHeader`, `TaintedCookie` | validate the value against the expected format |
| `TaintedSSRF` | check the URL's host against an allow-list |

A value can only carry the taints its type can hold: `(int) $_GET['id']` carries no SQL or HTML, and after
`in_array($sort, ['name', 'date'], true)` the value is one of the literals and carries nothing.

## A project-specific sanitiser or sink

```php
/** @psalm-taint-escape html */
$clean = $purifier->purify($html);

/** @psalm-taint-sink sql $query */
function runRaw(string $query): void {}

/** @psalm-taint-source input */
function requestParam(string $name): string {}
```

`@psalm-taint-escape` on a statement removes the taint from what it assigns. Use it only where the code
really escapes for that sink. `TaintedTextWithQuotes` is the separate `has_quotes` taint: a sanitiser for
HTML attributes escapes both, `@psalm-taint-escape html` and `@psalm-taint-escape has_quotes`.

## False positives

- Output of command-line code reported as `TaintedHtml`: `@psalm-cli-only` on the function, method or class.
- One helper mixes the taints of all its callers: `@psalm-taint-specialize` on it tracks each call
  separately.
- Paths through test code: `<taintAnalysis><ignoreFiles><directory name="tests"/></ignoreFiles></taintAnalysis>`.
- Details and the remaining cases: `docs/security_analysis/avoiding_false_positives.md`.

Missing findings (a sink you expected is not reported): check that the extension is enabled (`ext-pdo`,
`ext-mysqli` in `composer.json`, see [config.md](config.md)), then `--dump-taint-graph=taints.dot`.
