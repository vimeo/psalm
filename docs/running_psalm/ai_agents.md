# Running Psalm from AI coding agents

When an AI coding agent runs Psalm, Psalm notices and changes its output: no progress, no colours, one line per issue and a footer that tells the agent what to do next. There is nothing to configure.

## What an agent needs

```bash
vendor/bin/psalm                          # whole project: the gate, run it after your last edit
vendor/bin/psalm src/Foo.php src/Bar.php  # only these files: for iterating
vendor/bin/psalm --explain=PossiblyNullArgument
vendor/bin/psalm --alter --issues=MissingReturnType --dry-run
```

* Exit status `0` means no issues. `2` means issues were found. `1` means Psalm itself failed (bad config, crash), so read its message: it says nothing about your code. Read the status straight after the command, because a pipe such as `| tail` reports its own ([exit status](command_line_usage.md#exit-status)).
* A run on single files does not report unused code (`UnusedClass`, `PossiblyUnusedMethod`, …) or taint paths that cross files. Only the whole-project run does.
* After changing `psalm.xml`, stubs or `composer.json`, add `--no-cache` once.

## How Psalm detects an agent

Psalm treats a run as an agent run when one of these environment variables is set (not empty, not `0`, not `false`):

| Variable | Agent |
|---|---|
| `CLAUDECODE`, `CLAUDE_CODE` | Claude Code |
| `CURSOR_AGENT`, `CURSOR_TRACE_ID` | Cursor |
| `GEMINI_CLI` | Gemini CLI |
| `CODEX_SANDBOX`, `CODEX_THREAD_ID` | OpenAI Codex CLI |
| `COPILOT_CLI` | GitHub Copilot CLI |
| `OPENCODE_CLIENT`, `OPENCODE` | OpenCode |
| `AMP_CURRENT_THREAD_ID` | Amp |
| `AUGMENT_AGENT` | Augment |
| `CLINE_ACTIVE` | Cline |
| `TRAE_AI_SHELL_ID` | TRAE AI |
| `ANTIGRAVITY_AGENT` | Google Antigravity |
| `PI_CODING_AGENT` | Pi coding agent |
| `REPL_ID` | Replit |
| `AI_AGENT` | any agent (the [vendor neutral convention](https://github.com/vercel/vercel/tree/main/packages/detect-agent)) |
| `AGENT=goose` | Goose |
| the file `/opt/.devin` | Devin |

`AGENT` only counts with the value `goose`, because the bare name is too common in shell setups.

An agent marker wins over `CI`. Some agents set `CI=1` for the commands they run (Cursor does, see its [terminal docs](https://prod.cursor.com/docs/agent/tools/terminal)), and an agent has no use for the phase lines of a CI log.

If your agent is not in the table, force the mode on:

```bash
AI_AGENT=my-agent vendor/bin/psalm
```

No variable turns the mode off, and `AI_AGENT=0` does not cancel `CLAUDECODE`. Undo the single effects with the flags below, or unset the variable for one command:

```bash
env -u CLAUDECODE vendor/bin/psalm
```

## What changes under an agent

| | Human or CI | Agent |
|---|---|---|
| Progress | progress bar, or phase lines in CI | none. `--long-progress` brings the CI phase lines (`Scanning files...`) back |
| Colours | on for a terminal | off. `FORCE_COLOR` turns them on |
| Output format | `console` | `compact`, unless you pass `--output-format` |
| Footer | the `console` summary with time, memory and type coverage | result line, fixable hint and explain line, on `console` and `compact` only |

`--report=FILE` writes the same files as always.

The same project (3 issues), run by a human and by an agent (`CLAUDECODE=1`). The human run is trimmed with `…`:

```
$ vendor/bin/psalm
…
Scanning files (16 threads)...
…
Analyzing files (16 threads)...

E 1 / 1 (100%)
…
ERROR: MissingReturnType - src/Greeter.php:5:21 - Method Greeter::greet does not have a return type, expecting non-falsy-string (see https://psalm.dev/050)
    public function greet(?string $name)

ERROR: PossiblyNullArgument - src/Greeter.php:7:39 - Argument 1 of strtoupper cannot be null, possibly null value provided (see https://psalm.dev/078)
        return 'Hello, ' . strtoupper($name);

ERROR: UndefinedMethod - src/Greeter.php:12:16 - Method Greeter::sya does not exist (see https://psalm.dev/022)
        $this->sya();

------------------------------
3 errors found
------------------------------
Psalm can automatically fix 1 of these issues.
Run Psalm again with 
--alter --issues=MissingReturnType --dry-run
to see what it can fix.
------------------------------

Checks took 0.18 seconds and used 39.498MB of memory
Psalm was able to infer types for 100% of the codebase
```

```
$ CLAUDECODE=1 vendor/bin/psalm
ERROR src/Greeter.php:5:21 MissingReturnType [*]: Method Greeter::greet does not have a return type, expecting non-falsy-string
ERROR src/Greeter.php:7:39 PossiblyNullArgument: Argument 1 of strtoupper cannot be null, possibly null value provided
ERROR src/Greeter.php:12:16 UndefinedMethod: Method Greeter::sya does not exist
3 issues
[*] 1 fixable with --alter --issues=MissingReturnType (add --dry-run to preview)
Explain an issue: psalm --explain=<IssueType>, or https://psalm.dev/docs/running_psalm/issues/<IssueType>.md
```

Commands that do not report issues print the same with or without an agent: `--explain`, `--help`, `--version`. So do the other output formats (`json`, `sarif`, `count`, …), which stay machine readable and have no footer.

## Reading the output

```
ERROR src/Greeter.php:5:21 MissingReturnType [*]: Method Greeter::greet does not have a return type, expecting non-falsy-string
│     │                    │                 │    └ message
│     │                    │                 └ [*]: --alter can fix this issue
│     │                    └ issue type
│     └ file:line:column
└ ERROR, or INFO for info issues
```

| Footer line | Meaning |
|---|---|
| `3 issues` | The result line. Always printed, so a clean run prints `0 issues`. Info issues hidden by `--show-info=false` are counted here: `0 issues, 1 info issue hidden (--show-info=true shows them)` |
| `[*] 1 fixable with --alter --issues=MissingReturnType (add --dry-run to preview)` | Only when some issues are marked `[*]`. Run this command to see the fix ([Applying fixes](#applying-fixes)). `--no-suggestions` leaves out this line and the `[*]` markers |
| `Explain an issue: psalm --explain=<IssueType>, …` | Printed once, only when there are issues. Replaces the link after each issue ([Explaining an issue](#explaining-an-issue)) |

A clean run:

```
$ CLAUDECODE=1 vendor/bin/psalm
0 issues
```

The time, memory and type coverage lines are left out. `--stats` and `--debug-performance` still print.

## Explaining an issue

`--explain` prints the documentation of an issue type bundled with the installed Psalm: what it means, an example and how to fix it. It works offline and needs no config file. It also takes a shortcode or a psalm.dev link ([Explaining an issue](command_line_usage.md#explaining-an-issue)).

```bash
vendor/bin/psalm --explain=PossiblyNullArgument
```

````
# PossiblyNullArgument

Emitted when calling a function with a value that’s possibly null when the function does not expect it

```php
<?php

function foo(string $s): void {}
foo(rand(0, 1) ? "hello" : null);
```
…
````

## Applying fixes

`--alter` fixes issues Psalm marks with `[*]`. Preview first, it prints a diff and writes nothing:

```bash
vendor/bin/psalm --alter --issues=MissingReturnType --dry-run
```

```
1) src/Greeter.php:5
--- a/src/Greeter.php
+++ b/src/Greeter.php
@@ -2,7 +2,10 @@
 
 final class Greeter
 {
-    public function greet(?string $name)
+    /**
+     * @psalm-return non-falsy-string
+     */
+    public function greet(?string $name): string
     {
         return 'Hello, ' . strtoupper($name);
     }
```

Review the diff, run the same command without `--dry-run` to write the files, then run Psalm again. Name the issue types in `--issues` (a comma separated list, or `all`). `--php-version`, `--safe-types` and the other options are in [Fixing code](../manipulating_code/fixing.md).

## Reading these docs as an agent

Every page of these docs is available as Markdown:

```bash
# the index of all pages, with one line each
curl https://psalm.dev/llms.txt

# one page: append .md to its path
curl https://psalm.dev/docs/running_psalm/issues/PossiblyNullArgument.md

# or ask for Markdown on the normal URL
curl -H "Accept: text/markdown" https://psalm.dev/docs/running_psalm/command_line_usage/
```

Fetch the one page you need, starting from `llms.txt`. It costs fewer tokens than the whole documentation. For an issue type, `vendor/bin/psalm --explain=<IssueType>` gives the same text for the installed version without network access.
