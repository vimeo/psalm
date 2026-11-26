# Psalm from agent hooks

Set up when the user asks for Psalm to run on every edit the agent makes. The recipe is in the section
"Running Psalm after each edit" of `vendor/vimeo/psalm/docs/running_psalm/ai_agents.md`: a Claude Code
`.claude/settings.json` entry, the `.claude/hooks/psalm.sh` script, an optional `Stop` hook that runs the
whole project, and how to check that the hook fires. Copy it from there.

What the recipe does:

- A `PostToolUse` hook with the matcher `Edit|Write` runs Psalm on the edited `.php` file and maps Psalm's
  exit code to the hook's: `0` stays silent, `2` (issues) goes to the agent through stderr and exit `2`,
  `1` (Psalm failed) is a non-blocking error.
- A `Stop` hook runs the whole project when the agent finishes, and keeps the agent working while issues
  remain.

Limits to tell the user:

- A run on single files misses unused code (`UnusedClass`, `PossiblyUnusedMethod`, …) and taint paths
  that cross files. The full `vendor/bin/psalm` run stays the gate before finishing.
- The `PostToolUse` hook doesn't fire when a `Bash` command rewrites a file.
- Hook configuration is the user's to change: propose it, don't add it unasked.
