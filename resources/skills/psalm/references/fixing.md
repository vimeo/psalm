# Psalter, suppressions and baselines

## Psalter

Psalter rewrites code to fix a set of issues. `vendor/bin/psalter --list-supported-issues` lists them;
`docs/manipulating_code/fixing.md` shows what each fix does.

```bash
vendor/bin/psalter --issues=MissingReturnType,MissingParamType --dry-run src/Foo.php
vendor/bin/psalter --issues=MissingReturnType,MissingParamType src/Foo.php
```

1. Run with `--dry-run` and read the diff.
2. Apply, then rerun Psalm on the same files.
3. Review types Psalter took from usage, such as `MissingParamType`: they describe how the code is called
   today, which may be narrower or wider than the intent.

Fixes that delete methods and properties (`UnusedMethod`, `PossiblyUnusedMethod`, `UnusedProperty`, …)
need `--find-unused-code`, a run on the whole project rather than single files, and a check that the symbol isn't used from outside the analysed code (see `@api` in
[annotations.md](annotations.md)).

`--php-version=8.2` keeps added type declarations valid for the target PHP version.
`--safe-types` only adds types Psalm got from code, not from docblocks.

Moving and renaming classes and methods: `vendor/bin/psalm-refactor`, see `docs/manipulating_code/refactoring.md`.

## When the user agrees to suppress

From narrowest to widest; pick the narrowest one that covers the case:

1. Inline, on one statement: `/** @psalm-suppress PossiblyNullReference reason */`.
2. On a function or class docblock: every occurrence inside it.
3. `issueHandlers` in `psalm.xml`, scoped to directories or files:

   ```xml
   <issueHandlers>
       <MixedAssignment>
           <errorLevel type="suppress">
               <directory name="src/Legacy"/>
               <file name="src/Kernel.php"/>
           </errorLevel>
       </MixedAssignment>
   </issueHandlers>
   ```

   Some issue types also take `referencedClass`, `referencedMethod`, `referencedProperty` or
   `referencedVariable`: `docs/running_psalm/dealing_with_code_issues.md`.
4. A baseline (below).

Suppressing an `Unused*` issue silences the report only; code reachable only through that symbol is still
reported. `@api` is the annotation for "used from outside".

`--find-unused-psalm-suppress` reports suppressions that no longer hide anything.

## Baseline

A baseline records the current issues so that only new ones fail the run. It is meant for adopting Psalm,
or raising `errorLevel`, on existing code.

- `--set-baseline=psalm-baseline.xml` writes a fresh baseline with every current issue. It hides new
  problems as well as old ones: create or regenerate it only when the user asks.
- `--update-baseline` only removes entries that are fixed; it never adds new ones. Safe after fixing code.
- `--ignore-baseline` shows every issue, including the ones in the baseline.
- `UnusedBaselineEntry` means an entry no longer matches anything: run `--update-baseline`.

## Raising errorLevel

`errorLevel` goes from 1 (strictest) to 8. Each level adds issue types: `docs/running_psalm/error_levels.md`.
To raise it on existing code: lower the number by one, fix or baseline the new issues, repeat.
