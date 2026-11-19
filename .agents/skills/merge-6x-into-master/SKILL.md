---
name: merge-6x-into-master
description: Use when asked to merge vimeo/psalm 6.x into master, or after any push to 6.x or master when the "Build docker image for stubs" workflow's push-composer-build job fails.
---

# Merging 6.x into master

Needs push access to vimeo/psalm (maintainers only).

The result is a plain "Merge remote-tracking branch 'origin/6.x'" commit pushed straight
to `origin/master`, but CI runs on it first.

1. `git fetch origin`, then create a worktree at `origin/master` and run
   `composer install` in it (master's dependencies differ from 6.x's: psalm-plugin-api,
   plugin-phpunit ^0.20, …).
2. `git merge origin/6.x` and resolve conflicts. Expect:
   - **Purity annotations:** master enforces `MissingPureAnnotation`, so new 6.x methods
     and test providers need them:
     `./psalm --alter --issues=MissingPureAnnotation <files>`.
   - **Native symbol availability:** master-only tests using newer native symbols at the
     default PHP 7.4 fail. Set `php_version`, positionally (see CLAUDE.md).
   - **Baseline:** psalm-baseline.xml entries the merge made stale.
   - `.github/workflows/build-stubs.yml` triggers must stay identical on both branches
     (branch pushes only, never tags or releases).
3. Push the merge commit to a temporary branch on your fork, open a **draft** PR against
   master (`gh pr create --repo vimeo/psalm --base master --draft`, plus a `release:*`
   label), and wait for green CI.
4. Push the same commit to `origin/master`. That closes the PR as merged. Delete the
   temporary branch.
5. **Composer build patch:** after a push to 6.x or master, the "Build docker image for
   stubs" workflow regenerates the callmaps and other `composer build` files, and its
   `push-composer-build` job commits any difference to the branch ("Update the files
   composer build generates") and dispatches CI on it. Pull before pushing again. If that
   job fails, run `bin/ci/apply-composer-build-patch.sh` on a checkout at the commit the
   run built (it finds the run by commit), commit, and push.
