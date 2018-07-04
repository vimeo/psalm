# Legacy plugins

Note: this is a legacy subsystem, being replaced with a new, composer-based one.

Psalm can be extended through plugins to find domain-specific issues.

All plugins must extend `Psalm\Plugin` and return an instance of themselves e.g.

```php
<?php
class SomePlugin extends \Psalm\Plugin
{
}
return new SomePlugin;
```

`Psalm\Plugin` offers six methods that you can override:
 - `afterStatementsCheck` - called after Psalm evaluates each statement
 - `afterExpressionCheck` - called after Psalm evaluates each expression
 - `afterVisitClassLike` - called after Psalm scans(?) a class-like (class,interface,trait)
 - `afterClassLikeExistsCheck` - called after Psalm analized a reference to a class-like
 - `afterMethodCallCheck` - called after Psalm analized a method call
 - `afterFunctionCallCheck` - called after Psalm analized a function call

An example plugin that checks class references in strings is provided [here](https://github.com/vimeo/psalm/blob/master/examples/StringChecker.php).

To ensure your plugin runs when Psalm does, add it to your [config](Configuration):
```php
    <plugins>
        <plugin filename="src/plugins/SomePlugin.php" />
    </plugins>
```
