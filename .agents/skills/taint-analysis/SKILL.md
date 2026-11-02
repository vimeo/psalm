---
name: taint-analysis
description: Use when changing or debugging Psalm's taint analysis (TaintFlowGraph, TaintFlowResolution, TaintKind, taint sinks/sources/escapes, InternalTaintSinkMap), writing TaintTest cases, reproducing a taint false positive or negative, or reviewing a taint PR. Covers repro modes, test-harness pitfalls, graph invariants, resolver limits and hang debugging.
---

# Taint analysis

## Where things live

- `src/Psalm/Type/TaintKind.php`: taint bits. Adding a builtin bit shifts
  `USER_SECRET`/`SYSTEM_SECRET`/`ALL_INPUT`/`BUILTIN_TAINT_COUNT`/`TAINT_NAMES` and the
  `Codebase::addTaintSource/addTaintSink` defaults. The BC check reports these, but an
  addition is not a BC break: fix them in `.roave-backward-compatibility-check.xml`
  (skill `plugin-api-bc`).
- `src/Psalm/Internal/Codebase/TaintFlowGraph.php`: graph building, `addPath()`,
  `connectSinksAndSources()`, `reportTaintedFlow()` and report dedupe.
- `src/Psalm/Internal/Codebase/TaintFlowResolution.php`: the resolver (specialized-call
  entries, open assignments, convergence, filters, widening). Hot code: see the
  hot-loop style rule in CLAUDE.md.
- `src/Psalm/Internal/Codebase/CombinedFlowGraph.php`: forwards to the taint graph and
  the variable-use graph (VUG).
- `dictionaries/InternalTaintSinkMap.php`: sinks for builtin functions **and methods**
  (`'Closure::fromCallable' => [TaintKind::INPUT_CALLABLE]`, `'mysqli::query' => ...`).
- `ArgumentsAnalyzer::getArgumentSinks()` / `addCurlOptionArraySinks()`: per-argument
  sinks that depend on another argument's value (curl options).
- `CastAnalyzer::stripCastTaints()`: scalar casts route parents through a `<type>-cast`
  node whose edge carries `removed_taints`.
- Providers: `tests/TaintTest.php` (`providerValidCodeParse`/`providerInvalidCodeParse`,
  `IGNORE`, dedicated test methods); they run sharded through
  `tests/Taint/TaintCases{1..8}Test.php` + `TaintCasesTestCase.php`, each case in both
  taint-only mode and tracking unused variables.

## Invariants: don't break these

- **`addPath()` replaces an existing edge between the same nodes, and that is
  load-bearing.** `FunctionCallReturnTypeFetcher::taintUsingFlows()` re-adds the
  `fn#1 -> fn` arg edge to *replace* it with the escaped taints; making `addPath` merge
  re-adds broke `filter_var(..., FILTER_SANITIZE_NUMBER_INT)`. For "last call decides
  for all callers" bugs on a shared edge, put the per-call taints on a node keyed by the
  call location (`DataFlowNode::getForAssignment($id, $node_location)`), like the
  `-escaped` node of conditional escapes.
- A node that only sits on a path still needs `$graph->addNode()`; otherwise the flow
  through it is silently dropped.
- **Mode parity:** types get the same parent nodes, and the taint graph the same edges,
  whether or not unused variables are tracked. Write everything through
  `data_flow_graph`; never write to `variable_use_graph` directly (only query it). Paths
  no taint goes through use `removed_taints = TaintKind::ALL`, which `TaintFlowGraph`
  drops. Taint-only paths (`addPathsFromNestedParentNodes`) stay out of the VUG: VUG edges
  drive `ComplexMethod` in self-analysis (see CLAUDE.md).
- Speculative per-call specialization nodes must not reach the VUG (they change its edge
  stats). Specialization of inferred-pure callees is resolved inside
  `connectSinksAndSources()`, because the test harness calls it directly, before
  `MutationLevelResolver::resolve()`.

## Accepted tradeoffs: don't report these as bugs

- **Resolver limits are a RAM vs precision compromise** (`WIDENING_STATES = 16`,
  `MAX_CONVERGENCE_KEYS = 8`, `MAX_FILTERS = 64` in `TaintFlowResolution`). A false
  positive caused by a lower cap is accepted. Do review *how* a limit is applied: uneven
  budget spending, order-dependent results, soundness gaps at the fallback, and whether a
  known-false-positive test documents the compromise. In the other direction, a real
  false-negative fix may justify a moderate RAM increase: measure it (main-process
  "used N MB" of self-analysis), report it, and ask when it's large.
- **Stale remembered types:** taint decisions that trust inferred types (e.g.
  `Union::getTaintsToRemove()` at argument boundaries) inherit Psalm's stale types. With
  the default `rememberPropertyAssignmentsAfterCall="true"`, a property/static/superglobal
  overwritten by a call keeps its old type. That is a config tradeoff, not a bug. Real
  type-tracking holes (non-literal key writes leaving per-key entries, superglobals not
  forgotten) are bugs. When a change strips taint or skips a sink by type, probe
  non-literal key writes, prop/global-writing calls and references against the base.

## Reproducing

- For an issue that a provider case covers (or a new case you are adding), prefer running
  that one case: `vendor/bin/phpunit tests/Taint --filter '@caseName'` runs it in both
  modes and avoids every CLI-vs-harness difference below. Never run whole test files.

### From the CLI

- A scratch `./psalm` run builds a taint graph even without `runTaintAnalysis`.
  - **Taint-only:** `findUnusedCode="false" findUnusedVariablesAndParams="false"` plus
    `--taint-analysis`.
  - **Combined mode:** `findUnusedVariablesAndParams="true"`.
  - Provider cases marked `// --taint-analysis` need the `--taint-analysis` flag.
- With `--output-format=compact`, taint lines look like
  `SECURITY src/a.php:5:1 TaintedShell: ...`, so a grep for `^ERROR` misses them. Grep
  both, and compare non-taint errors against `TaintTest::IGNORE`: an invalid provider
  case throws on the first issue not in IGNORE (e.g. `MissingClosureParamType` from an
  untyped `fn($s) => $s`).
- TaintTest analyses PHP 8.0 by default; local repros at errorLevel 8 hide issues the
  harness reports (e.g. `PossiblyUndefinedArrayOffset`).
- **Unwanted specialization:** project functions and private/final methods whose purity
  infers as pure get specialized per call, and `error_log()` doesn't prevent it. To test
  shared-node behaviour, use `@psalm-impure`, or a non-final public method or static
  method writing a static property.
- `PDO::query` isn't a sink in a bare scratch project (extension not loaded). Use
  `shell_exec` for a non-html sink.
- Two `@psalm-taint-escape (cond ? ...)` annotations on one function make every call
  remove both kinds, whatever the arguments.
- `@psalm-suppress TaintedHtml` (or any specific `Tainted*`) in a docblock does nothing.
  Only `@psalm-suppress TaintedInput` (it cuts the graph in that scope) and config
  `<issueHandlers>` work.
- Under `GITHUB_WORKFLOW` with generated reports, `IssueBuffer::finish()` skips its
  `exit(2)` when taint analysis ran. End-to-end tests asserting exit 2 set
  `runTaintAnalysis="false"`.
- To compare behaviour across many provider cases (only when explicitly asked: sweeps
  count as local test runs), load providers via
  `(new ReflectionClass(TaintTest::class))->newInstanceWithoutConstructor()->providerInvalidCodeParse()`,
  write one scratch project per case and mode, run with `xargs -P 8`, and diff against a
  master worktree. About 27 invalid cases never match from the CLI because of harness
  differences.

## Writing tests

- An invalid provider case and `DocumentationTest` only see the **first** issue. Which
  kind comes first when one sink reports several depends on the graph. **Whenever a change
  gives a sink more kinds, grep every provider case and docs example reaching it**
  (`DocumentationTest` has per-issue `$ignored_issues`, e.g. TaintedSSRF ignores
  TaintedFile). Assert multi-kind results in a dedicated method with
  `throw_exception = false` + `IssueBuffer::getIssuesDataForFile()` (see `testCurlSinks`).
- `TaintCasesTestCase::testInvalidCode` never calls `initializePlugins()`, so removals by
  internal AddRemoveTaints handlers (`JsonEncodeTainter`, `HtmlFunctionTainter`) don't
  happen in provider cases: a regression test there passes on unfixed code. Use a
  dedicated `TaintTest` method calling `initializePlugins()` with an exact issue list. It
  defaults to PHP < 8, so call `setPhpVersion('8.0', 'tests')` for union types.
- Don't write tests for sinks of extensions CI's setup-php doesn't load (e.g. `XMLReader`):
  `UndefinedClass` is in IGNORE, so they pass with no sink at all.
- Use int keys for spreads (`...$a`) in TaintTest: string-key unpacking is a
  `DuplicateArrayKey` error at PHP 8.0.
- Stub repros: a stub never overrides a class declared in project dirs. Put the stubbed
  library in `vendor/` + `<extraFiles>`, not in `<projectFiles>`.

## Adding sinks

- A builtin that isn't stubbed (e.g. `Closure`) takes params straight from the call map,
  so sinks on reflected `MethodStorage` are never seen: use `InternalTaintSinkMap`.
- `stubs/extensions/*.phpstub` load only when the extension is enabled (composer `ext-*`
  or `<enableExtensions>`), so sinks for simplexml/dom/soap/… must **also** go in
  `InternalTaintSinkMap`.
- URL-fetching sinks get `file|ssrf|url_component|url_path`; host-only sinks
  (fsockopen) get `ssrf` only.
- Report dedupe is per (sink, predecessor, origin): SSRF hides url_component and url_path,
  and url_component hides url_path. When one node fans out to per-kind nodes, link one
  node per *single* kind, never per mask, or a shared kind is reported twice.
- Verified sink facts (curl options, URL kinds, casts): see `references/sink-facts.md`.

## Debugging resolver hangs or blowups

- Instrument the resolver per round: queue size, `count(state_nodes)`, top state ids per
  node, per-walk time, exits per entry, filters per base entry. The hub node usually
  stands out (historically `Context::$vars_in_scope`, `Union::__construct` and Union's
  small getters: everything Psalm builds from parsed files is tainted by `$argv`/file
  reads, so self-analysis reaches every Union method).
- Ruled out before: dropping context at every property node (context sensitivity through
  shared state is intended), widening only flows without open assignments, converging
  shared state (17M states / 41 GB), and gating `leaveCalls()` behind caller counts.
  Widening must also forget open assignments, or the first widened flow's assignments
  take the merged state (false negative).
- A blowup in `reportFlow()` / `reported_pointers` with few states is sink replay, not
  the walk: count `addSinkThroughCaller()` calls per entry. An entry's sinks go through
  one caller per source and taint (`getSinkCallerTaints()`). Changes there must keep
  traces too: diff full `--monochrome` output against master on a fuzz corpus. Ruled out
  for #12469: reporting sinks past a non-specialized helper's exit like a convergence
  (filter budget, caller rootedness and trace provenance each broke), and leaving the
  calls by lexical scope (new false positives).
- Instrumentation patches kept in `/tmp` don't survive reboots; rebuild them.
- After fixing a hang, expect self-analysis errors that accumulated unseen while Shepherd
  was red.

## Known open false negatives (for context, not to re-discover)

- Converging flows with different open assignments through specialized functions:
  `return $b ? ["k" => $a] : $a;` then `$r["k"]["x"]`. Keying the visited guard on open
  assignments doesn't terminate on wrapping loops.
- Recursion deeper than the open-assignment window over-approximates (false positives
  only), by design.
