# Upgrading to Psalm 7

For most projects, upgrading is as simple as updating the Composer dependency:

```bash
composer require --dev vimeo/psalm:^7.0 -W
```

`-W` lets Composer also upgrade Psalm's own dependencies that your lock file pins to older versions (for example `amphp/parallel` or `nikic/php-parser`); without it Composer refuses the update. If your `composer.json` requires `psalm/plugin-phpunit`, add `psalm/plugin-phpunit:^0.21` to the same command.

`--set-baseline` writes a fresh baseline. `--update-baseline` isn't enough here: it only removes fixed entries and doesn't add new ones.

The rest of this page covers what some projects will notice. The complete list of breaking changes, mostly relevant to plugin authors, is in [UPGRADING.md](https://github.com/vimeo/psalm/blob/master/UPGRADING.md#upgrading-from-psalm-6-to-psalm-7).

## What you may notice

- **Taint analysis runs by default**, in the same run as type checking, and is faster than a separate `--taint-analysis` run (see the [release notes](https://github.com/vimeo/psalm/releases/tag/7.0.0)). To run type checking only, set `runTaintAnalysis="false"`.
- **New security findings.** If taint analysis reports more than you want to deal with at once, you have these tools ([docs](https://psalm.dev/docs/security_analysis/avoiding_false_positives/)):
    - `@psalm-cli-only` on a function, method or class: what its code prints is not reported as `TaintedHtml`;
    - `<taintAnalysis><ignoreFiles>` in `psalm.xml`: taint paths through the listed files and directories (tests, for example) are not reported;
    - `@psalm-taint-specialize`: each call of a function or each instance of a class is tracked separately;
    - `runTaintAnalysis="false"`: turn it off.
- **Baselines:** regenerate yours with `--set-baseline=psalm-baseline.xml` after upgrading (`--update-baseline` only removes fixed entries, it doesn't add new ones). Some issues are now reported at a different position (for example `ImpureMethodCall`), and baseline entries that are no longer used are reported as `UnusedBaselineEntry`.
- **Purity annotation suggestions are optional.** `MissingPureAnnotation`, `MissingImmutableAnnotation`, `MissingAbstractPureAnnotation` and `MissingInterfaceImmutableAnnotation` are only reported with `<psalm suggestPurityAnnotations="true">` (they are off by default), and `--alter --issues=MissingPureAnnotation,MissingImmutableAnnotation` works without it. If your baseline contains these issues, set the attribute or run `--update-baseline`. Annotating is still worth it, for three reasons:
    - **Fewer security false positives:** Psalm specializes the taints of calls to pure and read-only functions and methods per call site. Tainted input passed at one call then no longer taints the result of every other call. Without annotations, Psalm can only infer this for functions and methods that can't be overridden.
    - **Better type inference:** Psalm remembers the results of methods of immutable classes between calls, so `$a->get() !== null ? $a->get() : ''` is a `string`. Without `@psalm-immutable`, for a class that can be extended, the second call is still `?string`.
    - **Checked contracts:** pure code can call the annotated functions, and every override and implementation is checked not to do more than the annotation allows.
- **Purity annotations:** `@psalm-mutation-free` and `@psalm-external-mutation-free` are still accepted, but Psalm now suggests `@psalm-capabilities`. The `self-accessing-*` and `self-mutating-*` callable types are removed, `@psalm-purity-from $param` is replaced by purity templates, and purity arguments go in square brackets (`Traversable[pure]<int, string>`).
- **Dead code in libraries:** graph-based detection also reports code used only by other unused code, cycles included. Mark entry points and public API with `@psalm-api` (or `@api`).
- **`--output-format=compact`** now selects the new compact format. Use `table` for the old output.
- **PHP 8.1 and 32-bit PHP** are no longer supported for running Psalm. Code targeting older PHP versions can still be analysed.
- **`psalm/plugin-phpunit`:** Psalm 7 requires `^0.21`. Projects that pin `^0.19` or `^0.20` must raise the constraint.
- **Docker:** use the official image, `ghcr.io/psalm/psalm`.
- **Plugins:** taints are integers (`TaintKind` bitmask) in the plugin API. `TKeyedArray` is built with `TKeyedArray::make()`. Several `Codebase` methods take a `Context`. Plugins must require `psalm/psalm-plugin-api` (`^0.4`), because from now on a Psalm minor or patch release may break the plugin API (see [psalm/psalm-plugin-api](https://packagist.org/packages/psalm/psalm-plugin-api)).
