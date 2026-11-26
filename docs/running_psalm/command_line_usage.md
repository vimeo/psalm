# Running Psalm

Once you've set up your config file, you can run Psalm from your project's root directory with
```bash
./vendor/bin/psalm
```

and Psalm will scan all files in the project referenced by `<projectFiles>`.

If you want to run on specific files, use
```bash
./vendor/bin/psalm file1.php [file2.php...]
```

## Command-line options

Run with `--help` to see a list of options that Psalm supports.

## Statistics

At the end of a run Psalm prints how long it took, how much memory it used and how much of the code it could infer types for, plus the output of `--stats` and [`--debug-performance`](performance.md) if you give them. With any output format other than `console`, `phpstorm` and `github` (`json`, `checkstyle`, `sarif`, …) this goes to stderr, so that stdout stays the report; `--no-progress` leaves stderr empty.

## Exit status

Psalm exits with status `0` when it successfully completed and found no issues,
`1` when there was a problem running Psalm and `2` when it completed
successfully but found issues or the [type coverage](#minimum-type-coverage) is
below the minimum. Any exit status apart from those indicates some internal problem.

## Minimum type coverage

`--min-type-coverage` (or the [`minTypeCoverage`](configuration.md#mintypecoverage) config attribute) makes Psalm exit with status `2` when the type coverage is below the given percentage:

```bash
./vendor/bin/psalm --min-type-coverage=95
# STDERR: Type coverage 93.41% is below the minimum of 95.00%
```

Type coverage is the number in Psalm's summary line and on [Shepherd](#shepherd): the share of expressions whose inferred type is not `mixed`. Files in a `<directory ignoreTypeStats="true">` aren't counted.

Only full runs are checked: the option can't be combined with file paths, `--set-baseline` or `--update-baseline`, and the config attribute is skipped on such runs.

## Shepherd

[Shepherd](https://shepherd.dev) is a free service for public GitHub projects that stores the results of your CI Psalm runs and shows your type coverage over time, with README badges.

Add `--shepherd` to the Psalm command that runs in CI:

```bash
./vendor/bin/psalm --shepherd
```

The coverage history and the badges use the runs on the default branch of your repository. Psalm reports to Shepherd from GitHub Actions and other popular CI services, and from any other CI when the checkout has a GitHub remote.

See [Using Shepherd](shepherd.md) for badges, what is sent, and how to send reports to your own endpoint.

## Finding out why Psalm is slow

`--debug-performance` (or `--profile`) prints the stages of a run, the slowest files and the hottest statements and functions. `--debug-performance=PATH` also writes the measurements as JSON (the path needs the `=`):

```bash
./vendor/bin/psalm --no-cache --debug-performance=psalm-perf.json
```

See [Finding out why Psalm is slow](performance.md) for how to read the profile.

## Running Psalm faster

To run Psalm up to 50% faster, use the [official docker image](https://psalm.dev/docs/running_psalm/installation/#docker-image).  

Psalm also has a couple of command-line options that will result in faster builds:

- `--threads=[n|auto]` to run Psalm’s analysis in a number of threads (`auto`, the default, uses one per CPU core)
- `--diff` which only checks files you’ve updated since the last run (and their dependents).

In Psalm 4 `--diff` is turned on by default (you can disable it with `--no-diff`).

Data from the last run is stored in the *cache directory*, which may be set in [configuration](./configuration.md).
If you are running Psalm on a build server, you may want to configure the server to ensure that the cache directory
is preserved between runs.

Running them together (e.g. `--threads=8 --diff`) will result in the fastest possible Psalm run.

### Consolidating the cache

`--consolidate-cache` merges the cache entries of the project into an index and a data file per cache, without analysing anything.
On a build server this means saving and restoring a handful of files instead of tens of thousands.

```bash
# restore the cache directory with your CI's cache step
vendor/bin/psalm                      # analyses, writes cache entries as separate files
vendor/bin/psalm --consolidate-cache  # no analysis: merges them into an index and a data file per cache
# save the cache directory with your CI's cache step
```

A run reads the index of a consolidated cache and then only the entries it needs.
Entries written by later runs are stored as separate files again, so consolidate after the last run before saving the cache.
With `ext-zstd` the consolidated cache is compressed with a dictionary made from its entries, which makes it about a third smaller than the separate entries.
With another compressor it takes more disk space than the separate files, because each entry stays compressed on its own.

## Reviewing issues in your IDE of choice

Psalm now offers a `psalm-review` tool which allows you to manually review issues one by one in your favorite IDE.  

```bash
./vendor/bin/psalm-review report.json [code|phpstorm|code-server] [ inv|rev|[~-]IssueType1 ] [ [~-]IssueType2 ] ...
```

The tool may also be run using the main `psalm` entry point, useful for example when working with the phar:

```bash
./vendor/bin/psalm.phar --review report.json [code|phpstorm|code-server] [ inv|rev|[~-]IssueType1 ] [ [~-]IssueType2 ] ...
```

`psalm-review` parses the Psalm JSON report in report.json (generated using `vendor/bin/psalm --report=report.json`) and opens the specified IDE at the line and column of the issue, one by one for all issues; press enter to go to the next issue, `q` to quit.

The IDE argument is optional when running inside a supported IDE's integrated terminal — PhpStorm, VS Code, and code-server are auto-detected via environment variables. When running from an external terminal, the IDE must be specified explicitly.

The extra arguments may be used to filter only for issues of the specified types, or for all issues except the specified types (with the `~` or `-` inversion).  

The `rev` or `inv` keywords may be used to start from the end of the report instead of at the beginning.  
