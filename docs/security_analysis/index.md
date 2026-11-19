# Security Analysis

Psalm follows user input through your code (assignments, calls, arrays, properties) and reports where it reaches a
place that must not receive it as it is: in an HTML page it is cross-site scripting, in an SQL query SQL injection, in
a shell command command injection. This page goes from reading a first report to a clean one.

Taint analysis runs by default, in the same run as type checking. To turn it off, set
[`runTaintAnalysis="false"`](../running_psalm/configuration.md#runtaintanalysis) in `psalm.xml`; the
`--taint-analysis` flag still turns it on for a single run.

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

The issue is located at the *sink*, the place the input must not reach: here `echo`. Below it, the trace follows the
input step by step from its *source*, `$_GET`, each step with its location and its line of code:

- `$_GET['name']`, `$name`: an array element, a variable;
- `call to greeting`: an argument of a call, and `greeting#1`: the first parameter of `greeting()`;
- `greeting`: what `greeting()` returns;
- `coalesce`, `concat`, `string-cast`: an operation the input goes through.

Each step is a place where the input could be escaped or validated; the next sections show how.

Psalm reports the same path a second time as `TaintedTextWithQuotes`: `echo` is a sink for both HTML and quotes,
and each kind is its own issue. `htmlspecialchars()` fixes both.

Only the default `console` output prints the trace. `compact` and `github` print the sink alone, the JSON report has
it in a `taint_trace` field, and a [SARIF report](#viewing-results-in-a-user-interface) as a code flow, which GitHub
code scanning and IDE SARIF viewers let you step through.

## Where user input comes from

Psalm knows these taint sources:

- the `$_GET`, `$_POST`, `$_COOKIE` and `$_REQUEST` superglobals;
- the entries of `$_SERVER` the client sends: the request headers (`HTTP_*`), the URI and what is taken from it
  (`REQUEST_URI`, `QUERY_STRING`, `PATH_INFO`, `PHP_SELF`, `argv`, ...), `REQUEST_METHOD`, `CONTENT_TYPE` and the HTTP
  authentication credentials, the same entries of a redirected request (`REDIRECT_*`), and any entry read with a key
  that is not a literal;
- the names, types and full paths of the uploaded files in `$_FILES`;
- PSR-7 messages: all that the methods of `Psr\Http\Message\ServerRequestInterface`, `ResponseInterface`,
  `UriInterface`, `StreamInterface` and `UploadedFileInterface` return, and those of the classes implementing them.
  The request your middleware and controllers get is a source, and so is the response an HTTP client like Guzzle
  returns;
- reading from the input stream: `fopen()`, `file_get_contents()` and `file()` called with a literal `php://input` or
  `php://stdin` path, the stream reading functions (`fgets()`, `fread()`, `stream_get_contents()`, ...) applied to such
  a handle, and the `STDIN` constant;
- reading from the network: `socket_read()`, the data `socket_recv()`, `socket_recvfrom()` and `socket_recvmsg()` write
  to their by-reference parameter, `stream_socket_recvfrom()`, `curl_exec()` and `curl_multi_getcontent()`, and the
  streams opened with `fsockopen()`, `pfsockopen()`, `stream_socket_client()`, `stream_socket_accept()` and
  `socket_export_stream()`;
- DNS answers, which whoever runs the name servers of a domain chooses: `dns_get_record()`, the hosts `getmxrr()`
  writes to its by-reference parameter, and `gethostbyaddr()`;
- the command-line options `getopt()` returns;
- exceptions, since the analysis does not follow them from where they are thrown to where they are caught: their
  message (`getMessage()`), their trace (`getTraceAsString()`) and their string form, and the variable of a `catch`
  block.

```php
<?php
use Psr\Http\Message\ServerRequestInterface;

function handle(ServerRequestInterface $request): void {
    echo $request->getUri()->getQuery(); // TaintedHtml
    echo (string) ($request->getServerParams()['HTTP_REFERER'] ?? ''); // TaintedHtml
    echo (string) ($request->getServerParams()['DOCUMENT_ROOT'] ?? ''); // no issue: the server sets it
}
```

Psalm also ships [stubs](../running_psalm/configuration.md#loadlibrarystubs) for other libraries (Guzzle, amphp,
the MongoDB library, ...), and enables the [Symfony and Laravel plugins](../running_psalm/configuration.md#loadlibraryplugins),
which declare the sources and sinks of these frameworks. For your own, see [custom taint sources](custom_taint_sources.md).

## Where it must not go

A sink only reports the kinds of input that are dangerous there: `echo` reports HTML and quotes, a `PDO` query SQL,
`shell_exec()` shell commands. Each kind, defined in
[Psalm\Type\TaintKind](https://github.com/vimeo/psalm/blob/master/src/Psalm/Type/TaintKind.php), has its issue:

| Kind | Issue | Input that could contain |
|---|---|---|
| `sql` | [TaintedSql](../running_psalm/issues/TaintedSql.md) | SQL |
| `nosql` | [TaintedNosql](../running_psalm/issues/TaintedNosql.md) | the structure of a NoSQL query (a MongoDB filter, update or pipeline): only arrays and objects carry it |
| `ldap` | [TaintedLdap](../running_psalm/issues/TaintedLdap.md) | an LDAP DN or filter |
| `xpath` | [TaintedXpath](../running_psalm/issues/TaintedXpath.md) | an XPath expression |
| `html` | [TaintedHtml](../running_psalm/issues/TaintedHtml.md) | HTML tags |
| `has_quotes` | [TaintedTextWithQuotes](../running_psalm/issues/TaintedTextWithQuotes.md) | quotes ending an attribute |
| `shell` | [TaintedShell](../running_psalm/issues/TaintedShell.md) | shell commands |
| `callable` | [TaintedCallable](../running_psalm/issues/TaintedCallable.md) | the name of a function to call |
| `unserialize` | [TaintedUnserialize](../running_psalm/issues/TaintedUnserialize.md) | a serialized object |
| `include` | [TaintedInclude](../running_psalm/issues/TaintedInclude.md) | the path of a file to include |
| `eval` | [TaintedEval](../running_psalm/issues/TaintedEval.md) | PHP code |
| `extract` | [TaintedExtract](../running_psalm/issues/TaintedExtract.md) | an array passed to `extract()`, overwriting variables |
| `ssrf` | [TaintedSSRF](../running_psalm/issues/TaintedSSRF.md) | the host of a URL to request |
| `url_component` | [TaintedUrlComponent](../running_psalm/issues/TaintedUrlComponent.md) | URL syntax (`/`, `?`, `&`, `#`, ...) in the path, query or fragment of a URL |
| `url_path` | [TaintedUrlPath](../running_psalm/issues/TaintedUrlPath.md) | a `.` or `..` segment of the path of a URL, even URL-encoded |
| `file` | [TaintedFile](../running_psalm/issues/TaintedFile.md) | a path |
| `cookie` | [TaintedCookie](../running_psalm/issues/TaintedCookie.md) | an HTTP cookie |
| `header` | [TaintedHeader](../running_psalm/issues/TaintedHeader.md) | an HTTP header |
| `sleep` | [TaintedSleep](../running_psalm/issues/TaintedSleep.md) | how long the code waits (`sleep()`, curl timeouts, ...): numbers carry it too |
| `llm_prompt` | [TaintedLlmPrompt](../running_psalm/issues/TaintedLlmPrompt.md) | instructions to an LLM (prompt injection) |
| `user_secret` | [TaintedUserSecret](../running_psalm/issues/TaintedUserSecret.md) | a secret the user supplied |
| `system_secret` | [TaintedSystemSecret](../running_psalm/issues/TaintedSystemSecret.md) | a secret of the system |

Every kind but the secrets is user input: the sources above give all of them. Annotations can name them at once with
`input` (or its alias `tainted`), or `input_except_sleep`. You can define your own kinds in
[the configuration or a plugin](custom_taint_sources.md#custom-taints-declared-in-the-configuration), and your own sinks
with [`@psalm-taint-sink`](custom_taint_sinks.md).

What is written to a literal `php://output` path, which is the response, or `php://stdout`, which is the response
under the CGI SAPI, is a sink as `echo` is: `file_put_contents()` to such a path, and `fwrite()`, `fputs()`,
`fputcsv()`, `fprintf()`, `vfprintf()`, `stream_copy_to_stream()` and the `fwrite()` and `fputcsv()` methods of
`SplFileObject` on a stream opened on such a path in the same function.

## Fixing a report

### Escape for the sink

Escape the input where it is used, for the place it goes, or keep it out of the code altogether: bind it as a
parameter of a prepared query, pass it as an argument rather than in a shell command.

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

An escape only protects the context it was made for, and Psalm trusts it there:

```php
<?php
$sql = 'SELECT * FROM users WHERE id = ' . $mysqli->real_escape_string((string) $_GET['id']);

$html = "
  <img src=" . htmlentities((string) $_GET['img']) . " alt='' />
  <a href='" . htmlentities((string) $_GET['a1']) . "'>Link 1</a>
  <a href='" . htmlentities((string) $_GET['a2']) . "'>Line 2</a>";

// Details:
//    $id  = 'id'                   - Missing quotes
//    $img = '/ onerror=alert(1)'   - Missing quotes
//    $a1  = 'javascript:alert(1)'  - Normal inline JavaScript
//    $a2  = '/' onerror='alert(1)' - Pre PHP 8.1, single quotes are not escaped by default
// Test:
//    /?id=id&img=%2F+onerror%3Dalert%281%29&a1=javascript%3Aalert%281%29&a2=%2F%27+onerror%3D%27alert%281%29
```

Prefer parameterised queries for SQL and commands, and a context-aware templating engine for HTML. The
[literal-string](../annotating_code/type_syntax/scalar_types.md#literal-string) type then makes sure the queries
and commands are written by a developer.

### Validate the type

A value can only carry the taints its type can hold: a number or a boolean can't hold HTML, SQL or a path, a string
can't be a NoSQL query document (but what `json_decode()`, `unserialize()`, `parse_str()` and the other decoders build
out of it can), and a literal string (a value the code wrote, or one Psalm knows is among such values) can't hold
anything the client sent. An array holds what its keys and values can: `list<int>` holds no HTML, while the string
keys of `array<string, int>` can hold anything. A union holds what any of its types can hold, and `null` holds nothing.

Psalm removes the taints a value can't carry where it is cast, passed to a function, returned, or assigned, using the
type it infers for it, and where PHP guarantees a type: the native type of a parameter, inside the function, and the
native return type of a function, taint sources included.

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

A string that matches a pattern anchored at both ends can only hold the characters the pattern allows, so it can't
carry the taints that need others. Psalm understands literals, character classes, `.`, `\d`, `\w`, `\s`, groups of
alternatives and quantifiers, checked with `preg_match()`, either truthy or compared to `1`:

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

`$` also matches before a final newline, unless the pattern has the `D` modifier or ends with `\z`: that newline ends a
shell command.

A function that validates a value against data the code trusts can say so with `@psalm-assert-if-true
literal-string`: once validated, the value can't hold anything the client chose.

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

`@psalm-taint-escape <kind>` on a function, or on an assignment, removes that kind from what it returns:

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

The kinds removed can depend on the arguments, with a conditional type:

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

When the input itself must be HTML (rich text editors like [TinyMCE](https://www.tiny.cloud/)), filter it with a
library made for it, such as [HTML Purifier](http://htmlpurifier.org/docs), and say what it removes:

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

`json_encode()` removes the `html` taint when its flags are a known value including `JSON_HEX_TAG` and
`JSON_HEX_AMP`. It keeps `has_quotes` whatever the flags, as the `"` delimiting a JSON string ends a double-quoted
attribute; a function that only puts JSON in a `<script>`, or with `JSON_HEX_APOS` in an attribute in single quotes,
can declare `@psalm-taint-escape has_quotes`.

## Reports through shared code

A function that is called from several places has one node for its parameters and one for its result: what one call
passes in comes out at every call, and the trace of a report then goes through a call unrelated to the sink.

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

`@psalm-taint-specialize` tells Psalm to follow each call separately: what it returns only depends on what that call
gets.

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

A sink the input reaches through a specialized function, in its body or in a function it calls, is reported for every
call that passes input to it:

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

Pure functions are specialized too, since what they return only depends on their arguments: those annotated
[`@psalm-pure`](../annotating_code/purity_model.md), and those Psalm infers to be pure, as `takesInput()` would be
without the `error_log()` call. A method could be overridden by an impure one, so Psalm only infers this for methods
that can't be: `private` or `final` methods, and methods of `final` classes. Annotate the others.

### Specializing classes

A property is shared the same way by all the objects of its class:

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

The taints of an object only follow the variables it is assigned to, so Psalm makes sure it doesn't change once
constructed: its properties are readonly, and its methods can't write them, nor other objects, static properties or
global variables, from which another object could read back what this one wrote. To change an object, create a new one,
as `withName()` does above. The classes extending a specialized class are specialized too, and are reported as
[ImmutableDependency](../running_psalm/issues/ImmutableDependency.md) unless they have the annotation themselves.

## Command-line code

What a script run from the command line prints goes to a terminal or a log, not to an HTML page. `@psalm-cli-only` on
a function, a method or a class says that it only runs from the command line, and so does a `PHP_SAPI` or
`php_sapi_name()` check for the code it guards: what that code (and the closures in it) prints with `echo`, `print`,
`printf()`, `vprintf()`, `var_dump()`, `debug_zval_dump()`, and `print_r()` and `var_export()` not returning it, isn't
reported as `TaintedHtml`.

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

A function or method can make the check for its callers by [asserting](../annotating_code/adding_assertions.md#asserting-the-sapi)
`PHP_SAPI` (`@psalm-assert 'cli' PHP_SAPI`, or `@psalm-assert-if-true 'cli' PHP_SAPI` on one returning a `bool`): the
code after the call, or the branch it guards, then counts as command-line code too.

Code marked `@psalm-cli-only` can only be called from such code, or [CliOnlyCall](../running_psalm/issues/CliOnlyCall.md)
is reported. Printing a secret is still reported, and the values stay tainted for every other sink. A function called
from command-line code is checked as usual, so mark it too if it only runs from the command line.

`@psalm-cli-only-instances` on a class says that its instances only exist in command-line processes, because its
constructor and `__wakeup()` (or `__unserialize()`) refuse to run anywhere else. Code that can only run with an
instance then counts as command-line code, and calling it needs no check, since the caller holds an instance too:

- the methods of the class that aren't static, except the constructor, `__unserialize()` and `__wakeup()`, which run
  on an object not checked yet;
- a function, method or closure with a parameter whose native type requires an instance of the class (alone or in an
  intersection, not in a union), when the parameter isn't nullable, has no default and isn't variadic: PHP throws a
  `TypeError` before its body runs without one. A docblock type proves nothing, as PHP doesn't check it.

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

[CliOnlyInstancesUnguarded](../running_psalm/issues/CliOnlyInstancesUnguarded.md) is reported when the class doesn't
guard its instances this way: see there what is checked, and what is trusted.

## What Psalm can't see

Psalm only reports the paths it can follow. Tell it about the rest:

- the input your code gets from elsewhere (a framework, a queue, a database written by users):
  [custom taint sources](custom_taint_sources.md);
- the places it must not go: [custom taint sinks](custom_taint_sinks.md);
- the flows through code it doesn't analyze, such as interfaces, stubs and vendor code, through caches and through
  ORM models: [taint flow](taint_flow.md);
- the functions that undo an escape, with `@psalm-taint-unescape <kind>`:

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

A taint issue is reported at the sink, not where the tainted value is passed to it, so a docblock can't suppress it by
its own name. `@psalm-suppress TaintedInput` stops tracking taints in the function or statement it annotates, and the
`<issueHandlers>` of the config suppress a specific issue:

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

To ignore the paths that go through some files or directories, such as your tests, list them in the config:

```xml
<taintAnalysis>
    <ignoreFiles>
        <directory name="tests"/>
    </ignoreFiles>
</taintAnalysis>
```

### Using a baseline

[`--set-baseline`](../running_psalm/dealing_with_code_issues.md#using-a-baseline-file) records the security issues
with the others, so that only new ones are reported while you work through the existing ones.

## Security issues in reports

The human-readable reports (`console`, `compact`, `table`, `by-issue-level` and `phpstorm`) show security issues under
a `SECURITY` header instead of `ERROR`. A security issue configured with `errorLevel="info"` is still shown as `INFO`.
Machine-readable reports (JSON, checkstyle, SARIF, ...) keep their usual severities; the JSON and XML reports also
include an `is_security` field. Plugins can mark their own issues the same way by implementing the
`Psalm\Issue\SecurityIssue` interface.

Each issue page lists the [CWE](https://cwe.mitre.org/) weaknesses the issue flags, and reports carry them too:
human-readable reports after the message, the JSON report in a `cwe_ids` field, and SARIF reports as
`external/cwe/cwe-NNN` rule tags.

```
SECURITY: TaintedSql - src/index.php:2:1 - Detected tainted SQL (CWE-89)
```

A plugin issue declares its weaknesses with the `CWE_IDS` constant, the most specific first:
`public const CWE_IDS = [95, 94];`.

### Viewing results in a user interface

Psalm supports the [SARIF](http://docs.oasis-open.org/sarif/sarif/v2.0/csprd01/sarif-v2.0-csprd01.html) standard for
exchanging static analysis results, so any SARIF compatible software can show the results, with the trace of each.
To generate a SARIF report, run Psalm with the `--report` flag and a `.sarif` extension:

```bash
psalm --report=results.sarif
```

The `ruleId` of a result is the issue type, e.g. `TaintedSql`.

[GitHub code scanning](https://docs.github.com/en/code-security/code-scanning/introduction-to-code-scanning/about-code-scanning)
shows them in the "Security" tab of your repository: use the
[Psalm Security Scan](https://github.com/psalm/psalm-github-security-scan) action, the
[Psalm GitHub Action](https://github.com/psalm/psalm-github-actions) with `report_file: results.sarif` (see
[GitHub Actions](../running_psalm/installation.md#github-actions)), or
[upload the SARIF file](https://docs.github.com/en/code-security/code-scanning/integrating-with-code-scanning/uploading-a-sarif-file-to-github)
yourself.

## Debugging the taint graph

When a report you expect is missing, look at the graph Psalm builds (see [the taint graph](taint_flow.md#the-taint-graph)).
`--dump-taint-graph` writes it in the DOT language:

```bash
psalm --taint-analysis --dump-taint-graph=taints.dot
dot -Tsvg -o taints.svg taints.dot
```
