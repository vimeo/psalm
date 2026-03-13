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
successfully but found some issues. Any exit status apart from those indicate
some internal problem.

## Shepherd

Psalm currently offers some GitHub integration with public projects.

Add `--shepherd` to send information about your build to https://shepherd.dev.

Currently, Shepherd tracks type coverage (the percentage of types Psalm can infer) on `master` branches.

## Running Psalm faster

To run Psalm up to 50% faster, use the [official docker image](https://psalm.dev/docs/running_psalm/installation/#docker-image).  

Psalm also has a couple of command-line options that will result in faster builds:

- `--threads=[n]` to run Psalm’s analysis in a number of threads
- `--diff` which only checks files you’ve updated since the last run (and their dependents).

In Psalm 4 `--diff` is turned on by default (you can disable it with `--no-diff`).

Data from the last run is stored in the *cache directory*, which may be set in [configuration](./configuration.md).
If you are running Psalm on a build server, you may want to configure the server to ensure that the cache directory
is preserved between runs.

Running them together (e.g. `--threads=8 --diff`) will result in the fastest possible Psalm run.

## Reviewing issues in your IDE of choice

Psalm now offers a `psalm-review` tool which allows you to manually review issues one by one in your favorite IDE.  

```bash
./vendor/bin/psalm-review report.json code|phpstorm|code-server [ inv|rev|[~-]IssueType1 ] [ [~-]IssueType2 ] ...
```

The tool may also be run using the main `psalm` entry point, useful for example when working with the phar:

```bash
./vendor/bin/psalm.phar --review report.json code|phpstorm|code-server [ inv|rev|[~-]IssueType1 ] [ [~-]IssueType2 ] ...
```

`psalm-review` parses the Psalm JSON report in report.json (generated using `vendor/bin/psalm --report=report.json`) and open the specified IDE at the line and column of the issue, one by one for all issues; press enter to go to the next issue, `q` to quit.  

The extra arguments may be used to filter only for issues of the specified types, or for all issues except the specified types (with the `~` or `-` inversion).  

The `rev` or `inv` keywords may be used to start from the end of the report instead of at the beginning.  
