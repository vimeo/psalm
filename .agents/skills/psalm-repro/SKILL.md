---
name: psalm-repro
description: Use when reproducing a Psalm bug from the CLI, showing a test fails on the base branch, comparing behaviour between a branch, master, 6.x or an old release (e.g. 7.0.0-beta19), or measuring Psalm's speed and memory (self-analysis, WordPress taint benchmark).
---

# CLI repros, version comparisons and measurements

## Scratch-project repro

1. Make a fresh dir in the scratchpad with a `src/` and a minimal `psalm.xml`:

   ```xml
   <?xml version="1.0"?>
   <psalm errorLevel="1" findUnusedCode="false" findUnusedVariablesAndParams="false">
       <projectFiles><directory name="src"/></projectFiles>
   </psalm>
   ```

   - Extension stubs (mongodb, …) need
     `<enableExtensions><extension name="mongodb"/></enableExtensions>`.
   - A stubbed library goes in `vendor/` + `<extraFiles>`, never in `<projectFiles>`:
     stubs don't override project-dir classes.
   - For taint modes see skill `taint-analysis`.
2. Run
   `cd <dir> && php /path/to/worktree/psalm --no-cache --output-format=compact`
   (or `-c <dir>/psalm.xml --root=<dir>`). Suppress `MissingPureAnnotation` /
   `ClassMustBeFinal` noise.
3. `ls` the repro files first: an unmatched glob can abort the rest of the command (zsh)
   or be passed on literally so the command acts on a nonexistent path (bash), and a missing file looks exactly like
   "no issue". Empty output means Psalm didn't run.
4. Grep both `^ERROR` and `^SECURITY` (taint issues), and watch for crashes (exit 11).

To show a new test fails on the base branch, either use a CLI repro or run **just that
one test case** with phpunit in a base worktree (`--filter 'testValidCode@caseName'`, or
`tests/Taint --filter '@caseName'` for TaintTest; see CLAUDE.md). Never run the whole
test file.

## Comparing versions

- Reference points: `origin/master`, `origin/6.x`, and **7.0.0-beta19**, the last release
  of the eager template model, which is the bar for regressions since type variables
  (#11875).
- `git worktree add /tmp/psalm-<ref> <ref>`, then give it a vendor:
  - `cp -R vendor /tmp/psalm-<ref>/` (never a symlink: composer's PSR-4
    autoloader resolves `Psalm\` through the real path and loads the main checkout's
    classes, "Cannot redeclare class");
  - or `composer install` in the worktree if it will run phpunit, if the main `vendor/`
    may be stale, or for 6.x (its dependencies differ);
  - bin/ci/test-with-real-projects.sh needs `composer install --no-dev`.
- Run each worktree's `./psalm` on the same scratch project and diff the outputs.
- Always `cd <worktree> &&` in the same command. Don't use `git stash` to get a "without
  the change" build: use `git diff > f.patch && git apply -R f.patch`, or a second
  worktree.
- For behaviour-preserving refactors, diff full `--monochrome` output (traces included)
  before and after.

## Measuring time and memory

- "used N MB of memory" is `memory_get_peak_usage()` of the **main process only**. Taint
  resolution runs there after the workers merge, so it tracks taint cost. For total RSS,
  wrap `./psalm` itself (not `timeout`) in `/usr/bin/time -l` on macOS (bytes) or
  `/usr/bin/time -v` on Linux (kbytes; GNU `time`, not the shell builtin).
- Self-analysis: `./psalm --no-cache` with `psalm.xml.dist` (taint on). Don't pass
  `--threads`: Psalm detects the CPU count itself. To
  measure without taint, set `runTaintAnalysis="false"`; removing the attribute isn't
  enough, it defaults to true. Expect two ComplexMethod errors to appear then, since they
  depend on taint edges.
- Reference numbers (Apple Silicon Mac, 18 GB RAM, 12 CPUs, measured with `--threads=8`): 7.0.0-beta24 20 s / 1.2 GB;
  6.x ~9 s / 0.43 GB; master mid-October 2026 ~27 s / 2.7 GB with taint, ~15 s / 0.6 GB
  without. Times don't transfer to other machines; on a different one, measure
  `origin/master` as the baseline in the same session. Memory figures transfer roughly.
  Treat a big jump as a regression worth reporting.
- Run measurements twice, on an idle machine (on a laptop, on AC power: see the platform
  table in CLAUDE.md), and never alongside another self-analysis. With swap nearly full,
  master's self-analysis doesn't finish at all.
- Taint benchmark corpus: a WordPress core clone (errorLevel 8, `allFunctionsGlobal`)
  with `--taint-analysis --long-progress`. Expect about ±12 findings of
  run-to-run variance from `mt_rand()` condition ids in `Algebra::negateFormula()` /
  CallAnalyzer, even single-threaded.

## Reading CI logs

- `gh run view <id> --log-failed`. Use `gh run list --commit <sha>` rather than
  `--branch`, which can be stale.
- CircleCI `test-with-real-projects`: read the failing action's `output_url` from the
  v1.1 API (`/project/gh/<owner>/psalm/<build>`).
