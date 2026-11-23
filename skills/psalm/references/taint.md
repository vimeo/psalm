# SECURITY findings (taint analysis)

Docs: `docs/security_analysis/`. Psalm follows values from taint sources (`$_GET`, `$_POST`, `$_COOKIE`,
request objects with plugins, …) to sinks (SQL queries, `echo`, shell, file paths, `include`, headers, …)
and reports a `Tainted*` issue at the sink: `TaintedSql`, `TaintedHtml`, `TaintedShell`, `TaintedFile`, …

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

In a JSON report the same path is in `taint_trace`.

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

A check Psalm can't follow still needs declaring. A path kept inside a directory:

```php
$path = realpath($this->directory . '/' . $name);
if ($path === false || !str_starts_with($path, $this->directory . DIRECTORY_SEPARATOR)) {
    return null;
}
/**
 * @psalm-taint-escape file
 * @psalm-taint-escape ssrf
 * @psalm-taint-escape url_path
 * @psalm-taint-escape url_component
 */
$safe = $path;
return file_get_contents($safe);
```

Name each kind the check removes and only those: a path resolved inside the directory is no longer a
user-chosen path, and `realpath()` never returns a URL. The kinds are listed in
`docs/security_analysis/index.md`; `input` names them all at once and fits only a function that really
removes every kind.

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

The other direction, and functions whose body Psalm can't follow:

```php
/** @psalm-taint-unescape html */
function decode(string $s): string { return html_entity_decode($s); }

/** @psalm-flow ($format, $args) -> return */
function fmt(string $format, string ...$args): string { … }

out(decode(htmlspecialchars($_GET['a']))); // TaintedHtml: decode() undoes the escaping
out(fmt('%s', htmlspecialchars($_GET['b']))); // safe
out(fmt('%s', $_GET['c']));                   // TaintedHtml
```

`@psalm-taint-unescape` adds a taint back to what the function returns. `@psalm-flow` declares which
parameters reach the return value, for functions implemented in an extension, through `eval`, or in code
Psalm doesn't analyse. More forms: `docs/security_analysis/taint_flow.md`.

## False positives

- Output of command-line code reported as `TaintedHtml`: `@psalm-cli-only` on the function, method or class.
- One helper mixes the taints of all its callers: `@psalm-taint-specialize` on it tracks each call
  separately.
- Paths through test code: `<taintAnalysis><ignoreFiles><directory name="tests"/></ignoreFiles></taintAnalysis>`.
- Details and the remaining cases: `docs/security_analysis/avoiding_false_positives.md`.

Missing findings (a sink you expected is not reported): check that the extension is enabled (`ext-pdo`,
`ext-mysqli` in `composer.json`, see [config.md](config.md)), then `--dump-taint-graph=taints.dot`.
