# Using Shepherd

[Shepherd](https://shepherd.dev) stores the results of Psalm runs from CI for public GitHub projects. It shows the type coverage history of your default branch at `https://shepherd.dev/github/{owner}/{repo}` (for example [vimeo/psalm](https://shepherd.dev/github/vimeo/psalm)) and serves badges for your README.

## Getting started

Add `--shepherd` to the Psalm command that runs in CI on your default branch:

```bash
./vendor/bin/psalm --shepherd
```

Setting the environment variable `PSALM_SHEPHERD=1` does the same. Psalm prints `Results sent to Shepherd (shepherd.dev)` when the report was accepted. A failed upload never changes Psalm's exit status.

Psalm detects GitHub Actions and other popular CI services, and elsewhere in CI uses the GitHub remote of the checkout. Outside CI, or without a GitHub remote, nothing is sent and Psalm warns `Results not sent to Shepherd: no CI service with a GitHub repository detected`.

For pull requests, Shepherd also comments on the changed lines where Psalm finds errors, when it can access the repository.

## Badges

```markdown
[![Psalm type coverage](https://shepherd.dev/github/{owner}/{repo}/coverage.svg)](https://shepherd.dev/github/{owner}/{repo})
[![Psalm level](https://shepherd.dev/github/{owner}/{repo}/level.svg)](https://shepherd.dev/github/{owner}/{repo})
```

Type coverage is the share of expressions whose inferred type is not `mixed`, the number in Psalm's summary line.

## What is sent

One JSON `POST` request at the end of the run, with:

* the CI build and the last commit, including the **names and emails** of its author and committer
* every issue of severity `error`, with its file path and the **source code snippet** around it
* how many issues your [baseline](dealing_with_code_issues.md#using-a-baseline-file) covers, by issue type (only the types and counts, no code), for example `"baselined": {"InvalidReturnType": 3, "MixedAssignment": 12}`. It is `{}` when the run uses no baseline (none is configured, or `--ignore-baseline` is given) or the baseline covers none of the issues
* how many entries the baseline applied in this run holds, as `baseline_entries` (for example `17`, and `0` when the run uses no baseline). `baseline_entries` minus the sum of `baselined` is the number of entries no issue used (stale entries)
* how many issues your config reports as `info` (see [issue handlers](dealing_with_code_issues.md)) and the baseline doesn't cover, by issue type, for example `"info": {"MissingReturnType": 4}`. Every issue is in exactly one of `issues` (errors), `baselined` and `info`: an `info` issue the baseline covers is only counted in `baselined`
* the run itself, for example `"run": {"full": true, "files": 1200}`: whether the run covered the whole project, and the number of files it reports on. A run on given paths is partial (`"full": false`) and applies only the baseline entries of those files, so follow trends with full runs
* the analysis settings, for example `"config": {"php_version": "8.2", "find_unused_code": true, "taint_analysis": false}`. `php_version` is the PHP version Psalm analyses your code for: the one given with `--php-version`, `phpVersion` in `psalm.xml`, the one `composer.json` requires, or else the PHP that runs Psalm. `find_unused_code` and `taint_analysis` take the CLI flags into account, not only `psalm.xml`
* the type coverage counts, your error level, and the versions of Psalm, the PHP parser and the PHP that runs Psalm

Because of the code snippets, don't use `--shepherd` for code that must not leave your servers. Without `--shepherd` (or `PSALM_SHEPHERD`) Psalm sends nothing.

## Sending reports to your own endpoint

Pass a URL instead, and Psalm posts the same JSON there:

```bash
./vendor/bin/psalm --shepherd=https://example.com/psalm-report
```

Psalm posts to exactly that URL, and adds `https://` when the value has no scheme.
