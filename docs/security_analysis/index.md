# Security Analysis in Psalm

Psalm can attempt to find connections between user-controlled input (like `$_GET['name']`) and places that we don’t want unescaped user-controlled input to end up (like `echo "<h1>$name</h1>"` by looking at the ways that data flows through your application (via assignments, function/method calls and array/property access).

You can enable this mode with the `--taint-analysis` command line flag. When taint analysis is enabled, no other analysis is performed.  To [ensure comprehensive results](https://github.com/vimeo/psalm/issues/6156), Psalm should be run normally prior to taint analysis, and any errors should be fixed.

Tainted input is anything that can be controlled, wholly or in part, by a user of your application. In taint analysis, tainted input is called a _taint source_.

Example sources:

 - `$_GET[‘id’]`
 - `$_POST['email']`
 - `$_COOKIE['token']`

 Taint analysis tracks how data flows from taint sources into _taint sinks_. Taint sinks are places you really don’t want untrusted data to end up.

Example sinks:

 - `<div id="section_<?= $id ?>">`
 - `$pdo->exec("select * from users where name='" . $name . "'")`

## Taint Types

Psalm recognises a number of taint types by default, defined in the [Psalm\Type\TaintKind](https://github.com/vimeo/psalm/blob/master/src/Psalm/Type/TaintKind.php) class:

- `sql` - used for strings that could contain SQL
- `ldap` - used for strings that could contain a ldap DN or filter
- `html` - used for strings that could contain angle brackets or unquoted strings
- `has_quotes` - used for strings that could contain unquoted strings
- `shell` - used for strings that could contain shell commands
- `callable` - used for callable strings that could be user-controlled
- `unserialize` - used for strings that could contain a serialized string
- `include` - used for strings that could contain a path being included
- `eval` - used for strings that could contain code
- `ssrf` - used for strings that could contain text passed to Curl or similar
- `file` - used for strings that could contain a path
- `cookie` - used for strings that could contain a http cookie
- `header` - used for strings that could contain a http header
- `user_secret` - used for strings that could contain user-supplied secrets
- `system_secret` - used for strings that could contain system secrets

You're also free to define your own taint types when defining custom taint sources – they're just strings.

## Taint Sources

Psalm currently defines three default taint sources: the `$_GET`, `$_POST` and `$_COOKIE` server variables.

You can also [define your own taint sources](custom_taint_sources.md).

## Taint Sinks

Psalm currently defines a number of different sinks for builtin functions and methods, including `echo`, `include`, `header`.

You can also [define your own taint sinks](custom_taint_sinks.md).

## Avoiding False-Positives

Nobody likes to wade through a ton of false-positives – [here’s a guide to avoiding them](avoiding_false_positives.md).

## Limitations

Taint Analysis relies on not making any mistakes when escaping values, e.g.

```php
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

To avoid these issues, use Parameterised Queries for SQL and Commands (e.g. `exec`); and a context-aware templating engine for HTML. Then use the [literal-string](https://psalm.dev/docs/annotating_code/type_syntax/scalar_types/#literal-string) type to ensure sensitive strings are defined in your application (i.e. have been written by a developer).

## Using Baseline With Taint Analysis

Since taint analysis is performed separately from other static code analysis, it makes sense to use a separate baseline for it.

You can use --use-baseline=PATH option to set a different baseline for taint analysis.

## Viewing Results in a User Interface

Psalm supports the [SARIF](http://docs.oasis-open.org/sarif/sarif/v2.0/csprd01/sarif-v2.0-csprd01.html) standard for exchanging static analysis results. This enables you to view the results in any SARIF compatible software, including the taint flow.

### GitHub Code Scanning

[GitHub code scanning](https://docs.github.com/en/free-pro-team@latest/github/finding-security-vulnerabilities-and-errors-in-your-code/about-code-scanning) can be set up by using the [Psalm GitHub Action](https://github.com/marketplace/actions/psalm-static-analysis-for-php).

Alternatively, the generated SARIF file can be manually uploaded as described in [the GitHub documentation](https://docs.github.com/en/free-pro-team@latest/github/finding-security-vulnerabilities-and-errors-in-your-code/uploading-a-sarif-file-to-github).

The results will then be available in the "Security" tab of your repository.

### Other SARIF compatible software

To generate a SARIF report run Psalm with the `--report` flag and a `.sarif` extension. For example:

```bash
psalm --report=results.sarif
```

## Debugging the taint graph

Psalm can output the taint graph using the DOT language. This is useful when expected taints are not detected. To generate a DOT graph run Psalm with the `--dump-taint-graph` flag. For example:

```bash
psalm --taint-analysis --dump-taint-graph=taints.dot
dot -Tsvg -o taints.svg taints.dot
```
