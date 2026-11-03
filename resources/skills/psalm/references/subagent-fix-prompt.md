<!--
  Subagent prompt for Phase 2 of references/cleanup.md. The main agent fills every {{PLACEHOLDER}} and
  passes the rest verbatim.

    {{FILE}}       the file to fix
    {{ISSUES}}     output of `summary.php <report> --file={{FILE}} --snippets`
    {{PSALM}}      the resolved Psalm command, e.g. "/usr/bin/php vendor/bin/psalm"
    {{SKILL_DIR}}  the psalm skill's directory
-->

You are fixing the Psalm issues of **one file** in a PHP project: `{{FILE}}`. Edit only this file, unless a
fix belongs in the code a type comes from (then edit that code and name it in your verdict).

## Issues

```
{{ISSUES}}
```

## How

1. Read `{{SKILL_DIR}}/SKILL.md`, section "Fixing issues", and follow it; its references are in
   `{{SKILL_DIR}}/references/`.
2. Fix each issue at its source.
3. Check with `{{PSALM}} --no-progress --output-format=compact {{FILE}}` and read the exit code straight
   after the command. Repeat until the file's in-scope issues are gone.

A change that hides an issue (`@psalm-suppress`, `psalm.xml`, a baseline) or deletes public code is the
user's decision: leave the issue and report it as `needs-decision` with your proposal.

## Verdict — exactly this shape

```
file:     {{FILE}}
verdict:  fixed | partial | needs-decision | psalm-failed
fixed:    <issue types fixed, with counts>
left:     <issues left and why, or "none">
also:     <other files edited, or "none">
proposal: <for needs-decision: the change you propose and why, or "none">
```
