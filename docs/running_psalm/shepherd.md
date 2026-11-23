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
* how many issues of each type are not errors, as `issue_counts` (only types and counts, no code): those your [baseline](dealing_with_code_issues.md#using-a-baseline-file) covers, and those your error level or an [issue handler](dealing_with_code_issues.md) reports as `info`
* how the analysis ran, as `analysis`: whether it covered the whole project, how many files it analysed, the target PHP version, and whether unused code detection and taint analysis were on
* the type coverage counts, your error level, and the versions of Psalm, the PHP parser and the PHP that runs Psalm

Because of the code snippets, don't use `--shepherd` for code that must not leave your servers. Without `--shepherd` (or `PSALM_SHEPHERD`) Psalm sends nothing.

## Sending reports to your own endpoint

Pass a URL instead, and Psalm posts the same JSON there:

```bash
./vendor/bin/psalm --shepherd=https://example.com/psalm-report
```

Psalm posts to exactly that URL, and adds `https://` when the value has no scheme.

### Report format

For example, with `build`, `git` and the issue trimmed to a few of their keys:

```json
{
  "build": {"CI_NAME": "github-actions", "CI_REPO_OWNER": "psalm", "CI_REPO_NAME": "example", "CI_BRANCH": "master", "CI_PR_NUMBER": "42"},
  "git": {"branch": "main", "head": {"id": "0123456789abcdef0123456789abcdef01234567", "author_name": "Psalm"}, "remotes": [{"name": "origin", "url": "https://github.com/psalm/example.git"}]},
  "issues": [{"severity": "error", "type": "InvalidReturnStatement", "file_name": "src/B.php", "line_from": 4, "snippet": "    return 'b';", "…": "…"}],
  "issue_counts": {
    "baselined": {"InvalidReturnStatement": 2, "InvalidReturnType": 2},
    "info": {"InvalidReturnType": 1, "MissingReturnType": 1}
  },
  "analysis": {
    "full": true,
    "file_count": 4,
    "target_php_version": "7.1",
    "find_unused_code": true,
    "taint_analysis": false
  },
  "coverage": [0, 12],
  "level": 1,
  "versions": {"psalm": "dev-master@f2ccf76759784652b74313def4ac90a9322ffe9a", "parser": "v5.9.0@9e33da9553fe7786f0962b35f4e4ecf01be89def", "php": "8.5.12"}
}
```

Every issue is in exactly one place: an error is in `issues`, an issue your baseline covers is counted in `issue_counts.baselined` (also when your config reports it as `info`), and any other `info` issue is counted in `issue_counts.info`. A group without issues is `{}`.

`coverage` is `[mixed, non-mixed]` expression counts. `analysis.target_php_version` is the version Psalm analyses your code for, and `versions.php` is the one that runs Psalm. A run on given paths has `"full": false` and counts only those paths, so follow trends with full runs.

Newer versions of Psalm only add keys. Ignore the keys you don't know, and treat a missing key as unknown, not as zero.
