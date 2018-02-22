# Plugins

Psalm can be extended through plugins to find domain-specific issues.

All plugins must extend `Psalm\Plugin` and return an instance of themselves e.g.

```php
<?php
class SomePlugin extends \Psalm\Plugin
{
}
return new SomePlugin;
```

`Psalm\Plugin` offers two methods that you can override:
 - `afterStatementsCheck` - called after Psalm evaluates each statement
 - `afterExpressionCheck` - called after Psalm evaluates each expression

An example plugin that checks class references in strings is provided [here](https://github.com/vimeo/psalm/blob/master/examples/StringChecker.php).

To ensure your plugin runs when Psalm does, add it to your [config](Configuration):
```php
    <plugins>
        <plugin filename="src/plugins/SomePlugin.php" />
    </plugins>
```
