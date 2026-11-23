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
* how many issues of each type the baseline covers or the config reports as `info`, as `issue_counts` (only the types and counts, no code), for example `"issue_counts": {"baselined": {"InvalidReturnStatement": 3}, "info": {"MissingReturnType": 1}}`. Every issue is either in `issues` above (errors) or counted in exactly one of `baselined` (covered by your [baseline](dealing_with_code_issues.md#using-a-baseline-file), whatever their level) and `info` (reported as `info` by your config, see [issue handlers](dealing_with_code_issues.md)). A group without issues is `{}`
* how many entries the baseline the run applied holds, for example `"baseline": {"entries": 7}`, with `0` when it applied none (no baseline is configured, `--ignore-baseline` is given, or the baseline has no entries for the files analysed). `entries` minus the sum of `issue_counts.baselined` is the number of entries no issue used (stale entries)
* the analysis itself, for example `"analysis": {"full": true, "files": 1200, "php_version": "8.2", "find_unused_code": true, "taint_analysis": false}`: whether the run covered the whole project, the number of files it reports on, the PHP version Psalm analyses your code for (the one given with `--php-version`, `phpVersion` in `psalm.xml`, the one `composer.json` requires, or else the PHP that runs Psalm), and whether unused code detection and taint analysis are on (CLI flags included, not only `psalm.xml`). A run on given paths is partial (`"full": false`) and applies only the baseline entries of those files, so follow trends with full runs
* the type coverage counts, your error level, and the versions of Psalm, the PHP parser and the PHP that runs Psalm

Because of the code snippets, don't use `--shepherd` for code that must not leave your servers. Without `--shepherd` (or `PSALM_SHEPHERD`) Psalm sends nothing.

## Sending reports to your own endpoint

Pass a URL instead, and Psalm posts the same JSON there:

```bash
./vendor/bin/psalm --shepherd=https://example.com/psalm-report
```

Psalm posts to exactly that URL, and adds `https://` when the value has no scheme.
