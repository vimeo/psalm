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

`psalm_explain` with `issue: "PossiblyNullReference"` (or `83`) returns the documentation of the issue type.
