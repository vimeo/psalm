# Using Psalm with AI agents

Psalm ships an [MCP](https://modelcontextprotocol.io) server, `psalm-mcp`, and an
[agent skill](https://agentskills.io) that teaches agents to run Psalm and fix what it reports. The plugins below set
both up, together with the [language server](language_server.md). They run the Psalm of the project, which loads the
project's autoloader and plugins as `vendor/bin/psalm` does, so install it first:

```bash
composer require --dev vimeo/psalm
vendor/bin/psalm --init
```

## Claude Code

```text
/plugin marketplace add vimeo/psalm#agent-plugins
/plugin install psalm@psalm
```

The plugin adds the MCP tools, the skill, and the language server, which shows Claude Psalm's errors after each edit.
The `agent-plugins` branch gets each Psalm release, and `/plugin marketplace update psalm` fetches the latest.

## OpenCode

```bash
opencode plugin add 'github:vimeo/psalm#agent-plugins::path:psalm/opencode'
```

Or load the plugin of the installed Psalm, in `opencode.json`:

```json
{
  "plugins": ["./vendor/vimeo/psalm/skills/psalm/opencode"]
}
```

The plugin adds the MCP tools and the skill. OpenCode 1 takes the same path under `"plugin"`. It also starts the
language server if `opencode.json` has an `"lsp"` section. OpenCode 1 doesn't load skills from plugins, so copy
`vendor/vimeo/psalm/skills/psalm` to `.agents/skills/psalm` there.

## Other agents

Any MCP client can start `psalm-mcp` in the project directory, or pass the directory with `--root`:

```bash
claude mcp add psalm -- php vendor/bin/psalm-mcp          # Claude Code, without the plugin
codex mcp add psalm -- php vendor/bin/psalm-mcp           # Codex
```

```json
{
  "mcpServers": {
    "psalm": {
      "command": "php",
      "args": ["vendor/bin/psalm-mcp"]
    }
  }
}
```

That is `.cursor/mcp.json` for Cursor and `.gemini/settings.json` for Gemini CLI. With the PHAR, the command is
`php psalm.phar --mcp`. `psalm-mcp --help` lists the options: `--config`, `--use-baseline`, `--php-version`, ….

Agents that read skills find the skill in `vendor/vimeo/psalm/skills/psalm`; copy it to `.agents/skills/psalm` or
`.claude/skills/psalm`.

## Tools

`psalm-mcp` keeps the codebase in memory, like the language server, and reloads the files that changed on disk before
each call. Given `src/Greeter.php`:

```php
<?php
namespace Acme\SampleProject;

final class Greeter
{
    public function name(?User $user): string
    {
        return $user->getName();
    }
}
```

`psalm_check` with `paths: ["src/Greeter.php"]` returns the issues of the file in a fraction of a second:

```text
ERROR: PossiblyNullReference - src/Greeter.php:8:23 - Cannot call method getName on possibly null value (CWE-476) (see https://psalm.dev/083)
        return $user->getName();
```

Without `paths`, it runs `psalm` on the whole project, which also reports unused code and taint paths between files.
The server answers other calls while that run lasts, and stops it if the agent cancels the call (except on Windows,
where it handles one call at a time).

`psalm_inspect` with `file: "src/Greeter.php", line: 8, text: "getName"` shows the type Psalm infers there, and what
the name refers to:

```text
`getName()` at 8:23
  type: string

`getName` at 8:23
  symbol: Acme\SampleProject\User::getName, declared at src/User.php:14:21
    public function getName(): string
```

`psalm_references` with `symbol: "Acme\SampleProject\User::getName"` lists the uses of a class, function, method,
property or constant:

```text
1 reference to Acme\SampleProject\User::getName:
src/Greeter.php:8:23 return $user->getName();
```

`psalm_explain` with `issue: "PossiblyNullReference"` (or `83`) returns the documentation of the issue type, like
[`psalm --explain`](#explaining-an-issue).

## Running `psalm` from an agent

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

### What changes under an agent

| | Agent | To get the usual behaviour |
|---|---|---|
| Progress | none | `--long-progress` |
| Colours | none | `FORCE_COLOR=1` |
| Output format | `compact` | `--output-format=console` |
| Footer | result, fixable and explain lines instead of time, memory and type coverage | unset the agent's variable: `env -u CLAUDECODE vendor/bin/psalm` |

Other output formats (`json`, `sarif`, `count`, …), reports written with `--report`, and `--explain`, `--help` and `--version` print the same with or without an agent.

### How Psalm detects an agent

Psalm looks for the variable each agent sets, such as `CLAUDECODE`, `CURSOR_AGENT`, `CODEX_THREAD_ID`, `GEMINI_CLI` or `COPILOT_CLI` (the full list is in [`CliUtils::runningUnderAiAgent()`](https://github.com/vimeo/psalm/blob/master/src/Psalm/Internal/CliUtils.php)). An agent marker wins over `CI`, because some agents set `CI=1` for the commands they run ([Cursor](https://prod.cursor.com/docs/agent/tools/terminal)).

For an agent Psalm doesn't know, set `AI_AGENT`:

```bash
AI_AGENT=my-agent vendor/bin/psalm
```

### Explaining an issue

```bash
vendor/bin/psalm --explain=PossiblyNullArgument
```

prints the documentation of the issue type for the installed Psalm version, offline: what it means, an example and how to fix it. It also takes a shortcode or a psalm.dev link ([`--explain`](command_line_usage.md#explaining-an-issue)).

### Applying fixes

```bash
vendor/bin/psalm --alter --issues=MissingReturnType --dry-run
```

prints the diff of the fix and writes nothing. Without `--dry-run` it writes the files; run Psalm again afterwards. See [Fixing code](../manipulating_code/fixing.md).

### Reading these docs as an agent

Every page is available as Markdown: start from [`llms.txt`](https://psalm.dev/llms.txt), and append `.md` to a page path (`https://psalm.dev/docs/running_psalm/issues/PossiblyNullArgument.md`) or send `Accept: text/markdown`. Fetching one page costs fewer tokens than the whole documentation.
