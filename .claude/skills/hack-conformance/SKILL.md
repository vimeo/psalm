---
name: hack-conformance
description: Use when a Psalm feature is defined by matching Hack, i.e. class-template type variables, capabilities/coeffects, purity templates/contexts, or when adding a TypeVariableTest/CapabilitiesTest case whose expectation is unclear. Covers running the HHVM oracle harness (bin/hack-conformance) via docker, verified Hack facts, and where to look in the HHVM source.
---

# Hack conformance (HHVM as the oracle)

The maintainers treat HHVM as the oracle for type variables and the purity model. Divergences
get fixed on Psalm's side. For any new type-variable or capability test, write the Hack
analogue and run it **before** deciding the Psalm expectation.

## Running the harness

- Docker must be running: check `docker info`, and if it fails start it (macOS:
  `open -a Docker`; Linux: `sudo systemctl start docker`, see the platform table in
  CLAUDE.md) and wait until `docker info` works. The pinned HHVM image
  (`bin/hack-conformance/manifest.json`) is amd64: native on x86_64 Linux, emulated on
  Apple Silicon and arm64 Linux (slower; arm64 Linux needs qemu/binfmt set up). The
  first run pulls it.
- `php bin/hack-conformance/run.php --verbose` (~2 min for all fixtures). Running all
  of `vendor/bin/phpunit tests/HackConformanceTest.php` falls under the "CI, not local
  tests" rule in CLAUDE.md; a single fixture's case via `--filter` is fine.
- Fixtures: `bin/hack-conformance/fixtures/<topic>/*.hack`, with an
  `//// expect: no-errors|error` header. See `bin/hack-conformance/README.md`.
  - `//// hhconfig: key = value` lines set .hhconfig options; union type hints need
    `union_intersection_type_hints = true`.
  - `.hack` files have **no** `<?hh` header; `.php` probes need `<?hh`.
  - Avoid Hack builtin names (`Collection`). `dict<TKey, _>` needs `TKey as arraykey`.
- Ad-hoc probing: one temp dir + empty `.hhconfig` per probe, run with the
  `docker run ... hh_client --no-load` loop from `bin/hack-conformance/lib.php`.
  `hh_client --type-at-pos file:line:col` prints solved types; `hh_single_type_check`
  is not in the image.
- In Psalm-side fixtures put Hack-ported tags on separate docblock lines.

## Hack source of truth (`../hhvm`)

- `hphp/hack/src/typing/typing_local_ops.ml`, `typing_coeffects.ml`, and `typing.ml`
  (`check_implicit_args`, `should_forget_fakes`, `bind_params ~can_read_globals`).
- `hphp/hack/src/parser/coeffects_check.rs`, `hphp/hack/hhi/coeffect/*.hhi`.
- The manual: `hphp/hack/manual/hack/13-contexts-and-capabilities`.
- Above all the test corpus: `hphp/hack/test/typecheck/coeffects/`,
  `coeffect_encapsulation/` (newctx), `context_constant_override.php`.
- Wildcards: `naming/naming_elab_wildcard_hint.ml`, `parser/lowerer/lowerer.rs`
  (`rewrite_fun_ctx`), test `test/typecheck/coeffects/invalid_wildcards.php`.

## Verified Hack facts (HHVM 4.172)

- `read_globals` forces `readonly` on static reads.
- Lambdas inherit the enclosing context both ways and cannot have polymorphic contexts.
- A nullable `[ctx $f]` treats null as empty.
- Context constants:
  - they support `as` (= Psalm lower bound) / `super` (= Psalm upper bound) and
    defaults, only when abstract;
  - abstract subclasses may tighten bounds, never loosen them;
  - a final class must implement abstract ctx consts;
  - overriding a concrete ctx const is an error.
- `[]` constructors may write `$this`, but `__clone()[]` may not.
- Parameter default expressions are checked in a pure context (or `[globals]` with
  AccessGlobals), regardless of the function's own context.
- `exit()` is `[defaults]`.
- Foreach over `Iterator` in `[]` is not checked (Hack is unsound there; Psalm charges
  it).
- Refinements of `$a->x` survive calls to callees without WriteProperty, including `new`
  and static calls.
- `__ConsistentConstruct` makes constructor contexts override-checked.
- `newctx X as [lower] super [upper]` is transparent in its defining file and opaque
  elsewhere.
- `[defaults]` is not a top: polymorphic callees can't be called from it.
- Wildcard `[_]` is only valid as the sole context of a parameter's own function type
  (optionally under `?`/`~`), and the function must list `[ctx $f]`. Psalm deliberately
  allows more: `_` nested in params, `Traversable[_]`, `@method` params, no `[ctx $f]`.
- Every public Hack context (pure, write_this_props, write_props, read_globals, globals,
  defaults, IO) has a Psalm counterpart. Psalm adds `read-props` and `write-refs`.
- Type variables: a declared `(It<string> | It<int>)` localizes to `It<(string | int)>`
  when `It` is covariant. For an invariant `It`, a direct `return new It(...)` is
  rejected only because Hack pushes the expected type into `new` and picks the first
  union arm.

## Type-variable rules (deferred class-template model, `` `_0 `` variables)

- Values that flow in after construction (`$c->add(new B())`) are extra lower bounds.
  **Read sites** (property fetch, method call, cast, array fetch:
  `TypeVariableResolver::resolveTopLevel`) must resolve through **all** lower bounds so
  far, or `$x->onlyA()` on an `A|B` element silently loses
  PossiblyUndefinedMethod/UndefinedPropertyFetch.
- Construction-site-only bounds (`initial_lower_bound_count`) are right only for untyped
  closure params, the plugin node-type view and the TemplateStandinTypeReplacer gate.
- Review check: `new Coll([new A()]); $c->add(new B());`, then read an element. The
  deferred model reports the B-side issues; 7.0.0-beta19 (eager model) rejects the `add`
  instead.
- A typed closure param (`Base $row`) passed where `callable(Row)` is expected is
  narrowed to `Row` on purpose. Silence there is not a lost detection.
- Regressions since type variables landed (#11875) are judged against **7.0.0-beta19**:
  "restores beta19's issue type at the same position" is the bar (skill `psalm-repro`).
