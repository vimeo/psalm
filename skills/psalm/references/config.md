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

Code using a function or class of an extension `composer.json` doesn't declare is
`MissingExtensionRequirement`. Add the extension to `require` (`"ext-mbstring": "*"`), or to `suggest` when the
code checks `extension_loaded()` first.

Psalm loads the precise stubs of an extension (`pdo`, `redis`, `mongodb`, `imagick`, …) only when
`composer.json` requires it or `psalm.xml` enables it; without them its methods may return `mixed`:

```xml
<enableExtensions>
    <extension name="redis"/>
</enableExtensions>
```

`ext-*` in `composer.json` is the better fix: it is true for the project anyway.

## Stubs and plugins

- `<stubs><file name="stubs/Legacy.phpstub"/></stubs>`: declarations Psalm should believe over the real code,
  e.g. a library without types. Stubs don't override classes inside `<projectFiles>`.
- Plugins add framework knowledge. Psalm ships the plugins of PHPUnit, Mockery, Symfony, Laravel, Doctrine
  and webmozart/assert and enables each when the project depends on its library (`loadLibraryPlugins`);
  `--debug` prints `Loaded plugin …` for each. Others: install with Composer, enable with
  `vendor/bin/psalm-plugin enable <package>`.

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
