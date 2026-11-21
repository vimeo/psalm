# Using Shepherd

[Shepherd](https://shepherd.dev) is a service that stores the results of Psalm runs from CI for public GitHub projects. Its source is at [github.com/psalm/shepherd](https://github.com/psalm/shepherd).

It gives you:

* a **type coverage history** of your default branch, at `https://shepherd.dev/github/{owner}/{repo}` (for example [vimeo/psalm](https://shepherd.dev/github/vimeo/psalm))
* **badges** for your README
* **review comments** on pull requests (on some CI services, see [below](#pull-request-comments))

## Getting started

Add `--shepherd` to the Psalm command that runs in CI:

```bash
./vendor/bin/psalm --shepherd
```

For example, with GitHub Actions:

```yaml
name: Psalm

on:
  push:
  pull_request:

jobs:
  psalm:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v6
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - run: composer install --no-interaction --no-progress
      - run: vendor/bin/psalm --shepherd
```

The first report from your default branch creates the history page. Psalm prints `Results sent to Shepherd (shepherd.dev)` when the report was accepted, and a warning when it was not (run with `--debug` for details). A failed upload never changes Psalm's exit status, and Psalm stops waiting after 30 seconds.

You can set the environment variable `PSALM_SHEPHERD=1` instead of passing the flag.

Requirements:

* the `curl` PHP extension
* a public GitHub repository
* one of these CI services: Travis CI, CircleCI, AppVeyor, Jenkins, Scrutinizer or GitHub Actions. Psalm recognises them by their environment variables. On any other CI (GitLab CI, Bitbucket Pipelines and so on) nothing is sent, and Psalm warns `Results not sent to Shepherd: no supported CI service detected`.

Shepherd adds a run to the history when it comes from the default branch of the repository, whatever its name. Runs on other branches and on pull requests are not part of the history and the badges.

## Badges

Once Shepherd has a report from your default branch:

```markdown
[![Psalm type coverage](https://shepherd.dev/github/{owner}/{repo}/coverage.svg)](https://shepherd.dev/github/{owner}/{repo})
[![Psalm level](https://shepherd.dev/github/{owner}/{repo}/level.svg)](https://shepherd.dev/github/{owner}/{repo})
```

`https://shepherd.dev/github/{owner}/{repo}/coverage` and `.../level` return the same data as JSON for a [shields.io endpoint badge](https://shields.io/badges/endpoint-badge).

Type coverage is the share of expressions whose inferred type is not `mixed`, the number in Psalm's summary line. See [minimum type coverage](command_line_usage.md#minimum-type-coverage).

## Pull request comments

When a report says which pull request it belongs to, Shepherd posts a review on that pull request:

* a comment on each changed line where Psalm reports an error
* errors on lines the pull request did not change, listed in the review text
* `Psalm didn’t find any errors!` when there are none

Only issues with severity `error` count. Psalm sends the pull request number from Travis CI, CircleCI, AppVeyor and Scrutinizer. It does not send it from Jenkins and from GitHub Actions, so there are no review comments for those two yet.

## What is sent

At the end of the run, Psalm sends one JSON `POST` request to `https://shepherd.dev/hooks/psalm` with:

* **build**: the name of the CI service, build and job ids, repository owner and name, branch, the pull request number where the CI service gives it, and the CI environment variables Psalm recognises it by
* **git**: branch, the last commit (hash, date, subject, and author and committer names **and emails**) and your git remotes (name and URL, so don't put credentials in a remote URL)
* **issues**: every issue of severity `error`, with its type, message, file name, **absolute file path on the CI machine**, line and column numbers and the **source code snippet** around it. Taint analysis issues include their traces.
* **coverage**: the number of expressions with a `mixed` type and with another type
* **level**: your error level
* **versions**: the versions of Psalm and the PHP parser

Shepherd stores the repository, the commit hash and date, the pull request, the issues, the coverage numbers and the level. Because the issues include code snippets, don't use `--shepherd` for code that must not leave your servers (see below). Without `--shepherd` (or `PSALM_SHEPHERD`) Psalm sends nothing.

## Running your own Shepherd

Shepherd is open source, so you can run it for private code. Give Psalm the URL of your server:

```bash
./vendor/bin/psalm --shepherd=https://shepherd.example.com
```

or:

```bash
PSALM_SHEPHERD=https://shepherd.example.com ./vendor/bin/psalm
```

Psalm sends the report to `/hooks/psalm` of that server. A URL with its own path (`https://shepherd.example.com/api/psalm`) is used as it is. Psalm adds `https://` to a value without a scheme. A value of one or two characters (such as the `1` in `PSALM_SHEPHERD=1`) selects shepherd.dev. When Psalm reports a failure it only prints the host of the URL, and masks credentials and query values in `--debug` output, so you can put a token in the URL.

See the [Shepherd README](https://github.com/psalm/shepherd#readme) for the server.
