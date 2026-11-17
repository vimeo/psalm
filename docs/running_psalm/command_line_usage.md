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

## Exit status

Psalm exits with status `0` when it successfully completed and found no issues,
`1` when there was a problem running Psalm and `2` when it completed
successfully but found some issues, or a [type coverage](#minimum-type-coverage)
below the minimum. Any exit status apart from those indicates some internal problem.

## Minimum type coverage

```bash
vendor/bin/psalm --min-type-coverage=95                      # exit status 2 when the type coverage is below 95%
vendor/bin/psalm --min-type-coverage=95 --output-format=json  # the same, the JSON report is unchanged
vendor/bin/psalm --min-type-coverage=95 src/Foo.php          # exit status 1: the option can't be combined with file paths
```

Below the minimum, Psalm writes this to STDERR with any `--output-format` and exits with status `2`:

```
Type coverage 93.41% is below the minimum of 95.00%
```

The minimum is a percentage from 0 to 100 with at most two decimals. The coverage in the message is rounded down,
so 94.999% prints as `94.99%`. The option overrides the [`minTypeCoverage`](configuration.md#mintypecoverage)
config attribute.

Runs with `--set-baseline` or `--update-baseline` don't check the minimum. Runs with file paths
(`psalm src/Foo.php`) skip the config attribute, which isn't an error. Like for issues, a taint analysis run in
GitHub Actions that writes a SARIF report prints the message but exits with status `0`, so that a following step
can upload the report.

Type coverage is the number in the summary line (`Psalm was able to infer types for 93.4127% of the codebase`)
and the one [Shepherd](#shepherd) tracks: the share of the counted expressions whose inferred type is not `mixed`.
Psalm counts assignments to variables and properties, returned values, call arguments, the objects that methods
are called on, arithmetic operations, string concatenations, and array and property fetches. Variable reads and
literals aren't counted.

```php
/** @param mixed $a */
function f($a, int $b): int
{
    $c = $a;      // 1 expression, mixed
    $d = $b + 1;  // 2 expressions (the sum and the assignment), inferred
    return $d;    // 1 expression, inferred
}
// type coverage: 3 of 4 expressions, 75.00%
```

`--stats` lists the coverage of each file. It also lists the files of a directory with `ignoreTypeStats="true"`,
but they aren't counted in the total:

```xml
<projectFiles>
  <directory name="src" />
  <directory name="generated" ignoreTypeStats="true" />
</projectFiles>
```

## Shepherd

Psalm currently offers some GitHub integration with public projects.

Add `--shepherd` to send information about your build to https://shepherd.dev.

Currently, Shepherd tracks type coverage (the percentage of types Psalm can infer) on `master` branches.

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
