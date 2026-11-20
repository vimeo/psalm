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
   - **Stub images:** 6.x still builds floating stub images, master builds them from
     `bin/stubs/image_lock.php`. Keep master's side of the stub flow, even where the merge
     raised no conflict:
     `git checkout HEAD -- .github/workflows/build-stubs.yml bin/stubs/build_docker.php bin/stubs/gen_callmap.sh bin/stubs/gen_base_callmap.php bin/stubs/image_lock.php dictionaries/autogen/`.
     Port 6.x changes of these files by hand (`git diff HEAD...MERGE_HEAD -- <files>` lists
     them), e.g. new `EXTENSIONS` or `PINS` in `build_docker.php` or a fix in
     `gen_base_callmap.php`, but never 6.x's `dictionaries/autogen/`: the stubs workflow on
     the PR branch regenerates the affected versions. The
     `Check PHP syntax` job fails while a stamp in `dictionaries/autogen/CallMapInputs.php`
     doesn't match.
3. Push the merge commit to a temporary branch on your fork, open a **draft** PR against
   master (`gh pr create --repo vimeo/psalm --base master --draft`, plus a `release:*`
   label), and wait for green CI.
4. Push the same commit to `origin/master`. That closes the PR as merged. Delete the
   temporary branch.
5. **Composer build patch:** after a push to 6.x or master, the "Build docker image for
   stubs" workflow regenerates the callmaps and other `composer build` files, and its
   `push-composer-build` job commits any difference to the branch ("Update the files
   composer build generates") and dispatches CI on it. On master, a difference in
   `bin/stubs/image_lock.php` or `dictionaries/autogen/` goes to a pull request from
   `stubs-refresh/master` instead. Pull before pushing again. If that
   job fails, run `bin/ci/apply-composer-build-patch.sh` on a checkout at the commit the
   run built (it finds the run by commit), commit, and push.
