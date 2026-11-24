# Psalm perf and issue-diff harness

Every non-draft PR to `master` or `6.x` that changes more than docs, tests or CI (changes to `perf.yml` count) runs Psalm on a pinned corpus of real projects (`corpus.yml`) with the merge-base (base) and the PR head (head) on the same runner, and reports peak memory, wall time, weighted issues and the exact issues added or removed (job summary and the `perf-report` artifact).

- ❌ fail (only this fails the check): Psalm memory more than 5 % above base (not when the PR changes `composer.json`); head timed out or ran out of memory while base used less than half of that limit; any other head crash.
- ⚠️ warn: a timeout or memory crash with base already above half of the limit, wall time of a project more than 10 % above base after a re-run, wall time summed over the compared projects more than 5 % above base (neither when the PR changes `composer.json`), a missing or invalid result, a setup failure, or 💥 (base crashed or timed out, so the project is not comparable).
- ✅ pass: otherwise. Weighted issues never change the verdict.

An issue removed and added at the same file, line and column is listed under **Changed in place**, as a `diff` block: the old message on a `-` line, the new one on a `+` line, the text both share trimmed alike, namespaces dropped when the short name is unambiguous. The same change in several files is one hunk, and `{self}` then stands for the class named after each file:

```diff
@@ 6 places PossiblyInvalidArgument @@
  src/Symfony/Component/Config/Definition/Builder/BooleanNodeDefinition.php:40
  src/Symfony/Component/Config/Definition/Builder/EnumNodeDefinition.php:73
  …
- …possibly different type NodeInterface|(TParent:{self} as NodeParentInterface|null) provided
+ …possibly different type NodeInterface|(TParent:VariableNodeDefinition as NodeParentInterface|null) provided
```

The report has a collapsed **Hot files** block, only when some project has a file whose analysis CPU grew by at least 0.2 s and 20 % from base to head: up to 10 such files per project, with the CPU of the scan stages, analysis.files and taint, taken from the first run of each side. It is informational and never changes the verdict. A line below it lists the projects without data and why (an old merge-base or a 6.x PR without `--debug-performance[=PATH]`, a missing or invalid profile).

The profiles of both sides are in the `perf-report` artifact (`profiles/<project>.base.json` and `.head.json`); open them at https://psalm.dev/profile/.

The measured runs are profiled with `--debug-performance=PATH`, which adds the same small CPU and memory cost to both sides. If either side does not support the option, or the PR changes `src/Psalm/Internal/Perf`, neither side is profiled.

The sticky PR comment is posted by `perf-comment.yml` (`workflow_run`), which GitHub only runs from the default branch: until this lands on `master`, `6.x` PRs get the job summary and the check but no comment.

Run it manually for a PR with `gh workflow run Perf -f pr=<number>` (only works once the workflow is on the default branch).

Add or bump a project: edit `corpus.yml` (the header lists every field; pin `ref` to a commit SHA) and add it to exactly one job.

Run one job locally (needs `yq`, `jq`, `gtimeout` on macOS; both checkouts need `composer install --no-dev`, base and head may be the same directory):

```bash
bin/perf/run-shard.sh --job small --base ../psalm-base --head "$PWD" --base-sha "$(git -C ../psalm-base rev-parse HEAD)" --head-sha "$(git rev-parse HEAD)" --out /tmp/perf-out --work /tmp/perf-work
```
