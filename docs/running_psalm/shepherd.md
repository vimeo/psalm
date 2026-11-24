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
* how many issues of each type are not errors, as `issue_counts` (only types and counts, no code): those your [baseline](dealing_with_code_issues.md#using-a-baseline-file) covers (counted only as baselined, also when your config reports them as `info`), and those your error level or an [issue handler](dealing_with_code_issues.md) reports as `info`; a group without issues is `{}`
* how the analysis ran, as `analysis`: whether it covered the whole project, how many files the results cover, the target PHP version, and whether unused code detection and taint analysis were on
* the type coverage counts, your error level, and the versions of Psalm, the PHP parser and the PHP that runs Psalm

Because of the code snippets, don't use `--shepherd` for code that must not leave your servers. Without `--shepherd` (or `PSALM_SHEPHERD`) Psalm sends nothing.

## Sending reports to your own endpoint

Pass a URL instead, and Psalm posts the same JSON there. The URL must use `http` or `https`.

```bash
./vendor/bin/psalm --shepherd=https://example.com/psalm-report
```

Psalm posts to exactly that URL, and adds `https://` when the value has no scheme.

To check what Psalm would send, for example to test your own endpoint, write the report to a file instead:

```bash
./vendor/bin/psalm --shepherd=file://$PWD/build/psalm-shepherd.json
```

The file holds exactly the JSON Psalm would post, and Psalm prints `Results written to` with its path. Psalm replaces an existing file. As with a URL, Psalm writes it only in CI with a GitHub repository. The path must be absolute.
