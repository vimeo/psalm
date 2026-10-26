---
name: pr-description
description: Use when writing the description of a vimeo/psalm pull request (gh pr create / gh pr edit), or when asked to reformat, tidy or shorten an existing PR description, including PRs opened by other people. Defines the house format (one-line bug + Before/After code blocks taken from PR test cases that assert the fix) and how to verify them.
---

# PR descriptions

## Format

As brief as possible: the reader only cares which bug is fixed, shown with code. The shape
(the bug below is made up, only the layout matters):

````markdown
Psalm reports a false `PossiblyNullReference` after `assert($x !== null)` inside a closure.

**Before**
```php
<?php
function f(?Foo $x): void {
    $g = function () use ($x): void {
        assert($x !== null);
        $x->bar(); // PossiblyNullReference
    };
}
```

**After**
```php
        $x->bar(); // no issue
```
````

- **One sentence** naming the bug, as the user sees it (wrong issue, missed issue, crash,
  wrong inferred type), not the implementation.
- A **Before** block: the code of a test case of the PR that asserts the fix (see
  below), plus what master reports, written as trailing `// IssueName` (or `// IssueName: short
  message` when the message matters) comments on the lines where the issue appears.
- An **After** block with **only the lines whose result changes**, commented with the
  new result (`// no issue`, `// TaintedHtml`, `// InvalidReturnType`, or the new inferred
  type).
- **Several bugs in one PR:** one short `###` heading per bug, each with its own one-line
  summary and Before/After pair.
- **No PHP example possible** (CI, memory, performance, crash in tooling): one plain line,
  e.g. "Self-analysis no longer hangs in taint resolution." Numbers only if they *are* the
  fix ("cuts self-analysis memory from 20 GB to 3 GB").
- Keep closing keywords for issues the PR fixes (`Fixes #1234`) on their own last line,
  so GitHub links and closes them.
- Leave out: background and motivation, design notes, "stacked on / combines / merged
  #…", measurements that aren't the point, review follow-ups, checklists, test lists,
  changelog prose, and any AI attribution.

## Every example is a test case that asserts the fix

- Each Before/After example must correspond to a test case of the PR, and that test case
  must **assert the fix**: it fails on master and passes on the branch, because it checks
  exactly the result the After block shows (see "Tests must assert the change" in
  CLAUDE.md for how tests silently fail to do that).
- The example is that test's code. If the test is long, the description may show only
  the lines that matter, as long as the test asserts what they show. Never show a snippet
  no test covers.
- If no test case makes a short, clear example, add one to the PR first. Specific,
  easy-to-follow examples beat abstract ones (`Foo`/`bar` is fine; `T1`/`T2` chains are
  not).
- **Verify by running that single test case** (`--filter 'testValidCode@caseName'`, or
  `tests/Taint --filter '@caseName'`; see CLAUDE.md) on master (must fail) and on the
  branch (must pass). Copy the issue names in the Before comments from master's actual
  output. Never write invented or "illustrative" output.
- Taint PRs: the test case should show source and sink in a few lines
  (`echo $_GET['x'];` → `// TaintedHtml`).

## Reformatting an existing description (including other people's PRs)

Only when asked, and only for PRs in repos you may edit (vimeo/psalm and the psalm org:
editing someone else's PR body needs maintainer rights).

1. Read the current body, title and diff:
   `gh pr view <n> --repo vimeo/psalm --json title,body,author,files` and
   `gh pr diff <n> --repo vimeo/psalm`.
2. Work out the bug(s) actually fixed from the **diff and the tests it adds**, not only
   from the prose. Pick the examples from the PR's test cases, and check that those tests
   assert the fix. If none does, or none makes a usable example, tell the user and
   suggest a test case to add; don't push
   tests to someone else's PR unless asked.
3. Keep everything the author wrote that matters to reviewers in the new format: the bug
   summary, issue links / closing keywords, and any explicit note about a BC break or a
   behaviour change users must know about (one line at the end). Drop the rest.
4. Verify the examples by running each one's test case on master and on the PR head
   (`gh pr checkout <n>` in a separate worktree, or `git fetch origin pull/<n>/head`).
5. Show the new body to the user before replacing someone else's. Then:
   `gh pr edit <n> --repo vimeo/psalm --body-file <file>`. Don't change the title unless
   asked; if it's misleading, suggest a new one.
6. Don't touch the code, labels or reviews of the PR as part of this.
