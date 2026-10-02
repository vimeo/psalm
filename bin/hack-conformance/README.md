# Hack conformance harness

Several Psalm features port the Hack typechecker's behaviour:

- class-template **type variables** (see `docs/annotating_code/type_variables.md`):
  because PHP has no constructor type arguments, every templated `new Foo(...)`
  is treated like Hack's `new Foo<_>(...)`, and constraints accumulate on a
  per-construction type variable until they reconcile;
- **capabilities** (`@psalm-capabilities`), Hack's contexts;
- **purity templates** (`@psalm-purity-template`, `Closure[_]`), Hack's context
  constants and dependent contexts (`[ctx $f]`).

Because these features are defined by *matching Hack's behaviour*, this harness
uses the real Hack typechecker (HHVM, via docker) as the oracle: every fixture is
a Hack program that HHVM checks, and that is transpiled to PHP for Psalm to check.
Psalm and HHVM must give the same verdict.

## Why this isn't a bulk import of Hack's tests

There is no self-contained corpus in `facebook/hhvm` that maps onto this feature:

- `hphp/hack/test/typecheck/wildcard/` tests Hack's **explicit `<_>` syntax**
  (`identity<_>(3)`), which PHP does not have — the Psalm feature is precisely
  the *implicit* inference at `new` sites.
- `hphp/hack/test/shadow_tyvars/` is about the `dynamic` type, unrelated.
- The actual implicit-inference tests are scattered across the ~7,500-file
  `hphp/hack/test/typecheck/` tree, entangled with Hack-only syntax (`vec`/`dict`/
  `nothing`, `==>` lambdas, reified generics, XHP, `Awaitable`).

Translating arbitrary Hack isn't achievable, so instead:

1. **`fixtures/<topic>/*.hack`** — hand-written Hack programs in the subset of
   Hack the transpiler understands, one subfolder per topic (`type_variables/`,
   `contexts/`, `polymorphic_contexts/`), each with an
   `//// expect: no-errors|error` header (see [Fixture headers](#fixture-headers)).
2. **`run.php`** — runs each fixture through the pinned HHVM typechecker and
   checks the verdict against `expect`.
3. **`transpile.php`** (with `Transpiler.php`) — transpiles every fixture to PHP
   with Psalm docblocks and generates `tests/HackConformanceTranspiledTest.php`,
   an ordinary Psalm test suite asserting the same verdict of Psalm. It does not
   parse Hack itself: it walks the parse tree HHVM's own parser gives each
   fixture (`hh_parse --full-fidelity-json-parse-tree`, run in the pinned image
   and cached in `.hh-parse-cache/`, gitignored). The mapping (types, contexts
   to capabilities, context constants to purity templates, lambdas, Hack arrays,
   `inout`, …) is documented at the top of `Transpiler.php`; a node kind it does
   not know is an error naming it, never a silent mistranslation.
4. **`track-upstream.php`** — hashes the Hack test directories most likely to
   grow relevant cases and reports additions/removals/changes since the pinned
   commit, so new upstream cases can be hand-translated into `fixtures/`
   (verified with `run.php`).

A mismatch in `run.php` means Hack drifted from what the fixture encodes; a
failure in the transpiled suite means Psalm does not agree with Hack.

## Fixture headers

```
//// expect: no-errors|error         HHVM's verdict, asserted of HHVM and of Psalm
//// psalm-error: <IssueType>        for `expect: error`, the issue Psalm reports first
//// psalm-ignore: <IssueType>, ...  issues Psalm must not report on the transpiled code,
////                                 when they are beside the point of the fixture
//// psalm-divergence: <why>         Psalm does not agree with Hack yet: the transpiled
////                                 case is generated skipped (`SKIPPED-…`)
//// hhconfig: <key> = <value>       a line of the fixture's .hhconfig (repeatable)
//// note: <what the fixture checks>
////       indented `////` lines continue the header above them
```

The note (and divergence) become a comment on the transpiled case.

## Part of the unit suite

`tests/HackConformanceTranspiledTest.php` (generated) checks every fixture with
Psalm. Before running any case it transpiles the fixtures again, and every case
fails if the output differs from the committed file, so a fixture edit without a
regeneration cannot pass. Transpiling needs HHVM's parser (or parse trees cached
by an earlier run): where it cannot run, the committed cases run as they are.

`tests/HackConformanceTest.php` checks that the transpiled suite is up to date,
and drives the fixtures through HHVM as ordinary PHPUnit tests (one per fixture,
asserting HHVM's verdict matches `expect`). It **skips cleanly** when HHVM cannot
run here — no docker, no daemon, not Linux, or the pinned HHVM image is not
already present locally (it never pulls a multi-hundred-MB image inside a
unit-test shard). The `Hack conformance` CI job pulls the image and runs it with
`--fail-on-skipped`, so both checks always run there. Pull the image once to
enable the checks in a given environment:

```sh
docker pull "$(php -r 'echo json_decode(file_get_contents("bin/hack-conformance/manifest.json"),true)["hhvm_image"];')"
vendor/bin/phpunit tests/HackConformanceTest.php
```

## Usage (standalone CLI)

```sh
# Behaviour conformance (requires docker; pulls the image if missing):
php bin/hack-conformance/run.php            # ok/FAIL per fixture
php bin/hack-conformance/run.php --verbose  # also print HHVM output
php bin/hack-conformance/run.php --json     # machine-readable (what the test consumes)

# Regenerate tests/HackConformanceTranspiledTest.php after changing a fixture
# (requires docker; pulls the image if missing):
php bin/hack-conformance/transpile.php
php bin/hack-conformance/transpile.php --check                       # is it up to date? (exit 3: no HHVM)
php bin/hack-conformance/transpile.php type_variables/array_access.hack  # print one fixture's PHP

# Upstream drift (requires git):
php bin/hack-conformance/track-upstream.php          # report changes
php bin/hack-conformance/track-upstream.php --write  # accept current snapshot
```

Pins (HHVM image digest, Hack commit, tracked paths) live in `manifest.json`.
The blobless Hack checkout is cached in `.hack-cache/` (gitignored).

## Adding a case

1. Add the Hack program to `fixtures/<topic>/<name>.hack` with its headers. If
   the case needs a typechecker option (e.g. union type hints), add
   `//// hhconfig: <key> = <value>` lines; each becomes a line of that fixture's
   `.hhconfig`.
2. `php bin/hack-conformance/run.php` — confirm HHVM agrees with `expect`.
3. `php bin/hack-conformance/transpile.php` — regenerate the Psalm suite (extend
   `Transpiler.php` if the fixture uses Hack it does not know yet), then run
   `vendor/bin/phpunit tests/HackConformanceTranspiledTest.php`.

Fixtures reuse class names across files, so the runner typechecks each one in its
own isolated Hack project.
