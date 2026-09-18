# Hack conformance harness

Psalm's class-template **type-variable** feature (see `docs/annotating_code/type_variables.md`)
ports the Hack typechecker's local type inference: because PHP has no constructor
type arguments, every templated `new Foo(...)` is treated like Hack's
`new Foo<_>(...)`, and constraints accumulate on a per-construction type
variable until they reconcile.

Because the feature is defined by *matching Hack's behaviour*, this harness uses
the real Hack typechecker (HHVM, via docker) as the oracle.

## Why this isn't a bulk import of Hack's tests

There is no self-contained corpus in `facebook/hhvm` that maps onto this feature:

- `hphp/hack/test/typecheck/wildcard/` tests Hack's **explicit `<_>` syntax**
  (`identity<_>(3)`), which PHP does not have — the Psalm feature is precisely
  the *implicit* inference at `new` sites.
- `hphp/hack/test/shadow_tyvars/` is about the `dynamic` type, unrelated.
- The actual implicit-inference tests are scattered across the ~7,500-file
  `hphp/hack/test/typecheck/` tree, entangled with Hack-only syntax (`vec`/`dict`/
  `nothing`, `==>` lambdas, reified generics, XHP, `Awaitable`).

Faithful automatic Hack → PHP+docblock translation isn't achievable, so instead:

1. **`fixtures/`** — hand-written Hack programs, each the direct analogue of a
   Psalm test in `tests/Template/TypeVariableTest.php` (and a couple elsewhere),
   carrying `//// psalm-test:` and `//// expect: no-errors|error` headers.
2. **`run.php`** — runs each fixture through the pinned HHVM typechecker and
   checks the verdict against `expect`. A mismatch means Hack drifted from what
   the Psalm test encodes.
3. **`track-upstream.php`** — hashes the Hack test directories most likely to
   grow relevant cases and reports additions/removals/changes since the pinned
   commit, so new upstream cases can be hand-translated and added to `fixtures/`
   (verified with `run.php`) alongside a Psalm test.

## Usage

```sh
# Behaviour conformance (requires docker):
php bin/hack-conformance/run.php            # ok/FAIL per fixture
php bin/hack-conformance/run.php --verbose  # also print HHVM output

# Upstream drift (requires git):
php bin/hack-conformance/track-upstream.php          # report changes
php bin/hack-conformance/track-upstream.php --write  # accept current snapshot
```

Pins (HHVM image digest, Hack commit, tracked paths) live in `manifest.json`.
The blobless Hack checkout is cached in `.hack-cache/` (gitignored).

## Adding a case

1. Add a Psalm case to `tests/Template/TypeVariableTest.php` (or the relevant
   test), verified with `vendor/bin/phpunit`.
2. Add the Hack analogue to `fixtures/<name>.hack` with `//// psalm-test:` and
   `//// expect:` headers.
3. `php bin/hack-conformance/run.php` — confirm HHVM agrees.

Fixtures reuse class names across files, so the runner typechecks each one in its
own isolated Hack project.
