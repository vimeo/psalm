---
name: plugin-api-bc
description: Use when the "Check backward compatibility" CI check (roave/backward-compatibility-check) fails on a vimeo/psalm PR, or when a PR deliberately changes Psalm's public/plugin API. Covers telling real BC breaks from false positives, fixing false positives in .roave-backward-compatibility-check.xml, and the companion psalm/psalm-plugin-api PR (version bump + changelog).
---

# Backward-compatibility check and psalm-plugin-api

A red "Check backward compatibility" is never just "expected red". Every error it lists
is either a **real BC break**, which must be announced through psalm/psalm-plugin-api, or
a **false positive**, which must be fixed in `.roave-backward-compatibility-check.xml`.
When the check is done, it is green.

## 1. Read the errors

`gh run view <run-id> --repo vimeo/psalm --log-failed | grep '\[BC\]'`. Locally (mirrors
`.github/workflows/bcc.yml`): `COMPOSER_ROOT_VERSION=dev-master
vendor-bin/bcc/vendor/bin/roave-backward-compatibility-check --from=origin/master`
(after `composer bin bcc install`, if available).

## 2. Classify each error

**False positive: not a BC issue.** Fix in the XML (step 3), never in the plugin-api
changelog:
- Values of bit/flag constants, aggregate masks, counts and name maps that change only
  because a member was **added**: e.g. a new taint kind shifts
  `TaintKind::USER_SECRET`/`SYSTEM_SECRET` and changes `ALL_INPUT`,
  `BUILTIN_TAINT_COUNT`, `TAINT_NAMES`. These constants are meant to be used
  symbolically, so an addition is absolutely normal.
- Default parameter values that only change because they reference such a constant.
- Anything else that is purely a consequence of an addition, or of a fix to a
  clearly-wrong value (precedent in the XML: a duplicate `SHORTCODE`).

**Real BC break: announce it** (step 4):
- removed/renamed classes, methods, properties, constants or parameters;
- changed signatures (new required params, narrower param types, wider return types,
  changed by-ref-ness), visibility reductions, classes made final;
- changed **meaning** of an existing API, even if the check can't see it: an existing
  constant/bit now covers something different (e.g. `time` split out of `io`), a default
  that now behaves differently, a method returning a different shape.

If unsure, treat it as real and ask.

## 3. Fix false positives in `.roave-backward-compatibility-check.xml`

In the same vimeo/psalm PR, add one `<ignored-regex>` per false positive under
`<baseline>`, with a short XML comment saying why it isn't a BC break:

```xml
<!-- Adding a taint kind shifts the bits after it and changes the aggregates; plugins use them symbolically. -->
<ignored-regex>#^\[BC\] CHANGED: Value of constant Psalm\\Type\\TaintKind::(USER_SECRET|SYSTEM_SECRET|ALL_INPUT|BUILTIN_TAINT_COUNT|TAINT_NAMES) changed from #</ignored-regex>
```

- Anchor with `^` and escape backslashes (`\\`) and brackets (`\[BC\]`), as the existing
  entry does.
- Keep each regex as narrow as the false positive (name the class and the constants), so
  a real break elsewhere still fails the check. Prefer patterns that stay valid for the
  next addition of the same kind (don't pin the old/new values unless it's a one-off
  correction).
- This is the sanctioned fix for check bugs. It is **not** a way to silence real breaks:
  those go to step 4, never into the XML.

## 4. Real breaks: PR to psalm/psalm-plugin-api

psalm/psalm-plugin-api is an empty metapackage (`README.md` + `composer.json`); its
version is the plugin-API version and its README is the changelog.

1. Clone it into a scratch dir and branch off `master`; push the branch to your fork of
   it (or a branch on psalm/psalm-plugin-api if you have write access), then
   `gh pr create --repo psalm/psalm-plugin-api --base master`.
2. **Version:** while it's `0.x`, a release with BC breaks bumps the minor
   (`0.4.1` → `0.5.0`); additions only bump the patch (`0.4.0` → `0.4.1`). From `1.0.0`
   on, BC breaks bump the major and additions the minor (per the README). If the
   unreleased top section already exists from an earlier PR, add to it (and upgrade it
   to a BC bump if needed) instead of creating another.
3. **Changelog:** add a `` ### `X.Y.Z` `` section at the top of `## Changelog` in
   `README.md`. One bullet per change, `[BC]` prefix on breaking ones, written for plugin
   authors: name the exact symbols (`Psalm\Codebase#method()`, `Class::CONST`,
   `Class#$property`) and say what to use instead. Match the style of the existing
   entries. Add a "Covers …" line when it marks a Psalm release boundary.
4. **`composer.json`:** raise `conflict.vimeo/psalm` (`"<7.0.0-rcN"`) only if the
   release requires a newer Psalm than the current bound.
5. Link the two PRs to each other in a comment (not in the vimeo/psalm description,
   which follows skill `pr-description`).
6. After the plugin-api PR merges, tag the release (`gh release create X.Y.Z --repo
   psalm/psalm-plugin-api`) so Packagist publishes it, then bump Psalm's exact
   requirement `"psalm/psalm-plugin-api": "X.Y.Z"` in vimeo/psalm's `composer.json`
   (precedent commits: "Require psalm/psalm-plugin-api 0.4.0"/"0.4.1"), either in the
   Psalm PR or right after it merges. Don't bump the requirement before the tag exists:
   `composer install` in CI would fail.

Additions to the plugin API that aren't breaks (a new public method, event, annotation
plugins can use) also get a plugin-api changelog entry with a patch/minor bump, as
`0.4.1` did, but they don't need one to make the check green.
