# Security Analysis in Psalm

Psalm can attempt to find connections between user-controlled input (like `$_GET['name']`) and places that we don’t want unescaped user-controlled input to end up (like `echo "<h1>$name</h1>"` by looking at the ways that data flows through your application (via assignments, function/method calls and array/property access).

You can enable this mode with the `--taint-analysis` command line flag. When taint analysis is enabled, no other analysis is performed.  To [ensure comprehensive results](https://github.com/vimeo/psalm/issues/6156), Psalm should be run normally prior to taint analysis, and any errors should be fixed.

### Taint analysis alongside normal analysis

To get both in one run, use `--combined-taint-analysis` (or `runCombinedTaintAnalysis="true"` in `psalm.xml`; `--no-combined-taint-analysis` turns the config setting off for one run). Psalm scans the codebase once, then forks a process that runs the taint analysis on a copy of the scanned codebase, runs the normal analysis in the original process, and reports the issues from both together. A baseline made with `--set-baseline` or `--update-baseline` in this mode keeps both kinds of issues, and the exit code is non-zero if either kind remains. It needs the `pcntl` and `posix` extensions and a writable temporary directory. If the mode is turned on with `--combined-taint-analysis` and they are missing, Psalm stops with an error; if it is turned on only in `psalm.xml`, Psalm prints a warning and runs without taint analysis, so a shared config still works on machines that can't fork. `--dump-taint-graph` is not supported in this mode. If the taint analysis process fails (it is killed, crashes or sends back unreadable results), Psalm prints why and exits with code 1 instead of reporting a run without taint issues. If Psalm itself is stopped or killed, the taint analysis process and its workers stop too, within about a second.

The results are meant to match a normal run plus a `--taint-analysis` run. These differences are known:

 - **Plugin hooks run twice.** Both analyses run the plugin hooks, so a plugin with side effects outside Psalm (writing a file, counting calls, printing) does them in both processes. Hooks that run while the codebase is scanned and populated run once, and any taint sources and sinks they add are used by the taint analysis.
 - **`AfterAnalysis` hooks see both kinds of issues**, where each separate run would show them only its own kind.
 - **Taint data added while populating is visible to the normal analysis.** A hook that adds taint sources to stored types (for example in `AfterCodebasePopulated`) leaves them on those types for both analyses, as a `--taint-analysis` run does. The normal analysis doesn't report anything from them.
 - **Issue handler suppressions are counted across both analyses.** With `findUnusedIssueHandlerSuppression`, an `<issueHandlers>` suppression counts as used if either analysis used it. A normal run alone would report a suppression of a `Tainted*` issue as unused, and a `--taint-analysis` run alone would report one of any other issue. Only a suppression that neither analysis used is reported.
 - **The exit code with `--report` and `GITHUB_WORKFLOW`.** A `--taint-analysis` run exits 0 when it writes its issues to a report file on GitHub Actions. A combined run exits 2 if any issue is left, as a normal run does.
 - **Memory.** The taint analysis process starts as a copy-on-write fork of the scanned codebase, and the original process waits for it, so the two analyses do not run at the same time and the waiting process mostly shares its memory with the fork. On a ~5,600-file codebase, the peak memory of the whole process tree was 9.5 GB in combined mode against 9.7 GB for a `--taint-analysis` run, so plan for the memory a taint analysis run needs.
 - **Time.** The run takes about as long as a normal run plus a taint analysis run, minus one scan, because the two analyses run one after the other.


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
