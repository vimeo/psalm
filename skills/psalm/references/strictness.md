# Raising strictness

Lowering `errorLevel` by one, or turning on a stricter option (`ensureOverrideAttribute`, `findUnusedCode`).
Which issue types each level adds: `docs/running_psalm/error_levels.md`. Go one level at a time; each level
is its own pass through the phases below. Tightening the types of code that already passes, with the level
unchanged: [Tightening passing types](#tightening-passing-types).

## Phase 0 — Facts

- Current level: `errorLevel` in `psalm.xml`; without the attribute it is `2`.
- Target PHP version: `phpVersion` in `psalm.xml`, else the lowest version `composer.json` allows. Use it as
  `<phpv>` in every Psalter command: the native types Psalter adds must exist in that version.
- **Gate:** the current level passes (`0`, a baseline counts). If it doesn't, finish the current level first.

## Phase 1 — Preview and agree (ask first)

See the next level without touching `psalm.xml`; `--error-level` overrides the config for one run:

```bash
vendor/bin/psalm --no-progress --error-level=<N-1> --output-format=count --report=<report>
php scripts/summary.php <report> --limit=40
```

Show the counts, then ask and wait for the answers, unless the request already gives them:

- the target level: the next one, or further;
- what to do with issues left over: fix all, or a baseline (it hides them, see [fixing.md](fixing.md));
- the decisions of Phase 2 that depend on the PHP version, such as a polyfill.

More than 100 issues or issues in more than 10 files: run Phases 2 and 3 through
[cleanup.md](cleanup.md), with `--error-level=<N-1>` in its Psalm commands.

## Phase 2 — Automatic fixes

Psalter fixes part of what a level adds. Pass the types the report shows that
`vendor/bin/psalter --list-supported-issues` lists, leaving out the ones that need a decision:

```bash
vendor/bin/psalter --issues=MissingReturnType,MissingPropertyType,MissingClosureReturnType,InvalidReturnType,\
LessSpecificReturnType,MismatchingDocblockReturnType,RedundantCast,UnnecessaryVarAnnotation \
  --php-version=<phpv> --dry-run
```

1. Read the diff, then run it without `--dry-run`.
2. `MissingParamType` takes its types from today's callers: run it on its own and review each type.
3. Fixes that delete code (`Unused*`, `PossiblyUnused*`) and `ClassMustBeFinal` follow the unused-code rule
   in SKILL.md, not Psalter.

`MissingOverrideAttribute` (`ensureOverrideAttribute`, on by default) depends on the PHP version:

| Target PHP | `#[\Override]` |
|---|---|
| 8.3 and later | `--issues=MissingOverrideAttribute` adds it |
| 8.0 – 8.2 | the class `Override` doesn't exist there, harmless unless code instantiates attributes through reflection; Psalm's docs recommend `symfony/polyfill-php83`: propose it, then add the attribute |
| 7.x | `#[…]` is a comment there: adding it is harmless, but propose `ensureOverrideAttribute="false"` instead |

`MissingClassConstType` is reported only for PHP 8.3 and later, and Psalter doesn't fix it.

## Phase 3 — The rest by hand, sources before users

Fix in this order and rerun the preview between the steps: each step removes issues of the next.

1. Declarations: `Missing*Type`, `Invalid*ReturnType`, `MismatchingDocblock*`, template issues
   (`MissingTemplateParam`, `TooManyTemplateParams`).
2. `Possibly*`: `null` and `false` handling, see SKILL.md.
3. `Mixed*` (level 1): narrow outside data, add templates and shapes; many go once step 1 is done.

One type at a time: `php scripts/summary.php <report> --type=<Type> --snippets`.

## Phase 4 — Switch and gate

1. Set `errorLevel="<N-1>"` in `psalm.xml`; the request to raise the level covers this change.
2. Agreed leftovers: `--set-baseline=<file>` rewrites the whole baseline, old entries included.
3. **Gate:** `vendor/bin/psalm --no-progress --no-cache` exits `0`.
4. Further target: start again at Phase 1 with the next level.

## Tightening passing types

Psalm is green and the request is for narrower types. Psalm reports nothing here, so go through the
public API, interfaces first, and look for:

- `string` that is never empty: `non-empty-string`; a class name: `class-string<T>`;
- `array` that is a list or has known keys: `list<T>`, `non-empty-list<T>`, `array{…}`;
- `int` with a range: `positive-int`, `int<0, max>`;
- registries, collections and containers without generics: `@template`;
- value objects: native `readonly`, `@psalm-immutable` (its constructor must be pure, see
  [annotations.md](annotations.md)).

`@psalm-check-type-exact` ([debugging.md](debugging.md)) shows what Psalm infers today before you write
the new type.

A narrowed `@param` on an interface is inherited by every implementation without its own docblock, so a
guard there becomes `RedundantCondition`:

```php
interface Named
{
    /** @param non-empty-string $name */
    public function set(string $name): void;
}

final class Impl implements Named
{
    #[\Override]
    public function set(string $name): void { assert($name !== ''); }  // RedundantCondition
}

final class Impl2 implements Named
{
    /** @param string $name */
    #[\Override]
    public function set(string $name): void { assert($name !== ''); }  // no issue: keeps its own guard
}
```

**Gate:** the whole project exits `0` and the tests pass.
