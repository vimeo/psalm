# Running Psalm

Once you've set up your config file, you can run Psalm from your project's root directory with
```bash
./vendor/bin/psalm
```

and Psalm will scan all files in the project referenced by `<inspectFiles>`.

If you want to run on specific files, use
```bash
./vendor/bin/psalm file1.php [file2.php...]
```

### Command-line options

Run with `--help` to see a list of options that Psalm supports.
