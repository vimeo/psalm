# Using Plugins

Psalm can be extended through plugins to find and fix domain-specific issues.

## Using your own plugins

You can [write your own Psalm plugins](authoring_plugins.md) and reference them in your Psalm config.

## Using Composer-based plugins

Composer-based plugins provide an easier way to manage and distribute your plugins.

### Discovering plugins

Plugins can be found on Packagist by [setting the package type filter to `psalm-plugin`](https://packagist.org/?type=psalm-plugin) or using the `type=psalm-plugin` query: https://packagist.org/packages/list.json?type=psalm-plugin

### Installing plugins

`composer require --dev plugin-vendor/plugin-package`

### Managing known plugins

Once installed, you can use `psalm-plugin` tool to enable, disable and show available and enabled plugins.

To enable the plugin, run `psalm-plugin enable plugin-vendor/plugin-package`. To disable it, run `psalm-plugin disable plugin-vendor/plugin-package`. `psalm-plugin show` (as well as bare `psalm-plugin`) will show you the list of enabled plugins, and the list of plugins known to `psalm-plugin` (installed into your `vendor` folder)

