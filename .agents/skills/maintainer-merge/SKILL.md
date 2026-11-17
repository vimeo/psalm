---
name: maintainer-merge
description: Use when merging PRs or tagging releases as a maintainer with merge rights (vimeo/psalm or any github.com/psalm repo), or when working through an actions.md / actions_2.md task list.
---

# Merging and releasing (maintainers with merge rights)

- In any repo of github.com/psalm (plugins, codeception-psalm-module, psalm-plugin-api,
  …), review the diff yourself, wait for green CI, merge, and **tag a release** of every
  plugin you changed (fixture/CI/dependency-only changes too), following each repo's
  versioning. This includes PRs whose maintainer of record is someone else. It does not
  extend to third-party orgs (php-stubs, …).
- Never use `--admin` to bypass red required checks unless asked for that specific PR.
- Shipping a plugin whose users see InvalidDocblock errors from broken upstream stubs is
  fine: users report them upstream. Don't hold releases for upstream stub fixes.

## Task lists (`actions.md`, `actions_2.md`: untracked, in the repo root)

- PRs in the **Merge** section have already been reviewed by whoever wrote the list.
  Follow the file's bugbot gating, then merge them all into one temporary branch, check
  it contains each PR's current head, and fast-forward push it to `origin/master`
  **yourself in the main session**. Don't relay the push authorization to a subagent.
- **Modify** section PRs are not authorized for merge: make the changes in the current
  chat and open the PR in Brave (see the platform table).
- If the auto-mode classifier denies the master push ("Merge Without Review"), stop and
  ask for approval rather than retrying.

