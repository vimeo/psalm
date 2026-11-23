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
* how many issues your config reports as `info` (see [issue handlers](dealing_with_code_issues.md)) and the baseline doesn't cover, by issue type, for example `"info": {"MissingReturnType": 4}`. Every issue is in exactly one of `issues` (errors), `baselined` and `info`: an `info` issue the baseline covers is only counted in `baselined`
* how many issues Psalm can fix with `--alter`, by issue type, for example `"fixable": {"InvalidReturnType": 3}`. These count every fixable issue, whether it is an error, `info` or covered by the baseline
* the run itself, for example `"run": {"seconds": 12.34, "peak_memory": 268435456, "threads": 8}`: how long the analysis took, the peak memory in bytes (of the main process only, the one that prints how much memory it used, so forked workers are not included), and the number of threads
* the type coverage counts, your error level, and the versions of Psalm and the PHP parser

Because of the code snippets, don't use `--shepherd` for code that must not leave your servers. Without `--shepherd` (or `PSALM_SHEPHERD`) Psalm sends nothing.

## Sending reports to your own endpoint

Pass a URL instead, and Psalm posts the same JSON there:

```bash
./vendor/bin/psalm --shepherd=https://example.com/psalm-report
```

Psalm posts to exactly that URL, and adds `https://` when the value has no scheme.
