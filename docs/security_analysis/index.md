# Security Analysis

Psalm follows user input through your code (assignments, calls, arrays, properties) and reports it when it reaches a
place where it is dangerous. Printed into an HTML page, it allows cross-site scripting; put into an SQL query, SQL
injection; put into a shell command, command injection.

This page starts with reading a first report, and ends with a clean run.

Taint analysis runs by default, in the same run as type checking. To turn it off, set
[`runTaintAnalysis="false"`](../running_psalm/configuration.md#runtaintanalysis) in `psalm.xml`. The `--taint-analysis`
flag then still runs it for a single run.

## Reading a report

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

`vendor/bin/psalm` reports:

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

The issue points at the *sink*: the place the input must not reach, here `echo`. Below it, the trace follows the input
from its *source*, `$_GET`, to the sink. Each step shows its location and its line of code:

| Step | What it is |
|---|---|
| `$_GET['name']`, `$name` | an array element, a variable |
| `call to greeting` | the argument passed to `greeting()` |
| `greeting#1` | the first parameter of `greeting()` |
| `greeting` | the value `greeting()` returns |
| `coalesce`, `concat`, `string-cast` | an operation the input goes through |

You can escape or validate the input at any of these steps. The next sections show how.

Psalm reports the same path a second time, as `TaintedTextWithQuotes`. `echo` is a sink for both HTML and quotes, and
each kind gets its own issue. `htmlspecialchars()` fixes both.

Only the default `console` output prints the trace:

- `compact` and `github` print the sink alone;
- the JSON report puts the trace in a `taint_trace` field;
- a [SARIF report](#viewing-results-in-a-user-interface) includes it as a code flow, which you can step through in
  GitHub code scanning and in IDE SARIF viewers.

## Where user input comes from

Psalm treats these as taint sources:

- **Request superglobals:** `$_GET`, `$_POST`, `$_COOKIE` and `$_REQUEST`.
- **The `$_SERVER` entries the client sends:** the request headers (`HTTP_*`), the URI and the entries taken from it
  (`REQUEST_URI`, `QUERY_STRING`, `PATH_INFO`, `PHP_SELF`, `argv`, ...), `REQUEST_METHOD`, `CONTENT_TYPE`, the HTTP
  authentication credentials, and the same entries of a redirected request (`REDIRECT_*`). An entry read with a key
  that isn't a literal is a source too.
- **Uploaded files:** the name, type and full path of each file in `$_FILES`.
- **PSR-7 messages:** everything returned by the methods of `Psr\Http\Message\ServerRequestInterface`,
  `ResponseInterface`, `UriInterface`, `StreamInterface` and `UploadedFileInterface`, and of the classes implementing
  them. This covers the request your middleware and controllers receive, and the response an HTTP client like Guzzle
  returns.
- **Input streams:** `fopen()`, `file_get_contents()` and `file()` on a literal `php://input` or `php://stdin` path,
  the stream functions (`fgets()`, `fread()`, `stream_get_contents()`, ...) reading such a handle, and the `STDIN`
  constant.
- **The network:** `socket_read()`, `stream_socket_recvfrom()`, `curl_exec()` and `curl_multi_getcontent()`; the data
  `socket_recv()`, `socket_recvfrom()` and `socket_recvmsg()` write to their by-reference parameter; and the streams
  opened with `fsockopen()`, `pfsockopen()`, `stream_socket_client()`, `stream_socket_accept()` and
  `socket_export_stream()`.
- **DNS answers**, which whoever runs a domain's name servers chooses: `dns_get_record()`, `gethostbyaddr()`, and the
  hosts `getmxrr()` writes to its by-reference parameter.
- **Command-line options** returned by `getopt()`.
- **Exceptions:** the analysis doesn't follow an exception from where it is thrown to where it is caught, so the
  variable of a `catch` block is a source, and so are an exception's message (`getMessage()`), trace
  (`getTraceAsString()`) and string form.

```php
<?php
use Psr\Http\Message\ServerRequestInterface;

function handle(ServerRequestInterface $request): void {
    echo $request->getUri()->getQuery(); // TaintedHtml
    echo (string) ($request->getServerParams()['HTTP_REFERER'] ?? ''); // TaintedHtml
    echo (string) ($request->getServerParams()['DOCUMENT_ROOT'] ?? ''); // no issue: the server sets it
}
```

Psalm also ships [stubs](../running_psalm/configuration.md#loadlibrarystubs) for some libraries (Guzzle, amphp, the
MongoDB library, ...) and enables the [Symfony and Laravel plugins](../running_psalm/configuration.md#loadlibraryplugins).
They declare the sources and sinks of these libraries and frameworks. To declare your own, see
[custom taint sources](custom_taint_sources.md).

## Where user input must not go

A sink only reports the kinds of input that are dangerous there: `echo` reports HTML and quotes, a `PDO` query reports
SQL, and `shell_exec()` reports shell commands. Each kind, defined in
[Psalm\Type\TaintKind](https://github.com/vimeo/psalm/blob/master/src/Psalm/Type/TaintKind.php), has its own issue:

| Kind | Issue | Reported for a value that could contain |
|---|---|---|
| `sql` | [TaintedSql](../running_psalm/issues/TaintedSql.md) | SQL |
| `nosql` | [TaintedNosql](../running_psalm/issues/TaintedNosql.md) | the structure of a NoSQL query (a MongoDB filter, update or pipeline). Only arrays and objects carry it |
| `ldap` | [TaintedLdap](../running_psalm/issues/TaintedLdap.md) | an LDAP DN or filter |
| `xpath` | [TaintedXpath](../running_psalm/issues/TaintedXpath.md) | an XPath expression |
| `html` | [TaintedHtml](../running_psalm/issues/TaintedHtml.md) | HTML tags |
| `has_quotes` | [TaintedTextWithQuotes](../running_psalm/issues/TaintedTextWithQuotes.md) | quotes that end an HTML attribute |
| `shell` | [TaintedShell](../running_psalm/issues/TaintedShell.md) | shell commands |
| `callable` | [TaintedCallable](../running_psalm/issues/TaintedCallable.md) | the name of a function to call |
| `unserialize` | [TaintedUnserialize](../running_psalm/issues/TaintedUnserialize.md) | a serialized object |
| `include` | [TaintedInclude](../running_psalm/issues/TaintedInclude.md) | the path of a file to include |
| `eval` | [TaintedEval](../running_psalm/issues/TaintedEval.md) | PHP code |
| `extract` | [TaintedExtract](../running_psalm/issues/TaintedExtract.md) | an array passed to `extract()`, which overwrites variables |
| `ssrf` | [TaintedSSRF](../running_psalm/issues/TaintedSSRF.md) | the host of a URL to request |
| `url_component` | [TaintedUrlComponent](../running_psalm/issues/TaintedUrlComponent.md) | URL syntax (`/`, `?`, `&`, `#`, ...) in the path, query or fragment of a URL |
| `url_path` | [TaintedUrlPath](../running_psalm/issues/TaintedUrlPath.md) | a `.` or `..` segment in the path of a URL, even URL-encoded |
| `file` | [TaintedFile](../running_psalm/issues/TaintedFile.md) | a path |
| `cookie` | [TaintedCookie](../running_psalm/issues/TaintedCookie.md) | an HTTP cookie |
| `header` | [TaintedHeader](../running_psalm/issues/TaintedHeader.md) | an HTTP header |
| `sleep` | [TaintedSleep](../running_psalm/issues/TaintedSleep.md) | how long the code waits (`sleep()`, curl timeouts, ...). Numbers carry it too |
| `llm_prompt` | [TaintedLlmPrompt](../running_psalm/issues/TaintedLlmPrompt.md) | instructions to an LLM (prompt injection) |
| `user_secret` | [TaintedUserSecret](../running_psalm/issues/TaintedUserSecret.md) | a secret the user supplied |
| `system_secret` | [TaintedSystemSecret](../running_psalm/issues/TaintedSystemSecret.md) | a secret of the system |

Every kind except the two secrets is user input, and the sources above carry all of them. In annotations, `input` (or
its alias `tainted`) names all of them at once, and `input_except_sleep` all of them but `sleep`.

You can add your own kinds [in the configuration or in a plugin](custom_taint_sources.md#custom-taints-declared-in-the-configuration),
and your own sinks with [`@psalm-taint-sink`](custom_taint_sinks.md).

Writing to the response through a stream is a sink, just like `echo`. That is a literal `php://output` path, or
`php://stdout`, which is the response under the CGI SAPI. These write to it:

- `file_put_contents()` with such a path;
- `fwrite()`, `fputs()`, `fputcsv()`, `fprintf()`, `vfprintf()`, `stream_copy_to_stream()`, and the `fwrite()` and
  `fputcsv()` methods of `SplFileObject`, on a stream opened on such a path in the same function.

## Fixing a report

### Escape for the sink

Escape the input where it is used, for the place it goes. Better still, keep it out of the code altogether: bind it as
a parameter of a prepared query, or pass it as an argument instead of building it into a shell command.

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

Psalm trusts an escaping function wherever it is used, but an escape only protects the context it was made for. Each
value below is escaped for the wrong context:

```php
<?php
$sql = 'SELECT * FROM users WHERE id = ' . $mysqli->real_escape_string((string) $_GET['id']);

$html = "
  <img src=" . htmlentities((string) $_GET['img']) . " alt='' />
  <a href='" . htmlentities((string) $_GET['a1']) . "'>Link 1</a>
  <a href='" . htmlentities((string) $_GET['a2']) . "'>Link 2</a>";

// Details:
//    $id  = 'id'                   - Missing quotes
//    $img = '/ onerror=alert(1)'   - Missing quotes
//    $a1  = 'javascript:alert(1)'  - Normal inline JavaScript
//    $a2  = '/' onerror='alert(1)' - Pre PHP 8.1, single quotes are not escaped by default
// Test:
//    /?id=id&img=%2F+onerror%3Dalert%281%29&a1=javascript%3Aalert%281%29&a2=%2F%27+onerror%3D%27alert%281%29
```

So prefer parameterised queries for SQL and commands, and a context-aware templating engine for HTML. The
[literal-string](../annotating_code/type_syntax/scalar_types.md#literal-string) type then makes sure that queries and
commands are written by a developer.

### Validate the type

A value can only carry the taints its type can hold:

- a number or a boolean can't hold HTML, SQL or a path;
- a string can't be a NoSQL query document, but what `json_decode()`, `unserialize()`, `parse_str()` and the other
  decoders build from it can;
- a literal string can't hold anything the client sent. It is a value the code wrote, or one Psalm knows is among such
  values;
- an array holds what its keys and values can: `list<int>` holds no HTML, but the string keys of `array<string, int>`
  can hold anything;
- a union holds what any of its types can, and `null` holds nothing.

Psalm removes the taints a value can't carry wherever the value is cast, passed to a function, returned or assigned,
based on the type it infers for it. It also relies on the types PHP guarantees: the native type of a parameter inside
the function, and the native return type of a function, taint sources included.

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
        echo $direction; // no issue: 'asc'|'desc' is a literal
    }
}
```

A regular expression can validate a string too. If a string matches a pattern anchored at both ends, it only holds the
characters the pattern allows, and so it can't carry the taints that need other characters. Psalm understands literal
characters, character classes, `.`, `\d`, `\w`, `\s`, groups of alternatives and quantifiers. The check is a
`preg_match()` call, either used as a condition or compared to `1`:

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
        shell_exec('grep -r ' . $id . ' /var/log'); // TaintedShell: a dash starts an option
    }

    $name = getParam('name');
    if (preg_match('/^[a-z0-9 ]+$/i', $name)) {
        $xpath->query('//user[@id=' . $name . ']'); // TaintedXpath: a space makes `1 or 1`
    }

    if (preg_match('/[a-z]+/', $id)) {
        echo $id; // TaintedHtml: the pattern isn't anchored
    }
}
```

Watch out for `$`: it also matches before a final newline, which ends a shell command. Add the `D` modifier, or end the
pattern with `\z`, to rule the newline out.

A function that checks a value against data the code trusts can say so with `@psalm-assert-if-true literal-string`.
Once the check passes, the value can't hold anything the client chose:

```php
<?php

/** @psalm-assert-if-true literal-string $city */
function isKnownCity(string $city): bool {
    return in_array($city, getCityKeysFromDatabase(), true);
}

$city = getParam('city');
if (isKnownCity($city)) {
    $organization->city = $city; // stored without any taint
}
```

### Your own escaping functions

`@psalm-taint-escape <kind>` removes a kind of taint. On a function, it removes it from what the function returns; on
an assignment, from the value assigned:

```php
<?php

function echoVar(string $str) : void {
    /**
     * @psalm-taint-escape html
     * @psalm-taint-escape has_quotes
     */
    $str = str_replace(['<', '>', '"', "'"], '', $str);
    echo $str; // no issue
}

echoVar($_GET["text"]);
```

A conditional type makes the kinds removed depend on the arguments:

```php
<?php
/**
 * @psalm-taint-escape ($escape is true ? 'html'|'has_quotes' : null)
 */
function processVar(string $str, bool $escape = true) : string {
    if ($escape) {
      $str = htmlspecialchars($str, ENT_QUOTES);
    }
    return $str;
}

echo processVar($_GET['text'], false); // TaintedHtml
echo processVar($_GET['text'], true); // no issue
```

Sometimes the input must be HTML, as with rich text editors like [TinyMCE](https://www.tiny.cloud/). Filter it with a
library made for that, such as [HTML Purifier](http://htmlpurifier.org/docs), and declare what the filter removes:

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

`json_encode()` removes the `html` taint when its flags are known and include both `JSON_HEX_TAG` and `JSON_HEX_AMP`.
It never removes `has_quotes`, because the `"` around a JSON string ends a double-quoted attribute. If your function
only puts the JSON inside a `<script>`, or uses `JSON_HEX_APOS` and puts it in a single-quoted attribute, declare
`@psalm-taint-escape has_quotes` on it.

## Reports through shared code

By default, Psalm tracks the parameters and the result of a function once for all its calls. What one call passes in
comes out at every other call, so a report's trace can go through a call that has nothing to do with the sink:

```php
<?php

function takesInput(string $s) : string {
    error_log("Got input");
    return $s;
}

echo htmlentities(takesInput($_GET["name"]));
echo takesInput("hello"); // TaintedHtml
```

### Specializing functions

`@psalm-taint-specialize` makes Psalm track each call separately: what a call returns then only depends on what that
call passed in.

```php
<?php

/**
 * @psalm-taint-specialize
 */
function takesInput(string $s) : string {
    error_log("Got input");
    return $s;
}

echo htmlentities(takesInput($_GET["name"]));
echo takesInput("hello"); // no issue
echo takesInput($_GET["name"]); // TaintedHtml
```

When a specialized function passes its input to a sink, in its body or in a function it calls, the sink is reported
once for each call that passes input in:

```php
<?php

/**
 * @psalm-taint-specialize
 */
function run(string $command) : void {
    exec($command); // TaintedShell, twice: once for each call below
}

run($_GET["first"]);
run($_GET["second"]);
```

Pure functions are specialized too, since what they return only depends on their arguments. That includes functions
annotated [`@psalm-pure`](../annotating_code/purity_model.md), and those Psalm infers to be pure, as `takesInput()` would
be without its `error_log()` call. An impure method could override a pure one, so Psalm only infers purity for methods
that can't be overridden: `private` and `final` methods, and the methods of `final` classes. Annotate the others.

### Specializing classes

Properties have the same problem: a property is shared by all the objects of its class.

```php
<?php

class User {
    public function __construct(public string $name) {}
}

/**
 * @psalm-taint-specialize
 */
function echoUserName(User $user) {
    echo $user->name; // TaintedHtml
}

$user1 = new User("Keith");
$user2 = new User($_GET["name"]);

echoUserName($user1);
```

With `@psalm-taint-specialize` (or `@psalm-immutable`) on the class, each object holds its own taints:

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
function echoUserName(User $user) {
    echo $user->name; // no issue
}

$user1 = new User("Keith");
$user2 = new User($_GET["name"]);

echoUserName($user1);
```

The taints of an object only follow the variables that hold it, so Psalm makes sure the object doesn't change once it
is constructed:

- its properties are readonly;
- its methods can't write to them, nor to other objects, static properties or global variables, where another object
  could read back what this one wrote.

To change such an object, make a modified copy, as `withName()` does above.

Subclasses of a specialized class are specialized too. Psalm reports
[ImmutableDependency](../running_psalm/issues/ImmutableDependency.md) for a subclass without the annotation.

## Command-line code

What a command-line script prints goes to a terminal or a log, not to an HTML page. Psalm counts code as command-line
code when:

- it is a function, method or class annotated `@psalm-cli-only`;
- it is guarded by a `PHP_SAPI` or `php_sapi_name()` check (closures inside it included).

What command-line code prints isn't reported as `TaintedHtml`. This covers `echo`, `print`, `printf()`, `vprintf()`,
`var_dump()`, `debug_zval_dump()`, and `print_r()` and `var_export()` when they don't return their output.

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

A function or method can do the check for its callers by [asserting](../annotating_code/adding_assertions.md#asserting-the-sapi)
`PHP_SAPI`: `@psalm-assert 'cli' PHP_SAPI`, or `@psalm-assert-if-true 'cli' PHP_SAPI` on one returning a `bool`. The
code after the call, or the branch the call guards, then counts as command-line code too.

Code marked `@psalm-cli-only` can only be called from command-line code; any other call is reported as
[CliOnlyCall](../running_psalm/issues/CliOnlyCall.md).

Only printing is affected. Printing a secret is still reported, and the values stay tainted for every other sink. A
function called from command-line code is checked like any other, so if it only runs from the command line, mark it
too.

### Classes only instantiated from the command line

`@psalm-cli-only-instances` on a class says that its instances only exist in command-line processes: its constructor
and its `__wakeup()` (or `__unserialize()`) refuse to run anywhere else. Code that can only run with an instance then
counts as command-line code. Calling it needs no check, since the caller holds an instance too. This code is:

- the non-static methods of the class, except the constructor, `__unserialize()` and `__wakeup()`, which run before
  the object is checked;
- a function, method or closure with a parameter whose native type requires an instance of the class, alone or in an
  intersection (not in a union). The parameter must not be nullable, have a default or be variadic: PHP then throws a
  `TypeError` before the body runs without an instance. A docblock type isn't enough, as PHP doesn't check it.

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

    public function __wakeup(): void
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

Psalm reports [CliOnlyInstancesUnguarded](../running_psalm/issues/CliOnlyInstancesUnguarded.md) when the class
doesn't guard its instances this way. That page lists what is checked and what is trusted.

## What Psalm can't see

Psalm only reports the paths it can follow. Tell it about the rest:

- input your code gets from elsewhere (a framework, a queue, a database users write to):
  [custom taint sources](custom_taint_sources.md);
- places input must not go: [custom taint sinks](custom_taint_sinks.md);
- flows through code Psalm doesn't analyze (interfaces, stubs, vendor code), through caches and through ORM models:
  [taint flow](taint_flow.md);
- functions that undo an escape: `@psalm-taint-unescape <kind>`.

```php
<?php

/**
 * @psalm-taint-unescape html
 */
function decode(string $str): string
{
    return str_replace(
        ['&lt;', '&gt;', '&quot;', '&apos;'],
        ['<', '>', '"', '"'],
        $str
    );
}

$safe = htmlspecialchars($_GET['text']);
echo decode($safe); // TaintedHtml
```

## Dealing with the remaining reports

### Suppressing

A taint issue is reported at the sink, not where the tainted value is passed to it, so suppressing it by name at the
call doesn't work. Instead:

- `@psalm-suppress TaintedInput` stops tracking taints in the function or statement it annotates;
- `<issueHandlers>` in the config suppresses an issue type everywhere.

```php
<?php

/** @psalm-taint-sink header $value */
function sendHeader(string $value): void {} // TaintedHeader, from the call in a()

function a(): void {
    /** @psalm-suppress TaintedHeader */
    sendHeader($_GET['h']); // not suppressed
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

To ignore the taint paths that go through some files or directories, such as your tests, list them in the config:

```xml
<taintAnalysis>
    <ignoreFiles>
        <directory name="tests"/>
    </ignoreFiles>
</taintAnalysis>
```

### Using a baseline

[`--set-baseline`](../running_psalm/dealing_with_code_issues.md#using-a-baseline-file) records security issues along
with the others. Psalm then only reports new ones while you work through the existing ones.

## Security issues in reports

Human-readable reports (`console`, `compact`, `table`, `by-issue-level` and `phpstorm`) show security issues under a
`SECURITY` header instead of `ERROR`. A security issue configured with `errorLevel="info"` is still shown as `INFO`.

Machine-readable reports (JSON, checkstyle, SARIF, ...) keep their usual severities. The JSON and XML reports add an
`is_security` field.

A plugin can mark its own issues as security issues by implementing the `Psalm\Issue\SecurityIssue` interface.

Each issue page lists the [CWE](https://cwe.mitre.org/) weaknesses the issue flags, and reports include them:

- human-readable reports, after the message;
- the JSON report, in a `cwe_ids` field;
- SARIF reports, as `external/cwe/cwe-NNN` rule tags.

```
SECURITY: TaintedSql - src/index.php:2:1 - Detected tainted SQL (CWE-89)
```

A plugin issue declares its weaknesses in a `CWE_IDS` constant, most specific first:
`public const CWE_IDS = [95, 94];`.

### Viewing results in a user interface

Psalm can write [SARIF](http://docs.oasis-open.org/sarif/sarif/v2.0/csprd01/sarif-v2.0-csprd01.html), a standard
format for static analysis results. Any SARIF viewer can then show the results, each with its trace. To write a SARIF
report, pass `--report` a file name ending in `.sarif`:

```bash
psalm --report=results.sarif
```

The `ruleId` of each result is the issue type, e.g. `TaintedSql`.

[GitHub code scanning](https://docs.github.com/en/code-security/code-scanning/introduction-to-code-scanning/about-code-scanning)
shows the results in the "Security" tab of your repository. To send them there, use one of:

- the [Psalm Security Scan](https://github.com/psalm/psalm-github-security-scan) action;
- the [Psalm GitHub Action](https://github.com/psalm/psalm-github-actions) with `report_file: results.sarif` (see
  [GitHub Actions](../running_psalm/installation.md#github-actions));
- [uploading the SARIF file](https://docs.github.com/en/code-security/code-scanning/integrating-with-code-scanning/uploading-a-sarif-file-to-github)
  yourself.

## Debugging the taint graph

When a report you expect is missing, look at the graph Psalm builds (see [the taint graph](taint_flow.md#the-taint-graph)).
`--dump-taint-graph` writes it in the DOT language, which Graphviz renders:

```bash
psalm --taint-analysis --dump-taint-graph=taints.dot
dot -Tsvg -o taints.svg taints.dot
```
