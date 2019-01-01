# File-based plugins

Psalm can be extended through plugins to find domain-specific issues.

Plugins may implement one of (or more than one of) `Psalm\Plugin\Hook\*` interface(s).

```php
<?php
class SomePlugin implements \Psalm\Plugin\Hook\AfterStatementAnalysisInterface
{
}
```

`Psalm\Plugin\Hook\*` offers six interfaces that you can implement:

 - `AfterStatementAnalysisInterface` - called after Psalm evaluates each statement
 - `AfterExpressionAnalysisInterface` - called after Psalm evaluates each expression
 - `AfterClassLikeVisitInterface` - called after Psalm crawls the parsed Abstract Syntax Tree for a class-like (class, interface, trait). Due to caching the AST is crawled the first time Psalm sees the file, and is only re-crawled if the file changes, the cache is cleared, or you're disabling cache with `--no-cache`
 - `AfterClassLikeExistenceCheckInterface` - called after Psalm analyzes a reference to a class-like
 - `AfterMethodCallAnalysisInterface` - called after Psalm analyzes a method call
 - `AfterFunctionCallAnalysisInterface` - called after Psalm analyzes a function call

An example plugin that checks class references in strings is provided [here](https://github.com/vimeo/psalm/blob/master/examples/plugins/StringChecker.php).

To ensure your plugin runs when Psalm does, add it to your [config](Configuration):
```php
    <plugins>
        <plugin filename="src/plugins/SomePlugin.php" />
    </plugins>
```

# Composer-based plugins

Composer-based plugins provide easier way to manage and distribute your plugins.

## Using composer-based plugins
### Discovering plugins

Plugins can be found on Packagist by `type=psalm-plugin` query: https://packagist.org/packages/list.json?type=psalm-plugin

### Installing plugins

`composer require --dev plugin-vendor/plugin-package`

### Managing known plugins

Once installed, you can use `psalm-plugin` tool to enable, disable and show available and enabled plugins.

To enable the plugin, run `psalm-plugin enable plugin-vendor/plugin-package`. To disable it, run `psalm-plugin disable plugin-vendor/plugin-package`. `psalm-plugin show` (as well as bare `psalm-plugin`) will show you the list of enabled plugins, and the list of plugins known to `psalm-plugin` (installed into your `vendor` folder)

## Authoring composer-based plugins

### Requirements

Composer-based plugin is a composer package which conforms to these requirements:

1. Its `type` field is set to `psalm-plugin`
2. It has `extra.psalm.pluginClass` subkey in its `composer.json` that reference an entry-point class that will be invoked to register the plugin into Psalm runtime.
3. Entry-point class implements `Psalm\Plugin\PluginEntryPointInterface`

### Using skeleton project

Run `composer create-project weirdan/psalm-plugin-skeleton:dev-master your-plugin-name` to quickly bootstrap a new plugin project in `your-plugin-name` folder. Make sure you adjust namespaces in `composer.json`, `Plugin.php` and `tests` folder.

### Upgrading file-based plugin to composer-based version

Create new plugin project using skeleton, then pass the class name of you file-based plugin to `registerHooksFromClass()` method of the `Psalm\Plugin\RegistrationInterface` instance that was passed into your plugin entry point's `__invoke()` method. See the [conversion example](https://github.com/vimeo/psalm/tree/master/examples/plugins/composer-based/echo-checker/).

### Registering stub files

Use `Psalm\Plugin\RegistrationInterface::addStubFile()`. See the [sample plugin] (https://github.com/weirdan/psalm-doctrine-collections/).

Stub files provide a way to override third-party type information when you cannot add Psalm's extended docblocks to the upstream source files directly.
