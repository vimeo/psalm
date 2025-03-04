# Custom Taint Sources

You can define your own taint sources with an annotation or a plugin.

## Taint source annotation

You can use the annotation `@psalm-taint-source <taint-type>` to indicate a function or method that provides user input.

In the below example the `input` taint type is specified as a standin for input taints as defined in [Psalm\Type\TaintKindGroup](https://github.com/vimeo/psalm/blob/master/src/Psalm/Type/TaintKindGroup.php).

```php
<?php
/**
 * @psalm-taint-source input
 */
function getQueryParam(string $name) : string {}
```

## Custom taint plugin

For example this plugin treats all variables named `$bad_data` as taint sources.

```php
<?php

namespace Psalm\Example\Plugin;

use PhpParser\Node\Expr\Variable;
use Psalm\Codebase;
use Psalm\Plugin\EventHandler\AddTaintsInterface;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Type\TaintKind;

/**
 * Add input taints to all variables named 'bad_data' or 'even_badder_data'.
 * 
 * RemoveTaintsInterface is also available to remove taints.
 */
class TaintBadDataPlugin implements AddTaintsInterface
{
    private static int $myCustomTaint;
    private static int $myCustomTaintAlias;
    /**
     * Must be called by the PluginEntryPointInterface (__invoke) of your plugin.
     */
    public static function init(Codebase $codebase): void
    {
        // Register a new custom taint
        // The taint name may be used in @psalm-taint-* annotations in the code.
        self::$myCustomTaint = $codebase->getOrRegisterTaint("my_custom_taint");

        // Register a taint alias that combines multiple pre-registered taint types
        // Taint alias names may be used in @psalm-taint-* annotations in the code.
        self::$myCustomTaintAlias = $codebase->registerTaintAlias(
            "my_custom_taint_alias",
            self::$myCustomTaint | TaintKind::ALL_INPUT
        );
    }

    /**
     * Called to see what taints should be added
     *
     * @return int A bitmap of taint from the IDs
     */
    public static function addTaints(AddRemoveTaintsEvent $event): int
    {
        $expr = $event->getExpr();

        if ($expr instanceof Variable && $expr->name === 'bad_data') {
            return TaintKind::ALL_INPUT;
        }

        if ($expr instanceof Variable && $expr->name === 'even_badder_data') {
            return self::$myCustomTaint;
        }

        if ($expr instanceof Variable && $expr->name === 'even_badder_data_2') {
            return self::$myCustomTaintAlias;
        }

        if ($expr instanceof Variable && $expr->name === 'secret_even_badder_data_3') {
            // Combine taints using |
            return self::$myCustomTaintAlias | USER_SECRET;
        }

        if ($expr instanceof Variable && $expr->name === 'bad_data_but_ok_cookie') {
            // Remove taints using & and ~ to negate a taint (group)
            return self::$myCustomTaintAlias & ~TaintKind::INPUT_COOKIE;
        }

        // No taints
        return 0;
    }
}
```
