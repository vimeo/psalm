# Large cleanups

For hundreds of issues, a whole-codebase cleanup or raising `errorLevel`. The main agent keeps only the report
and one verdict line per file; each file is fixed by a subagent that reads that file and its issues and
nothing else.

## Phase 0 — Resolve the commands

Subagents may start in a shell where `php` is missing or points elsewhere. Resolve both commands once and use
the absolute forms everywhere, subagents included:

```bash
php -r "echo PHP_BINARY;"          # <php>, e.g. /usr/bin/php or C:\php\php.exe
<php> vendor/bin/psalm --version   # <psalm> = "<php> vendor/bin/psalm", or the PHAR path
```

`<report>` is a JSON report path outside the repository, e.g. in the system temp dir.

## Phase 1 — Baseline and scope (ask first)

```bash
<psalm> --no-progress --output-format=count --report=<report>
<php> scripts/summary.php <report> --limit=40
```

Show the user the counts per type and the files with the most issues, then ask how far to go and wait for
the answer:

- **Everything**: every issue in every file.
- **Part**: issue types (`--type=…`) or directories (`--file=…`) the user picks.
- **Report only**: stop here.

Skip the question only when the request already says it. Name the decisions the cleanup can't make alone,
so the user can answer them up front: suppressions, a baseline, deleting public code.

## Phase 2 — Fix, one file per subagent, in sequence

Repeat until no in-scope issue is left:

1. Pick the in-scope file with the most issues from the current report.
2. List its issues: `<php> scripts/summary.php <report> --file=<path> --snippets --limit=200`.
3. Spawn **one** subagent with [subagent-fix-prompt.md](subagent-fix-prompt.md), every placeholder
   filled, the rest verbatim. Wait for its verdict.
4. Refresh the report with the Phase 1 command; Psalm's cache keeps the rerun short.

One subagent at a time, and one file per subagent. A fixed return type removes issues in its callers and can
add new ones elsewhere, so the next file is picked from a fresh report; parallel subagents would edit against
stale results, and parallel Psalm runs compete for memory. Parallel work needs one git worktree per subagent.

Collect the `needs-decision` items from the verdicts and keep going with the other files.

## Phase 3 — Gate

```bash
<psalm> --no-progress --no-cache --output-format=count
```

Done when it exits `0` for the agreed scope. Report to the user: issues fixed per type (baseline minus now),
the `needs-decision` items with each subagent's proposal, and every deleted public member.
