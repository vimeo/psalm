# Overlay analysis

Overlay analysis answers one question: which issues would this project have if some files had other contents, that it does not have now? Mutation testing (a mutant either breaks the types or not), editors and coding agents ask it. The files on disk and the cache are never changed.

```php
<?php // src/A.php
namespace App;

class A
{
    public function foo(): int
    {
        return 1;
    }
}
```

```php
<?php // src/B.php
namespace App;

final class B
{
    public function useA(A $a): int
    {
        return $a->foo();
    }
}
```

```bash
vendor/bin/psalm --record-references        # once, and after every change on disk
sed 's/public function foo/private function foo/' src/A.php > /tmp/A.php
vendor/bin/psalm --overlay=src/A.php=/tmp/A.php --output-format=json
# exit 2
# {"protocol":1,"new_issues":[{"severity":"error","type":"InaccessibleMethod","file_name":"src/B.php","line_from":8,...}],
#  "analyzed_files":["src/A.php","src/B.php"],"not_checked":["unused_code"]}
```

## What counts as new

An issue is new when the analysis with the overlays has it more often than the analysis without them, comparing the file, the type and the message (positions quoted in a message are left out). With `return "1";` already reported in `A::foo()`:

```php
return  "1";       // the same issue on a moved or rewritten line: not new
```

The baseline and `issueHandlers` apply as in a normal run, under the original path. The baseline covers the issues that were there before the overlay first: a new issue becomes `info` only through an entry nothing else uses. `UnusedPsalmSuppress`, `UnusedBaselineEntry` and `UnusedIssueHandlerSuppression` are never new.

Every new issue is listed with its `severity`. The exit status looks at `error` only:

| Exit status | Meaning |
| --- | --- |
| `0` | no new issue with the severity `error` |
| `2` | at least one |
| `255` | no answer: a config error, a missing or out of date reference index, an invalid overlay, a timeout, a crash |

With `--output-format=json` a failure prints `{"protocol":1,"error":{"kind":"...","message":"..."}}`. The kinds are `invalid_request`, `invalid_overlay`, `unsupported`, `references_not_recorded`, `references_stale`, `worker_failed`, `timeout` and `config`.

## Which files are analysed

Psalm analyses the overlaid files, the files their changes can reach, and lists them in `analyzed_files`. A change inside a method body reaches the overlaid file, the classes inheriting from it, the users of its traits and the files including it. A changed visibility also reaches the callers of that member. Another change to existing declarations (a type, a parameter, a default value) also reaches the files that depend on the overlaid file according to the reference index. Added or removed declarations reach every project file. Passes over the whole project (unused code and `ClassMustBeFinal`, taint analysis, purity suggestions) do not run. Those the configuration runs are listed in `not_checked`, unless the request asks for `"whole_project": true`.

`--record-references` makes the normal run write a reference index into the cache. Overlay analysis needs it, and refuses an index when the contents of a project file, the config, `composer.lock` or the baseline differ from what it recorded. Record with the baseline options the overlay runs use (`--use-baseline`, `--ignore-baseline`):

```bash
vendor/bin/psalm --overlay=src/A.php=/tmp/A.php
# exit 255: The cache has no reference index: run `psalm --record-references` first
```

## Several files, inline contents

`--overlays-from=FILE` (or `-` for standard input) takes a JSON request:

```bash
echo '{"overlays": [{"path": "src/A.php", "contents": "<?php ..."}, {"path": "src/B.php", "replacement": "/tmp/B.php"}]}' \
  | vendor/bin/psalm --overlays-from=- --output-format=json
```

Relative paths are relative to the directory Psalm runs in.

Overlays of files that do not exist yet, and `{"path": "...", "deleted": true}`, answer `unsupported` for now. Overlays of the config, the baseline, `composer.json`, `composer.lock` and stub files (preloaded or not) are `invalid_overlay`.

## Timeouts

`--overlay-timeout=SECONDS` stops the analysis and exits with `255` (kind `timeout`). It counts from the start of the process: loading the config and scanning the project count too, and the processes the run starts stop with it. For Infection, set it below Infection's own timeout, and count `0` as escaped, `2` as killed and anything else as not killed.

`"timeout_ms"` in a request read with `--overlays-from` counts from the start of the process as well, and stops the analysis from the moment the request is read.

Without pcntl (Windows), the deadline is PHP's time limit, which counts whole seconds: a run may take up to a second longer than its timeout.

## Protocol versions

The envelope and the issues in it can gain keys without a new `protocol` number. Clients must ignore keys they do not know. A change that removes or redefines a key raises `protocol`.
