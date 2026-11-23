# Finding out why Psalm is slow

`--debug-performance` prints where a run spent its time and memory: stages, workers, the slowest files, the slowest statements and functions, and the time spent in plugin and provider handlers.

```bash
./vendor/bin/psalm --no-cache --debug-performance
```

`--debug-performance=PATH` also writes everything it measured to a JSON file, whatever the `--output-format`:

```bash
./vendor/bin/psalm --no-cache --debug-performance=psalm-perf.json
```

The path needs the `=`: after a space it is a file to analyse. Use `--no-cache` to measure a cold run, and leave out `--debug`, which distorts the timings. Times per file are most reliable with `--threads=1`.

Measuring costs some time itself: about 1.4 % CPU on WordPress with taint analysis at `--threads=1`, most of it from timing each call of a plugin or Psalm handler. Without the option Psalm measures nothing and runs as fast as before.

## The dump

Times are integer microseconds (`_us`), sizes integer bytes (`_bytes`).

|Key|Content|
|---|---|
|`env`|Psalm, PHP and OS versions, extensions, thread counts, cache mode, plugin classes, the names of the options given, and `traceparent` when the `TRACEPARENT` environment variable holds a valid W3C trace context|
|`totals`|Wall and CPU time, peak memory, file and issue counts|
|`stages`|The main process, stage by stage|
|`pools`, `workers`|The forked processes: tasks, busy time, CPU time, memory|
|`files`|One record per file scanned or analysed|
|`hot`|The 50 statements and the 50 function-likes that took longest, per place, with the number of `passes` over them (a constructor is analysed twice) and the time of all of them. Only a statement execution (own time) or a function-like pass of 1 ms or more is counted|
|`hooks`|Time per handler of a plugin or of Psalm, and per hook|
|`counts`|Class-likes, functions, methods, parsed files, parser cache hits and misses|
|`taint`|Size of the taint graph|

The file is also a Trace Event Format file (`traceEvents`: the stages and the files, in one lane per process): open it in https://ui.perfetto.dev or https://www.speedscope.app to see what the workers did when.

`schema` changes when a key is removed or changes its meaning.

## Privacy

Psalm sends nothing; the dump is a local file. It holds file paths as Psalm prints them in issues, class and method names, line numbers and versions, but no source code and no issue messages. Review it before you attach it to a public issue.

A run that does not finish writes no dump. `psalter`, `psalm-refactor` and the language server don't support the option.
