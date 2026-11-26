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

## Telling your agent to use Psalm

Put the rules in the instruction file your agent reads. Four lines are enough:

```markdown
## Psalm
- While you work, run `vendor/bin/psalm` on the files you changed. Before you finish, run it on the whole project and fix every issue.
- Before fixing an issue type you don't know, run `vendor/bin/psalm --explain=<IssueType>`.
- Don't add `@psalm-suppress`, `issueHandlers` or baseline entries to make an issue go away without asking me first.
- Exit status `1` means Psalm itself failed, not that the code has issues.
```

Which file depends on the agent:

| Agent | Project instructions |
|---|---|
| Claude Code | `CLAUDE.md`. `AGENTS.md` is read only when there is no `CLAUDE.md`, so with both files start `CLAUDE.md` with `@AGENTS.md` ([memory docs](https://code.claude.com/docs/en/memory#agents-md)) |
| OpenAI Codex | `AGENTS.md` ([AGENTS.md guide](https://learn.chatgpt.com/docs/agent-configuration/agents-md)) |
| Cursor | `AGENTS.md` in the project root or a subdirectory, or `.cursor/rules/*.mdc` ([rules](https://cursor.com/docs/rules)) |
| GitHub Copilot | `.github/copilot-instructions.md`, `AGENTS.md`, or a `CLAUDE.md` or `GEMINI.md` in the repository root ([custom instructions](https://docs.github.com/en/copilot/how-tos/configure-custom-instructions/add-repository-instructions)) |
| Gemini CLI | `GEMINI.md`. To read `AGENTS.md` add it to the `context.fileName` setting ([GEMINI.md files](https://geminicli.com/docs/cli/gemini-md/)) |

Start a new session after editing the file. Claude Code reads `CLAUDE.md` "at the start of every session" and Codex builds its instruction chain when it starts ("restart Codex in the target directory"). In Gemini CLI, `/memory reload` rescans the files.

An instruction file is context, not enforcement ([Claude Code](https://code.claude.com/docs/en/memory#claude-md-vs-auto-memory)). To run Psalm every time, use a [hook](#running-psalm-after-each-edit).

## Running Psalm after each edit

An instruction can be skipped. A [Claude Code hook](https://code.claude.com/docs/en/hooks) cannot: it runs Psalm after every file the agent edits and hands the issues back, so the agent fixes them in the same turn. Add a `PostToolUse` hook to `.claude/settings.json` (shared with the team; `.claude/settings.local.json` stays on your machine):

```json
{
  "hooks": {
    "PostToolUse": [
      {
        "matcher": "Edit|Write",
        "hooks": [
          {
            "type": "command",
            "command": "${CLAUDE_PROJECT_DIR}/.claude/hooks/psalm.sh",
            "args": [],
            "timeout": 120
          }
        ]
      }
    ]
  }
}
```

Claude Code sends the event as JSON on stdin. For `Edit` and `Write` the edited file is `tool_input.file_path`. Save the script as `.claude/hooks/psalm.sh` and make it executable with `chmod +x .claude/hooks/psalm.sh` (it needs `jq` on the `PATH`):

```bash
#!/bin/bash
# .claude/hooks/psalm.sh
file=$(jq -r '.tool_input.file_path // empty')

case "$file" in
  *.php) ;;
  *) exit 0 ;;
esac
[ -f "$file" ] || exit 0

cd "$CLAUDE_PROJECT_DIR" || exit 1
output=$(vendor/bin/psalm --no-progress --output-format=compact "$file" 2>&1)
status=$?

case "$status" in
  0) exit 0 ;;
  2) printf '%s\n' "$output" >&2; exit 2 ;;
  *) printf 'Psalm failed (exit %s): %s\n' "$status" "$output" >&2; exit 1 ;;
esac
```

The script prints Psalm's output to stderr and exits with `2` because that is the only way the agent sees it. The hooks reference says: "Claude never sees stderr from a hook that exits 0. [...] To surface a warning to Claude from a `PostToolUse` or `PostToolUseFailure` hook, exit 2 instead so Claude sees the stderr even though the tool already ran" ([Exit code 0](https://code.claude.com/docs/en/hooks#exit-code-0)).

| Psalm | Hook | Effect |
| ----- | ---- | ------ |
| `0` | `0` | Nothing happens. |
| `2` | `2` | Claude Code shows the hook's stderr (the issues) to the agent. The edit has already happened, so this is feedback, not a block. |
| `1` | `1` | A non-blocking error: Claude Code shows a `hook error` notice with the message and carries on. The message says nothing about the edited code (see [exit status](command_line_usage.md#exit-status)). |

Any exit code other than `0` or `2` is non-blocking ([Exit code output](https://code.claude.com/docs/en/hooks#exit-code-output)), which is why a broken Psalm setup does not stall the agent.

Check the setup in two steps. Type `/hooks` in Claude Code: it opens a read-only list of your hooks and where each one is defined ([the `/hooks` menu](https://code.claude.com/docs/en/hooks#the-%2Fhooks-menu)). Then run the script by hand with the JSON Claude Code would send. It should print the issues and `2`:

```bash
echo "{\"tool_input\":{\"file_path\":\"$PWD/src/Greeter.php\"}}" \
  | CLAUDE_PROJECT_DIR=$PWD .claude/hooks/psalm.sh; echo $?
```

### Checking the whole project when the agent finishes

A single file run cannot report unused code (`UnusedClass`, `PossiblyUnusedMethod`, …) or taint paths that cross files. A `Stop` hook runs when Claude finishes responding, so it can run the whole project as the gate. On a `Stop` hook, exit code 2 "prevents Claude from stopping, continues the conversation" ([Exit code 2 behavior per event](https://code.claude.com/docs/en/hooks#exit-code-2-behavior-per-event)), and the stderr is what Claude gets. [Pyrefly recommends](https://pyrefly.org/blog/pyrefly-agentic-loop/) the same `Stop` hook with `>&2 || exit 2`. Add it next to the `PostToolUse` entry (a `Stop` hook has no matcher):

```json
{
  "hooks": {
    "Stop": [
      {
        "hooks": [
          {
            "type": "command",
            "command": "${CLAUDE_PROJECT_DIR}/.claude/hooks/psalm-stop.sh",
            "args": [],
            "timeout": 300
          }
        ]
      }
    ]
  }
}
```

```bash
#!/bin/bash
# .claude/hooks/psalm-stop.sh
[ "$(jq -r '.stop_hook_active // false')" = "true" ] && exit 0

cd "$CLAUDE_PROJECT_DIR" || exit 1
output=$(vendor/bin/psalm --no-progress --output-format=compact 2>&1)
status=$?

case "$status" in
  0) exit 0 ;;
  2) printf '%s\n' "$output" >&2; exit 2 ;;
  *) printf 'Psalm failed (exit %s): %s\n' "$status" "$output" >&2; exit 1 ;;
esac
```

`stop_hook_active` is `true` when Claude is already continuing because of a stop hook. The script lets the agent stop then, so an issue the agent cannot fix alone (one that needs your decision) ends the turn instead of repeating it. Claude Code also ends the turn after eight stop hook continuations in a row ([Stop](https://code.claude.com/docs/en/hooks#stop)).

Either hook works alone. The `PostToolUse` hook is fast feedback, the `Stop` hook is the gate. The `PostToolUse` hook does not fire when a `Bash` command rewrites a file, but the `Stop` hook still sees the result. Keep the whole project run before finishing in your [instructions](#telling-your-agent-to-use-psalm), in CI and locally.

## The Psalm agent skill

An [agent skill](https://code.claude.com/docs/en/skills) is a folder with a `SKILL.md` that the agent loads when the task matches the `description` in its header. Psalm ships one in `skills/psalm`, written for the Psalm version it ships with. It goes further than this page: a plan for a first run on an old codebase, how to fix each kind of issue, annotations, taint findings, baselines.

```
skills/psalm/
  SKILL.md                     workflow (baseline, fix, gate), how to run Psalm, rules for fixing issues
  references/
    annotations.md             type annotation cheat sheet
    bug-report.md              is it a Psalm bug, and how to report it
    cleanup.md                 large cleanups, one subagent per file (with subagent-fix-prompt.md)
    cli.md                     output formats, cache, threads, --stats
    config.md                  psalm.xml, stubs, plugins, extensions
    debugging.md               what type Psalm infers, crashes and hangs
    fixing.md                  Psalter, suppressions, baselines
    hooks.md                   this page's hook recipe, for the agent to set up on request
    strictness.md              raising errorLevel and stricter options
    taint.md                   SECURITY findings
  scripts/
    summary.php                counts per issue type and file from a JSON report
    sandbox.php                checks a minimal repro on psalm.dev (the file leaves your machine)
```

The skill tells the agent to leave suppressions, `psalm.xml` changes and baseline changes to the user.

### Installing it

Psalm's `composer.json` declares `"extra": {"skills": {"source": "skills"}}`. Composer ignores that key by itself. It is the convention of the third party Composer plugin [llm/skills](https://github.com/roxblnfk/skills), which copies the skills of your dependencies into the directory your agent reads. From its README:

```bash
composer require --dev llm/skills
composer skills:init --quick --target=.claude/skills --alias=.agents/skills
```

`--target` is where the skills are written. Claude Code reads project skills from `.claude/skills/` ([skills docs](https://code.claude.com/docs/en/skills#choose-where-skills-load)), Codex scans `.agents/skills/` ([Codex skills](https://learn.chatgpt.com/docs/build-skills)), and the plugin's default target is `.agents/skills/`. `--alias` mirrors the target at a second path. `composer skills:init` writes a `skills.json` to commit. A package you require directly is trusted, so `vimeo/psalm` needs no extra setting.

The skill is copied from `vendor/vimeo/psalm/skills`, so it matches the installed Psalm. The plugin syncs again after every `composer install` and `composer update` (not when the `CI` environment variable is set). To sync by hand: `composer skills:update`.

Without Composer plugins, the [`skills` CLI](https://github.com/vercel-labs/skills) (skills.sh, Node.js 22.20 or later) installs from a path or a repository. Point it at the installed package to get the matching version:

```bash
npx skills add ./vendor/vimeo/psalm
```

`npx skills add vimeo/psalm` takes the skill from the GitHub repository instead (`owner/repo` is a source format in the [README](https://github.com/vercel-labs/skills#install-a-skill)), which can be a different Psalm version than yours. `npx skills update` updates installed skills. After a `composer update`, run the `./vendor/vimeo/psalm` command again.

If Claude Code does not list a new skill, run `/reload-skills` ([Edit a skill during a session](https://code.claude.com/docs/en/skills#edit-a-skill-during-a-session)).

### Skill or instructions?

Use both. An instruction file is in the agent's context in every session. A skill is loaded only when the agent decides it is relevant (Claude Code: skills "only load when you invoke them or when Claude determines they're relevant to your prompt", [memory docs](https://code.claude.com/docs/en/memory)). Pyrefly saw that a skill on its own does not always lead to a type check, even with a clear description, and recommends a skill plus an `AGENTS.md` directive ([Adding Pyrefly to your agentic loop](https://pyrefly.org/blog/pyrefly-agentic-loop/)). So keep the [four lines](#telling-your-agent-to-use-psalm) in `AGENTS.md` or `CLAUDE.md`, and add a fifth that names the skill:

```markdown
- Use the `psalm` skill whenever you run Psalm or fix its issues.
```

In Claude Code you can also invoke it by hand: the folder name is the command, so `/psalm` ([skills docs](https://code.claude.com/docs/en/skills)).

## Troubleshooting

**Psalm prints the human output to my agent.** Psalm doesn't [know the agent](#how-psalm-detects-an-agent). Run `AI_AGENT=my-agent vendor/bin/psalm`, or set the variable in the agent's environment.

**The hook does nothing.**

* `/hooks` does not list it: check the path in `.claude/settings.json`, and that the file is valid JSON.
* `jq` is not on the `PATH` of Claude Code: the script prints `jq: command not found`, exits `0`, and Claude never sees stderr of a hook that exits `0`. Install `jq`.
* The script is not executable: `chmod +x .claude/hooks/psalm.sh`.
* The edit went through `Bash` (`sed -i`, a formatter): the `Edit|Write` matcher does not see it.
* The file does not end in `.php`: the script skips it.
* Run the script by hand as shown [above](#running-psalm-after-each-edit). Its output is what the agent would get.

**The agent sees `Psalm failed (exit 1)`.** Psalm itself failed. Run `vendor/bin/psalm` yourself and read the message: usually a missing `psalm.xml`, a bad config or a PHP error.

**The agent does not use the skill.** Name it in your instructions, as shown in [Skill or instructions?](#skill-or-instructions), or call `/psalm` in Claude Code. A skill installed after the session started may need `/reload-skills`.

## Reading these docs as an agent

Every page is available as Markdown: start from [`llms.txt`](https://psalm.dev/llms.txt), and append `.md` to a page path (`https://psalm.dev/docs/running_psalm/issues/PossiblyNullArgument.md`) or send `Accept: text/markdown`. Fetching one page costs fewer tokens than the whole documentation.
