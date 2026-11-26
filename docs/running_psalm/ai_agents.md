# Running Psalm from AI coding agents

Psalm detects an AI coding agent and prints output for it: no progress, no colours, one line per issue and a short footer. There is nothing to configure.

```
$ vendor/bin/psalm
ERROR src/Greeter.php:5:21 MissingReturnType [*]: Method Greeter::greet does not have a return type, expecting non-falsy-string
ERROR src/Greeter.php:7:39 PossiblyNullArgument: Argument 1 of strtoupper cannot be null, possibly null value provided
ERROR src/Greeter.php:12:16 UndefinedMethod: Method Greeter::sya does not exist
3 issues
[*] 1 fixable with --alter --issues=MissingReturnType (add --dry-run to preview)
Explain an issue: psalm --explain=<IssueType>, or https://psalm.dev/docs/running_psalm/issues/<IssueType>.md
```

* `[*]` marks the issues [`--alter`](#applying-fixes) can fix.
* A clean run prints `0 issues`.
* Exit status `0` means no issues, `2` means issues were found, `1` means Psalm itself failed (bad config, crash) and says nothing about the code. A pipe such as `| tail` hides it.
* A run on single files is for iterating. Only a run on the whole project reports unused code (`UnusedClass`, `PossiblyUnusedMethod`, …) and taint paths that cross files.

## What changes under an agent

| | Agent | To get the usual behaviour |
|---|---|---|
| Progress | none | `--long-progress` |
| Colours | none | `FORCE_COLOR=1` |
| Output format | `compact` | `--output-format=console` |
| Footer | result, fixable and explain lines instead of time, memory and type coverage | unset the agent's variable: `env -u CLAUDECODE vendor/bin/psalm` |

Other output formats (`json`, `sarif`, `count`, …), reports written with `--report`, and `--explain`, `--help` and `--version` print the same with or without an agent.

## How Psalm detects an agent

Psalm looks for the variable each agent sets, such as `CLAUDECODE`, `CURSOR_AGENT`, `CODEX_THREAD_ID`, `GEMINI_CLI` or `COPILOT_CLI` (the full list is in [`CliUtils::runningUnderAiAgent()`](https://github.com/vimeo/psalm/blob/master/src/Psalm/Internal/CliUtils.php)). An agent marker wins over `CI`, because some agents set `CI=1` for the commands they run ([Cursor](https://prod.cursor.com/docs/agent/tools/terminal)).

For an agent Psalm doesn't know, set `AI_AGENT`:

```bash
AI_AGENT=my-agent vendor/bin/psalm
```

## Explaining an issue

```bash
vendor/bin/psalm --explain=PossiblyNullArgument
```

prints the documentation of the issue type for the installed Psalm version, offline: what it means, an example and how to fix it. It also takes a shortcode or a psalm.dev link ([`--explain`](command_line_usage.md#explaining-an-issue)).

## Applying fixes

```bash
vendor/bin/psalm --alter --issues=MissingReturnType --dry-run
```

prints the diff of the fix and writes nothing. Without `--dry-run` it writes the files; run Psalm again afterwards. See [Fixing code](../manipulating_code/fixing.md).

## Reading these docs as an agent

Every page is available as Markdown: start from [`llms.txt`](https://psalm.dev/llms.txt), and append `.md` to a page path (`https://psalm.dev/docs/running_psalm/issues/PossiblyNullArgument.md`) or send `Accept: text/markdown`. Fetching one page costs fewer tokens than the whole documentation.
