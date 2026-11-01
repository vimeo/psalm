# Psalm — working conventions

`master` is the 7.x development branch and the base of feature PRs; `6.x` is maintained
separately and periodically merged into master (skill `merge-6x-into-master`).
Remotes: `origin` = vimeo/psalm. PR branches go to your own fork's remote (find it with
`git remote -v` and `gh api user --jq .login`; add it if missing). Never push PR branches
to `origin`.

Domain skills (load them before touching the area):
- `taint-analysis`: taint graph and resolver, sinks, taint repros and TaintTest pitfalls
- `purity-capabilities`: capability bitmask, purity templates, builtin/SPL purity stubs, memoization
- `hack-conformance`: HHVM oracle harness, Hack coeffect facts, type-variable rules
- `psalm-repro`: scratch-project repros, comparing against master/old releases, timing and memory
- `merge-6x-into-master`: the 6.x → master merge procedure
- `pr-description`: the PR description format, and reformatting existing descriptions
- `plugin-api-bc`: a failing BC check, the roave XML, and the psalm-plugin-api PR

## Fixing bugs and writing features

These come first, before every other rule here:

1. **Fix the root cause.** Find why the bug happens and fix that, not the symptom or the
   one input that showed it. If the same mistake exists elsewhere (sibling classes,
   other call sites), fix it there too.
2. **Think through every way the change could cause bugs**, in edge cases and ordinary
   ones: other callers of the changed code, subclasses and overrides, both analysis
   modes, other PHP versions, caching, ordering. Check each one by adding a test.
3. **Add an explicit test case for each of those cases**, not only for the reported bug.

## Validating changes: CI, not local test runs

- Never run the PHPUnit suite locally to validate a branch. That includes whole test
  files, directories or broad `--filter` patterns, re-running batches of existing
  provider cases through the CLI, and sweeps over provider snippets. Commit, push, and
  let the PR's CI run it. Local runs are slow (~7 min) and noisy: ~73
  `InternalCallMapHandlerTest` cases and `ReportOutputTest::testSarifReport` fail locally
  for environment reasons.
- **Running a single test case is fine, and recommended** for reproducing an issue or
  showing that a new test fails on the base and passes on the branch. Run just that one
  case, never the whole file:
  - provider case: `vendor/bin/phpunit tests/CapabilitiesTest.php --filter 'testValidCode@caseName'`;
  - TaintTest provider case (sharded over `tests/Taint/TaintCases{1..8}Test.php`):
    `vendor/bin/phpunit tests/Taint --filter '@caseName'`. This runs the case in both modes
    (plain and `TrackingUnusedVariables`);
  - test method: `vendor/bin/phpunit tests/TaintTest.php --filter 'testMethodName$'`.

  `@caseName` matches the data-set name exactly; don't append `$` (it then matches
  nothing). "No tests executed!" means a wrong filter, or a stale `vendor/` (PHPUnit
  must be ≥ 10.5: check `vendor/bin/phpunit --version`, and run `composer install` if
  it's older).
- Also fine locally: targeted `./psalm --no-cache` repros on a scratch project (skill
  `psalm-repro`), single-file self-analysis, and one full self-analysis.
- When briefing a subagent, spell all of this out ("single test cases only, no test files
  or suites, no sweeps, `timeout 300` on self-analysis"); a vague "don't run phpunit"
  was not enough.

## Pull requests

- Push the branch to your fork, then `gh pr create --repo vimeo/psalm --base master`.
- Add a `release:*` label (`release:fix`, `release:feature`, `release:internal`,
  `release:docs`, …) or the `label` check fails.
- Then poll `gh pr checks <n>`. For `gh run list`, `--commit <sha>` is reliable;
  `--branch` sometimes returns stale runs.
- Prefer a PR even when asked to fix something on master. A direct push to master was
  once reverted and redone as a PR.
- **Description:** follow skill `pr-description` (one-line bug + Before/After code blocks
  from a test case of the PR that asserts the fix, nothing else). Use it to write new
  descriptions and to reformat existing ones when asked.
- **"Check backward compatibility" fails:** follow skill `plugin-api-bc`. Real BC breaks
  get a PR to psalm/psalm-plugin-api that bumps its version and adds the changelog entry.
  False positives (e.g. bit-constant values and aggregates that change because a member
  was *added*, which is not a BC issue) are check bugs: fix them in
  `.roave-backward-compatibility-check.xml` in the same PR. Never leave the check red as
  "expected".

## Merging and releasing (maintainers with merge rights)

- In any repo of github.com/psalm (plugins, codeception-psalm-module, psalm-plugin-api,
  …), review the diff yourself, wait for green CI, merge, and **tag a release** of every
  plugin you changed (fixture/CI/dependency-only changes too), following each repo's
  versioning. This includes PRs whose maintainer of record is someone else. It does not
  extend to third-party orgs (php-stubs, …).
- Never use `--admin` to bypass red required checks unless asked for that specific PR.
- Shipping a plugin whose users see InvalidDocblock errors from broken upstream stubs is
  fine: users report them upstream. Don't hold releases for upstream stub fixes.

### Task lists (`actions.md`, `actions_2.md`: untracked, in the repo root)

- PRs in the **Merge** section have already been reviewed by whoever wrote the list.
  Follow the file's bugbot gating, then merge them all into one temporary branch, check
  it contains each PR's current head, and fast-forward push it to `origin/master`
  **yourself in the main session**. Don't relay the push authorization to a subagent.
- **Modify** section PRs are not authorized for merge: make the changes in the current
  chat and open the PR in Brave (see the platform table).
- If the auto-mode classifier denies the master push ("Merge Without Review"), stop and
  ask for approval rather than retrying.

## Agents and subagents

- Do fixes, reviews and PR work in the main session. Spawn a subagent only when asked to
  or for a genuinely independent, bounded task, and say so first.
- To review several PRs, use one subagent with an ordered target list (most urgent first,
  saying which to drop if time runs short), not one subagent per PR.
- Never use the Workflow tool unless explicitly asked for a workflow.
- Parallel agents running Psalm contend for RAM (one self-analysis can take most of an
  18 GB machine): follow the self-analysis lock rule below, and tell subagents the
  testing rules above explicitly.

## No workarounds

Never hide an issue instead of fixing it:
- no `@psalm-suppress`, `issueHandlers` or baseline entries added to dodge a problem;
- no `ignoreFiles` to dodge `DuplicateClass`, and no `BeforeAddIssue` filtering plugins;
- no skipping stub docblock issues, no `COMPOSER_NO_SECURITY_BLOCKING`-style env bypasses;
- no regenerating a baseline to paper over a Psalm bug;
- no changing fixtures to dodge behaviour, and no reading Psalm internals via reflection
  because an API is missing.

Fix the root cause in the code, the stubs (send stub bugs upstream) or Psalm itself, or
add the missing plugin API. If a proper fix is out of reach, say so and ask. List any
workaround explicitly in the report.

## Self-analysis (`./psalm` on this repo)

- `psalm.xml.dist` has `runTaintAnalysis="true"`, so self-analysis includes taint
  resolution. Removing the attribute doesn't turn it off; set it to `"false"`. Shepherd
  (`./psalm --shepherd`) is the only CI job that runs it.
- Full run: `timeout 300 ./psalm --no-cache --output-format=compact`. Don't pass
  `--threads`: Psalm detects the CPU count itself. Master
  takes roughly 30–180 s. A run past ~5 min is a hang, not slowness: in Shepherd's log a
  "runner has received a shutdown signal" during "Resolving taint graph" means a resolver
  blowup, not issues.
- One self-analysis can use many GB of RAM (up to ~16 GB has been seen), so never run two
  at once. Parallel agents serialize on the lock file `/tmp/psalm-self-analysis.lock`
  with the platform's lock command (see the table):
  `flock /tmp/psalm-self-analysis.lock timeout 300 ./psalm ...` on Linux,
  `lockf -k /tmp/psalm-self-analysis.lock timeout 300 ./psalm ...` on macOS.
- A file-subset run (`./psalm <files>`) misses class-level issues
  (`MissingImmutableAnnotation`) and reports spurious `UnusedPsalmSuppress` /
  `UnusedBaselineEntry`. Before removing a suppression or claiming self-analysis is
  clean, do a full run and diff it against a full run on `origin/master`.
- `ComplexMethod`/`ComplexFunction` are computed from the variable-use graph's edge stats
  (`StatementsAnalyzer`, `getEdgeStats()`: size > 200, mean path > 70, convergence > 1.1).
  A data-flow change can flip them on methods the PR never touched, making an existing
  `@psalm-suppress ComplexMethod` unused. Print the method's `getEdgeStats()` under base
  and branch to confirm no flow was lost before touching the suppression.
- Master enforces purity annotations on its own code (`MissingPureAnnotation`).
  `./psalm --alter --issues=MissingPureAnnotation <files>` adds them.
- `--set-baseline` still printing new issues and exiting 2 is deliberate (#5838, a "fix"
  was reverted in #6051); the written baseline is complete. `--update-baseline` only
  shrinks entries.

## Tests

- **Tests must assert the change.** Every test case a PR adds or changes for the bug or
  feature itself must fail on the base branch and pass on the PR, because it checks the
  exact behaviour being fixed or added. Prove it by running that single test case on
  both (see above). Edge-case tests that guard the change may pass on the base. Ways a
  test silently asserts nothing:
  - a valid-code case for a missed-issue fix (it passes on master too): use an invalid
    case asserting the issue;
  - an inferred-type fix without `'assertions' => ['$x' => 'expected type']` (key
    `'$x==='` compares the exact type id);
  - `error_message` only matches the **first** issue raised, as a substring
    (`'InvalidReturnType'` matches any line). If master already reports that issue type
    somewhere in the snippet, pin it with the location
    (`'Issue - src' . DIRECTORY_SEPARATOR . 'somefile.php:13:31'`) or message text;
  - `ignored_issues`/`error_levels` suppressing the issue under test, or a higher
    `php_version`/missing extension making the code path not run;
  - harness differences: e.g. TaintTest provider cases don't initialize plugins (skill
    `taint-analysis`).
- Data-provider entries are passed **by position**; string keys are ignored. To pin a PHP
  version write `'assertions' => [], 'ignored_issues' => [], 'php_version' => '8.1'`
  (valid) or `'error_levels' => [], 'php_version' => ...` (invalid). `'php_version'`
  alone becomes `$assertions` and TypeErrors. TaintTest is the exception:
  `testInvalidCode(string $code, string $error_message, string $php_version = '8.0')`.
- Test cases analyse PHP 7.4 by default, and native symbols are version-gated, so tests
  using newer classes/methods need `php_version`.
- Put each docblock tag on its own line. `/** @psalm-pure @param ... */` silently drops
  every tag after the first.
- Never start a docblock line in `src/` with an example `@psalm-...` tag; Psalm parses it
  as a real annotation.
- `DocumentationTest` and invalid-code provider cases only see the **first** issue raised.
  When a change makes one location report several kinds, check every case and docs
  example that reaches it, and assert multi-issue results in a dedicated test method
  (`throw_exception = false` + `IssueBuffer::getIssuesDataForFile()`).
- `IssueBuffer::finish()` calls `exit(2)` when errors remain; test reported errors end to
  end (`tests/EndToEnd/PsalmEndToEndTest.php`).
- Resolving merge conflicts in big provider files (TaintTest especially): git factors
  shared trailing lines (`'error_message' => ...,`, `],`) out of the hunk, so "keep both"
  can drop an entry's `error_message` or duplicate a `],`, and `php -l` still passes.
  Afterwards, check with a script that every 12-space-indented `'name' => [ ... ]` entry
  of both parents appears verbatim in the result, unless the other side changed it on
  purpose (compare with the merge base).

## Code style

- Match the surrounding code: comment density, naming, idiom.
- Comments are concise and only explain what isn't obvious from the code:
  - don't restate the code in prose;
  - don't mention approaches that were tried, considered, replaced or removed, or
    anything that only makes sense in the context of an agent's iteration history;
  - prefer deleting an unnecessary comment over expanding or rewording it;
  - document an idea once, where it naturally belongs.
- Documentation (`docs/`, issue pages, user-facing docblocks): more examples, less text.
  Show behaviour with a code example whose lines end in their result (`// TaintedNosql`,
  `// safe`, `// no issue`), cases that stay reported included, and keep the prose around
  it to a sentence. Check each line's result on the branch before writing it.
- Hot loops (`TaintFlowGraph::connectSinksAndSources()`, `TaintFlowResolution`): keep the
  branch structure inline and call `getChildNodes()` directly in each branch. Don't
  collect nodes into intermediate arrays (allocation per iteration), and avoid new
  single-use helpers (they make diffs harder to read). Keep the order of side-effecting
  calls. Verify refactors by diffing full `--monochrome` output, traces included, on
  probe projects.

## Debugging pitfalls

- STDERR from analysis gets swallowed by `--output-format=compact`/grep. Debug with
  `file_put_contents('/path', ..., FILE_APPEND)`.
- Empty Psalm output means it didn't run (wrong cwd, missing `vendor/`, crash), not
  "clean". Only trust "No errors found" or an errors summary. Detect crashes (exit 11,
  segfault) when counting issues.

## Shell, worktrees and git

- Write commands that work in both zsh (macOS default) and bash (usual on Linux); check
  with `echo $0` if unsure:
  - Quote globs meant for the tool, not the shell (`grep --include='*.php'`). In zsh an
    unmatched glob aborts the whole `&&` chain ("no matches found"); in bash it is passed
    on literally, so the command quietly acts on a path that doesn't exist. Either way
    the files you expect may not be there. Clean scratch
    dirs with `find dir -name '*.php' -delete`, and `ls` repro files before concluding
    anything from an absent issue.
  - Brace variables next to a colon: `"${b}:branch"`, `"${r}:tests/TaintTest.php"`. In
    zsh `$b:l`, `$r:t`, … are history modifiers and mangle refspecs; braces are harmless
    in bash.
  - Word-splitting a variable of args: zsh needs `${=args}`, bash splits unquoted
    `$args`. Prefer an array (`args=(a b); cmd "${args[@]}"`), which behaves the same in
    both.
- The working directory resets between Bash calls. Start every command that edits,
  commits or runs Psalm in a worktree with `cd <worktree> &&` (or use absolute paths),
  and check `git status` in the intended worktree before committing.
- All worktrees of this repository (sibling `../psalm-*` checkouts, `/tmp/psalm-*`, …)
  share one stash list.
  Never use `git stash` there, and never pop or drop entries you didn't create; the list
  may hold other people's old stashes. To compare with and without a change, use
  `git diff > f.patch` + `git apply -R`, or a second worktree.
- Put scratch worktrees and probe dirs under `/tmp` (on macOS it is `/private/tmp`; both
  paths work) or the session scratchpad. `/tmp` may be wiped on reboot on both platforms,
  so don't keep anything there that must survive.
- A worktree that runs Psalm needs its own `vendor/`. Copy it with `cp -R`; a symlink makes
  composer's autoloader load the main checkout's classes. If the worktree will run phpunit
  or the main `vendor/` may be stale against `composer.json`, `composer install` in it.

## Platform differences

The instructions here and in `.agents/skills/` target macOS and Linux. Where a command
differs, use the right column for the current OS (`uname` prints `Darwin` or `Linux`):

| Task | macOS | Linux |
|---|---|---|
| Serialize self-analysis runs | `lockf -k /tmp/psalm-self-analysis.lock CMD` | `flock /tmp/psalm-self-analysis.lock CMD` (util-linux) |
| Time limit | `timeout` from Homebrew coreutils (`gtimeout` if only that is on PATH) | `timeout` (coreutils, preinstalled) |
| Peak RSS of a command | `/usr/bin/time -l CMD` ("maximum resident set size", bytes) | `/usr/bin/time -v CMD` ("Maximum resident set size (kbytes)"; GNU `time` package, not the shell builtin) |
| RAM | `sysctl -n hw.memsize` | `free -g` |
| On AC power? | `pmset -g batt` | `cat /sys/class/power_supply/*/online` (or `upower -d`) |
| Start Docker | `open -a Docker`, then wait for `docker info` | `sudo systemctl start docker` (user in the `docker` group), or Docker Desktop |
| Open a URL in Brave | `open -a "Brave Browser" URL` | `brave-browser URL` (or `xdg-open URL` for the default browser) |
| In-place sed | `sed -i '' ...` | `sed -i ...` (or use a short PHP/Python script on both) |
| Temp dir | `/tmp` (= `/private/tmp`) | `/tmp` |

Every agent on one machine must use the same lock command, so pick it by OS, not by
whatever happens to be installed.
