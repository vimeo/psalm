# Changelog

## 7.0.0-beta20 (unreleased)

Changes since `7.0.0-beta19`.

### Features

* **Rewrite dead-code detection on a code-use graph and infer purity across the call
  graph.** Unused-code detection now runs on a directed graph of references between code
  elements (`Psalm\Internal\Codebase\CodeUseGraph`), in the same way as taint analysis:
  a class, method, property or class constant is only considered used if it is reachable
  from an entry point (the public API, top-level code, free functions, or code outside
  the analysed project). Code referenced only by other unused code — including cycles of
  otherwise unreferenced code — is now reported, and dead reads no longer keep
  constructor-only-written properties alive. `MissingPureAnnotation` (and its `--alter`
  fix) is inferred as a fixpoint over the call graph, handling call chains in any order,
  mutual recursion, recursive closures and closures assigned to a variable.
  `--find-unused-psalm-suppress` now also reports redundant `@psalm-suppress` on
  classes/interfaces/traits/enums and, under `--taint-analysis`, on `Tainted*` issues.
  All non-internal classes and interfaces are marked `@api`. **This PR contains breaking
  changes to internal APIs and to plugin-facing behaviour; see
  [`UPGRADING.md`](UPGRADING.md).** by @danog in https://github.com/vimeo/psalm/pull/11939
* Add type variable support for class templates: class templates that cannot be inferred
  at the construction site are tracked as type variables whose bounds are reconciled once
  the surrounding function has been analysed, legalizing widening within a template's
  declared bounds and introducing the new `IncompatibleTypeParameters` issue (level 1),
  documented in `docs/annotating_code/type_variables.md`
  by @muglug in https://github.com/vimeo/psalm/pull/11875

### Fixes

* Make every scalar cast strip the taints that cannot survive the target scalar type:
  `(int)`/`(float)`/`(bool)` now route their value through a taint pass-through node (like
  `(string)` already did), removing all non-numeric/non-boolean taints while preserving the
  ones a scalar can still carry (e.g. `sleep`), and consistently in both taint-only and
  combined (taint + variable-use) analysis modes. This fixes both false negatives
  (`sleep((int) $_GET[...])` was silently untainted) and false positives (a value cast to
  a scalar inside an array no longer flags `nosql`/`sql`). `Union::getTaintsToRemove()` now
  also handles literal unions such as `int(0)|int(1)` produced by casting a bool
  by @danog
* Fix `echo`/`print`/`exit` taint sinks not matching their argument nodes (which broke
  all `TaintedHtml` detection through those constructs), fix taint tracking through
  namespaced functions, and fix a crash on tainted closure/callable invocations, as part
  of refactoring the `DataFlowNode` factories
  by @danog in https://github.com/vimeo/psalm/pull/11887
* Fix multithreaded taint-analysis determinism, and report call-map sinks at the call
  site by @danog in https://github.com/vimeo/psalm/pull/11887
* Keep foreach key/value non-nullable after a breaking loop
  by @muglug in https://github.com/vimeo/psalm/pull/11874
* Fix `$this` failing to parse inside generic type parameters
  by @alies-dev in https://github.com/vimeo/psalm/pull/11768
* Ignore inline `covariant`/`contravariant` variance modifiers in generic type
  parameters instead of reporting `UndefinedDocblockClass` (backport)
  by @alies-dev in https://github.com/vimeo/psalm/pull/11836
* PHP 8.5 compatibility: `TLiteralFloat` now renders `NAN` values as `float(NAN)` in
  type keys and IDs instead of crashing, plus assorted 8.5 fixes
  by @danog in https://github.com/vimeo/psalm/commit/9d6db9ec7 and https://github.com/vimeo/psalm/commit/ca151242c
* Fix CI setup, static analysis, and dependency compatibility
  by @muglug in https://github.com/vimeo/psalm/pull/11934

### Docs

* Document graph-based unused-code detection, purity inference and extended
  `--find-unused-psalm-suppress` checking in `docs/running_psalm/configuration.md`,
  `docs/annotating_code/supported_annotations.md`,
  `docs/running_psalm/issues/MissingPureAnnotation.md` and
  `docs/running_psalm/issues/UnusedPsalmSuppress.md`, and update the CLI help
  by @danog in https://github.com/vimeo/psalm/pull/11939
* Add `docs/annotating_code/type_variables.md` and the `IncompatibleTypeParameters`
  issue page by @muglug in https://github.com/vimeo/psalm/pull/11875

### Internal changes

* Refactor the `DataFlowNode` factories: mandatory `$storage`, typed callable kinds,
  and explicit locations by @danog in https://github.com/vimeo/psalm/pull/11887
* Remove dead write-only static properties and simplify `ScopeAnalyzer`
  break/continue logic by @muglug in https://github.com/vimeo/psalm/pull/11889
* Clean up redundant assignment-conditions and repeated `getArgs()` calls
  by @muglug in https://github.com/vimeo/psalm/pull/11888
* Pin GitHub Actions to SHA for supply chain security
  by @riccardosarro in https://github.com/vimeo/psalm/pull/11863
* Test-fixture fixes: whitelist `Override` in the SuicidalAutoloader fixture and drop
  the incorrect `impure-` Closure prefix in `TypeParseTest`
  by @alies-dev in https://github.com/vimeo/psalm/pull/11835 and https://github.com/vimeo/psalm/pull/11834

### Other changes

* Allow `sebastian/diff` 9
  by @liviuconcioiu in https://github.com/vimeo/psalm/pull/11861

## New Contributors

* @riccardosarro made their first contribution in https://github.com/vimeo/psalm/pull/11863

**Full Changelog**: https://github.com/vimeo/psalm/compare/7.0.0-beta19...HEAD
