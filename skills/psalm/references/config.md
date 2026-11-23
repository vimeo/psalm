# psalm.xml

Every option: `docs/running_psalm/configuration.md`; the schema is `vendor/vimeo/psalm/config.xsd`.
Changing `psalm.xml` changes what is reported for the whole team: make the change the user asked for, or
propose one and explain it.

## Which files

| Element | Effect |
|---|---|
| `<projectFiles>` | analysed: issues are reported here |
| `<projectFiles><ignoreFiles>` | inside the project directories, but not analysed |
| `<extraFiles>` | scanned for classes and functions, never reported (e.g. a plugin directory) |
| Composer `autoload` and `vendor/` | scanned on demand, never reported |

A class Psalm can't find (`UndefinedClass` for code that exists) is usually outside both: check the Composer
autoloader (`composer dump-autoload`) or add the directory to `<extraFiles>`.

## Extensions

Psalm loads the stubs of a PHP extension (`pdo`, `mysqli`, `redis`, `mongodb`, `imagick`, …) only when
`composer.json` requires it (`"ext-pdo": "*"`) or `psalm.xml` enables it:

```xml
<enableExtensions>
    <extension name="pdo"/>
</enableExtensions>
```

Without it, the extension's methods return `mixed` and their taint sinks are missing (no `TaintedSql` for
`PDO::query()`). Adding `ext-*` to `composer.json` is the better fix: it is true for the project anyway.

## Stubs and plugins

- `<stubs><file name="stubs/Legacy.phpstub"/></stubs>`: declarations Psalm should believe over the real code,
  e.g. a library without types. Stubs don't override classes inside `<projectFiles>`.
- Plugins add framework knowledge. Install with Composer, enable with
  `vendor/bin/psalm-plugin enable <package>`:
  `psalm/plugin-phpunit`, `psalm/plugin-symfony`, `psalm/plugin-laravel`, `weirdan/doctrine-psalm-plugin`,
  `psalm/plugin-mockery`. `vendor/bin/psalm-plugin show` lists what is installed.

## Notable attributes of `<psalm>`

| Attribute | Default | Effect |
|---|---|---|
| `errorLevel` | `2` | 1 strictest … 8 most lenient, see `docs/running_psalm/error_levels.md` |
| `findUnusedCode` | `true` | `Unused*` / `PossiblyUnused*` issues |
| `findUnusedBaselineEntry` | `true` | `UnusedBaselineEntry` for stale baseline entries |
| `runTaintAnalysis` | `true` | `SECURITY` findings |
| `phpVersion` | from `composer.json` | target PHP version; `--php-version` overrides it |
| `ensureOverrideAttribute` | `true` | `MissingOverrideAttribute` for overriding methods without `#[\Override]` |

`issueHandlers` and baselines: [fixing.md](fixing.md).
