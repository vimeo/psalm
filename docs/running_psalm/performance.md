# Finding out why Psalm is slow

`--debug-performance` (short name: `--profile`) is Psalm's built-in self-profiling. It prints where a run spent its time and memory: stages, workers, the slowest files, the slowest statements and functions, and the time spent in plugin and provider handlers.

```bash
./vendor/bin/psalm --no-cache --debug-performance
```

`--debug-performance=PATH` also writes everything it measured to a JSON file, the performance profile:

```bash
./vendor/bin/psalm --no-cache --profile=psalm-perf.json
```

To see it as charts and tables, open the performance report at https://psalm.dev/profile/ and drop the profile on the page; the page reads it in your browser and sends it nowhere.

Use `--no-cache` to measure a cold run, and leave out `--debug`, which distorts the timings. Times per file are most reliable with `--threads=1`.

Measuring costs some CPU itself, from 0 to about 10 %; the upper end is reached with plugins. Without the option Psalm measures nothing and runs as fast as before.

## The profile

Times are integer microseconds (`_us`), sizes integer bytes (`_bytes`).

|Key|Content|
|---|---|
|`env`|Psalm, PHP and OS versions, whether this is the process Psalm restarted (`restarted`; the restart itself happens before it and is not in the profile), extensions, thread counts, cache mode, the process id (`pid`), the names of the options given, and `traceparent` when the `TRACEPARENT` environment variable holds a valid W3C trace context|
|`totals`|Wall and CPU time, peak memory, file and issue counts|
|`stages`|The main process, stage by stage|
|`steps`|Finer parts of some stages, in the main process|
|`pools`|The pools of forked processes, with their workers: tasks, busy time, CPU time, memory growth, and for analysis pools the size of the results|
|`files`|One record per file scanned or analysed, with its time and how much it raised the memory peak|
|`hot`|The slowest statements and function-likes (`limit` of each, none under `min_us`), per place: the number of `passes` (a constructor is analysed twice), the own time (`self_us`) and the total with the functions analysed meanwhile (`wall_us`)|
|`hooks`|Time per handler of a plugin or of Psalm, and per hook|
|`plugins`|The time each plugin took to initialise|
|`counts`|Class-likes, functions, methods and files in memory|
|`parser`|Files parsed and parser cache hits, in all processes|
|`taint`|Size of the taint graph|

`schema` changes when a key is removed or changes its meaning.

The names of stages and steps and the keys `taint`, `parser`, `peak_growth_bytes` and `storage` may change between releases.

## Privacy

Psalm sends nothing; the profile is a local file. It holds file paths as Psalm prints them in issues, class and method names, line numbers and versions, but no source code and no issue messages. Review it before you attach it to a public issue.

A run that does not finish writes no profile. `psalter`, `psalm-refactor` and the language server don't support the option.
