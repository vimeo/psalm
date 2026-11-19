---
name: purity-capabilities
description: Use when working on Psalm's purity model, i.e. capabilities (@psalm-capabilities, @psalm-pure/@psalm-impure), purity templates (@psalm-purity-template, @psalm-purity-from-template, Closure[P], Traversable[pure]<K, V>, the `_` wildcard), purity of builtin/SPL stubs, memoized method results, or MissingPureAnnotation/ImpureMethodCall behaviour. Records the maintainers' design decisions, which must not be relitigated.
---

# Purity and capabilities

## Model (where things live)

- `src/Psalm/Storage/Capabilities.php`: a bitmask `READ_PROPS`, `WRITE_THIS_PROPS`,
  `WRITE_PROPS`, `READ_GLOBALS`, `WRITE_GLOBALS`, `WRITE_REFS`, `IO`. `ALL` = impure.
- Closure/callable atomics carry `Union $purity`, which is a `TCapabilities` or a purity
  `TTemplateParam`.
- `@psalm-purity-template P` desugars to a template bounded by `impure` (covariant on
  classes). Bounds syntax: `lower <= Name(default) <= upper`.
- `@psalm-purity-from-template P` is resolved per call by `CallPurityResolver`
  (`getOwnCapabilities()`, `getCallCapabilities()`) for functions, methods, static calls
  and `new`. Unbound purity templates are defaulted to their bound by `ArgumentAnalyzer`
  with `arg_offset === null`, and the resolver skips those.
- **Conditional capabilities:** `@psalm-capabilities ($p is null ? io : pure)`.
  - `FunctionLikeStorage::$conditional_capabilities` holds the branches, and
    `$capabilities` their union: the body is checked against it, and it applies where
    there's no call.
  - Parsed in `FunctionLikeDocblockScanner` like conditional return types.
- Docs: `docs/annotating_code/purity_model.md` and supported_annotations.md
  ("Purity and capabilities"). Tests: `tests/CapabilitiesTest.php`,
  `tests/PurityTemplateTest.php`. BC list: `UPGRADING.md`.

## Maintainer decisions: settled, don't propose reverting them

- **Naming:** `mutation-free`/`external-mutation-free` (aliases and the
  `@psalm-mutation-free`/`@psalm-external-mutation-free` tags) are parse-only. Never print
  them (`Capabilities::toString`), suggest them, or document them; they're listed in
  `DocumentationTest::INTENTIONALLY_UNDOCUMENTED_ANNOTATIONS`. `@psalm-pure` and
  `@psalm-impure` stay first-class in docs and output. Docs use
  `@psalm-capabilities read-props` / `write-this-props` where the aliases used to be.
- **One bit per name:** each name in `Capabilities::NAMES` is only its own bit
  (`write-props` doesn't imply `read-props`, `write-globals` doesn't imply
  `read-globals`), and `toString` prints one name per bit. Consequences:
  - global writes charge `write-globals` alone (`static $x` charges both);
  - a plain `S::$n = ...` target isn't charged as a read;
  - using `$this` needs `read-props` unless the context has `write-this-props`;
  - a foreign receiver's `write-this-props` becomes `write-props`
    (`MethodCallPurityAnalyzer::getCapabilitiesForReceiver`; also for the receiver's
    class purity template args), but this is never waived for fresh receivers.
- **Bracket syntax (Hack-style):**
  - Purity args come before type args: `Traversable[pure]<K, V>`, `Doer[io]`,
    `iterable[P]<K, V>`, `Closure[io](int): void`. Commas separate purity args, `|`
    unions capabilities within one.
  - `[]` alone is always the array type. Purity inside `<...>` is an InvalidDocblock, and
    `Closure<P>` is rejected.
  - Internally a `TGenericObject` keeps purity args as trailing `type_params` (purity
    templates are sorted after type templates). Printing splits them out with
    `Capabilities::isPurityArgument`, and `PurityArguments::align` fills type args for
    `Foo[pure]`.
  - `TIterable` has a separate `purity` property (default impure, not printed).
  - Code that strips keys for name matching must cut at `<` **or** `[` (`strcspn`).
- **Wildcard `_`:** desugars exactly like an explicit `@psalm-purity-template` at any depth
  of a `@param` type (nested closures, `Traversable[_]<K,V>`, contravariant positions,
  `@method` params). Never reject a position the explicit form accepts. The bound
  template is named `_$param`. Everywhere else `_` is rejected (`TypeParser`
  `$allow_purity_wildcard`, true only for `@param` and alias validation). No `[ctx $f]`
  is needed (unlike Hack). `@method` signatures are re-printed via `toNamespacedString`
  into a fake docblock, so anything printing with `getId()` there breaks re-parsing.
- **Captured objects are closure state:** a captured value that was fresh in the
  enclosing scope (`reference_free && allow_mutations`) gets `Union::$closure_state`
  (set by `CapturedValueVisitor`, plumbed like `reference_free`). Writing it, or calling a
  `$this`-writing method on it, needs `write-this-props`, not `write-props`. This also
  covers by-ref captures, objects in captured arrays and nested closures. Only non-fresh
  captures (parameters) stay `write-props`.
- **Contracted-object reads cost `read-props`:** reading a property of an object whose
  class has a contract costs `read-props` unless the caller created it fresh. This
  deliberately breaks 6.x compatibility; don't propose relaxing it.
- **Memoization:** a callee with write capabilities but no recorded
  `this_property_mutations` forgets memoized `$x->m()` results
  (`removeMutableObjectVars(true)`), **including `ALL` callees** (an unannotated
  `reset() { $this->clear(); }`). Excluding ALL was rejected as overkill.
- `Closure` is not stubbed: `Reflection::PURE_METHODS` makes bind/bindTo/fromCallable
  pure, `ClosureReturnTypeProvider` keeps the closure type through bind/bindTo, and
  `Closure::call` is analysed as a call of the closure
  (`ExistingAtomicMethodCallAnalyzer::analyzeClosureCall`).
- SPL: every method SPL declares is annotated, ignoring user subclasses (they must satisfy
  the annotations or get ImmutableDependency).
- Unannotated (`ALL`) function-likes may use `new` in parameter defaults for free (DI
  code); annotated ones evaluate defaults as pure (or `write-globals`).

## Builtin stubs

- **Call-map functions:** a stub's purity declaration (`@psalm-pure` included) is
  authoritative in `Functions::getCallMapFunctionCapabilities()`. Without one, the
  heuristic applies: ImpureFunctionsList, else pure + the purity of each callable arg +
  `write-refs` per by-ref arg. Callable-string conversions use
  `getWorstCaseCapabilities()`.
- **Methods** are storage-driven: reflected native methods are `ALL`, so a stub must give
  both `@psalm-capabilities` and any purity template.
- For a builtin that calls a closure, prefer `@psalm-purity-template P` + `callable[P]` +
  `@psalm-purity-from-template P` (an explicit template, not `callable[_]`), unless the
  function has a params provider (array_map/filter/reduce/walk/u*/find* rely on
  ArrayFunctionArgumentsAnalyzer and are left alone on purpose).
- Wrapper iterators use the `IteratorIterator` pattern: class `TPurity`, methods
  `@psalm-pure` + `@psalm-purity-from-template TPurity`. Generator methods are
  `@psalm-pure` + `@psalm-purity-from-template TPurity` (position is engine state).
- Containers (SplDoublyLinkedList/Queue/Stack, SplFixedArray, SplHeap*, SplPriorityQueue,
  SplObjectStorage) have a class-level `@psalm-capabilities read-props|write-this-props`
  contract, so a fresh `new` may mutate them freely. Contracts aren't inherited: every
  stub subclass declares its own.
- Stub pitfalls:
  - A method the *native* class declares but the stub doesn't is reflected as `ALL` and
    shadows the annotated stub parent. Inventory with
    `ReflectionMethod::getDeclaringClass()` against the stub's declarations.
  - `@since 8.x` on a stub method makes Psalm swap in an impure placeholder on older
    versions, causing ImmutableDependency against annotated interfaces. Don't gate added
    methods: the call map already reports UndefinedMethod.
  - Runtime reflection merges interfaces the stub omits, without template args; declare
    them in the stub.
  - Stubbed methods take params from the stub, not the call map. Omit the return type to
    keep the call map's per-version one.
- When making a builtin reader mutation-free, probe drain-then-check patterns:
  `if ($q->isEmpty()) return; $q->dequeue(); if ($q->isEmpty()) ...`.
- `@var` on `$x = new X()` drops `reference_free` (general Psalm behaviour).

## Implementation pitfalls

- Issues on virtual method calls (`__get`, `__invoke`, `offsetGet`) need a located node:
  use `$stmt->name`, not the VirtualMethodCall (giving it attributes breaks the
  Undefined*Magic* issue types).
- `MissingPureAnnotation`/`MissingImmutableAnnotation` suggestions are made at named-level
  granularity (`Capabilities::toNamedLevel`) to avoid noise.
- Type-only fake `current`/`key`/`getIterator` calls are silenced via
  `Context::$inside_type_only_call`. Foreach charges the iterator methods
  (`ForeachAnalyzer::getIterationCapabilities`).
- Test cases: one docblock tag per line; CapabilitiesTest valid cases run at PHP 7.4
  unless `php_version` is set (positional provider args, see CLAUDE.md).

## Known gaps (left on purpose or open)

- No opaque user-defined capabilities (Hack `newctx`); `impure` is a fixed top, so an
  impure function may call a purity-polymorphic closure (Hack rejects that from
  `[defaults]`).
- `@psalm-purity-from-template T` rejects a type template bound to a class with a purity
  template (Hack `T::C`). Workaround: `@param A<P> $t` with a purity template `P`.
- `exit(int)`/`die()` are free; only exit with a non-int message costs `io`.
- `MethodComparator` treats a class purity template that a subclass forwards to its own
  open template as its upper bound (an override can do anything while `new` binds P to
  pure).
- Static calls to reflected (location-less) native methods are never charged.
- `new IteratorIterator(new ArrayIterator(...))` charges the inner's derived
  `write-this-props` to the caller.
- Accepted warts: `@psalm-external-mutation-free` grants `write-refs`; `global $x` always
  needs `write-globals`; `isset($a[$k])` charges both offsetExists and offsetGet.

Before adding a purity feature, check Hack's behaviour first (skill `hack-conformance`).
