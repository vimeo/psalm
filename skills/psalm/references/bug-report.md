# Is it a Psalm bug?

Most surprising results come from the setup. Rule these out first:

1. `--no-cache` gives the same result.
2. The class or function is known to Psalm: autoloader, `<extraFiles>`, `ext-*` in `composer.json`, the
   framework's plugin ([config.md](config.md)).
3. The inferred types are what you think: `@psalm-check-type` along the way ([debugging.md](debugging.md)).
4. `docs/running_psalm/common_issues.md` and the issue's page (`php scripts/explain.php IssueType`)
   don't describe the behaviour as intended.

## Minimal repro

Reduce the case to one self-contained file that defines everything it uses: no framework, no Composer
packages. Check after every step that the result is still the one you are reducing.

```bash
php scripts/sandbox.php repro.php            # issues and inferred types from psalm.dev
php scripts/sandbox.php repro.php --php=8.3  # PHP version of the sandbox
```

The sandbox runs the latest development version of Psalm. If it doesn't show the problem, it may already be
fixed: compare with `vendor/bin/psalm --version`. It sends the file to psalm.dev, so it is for the reduced
repro only, never for code of the project.

Locally, the same file in a scratch directory:

```xml
<?xml version="1.0"?>
<psalm errorLevel="1" findUnusedCode="false">
    <projectFiles><directory name="src"/></projectFiles>
</psalm>
```

`vendor/bin/psalm --no-cache -c /tmp/repro/psalm.xml --root=/tmp/repro`. Empty output means Psalm didn't
run: check that the file exists.

## Reporting

Search first: `gh search issues --repo vimeo/psalm "<IssueType> <keyword>"`.

Draft the issue for the user from `.github/ISSUE_TEMPLATE/bug_report.md` of vimeo/psalm: the repro, what
Psalm reports, what was expected, `vendor/bin/psalm --version` and `php --version`. The template asks for a
psalm.dev link: the user pastes the repro on https://psalm.dev and shares it there, since a shared snippet is
public and permanent. The user opens the issue.
