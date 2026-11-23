# Finding out why Psalm is slow

`--debug-performance` prints where a run spent its time and memory. `--debug-performance=PATH` also writes everything it measured to a JSON file.

```bash
./vendor/bin/psalm --no-cache --debug-performance=psalm-perf.json
```

The path needs the `=`: after a space it is a file to analyse. Run with `--no-cache` to measure a cold run, and without `--debug`, which distorts the timings.

## Reading the dump

```bash
# the ten files that took the most CPU
jq -r '.files | map(select(.op == "analysis")) | sort_by(-.cpu_us)[:10][] | [.cpu_us, .path] | @tsv' psalm-perf.json

# where the time went, in order
jq -r '.stages[] | [.name, .wall_us, .cpu_us] | @tsv' psalm-perf.json

# the files that raised the peak memory the most
jq -r '.files | map(select(.peak_growth_bytes > 0)) | sort_by(-.peak_growth_bytes)[:10][] | [.peak_growth_bytes, .op, .path] | @tsv' psalm-perf.json

# the statements and the functions that took the most time
jq -r '.top.statements[:5][] | [.self_us, .path, .line, .node] | @tsv' psalm-perf.json
jq -r '.top.functions[:5][] | [.wall_us, .id, .pass, .path, .line] | @tsv' psalm-perf.json

# the plugin handlers that took the most time
jq -r '.hooks | map(select(.origin == "plugin"))[:10][] | [.wall_us, .calls, .handler, .hook] | @tsv' psalm-perf.json

# how evenly the workers shared the work (busy time against the time the pool ran)
jq -r '.workers[] | [.pool, .pid, .tasks, .busy_us, (.end_us - .start_us)] | @tsv' psalm-perf.json
```

All times are integer microseconds (`_us`) and sizes integer bytes (`_bytes`).

## Seeing the workers on a timeline

[Perfetto](https://ui.perfetto.dev) and [speedscope](https://www.speedscope.app) open Chrome trace files. This filter turns the stages and files of a dump into one:

```bash
jq -c '{displayTimeUnit: "ms", traceEvents:
  ([.stages[] | {name, cat: "stage", ph: "X", ts: .start_us, dur: .wall_us, pid: 1, tid: 0}]
 + [.files[] | {name: (.path | split("/") | .[-1]), cat: .op, ph: "X", ts: .start_us, dur: .wall_us,
                pid: 1, tid: .pid, args: {path, cpu_us, mem_bytes}}])}' psalm-perf.json > trace.json
```

## What the dump holds

|Key|Content|
|---|---|
|`schema`|`1`. A removed key or a changed meaning raises it; read unknown keys and stage names as optional.|
|`env`|Psalm, PHP and OS versions, `opcache`, `jit`, `zend_assertions`, `memory_limit`, extensions, `threads`, `scan_threads`, `cache`, `taint_analysis`, `target_php_version`, plugin classes, the names of the options given, and a `config_sha256` of the config file. When the `TRACEPARENT` environment variable holds a valid W3C trace context, `traceparent` carries it, so that a dump can be linked to the trace of a CI run.|
|`totals`|Wall and CPU time of the run, CPU time and peak resident memory of the workers, the PHP memory peak (the number in `Checks took … used … MB`), file and issue counts.|
|`stages`|The main process, one stage after the other: `bootstrap`, `scan.setup`, `scan.files`, `scan.populate`, `analysis.setup`, `analysis.files`, `analysis.merge`, `taint`, `analysis.finish`, `post`, `report`. A stage name can appear more than once.|
|`pools`, `workers`|One entry per pool of forked processes, and one per worker: its tasks, busy time, CPU time and memory. `payload_bytes` is the size of the results the analysis workers sent back.|
|`files`|One record per file scanned or analysed, in the process that did it: wall and CPU time, retained memory, how much the file raised the peak, and, for a scan, whether the cached storage was used (`storage`). Analysis time includes the work a file triggers in other files.|
|`hooks`|Every handler of a plugin or of Psalm, per hook: `handler` (class), `hook` (an event such as `AfterFileAnalysis`, or a provider kind such as `FunctionReturnType`), `origin` (`plugin`, or `psalm` for a class in `Psalm\`), `calls` and `wall_us`, longest first. The time includes what the handler triggers, so a handler that makes Psalm analyse code includes that time. Workers add up in the main process.|
|`counts`|`classlikes`: class-like storages in memory (classes, interfaces, traits and enums, stubs included). `methods`: the methods those storages declare, not the inherited ones. `functions`: functions declared by the scanned files, closures not included. `file_storages`: scanned files. These four are read in the main process after the workers' results are merged. `files_parsed`: files the parser parsed, in all processes, so a file that two workers parse counts twice. `parser_cache_hits`: requests for a file that the parser cache answered, the one in memory and the statements kept of vendor files included. `parser_cache_misses`: files parsed after the parser cache was asked. Every request is a parse or a hit.|
|`taint`|Size of the taint graph and the number of resolver rounds.|
|`hot`, `top`|Every statement (own time) and function-like (`body` or `initializations` pass) that took 50 ms or more, and the 20 slowest of each.|

With `--threads=1` a repeated run gives the same records, and the CPU time of a file varies by a few percent. With more threads, the worker that gets a file varies as well.

## Privacy

Psalm sends nothing. The dump is a local file with file paths as Psalm prints them in issues, class and method names, line numbers, plugin class names and PHP version. It has no source code, no issue messages, no host name and no option values. A path inside the project is relative to it. A path outside the project keeps its `../` form, which can name directories above the project, and a file of Psalm's own installation outside the project shows as `<psalm>/<path in Psalm>`. Any other path that is still absolute shows as `<external>/<file name>`. Review the file before you attach it to a public issue.

## Not covered

A run that ends without finishing (a kill, a timeout, an out-of-memory error or a fatal error) writes no dump. `psalter`, `psalm-refactor` and the language server don't support the option.
