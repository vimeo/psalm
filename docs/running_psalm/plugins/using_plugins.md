# Using Plugins

Psalm can be extended through plugins to find and fix domain-specific issues.

## Using Composer-based plugins

Psalm plugins are distributed as composer packages.

### Discovering plugins

Plugins can be found on Packagist website: https://packagist.org/?type=psalm-plugin  or from CLI using `composer search -t psalm-plugin '.'`

### Installing plugins

`composer require --dev <plugin-vendor/plugin-package>`

### Managing known plugins

Once installed, use `psalm-plugin` tool to enable, disable and show available and enabled plugins.

To enable the plugin, run `psalm-plugin enable plugin-vendor/plugin-package`. To disable it, run `psalm-plugin disable plugin-vendor/plugin-package`. `psalm-plugin show` (as well as bare `psalm-plugin`) will show you the list of enabled plugins, and the list of plugins known to `psalm-plugin` (installed into your `vendor` folder)

## Using your own plugins

Is there no plugin for your favourite framework / library yet? Create it! It's as easy as forking a repository, tweaking some docblocks and publishing the package to Packagist.

Consult [Authoring Plugins](authoring_plugins.md) chapter to get started.
