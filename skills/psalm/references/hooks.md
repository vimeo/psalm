# Psalm from agent hooks

Set up when the user asks for Psalm to run on every edit the agent makes. The full recipe, a
`.claude/settings.json` entry and the `.claude/hooks/psalm.sh` script, is in the section "Running Psalm
from agent hooks" of `vendor/vimeo/psalm/docs/running_psalm/command_line_usage.md`. Copy it from there.

What the recipe does:

- A `PostToolUse` hook with the matcher `Edit|Write` runs after each edit. The script reads the edited
  path from the hook's JSON on stdin and skips files that don't end in `.php`.
- It runs `vendor/bin/psalm --no-progress --output-format=compact <file>` on that one file and maps
  Psalm's exit code to the hook's:

  | Psalm | Hook | Effect |
  | ----- | ---- | ------ |
  | `0`, no issues | `0` | Nothing happens. |
  | `2`, issues found | `2` | Claude Code shows the issues (stderr) to the agent. |
  | `1`, Psalm failed | `1` | Non-blocking error with the message. Bad config or a crash, not an issue in the code. |

Limits to tell the user:

- A run on single files misses unused code (`UnusedClass`, `PossiblyUnusedMethod`, …) and taint paths
  that cross files. The full `vendor/bin/psalm` run stays the gate before finishing.
- The hook doesn't fire when a `Bash` command rewrites a file.
- Hook configuration is the user's to change: propose it, don't add it unasked.
