# Security Analysis

Psalm tracks untrusted input as it moves through your code — through assignments, function calls, arrays, and
properties — and reports whenever it reaches a dangerous operation. Depending on where the input lands, that prevents
cross-site scripting (XSS), SQL injection, command injection, path traversal, and more.

Input such as `$_GET['id']`, `$_POST['email']` or `$_COOKIE['token']` is a **source**. A **sink** is a place it must
not reach unescaped, such as `<div id="section_<?= $id ?>">` or
`$pdo->exec("select * from users where name='" . $name . "'")`.

Taint analysis runs by default alongside type checking.

**On this page:**

- [Reading a report](#reading-a-report) — understand a taint trace and fix your first issue
- [Fixing reports](#fixing-reports) — escaping, type and regex validation, and custom escaping functions
- [Reports through shared code](#reports-through-shared-code) — isolate unrelated calls and objects with `@psalm-taint-specialize`
- [Command-line code](#command-line-code) — exempt CLI output from HTML taint reports
- [Dealing with remaining reports](#dealing-with-remaining-reports) — suppressions, ignored files, and baselines
- [What Psalm can't see](#what-psalm-cant-see) — custom sources/sinks, taint flow, unescaping, and graph debugging
- [Built-in sources and sinks](#built-in-sources-and-sinks) — reference tables for every source and taint kind
- [Security issues in reports](#security-issues-in-reports) — CWE IDs, SARIF, GitHub code scanning, and plugin API

## Reading a report

Consider a small script that echoes a query parameter inside HTML:

```php
<?php // src/greet.php
function greeting(string $name): string {
    return "<h1>Hello $name</h1>";
}

$name = $_GET['name'] ?? '';
if (is_string($name)) {
    echo greeting($name);
}
```

Running `vendor/bin/psalm` reports:

```
SECURITY: TaintedHtml - src/greet.php:8:5 - Detected tainted HTML (CWE-79) (see https://psalm.dev/245)
  $_GET - src/greet.php:6:9
$name = $_GET['name'] ?? '';

  $_GET['name'] - src/greet.php:6:9
$name = $_GET['name'] ?? '';

  coalesce - src/greet.php:6:9
$name = $_GET['name'] ?? '';

  $name - src/greet.php:6:1
$name = $_GET['name'] ?? '';

  $name - src/greet.php:6:1
$name = $_GET['name'] ?? '';

  call to greeting - src/greet.php:8:19
    echo greeting($name);

  greeting#1 - src/greet.php:2:19
function greeting(string $name): string {

  $name - src/greet.php:2:26
function greeting(string $name): string {

  string-cast - src/greet.php:3:23
    return "<h1>Hello $name</h1>";

  concat - src/greet.php:3:23
    return "<h1>Hello $name</h1>";

  greeting - src/greet.php:2:34
function greeting(string $name): string {

  call to echo - src/greet.php:8:10
    echo greeting($name);
```

Psalm reports the issue at the **sink** — the dangerous operation (`echo` on line 8). Below the header, the **trace**
walks step by step from the **source** (`$_GET`) to that sink:

| Trace step | Meaning |
|---|---|
| `$_GET['name']`, `$name` | An array element or variable |
| `call to greeting` | An argument passed to `greeting()` |
| `greeting#1` | The first parameter of `greeting()` |
| `greeting` | The return value of `greeting()` |
| `coalesce`, `concat`, `string-cast` | An operation the value passes through |

You can break the flow by escaping or validating the input at any step along the trace. Escaping `$name` right where it
enters HTML fixes the issue:

```php
<?php // src/greet.php
function greeting(string $name): string {
    return '<h1>Hello ' . htmlspecialchars($name) . '</h1>';
}
```

Psalm actually reports the original script twice — once as `TaintedHtml` and once as `TaintedTextWithQuotes`. That is
because `echo` is a sink for both HTML tags and quotes, and each taint kind gets its own issue. `htmlspecialchars()`
clears both.

How the trace is displayed depends on the output format:

- **`console` (default):** prints the full trace in the terminal.
- **`compact` and `github`:** print the sink location only.
- **JSON:** includes the trace in the `taint_trace` field.
- **[SARIF](#viewing-results-in-a-user-interface):** includes the trace as a navigable code flow for GitHub code
  scanning and IDE SARIF viewers.

## Fixing reports

### Escape for the sink

Whenever possible, avoid interpolating user input into structured strings at all — bind values in prepared SQL queries
or pass arguments directly instead of building a shell command. When you must embed input in output, escape it for that
specific context:

```php
<?php
function show(PDO $pdo): void {
    $q = $_GET['q'] ?? '';
    if (!is_string($q)) {
        return;
    }

    echo '<p>' . htmlspecialchars($q) . '</p>'; // no issue
    $pdo->query("SELECT * FROM items WHERE name = '$q'"); // TaintedSql
    $pdo->prepare('SELECT * FROM items WHERE name = ?')->execute([$q]); // no issue
    shell_exec('grep ' . escapeshellarg($q) . ' items.txt'); // no issue
}
```

An escaping function only protects the position it was written for, and Psalm checks where an escaped value lands:

```php
<?php
function show(mysqli $mysqli): void {
    $id = (string) ($_GET['id'] ?? '');
    $mysqli->query("SELECT * FROM users WHERE id = '" . $mysqli->real_escape_string($id) . "'"); // no issue
    $mysqli->query('SELECT * FROM users WHERE id = ' . $mysqli->real_escape_string($id)); // TaintedSql: `1 OR 1=1` needs no quotes

    $url = (string) ($_GET['url'] ?? '');
    echo '<img alt="' . htmlspecialchars($url, ENT_QUOTES) . '">'; // no issue
    echo '<img src=' . htmlspecialchars($url, ENT_QUOTES) . '>'; // TaintedHtmlAttribute: `x onerror=alert(1)` adds an attribute
    echo '<a href="' . htmlspecialchars($url, ENT_QUOTES) . '">'; // TaintedUrlScheme: `javascript:alert(1)` runs on click
    echo '<a href="/search?q=' . urlencode($url) . '">'; // no issue
}

// Try them with: /?id=1+OR+1%3D1&url=javascript%3Aalert%281%29%2F%2F+onerror%3Dalert%281%29
```

Psalm sees where a value lands from the text before it in the same expression: a concatenation, an interpolated string
or a `sprintf()` format. In a template, it can't see the HTML before `<?= htmlspecialchars($x) ?>`, so put attribute
values in quotes there. When it can't see the quotes around an SQL-escaped value, it trusts the escape. To allow a URL
in a link, check its scheme first ([TaintedUrlScheme](../running_psalm/issues/TaintedUrlScheme.md) shows how).

Prefer parameterized queries for SQL and commands, and a context-aware template engine for HTML. You can also use
[`literal-string`](../annotating_code/type_syntax/scalar_types.md#literal-string) to enforce that queries and commands
come only from developer-written code.

### Validate the type

A value can only carry taints that its type is capable of holding:

| Type | Taints it can carry |
|---|---|
| `int`, `float` | `sleep` only — never HTML, SQL, paths, etc. |
| `bool`, `null`, `literal-string` | None |
| `string` | All user-input taints except `nosql` (until decoded into an array/object by `json_decode()`, `unserialize()`, `parse_str()`, ...) |
| `array<K, V>` | Whatever its keys `K` and values `V` can carry (`list<int>` carries no HTML; `array<string, int>` can carry anything in its keys) |
| Union (`A\|B`) | Whatever any member type can carry |

Psalm automatically strips disallowed taints on casts, assignments, calls, and returns. It relies on both inferred types
and PHP-enforced native parameter and return types (including on taint sources):

```php
<?php

/** @psalm-taint-source input */
function getParam(string $name): string {
    return (string) ($_GET[$name] ?? '');
}

function getId(string $name): int {
    return (int) getParam($name);
}

function show(MongoDB\Collection $collection): void {
    $collection->find(['name' => getParam('name')]); // no issue: a string can't inject query operators
    $collection->find(['name' => json_decode(getParam('name'), true)]); // TaintedNosql
    echo '<b>' . getId('id') . '</b>'; // no issue: an int can't carry HTML

    $direction = getParam('direction');
    if ($direction === 'asc' || $direction === 'desc') {
        echo $direction; // no issue: narrowed to literal 'asc'|'desc'
    }
}
```

#### Validating strings with `preg_match()`

When a string matches a regular expression anchored at both ends, Psalm knows it can only contain the characters the
pattern allows and strips any taints that require other characters.

Psalm understands literal characters, character classes, `.`, `\d`, `\w`, `\s`, alternation groups, and quantifiers,
when `preg_match()` is used directly as a condition or compared to `1`:

```php
<?php

function search(DOMXPath $xpath): void {
    $sort = getParam('sort');
    if (preg_match('/^(asc|desc|name)$/', $sort)) {
        echo '<a href="?sort=' . $sort . '">'; // no issue: no quotes or angle brackets
    }

    $id = getParam('id');
    if (preg_match('/^[a-z0-9_.-]+$/iD', $id)) {
        $xpath->query("//user[@id='" . $id . "']"); // no issue: no quotes
        shell_exec('grep -r ' . $id . ' /var/log'); // TaintedShell: a leading '-' injects a flag
    }

    $name = getParam('name');
    if (preg_match('/^[a-z0-9 ]+$/i', $name)) {
        $xpath->query('//user[@id=' . $name . ']'); // TaintedXpath: spaces allow `1 or 1`
    }

    if (preg_match('/[a-z]+/', $id)) {
        echo $id; // TaintedHtml: pattern is not anchored
    }
}
```

> **Tip:** In PCRE, `$` matches before a trailing newline as well as the end of the string — and a newline can split a
> shell command. Add the `D` modifier or end the pattern with `\z` to forbid trailing newlines.

To keep a URL from reaching another server, check the host `parse_url()` extracts against a list of hosts. Psalm
trusts the check as long as the variable holding the URL isn't assigned again:

```php
<?php

$url = getParam('next');
$host = (string) parse_url($url, PHP_URL_HOST);
if (preg_match('/^(api\.example\.com|cdn\.example\.com)$/', $host)) {
    fsockopen($url, 80);           // no issue
    file_get_contents($url);       // TaintedFile: compress.zlib://api.example.com/etc/passwd is a local file
    header('Location: ' . $url);   // TaintedRedirect: a browser goes to evil.example for http://evil.example\@api.example.com/
}

if (preg_match('/^api.example.com$/', $host)) {
    fsockopen($url, 80);           // TaintedSSRF: `.` matches apixexample.com
}
```

#### Allowlist helper functions

If a helper validates input against a list of allowed values, annotate it with `@psalm-assert-if-true` and those
values. After the check succeeds, Psalm knows the value is one of those literals, so it carries no user input:

```php
<?php

/**
 * @psalm-assert-if-true 'london'|'paris'|'tokyo' $city
 */
function isKnownCity(string $city): bool {
    return in_array($city, ['london', 'paris', 'tokyo'], true);
}

$city = getParam('city');
if (isKnownCity($city)) {
    echo '<p>' . $city . '</p>'; // no issue
}
echo '<p>' . $city . '</p>'; // TaintedHtml
```

### Your own escaping functions

Use `@psalm-taint-escape <kind>` to tell Psalm that a function removes a taint kind from what it returns:

```php
<?php

/**
 * @psalm-taint-escape html
 * @psalm-taint-escape has_quotes
 */
function stripMarkup(string $str): string {
    return str_replace(['<', '>', '"', "'"], '', $str);
}

echo stripMarkup((string) $_GET['text']); // no issue
echo (string) $_GET['text']; // TaintedHtml
```

Use a conditional type when escaping depends on an argument:

```php
<?php
/**
 * @psalm-taint-escape ($escape is true ? 'html'|'has_quotes' : null)
 */
function processVar(string $str, bool $escape = true): string {
    if ($escape) {
        $str = htmlspecialchars($str, ENT_QUOTES);
    }
    return $str;
}

echo processVar($_GET['text'], false); // TaintedHtml
echo processVar($_GET['text'], true); // no issue
```

Where you can, accept and store user input as plain text fields rather than blocks of HTML, so that it can always be
escaped with `htmlspecialchars()` when it is output. When user input itself must be HTML (for example from a rich-text
editor like [TinyMCE](https://www.tiny.cloud/)),
sanitize it with a dedicated library such as [HTML Purifier](http://htmlpurifier.org/docs) and annotate your wrapper:

```php
<?php

/**
 * @psalm-taint-escape html
 * @psalm-taint-escape has_quotes
 */
function sanitizeHTML(string $html): string {
    $purifier = new HTMLPurifier();
    return $purifier->purify($html);
}
```

**Note on `json_encode()`:** Psalm automatically strips `html` when the flags are statically known to include both
`JSON_HEX_TAG` and `JSON_HEX_AMP`. It never strips `has_quotes`, because JSON strings are wrapped in `"` which can break
out of a double-quoted HTML attribute. If your helper only outputs JSON inside `<script>` tags — or passes
`JSON_HEX_APOS` for a single-quoted attribute — add `@psalm-taint-escape has_quotes` to the helper.

## Reports through shared code

By default, Psalm tracks a function's parameters and return value once across all of its call sites. That means tainted
input passed to one call can appear in the return value of an unrelated safe call:

```php
<?php

function takesInput(string $s): string {
    error_log('Got input');
    return $s;
}

echo htmlentities(takesInput($_GET['name']));
echo takesInput('hello'); // TaintedHtml (false positive from the call above)
```

### Specializing functions

Add `@psalm-taint-specialize` so Psalm tracks each call site independently — each call's return value only reflects the
arguments passed to *that* call:

```php
<?php

/**
 * @psalm-taint-specialize
 */
function takesInput(string $s): string {
    error_log('Got input');
    return $s;
}

echo htmlentities(takesInput($_GET['name']));
echo takesInput('hello'); // no issue
echo takesInput($_GET['name']); // TaintedHtml
```

If a specialized function passes its input to a sink (directly or through another call), Psalm reports the sink once for
each tainted call site:

```php
<?php

/**
 * @psalm-taint-specialize
 */
function run(string $command): void {
    exec($command); // TaintedShell, reported twice (once for each call below)
}

run($_GET['first']);
run($_GET['second']);
```

**Pure functions are specialized automatically**, because their return value depends only on their arguments (the idea
behind [immutability in Psalm](https://psalm.dev/articles/immutability-and-beyond)). This covers functions annotated
[`@psalm-pure`](../annotating_code/purity_model.md):

```php
<?php

/**
 * @psalm-pure
 */
function takesInput(string $s): string {
    return $s;
}

echo htmlentities(takesInput($_GET['name']));
echo takesInput('hello'); // no issue
```

It also covers functions Psalm infers to be pure, as the first `takesInput()` would be without `error_log()`. Because a
subclass could override a method with an impure
implementation, Psalm only infers purity for functions, `private` methods, `final` methods, and methods of `final`
classes — annotate other methods explicitly.

### Specializing classes

Object properties have the same sharing behavior: by default, a property's taints are shared across every instance of
the class:

```php
<?php

class User {
    public function __construct(public string $name) {}
}

/**
 * @psalm-taint-specialize
 */
function echoUserName(User $user): void {
    echo $user->name; // TaintedHtml (from $user2 below)
}

$user1 = new User('Keith');
$user2 = new User($_GET['name']);

echoUserName($user1);
```

Adding `@psalm-taint-specialize` (or `@psalm-immutable`) to the class tells Psalm to track each object's taints
separately:

```php
<?php

/**
 * @psalm-taint-specialize
 */
class User {
    public function __construct(public string $name) {}

    public function withName(string $name): static {
        $new = clone $this;
        $new->name = $name;
        return $new;
    }
}

/**
 * @psalm-taint-specialize
 */
function echoUserName(User $user): void {
    echo $user->name; // no issue
}

$user1 = new User('Keith');
$user2 = new User($_GET['name']);

echoUserName($user1);
```

Each object's taints follow the variables that hold it. If code changed the object through one variable, the other
variables wouldn't see the new taints, so Psalm forbids changing the object after construction:

- its properties are treated as readonly and cannot be reassigned outside the constructor (except on a fresh `clone`);
- its methods cannot mutate `$this`;
- its methods cannot write other objects, static properties, or global variables either, since another instance could
  read that data back.

To update a specialized object, return a modified copy as `withName()` does above. Subclasses inherit specialization and
must also carry the annotation, or Psalm reports
[ImmutableDependency](../running_psalm/issues/ImmutableDependency.md).

`@psalm-immutable` enforces the same rules, so an immutable class is specialized as well:

```php
<?php

/**
 * @psalm-immutable
 */
class User {
    public function __construct(public string $name) {}
}

/**
 * @psalm-taint-specialize
 */
function echoUserName(User $user): void {
    echo $user->name; // no issue
}

$user1 = new User('Keith');
$user2 = new User($_GET['name']);

echoUserName($user1);
```

## Command-line code

Output from a CLI script goes to a terminal or a log file, not to an HTML page. Psalm skips HTML and quote taint reports
(`TaintedHtml` and `TaintedTextWithQuotes`) for printing statements inside code it knows only runs from the command
line:

- a function, method, or class annotated `@psalm-cli-only`;
- code guarded by a `PHP_SAPI` or `php_sapi_name()` check (including closures defined inside it).

This applies to `echo`, `print`, `printf()`, `vprintf()`, `var_dump()`, `debug_zval_dump()`, and calls to `print_r()` or
`var_export()` that print directly instead of returning a string:

```php
<?php

/**
 * @psalm-cli-only
 */
final class ImportCommand
{
    public function run(): void
    {
        $file = $_SERVER['argv'][1] ?? '';
        echo 'Importing ' . $file . "\n"; // no issue
    }
}

if (PHP_SAPI !== 'cli') {
    exit(1);
}

(new ImportCommand())->run();
echo $_SERVER['argv'][1] ?? ''; // no issue
```

You can also move the SAPI check into a helper function using a
[SAPI assertion](../annotating_code/adding_assertions.md#asserting-the-sapi) — `@psalm-assert 'cli' PHP_SAPI` or
`@psalm-assert-if-true 'cli' PHP_SAPI` — so callers are treated as command-line code automatically.

Keep three rules in mind:

- Calling `@psalm-cli-only` code from outside command-line code reports
  [CliOnlyCall](../running_psalm/issues/CliOnlyCall.md).
- Only HTML/quote printing is exempted: printing secrets is still reported, and values remain tainted for every other
  sink (SQL, shell, files, etc.).
- Helper functions called from CLI code are still analyzed normally — annotate them `@psalm-cli-only` if they only run
  on the CLI.

### Classes only instantiated from the command line

Mark a `final` class `@psalm-cli-only-instances` when its constructor and `__unserialize()` (or `__wakeup()`) reject
non-CLI execution. Holding an instance then proves the code is running on the CLI, so no extra SAPI guard is needed at
call sites.

Psalm treats both of the following as command-line code automatically:

- non-static methods of the class (except `__construct()`, `__wakeup()`, and `__unserialize()`, which run before the
  guard completes);
- any function, method, or closure with a required native parameter typed as that class (alone or in an intersection).
  The parameter cannot be nullable, in a union, optional (with a default), or variadic, and docblock-only types do not
  qualify because PHP does not enforce them at runtime.

```php
<?php

/**
 * @psalm-cli-only-instances
 */
final class ConsoleRequest
{
    public function __construct(public string $path)
    {
        if (PHP_SAPI !== 'cli') {
            throw new LogicException('Run it from the command line');
        }
    }

    public function __unserialize(array $data): void
    {
        if (PHP_SAPI !== 'cli') {
            throw new LogicException('Run it from the command line');
        }
    }
}

function import(ConsoleRequest $request): void
{
    echo 'Importing ' . (string) ($_GET['file'] ?? '') . "\n"; // no issue
}
```

If the class does not guard construction and unserialization properly, Psalm reports
[CliOnlyInstancesUnguarded](../running_psalm/issues/CliOnlyInstancesUnguarded.md), which also documents the exact
checks and caveats.

## Dealing with remaining reports

### Suppressing

Taint issues are reported at the **sink**, not at the call site that passes the tainted value, so suppressing a specific
issue name (like `TaintedHeader`) on the caller has no effect. Instead:

- put `@psalm-suppress TaintedInput` on the statement or function to stop taint tracking there; or
- suppress an issue type project-wide with `<issueHandlers>` in `psalm.xml`.

```php
<?php

/** @psalm-taint-sink header $value */
function sendHeader(string $value): void {} // TaintedHeader (from a() below)

function a(): void {
    /** @psalm-suppress TaintedHeader */
    sendHeader($_GET['h']); // NOT suppressed — issue is reported at sendHeader()
}

function b(): void {
    /** @psalm-suppress TaintedInput */
    sendHeader($_GET['h']); // no issue
}
```

```xml
<issueHandlers>
    <TaintedHeader errorLevel="suppress"/> <!-- suppressed everywhere -->
</issueHandlers>
```

### Ignoring files

To ignore taint paths that pass through specific files or directories (such as tests), add them under `<taintAnalysis>`
in `psalm.xml`:

```xml
<taintAnalysis>
    <ignoreFiles>
        <directory name="tests"/>
    </ignoreFiles>
</taintAnalysis>
```

### Using a baseline

[`--set-baseline`](../running_psalm/dealing_with_code_issues.md#using-a-baseline-file) records existing security issues
alongside type issues. Psalm then only reports newly introduced issues while you work through the backlog.

## What Psalm can't see

Psalm can only report paths it knows about. Extend its coverage when input crosses boundaries it can't inspect:

- **Input from frameworks, queues, or user-writable database fields:** define
  [custom taint sources](custom_taint_sources.md).
- **Project-specific dangerous APIs:** define [custom taint sinks](custom_taint_sinks.md).
- **Flows through interfaces, stubs, vendor code, caches, or ORM models:** annotate [taint flow](taint_flow.md).
- **Functions that undo escaping:** annotate them with `@psalm-taint-unescape <kind>`.

```php
<?php

/**
 * @psalm-taint-unescape html
 */
function decode(string $str): string
{
    return str_replace(
        ['&lt;', '&gt;', '&quot;', '&apos;'],
        ['<', '>', '"', "'"],
        $str
    );
}

$safe = htmlspecialchars($_GET['text']);
echo decode($safe); // TaintedHtml
```

### Debugging the taint graph

If an expected security issue is missing, dump the taint graph Psalm builds (see
[the taint graph](taint_flow.md#the-taint-graph)) in Graphviz DOT format and render it to inspect where the path breaks:

```bash
psalm --taint-analysis --dump-taint-graph=taints.dot
dot -Tsvg -o taints.svg taints.dot
```

## Built-in sources and sinks

### Where user input comes from

Psalm treats the following built-in inputs as taint sources:

| Category | Built-in sources |
|---|---|
| **Request superglobals** | `$_GET`, `$_POST`, `$_COOKIE`, `$_REQUEST` |
| **Environment** | `getenv()` and `$_ENV` (chosen by whoever starts the process) |
| **Client-controlled `$_SERVER` entries** | Request headers (`HTTP_*`), URI fields (`REQUEST_URI`, `QUERY_STRING`, `PATH_INFO`, `PHP_SELF`, `argv`, ...), `REQUEST_METHOD`, `CONTENT_TYPE`, HTTP auth credentials, redirected equivalents (`REDIRECT_*`), and any read using a non-literal key (`$_SERVER[$key]`) |
| **Uploaded files** | Each file's `name`, `type`, and `full_path` in `$_FILES` |
| **PSR-7 HTTP messages** | Return values of `Psr\Http\Message\ServerRequestInterface`, `ResponseInterface`, `UriInterface`, `StreamInterface`, `UploadedFileInterface`, and implementations (covering framework requests and HTTP client responses such as Guzzle) |
| **Input streams** | `STDIN`; `fopen()`, `file_get_contents()`, and `file()` on literal `php://input` or `php://stdin`; and stream readers (`fgets()`, `fread()`, `stream_get_contents()`, ...) reading those handles |
| **Server responses** | `fopen()`, `file_get_contents()`, and `file()` on a URL starting with a literal `http://`, `https://`, `ftp://` or `ftps://` (as `file_get_contents('https://api.example.com/items/' . $id)`), and stream readers reading those handles |
| **Databases** | What the program stored in a database, where it reads it back (see [Databases](taint_flow.md#databases)) |
| **Network I/O** | `socket_read()`, `stream_socket_recvfrom()`, `curl_exec()`, `curl_multi_getcontent()`; by-reference data from `socket_recv()`, `socket_recvfrom()`, `socket_recvmsg()`; and streams opened with `fsockopen()`, `pfsockopen()`, `stream_socket_client()`, `stream_socket_accept()`, `socket_export_stream()` |
| **DNS lookups** | `dns_get_record()`, `gethostbyaddr()`, and by-reference hosts from `getmxrr()` (controlled by remote nameservers) |
| **CLI options** | `getopt()` |
| **Caught exceptions** | `catch` variables, `getMessage()`, `getTraceAsString()`, and string conversion (Psalm does not trace across `throw`/`catch`) |

For example, with PSR-7 requests Psalm distinguishes client-controlled server parameters from server-configured ones:

```php
<?php
use Psr\Http\Message\ServerRequestInterface;

function handle(ServerRequestInterface $request): void {
    echo $request->getUri()->getQuery(); // TaintedHtml
    echo (string) ($request->getServerParams()['HTTP_REFERER'] ?? ''); // TaintedHtml
    echo (string) ($request->getServerParams()['DOCUMENT_ROOT'] ?? ''); // no issue: set by the server
}
```

Psalm also ships [library stubs](../running_psalm/configuration.md#loadlibrarystubs) (Guzzle, amphp, the MongoDB
library, ...) and auto-enables the [Symfony and Laravel plugins](../running_psalm/configuration.md#loadlibraryplugins)
to declare their sources and sinks. To add your own, see [custom taint sources](custom_taint_sources.md).

### Where user input must not go

Each sink only reports the taint kinds that are dangerous in its context: `echo` reports HTML and quotes, `PDO::query()`
reports SQL, and `shell_exec()` reports shell commands. Each taint kind is defined in
[Psalm\Type\TaintKind](https://github.com/vimeo/psalm/blob/master/src/Psalm/Type/TaintKind.php) and maps to its own
issue:

| Kind | Issue | Protects against |
|---|---|---|
| `html` | [TaintedHtml](../running_psalm/issues/TaintedHtml.md) | HTML tags (XSS) |
| `has_quotes` | [TaintedTextWithQuotes](../running_psalm/issues/TaintedTextWithQuotes.md) | Quotes breaking out of an HTML attribute |
| `html_attribute` | [TaintedHtmlAttribute](../running_psalm/issues/TaintedHtmlAttribute.md) | Whitespace ending an unquoted attribute value, which HTML escaping keeps |
| `url_scheme` | [TaintedUrlScheme](../running_psalm/issues/TaintedUrlScheme.md) | `javascript:` URLs in links, forms and frames, which HTML escaping keeps |
| `cookie` | [TaintedCookie](../running_psalm/issues/TaintedCookie.md) | Attacker-controlled HTTP cookies |
| `header` | [TaintedHeader](../running_psalm/issues/TaintedHeader.md) | HTTP header injection |
| `redirect` | [TaintedRedirect](../running_psalm/issues/TaintedRedirect.md) | Choosing where a redirect sends the client (open redirect) |
| `sql` | [TaintedSql](../running_psalm/issues/TaintedSql.md) | SQL injection: both `sql_in_quotes` (breaking out of quotes) and `sql_unquoted` (SQL syntax outside of them, which `real_escape_string()` keeps) |
| `nosql` | [TaintedNosql](../running_psalm/issues/TaintedNosql.md) | NoSQL query structure (MongoDB filters, updates, pipelines; arrays and objects only) |
| `ldap` | [TaintedLdap](../running_psalm/issues/TaintedLdap.md) | LDAP DN or filter injection |
| `xpath` | [TaintedXpath](../running_psalm/issues/TaintedXpath.md) | XPath expression injection |
| `shell` | [TaintedShell](../running_psalm/issues/TaintedShell.md) | Shell command injection |
| `eval` | [TaintedEval](../running_psalm/issues/TaintedEval.md) | Arbitrary PHP code execution |
| `callable` | [TaintedCallable](../running_psalm/issues/TaintedCallable.md) | Calling an attacker-chosen function |
| `include` | [TaintedInclude](../running_psalm/issues/TaintedInclude.md) | Including an attacker-chosen file |
| `unserialize` | [TaintedUnserialize](../running_psalm/issues/TaintedUnserialize.md) | Unsafe deserialization |
| `extract` | [TaintedExtract](../running_psalm/issues/TaintedExtract.md) | Overwriting local variables via `extract()` |
| `file` | [TaintedFile](../running_psalm/issues/TaintedFile.md) | Path traversal / arbitrary file access |
| `ssrf` | [TaintedSSRF](../running_psalm/issues/TaintedSSRF.md) | Controlling the target host of an outbound request (SSRF) |
| `url_component` | [TaintedUrlComponent](../running_psalm/issues/TaintedUrlComponent.md) | URL syntax (`/`, `?`, `&`, `#`, ...) in a URL path, query, or fragment |
| `url_path` | [TaintedUrlPath](../running_psalm/issues/TaintedUrlPath.md) | `.` or `..` segments in a URL path (including URL-encoded) |
| `sleep` | [TaintedSleep](../running_psalm/issues/TaintedSleep.md) | Controlling wait durations (`sleep()`, cURL timeouts, ...; carried by numbers too) |
| `llm_prompt` | [TaintedLlmPrompt](../running_psalm/issues/TaintedLlmPrompt.md) | Prompt injection into an LLM |
| `user_secret` | [TaintedUserSecret](../running_psalm/issues/TaintedUserSecret.md) | Leaking a user-supplied secret |
| `system_secret` | [TaintedSystemSecret](../running_psalm/issues/TaintedSystemSecret.md) | Leaking a system secret |

Every kind except `user_secret` and `system_secret` is user input, and built-in sources carry all of them. In
annotations, `input` (or its alias `tainted`) matches all user-input kinds, and `input_except_sleep` matches all of them
except `sleep`.

Custom taint kinds are just names. A source and a sink naming the same new kind connect without any setup, and the
sink reports [TaintedCustom](../running_psalm/issues/TaintedCustom.md):

```php
<?php

/** @psalm-taint-source tracking_id */
function getTrackingId(): string { return ''; }

/** @psalm-taint-sink tracking_id $id */
function logTracking(string $id): void {} // TaintedCustom (from the call below)

logTracking(getTrackingId());
```

To treat a custom kind as user input (given by the built-in sources and included in `input`), declare it
[in `psalm.xml` or in a plugin](custom_taint_sources.md#custom-taints-declared-in-the-configuration). See
[custom taint sinks](custom_taint_sinks.md) for more on `@psalm-taint-sink`.

**Response streams:** Writing to a literal `php://output` stream — or `php://stdout`, which writes to the HTTP response
under the CGI SAPI — acts as an output sink just like `echo`. This covers:

- `file_put_contents()` with that path;
- `fwrite()`, `fputs()`, `fputcsv()`, `fprintf()`, `vfprintf()`, `stream_copy_to_stream()`, and
  `SplFileObject::fwrite()` / `fputcsv()`, on a handle opened on that path in the same function.

**Redirects:** For a `Location` header, `header()` reports `redirect`, `url_component` and `url_path` instead of
`header`. If the header value starts with a literal URL or path (`https://example.com/`, `/account`), only
`url_component` and `url_path` are reported:

```php
<?php
header('Location: ' . $_GET['next']); // TaintedRedirect
header('Location: https://example.com/' . $_GET['page']); // TaintedUrlComponent
header('Location: /account?tab=' . urlencode((string) $_GET['tab'])); // no issue
```

**Sessions:** A value stored in `$_SESSION` flows to every read of the same key, in any function. As with
[keyed stores](taint_flow.md#keyed-stores), Psalm checks each read against how it's used:

```php
<?php
function login(): void {
    $_SESSION['name'] = htmlspecialchars((string) $_GET['name'], ENT_QUOTES);
}

function show(): void {
    echo (string) $_SESSION['name']; // no issue
    exec('greet ' . (string) $_SESSION['name']); // TaintedShell
    echo (string) $_SESSION['page']; // no issue
}
```

## Security issues in reports

Security issues carry dedicated severity labels and [CWE](https://cwe.mitre.org/) identifiers (also listed on each issue
documentation page):

| Output format | Severity | Extra security metadata |
|---|---|---|
| **Human-readable** (`console`, `compact`, `table`, `by-issue-level`, `phpstorm`) | `SECURITY` (or `INFO` if `errorLevel="info"`) | `(CWE-NNN)` appended to the message |
| **JSON** | Usual severity | `is_security`, `cwe_ids`, and `taint_trace` fields |
| **XML** | Usual severity | `is_security` field |
| **SARIF** | Usual severity | `external/cwe/cwe-NNN` rule tags and code-flow traces |
| **Other machine-readable** (`checkstyle`, ...) | Usual severity | — |

```
SECURITY: TaintedSql - src/index.php:2:1 - Detected tainted SQL (CWE-89)
```

### Viewing results in a user interface

Psalm can export [SARIF](http://docs.oasis-open.org/sarif/sarif/v2.0/csprd01/sarif-v2.0-csprd01.html), a standard format
supported by IDEs and code-scanning platforms, with the full trace attached to each result. Pass `--report` a filename
ending in `.sarif`:

```bash
psalm --report=results.sarif
```

Each result's `ruleId` is the Psalm issue type (for example, `TaintedSql`).

To display findings in the **Security** tab of your repository with
[GitHub code scanning](https://docs.github.com/en/code-security/code-scanning/introduction-to-code-scanning/about-code-scanning),
use any of:

- the [Psalm Security Scan](https://github.com/psalm/psalm-github-security-scan) action;
- the [Psalm GitHub Action](https://github.com/psalm/psalm-github-actions) with `report_file: results.sarif` (see
  [GitHub Actions](../running_psalm/installation.md#github-actions));
- [uploading the SARIF file](https://docs.github.com/en/code-security/code-scanning/integrating-with-code-scanning/uploading-a-sarif-file-to-github)
  directly in your workflow.

### For plugin authors

A plugin can mark its own issues as security issues by implementing `Psalm\Issue\SecurityIssue`, and declare their CWE
weaknesses (most specific first) via a `CWE_IDS` constant:

```php
public const CWE_IDS = [95, 94];
```
