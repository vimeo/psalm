# Custom Taint Sources

You can define your own taint sources with an annotation or a plugin.

## Taint source annotation

You can use the annotation `@psalm-taint-source <taint-type>` to indicate a function or method that provides user input.

In the below example the `input` taint type is specified as a standin for input taints as defined in [Psalm\Type\TaintKind](https://github.com/vimeo/psalm/blob/master/src/Psalm/Type/TaintKind.php).

```php
<?php
/**
 * @psalm-taint-source input
 */
function getQueryParam(string $name) : string {}
```

When only a key of what a function or a method returns comes from the user, name it after `return`: the other keys
aren't sources. Keys are quoted (`'name'` or `"name"`) or digits, and can be nested: `return['file']['name']`.

```php
<?php
/**
 * The name the client gave the uploaded file is its input; the path the upload is moved to isn't.
 *
 * @psalm-taint-source input return['original']
 * @return array{original: string, path: string}
 */
function handleUpload(): array {}
```

## Custom taints declared in the configuration

Custom taints can be declared in `psalm.xml`, so that the annotations can name them without a plugin registering
them:

```xml
<taintAnalysis>
  <taints>
    <taint name="file_upload">
      <alias name="input" />
    </taint>
    <taint name="session_id" />
  </taints>
</taintAnalysis>
```

A taint added to the alias `input` with `<alias name="input" />` is a kind of user input like the built-in ones:
`$_GET` and the other built-in sources of user input give it, `@psalm-taint-source input`, `@psalm-taint-sink input`
and `@psalm-taint-escape input` name it, and a value of a type that can't hold user input (a literal string, a number,
a boolean, null) can't carry it. Plugins get these taints with `Codebase::getInputTaints()`. Each of the other aliases,
`tainted` and `input_except_sleep`, holds the custom taints added to it with its own `<alias>`, and only those. A
taint without an alias, like `session_id` above, only comes from the annotations naming it
(`@psalm-taint-source session_id`), and only reaches the sinks naming it.

A taint's name is a word (letters, digits, `_` and `-`) other than those of the built-in taints.

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
            self::$myCustomTaint | $codebase->getInputTaints()
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
            // the taints of user input: the built-in kinds, and the custom taints psalm.xml adds to `input`
            return $event->getCodebase()->getInputTaints();
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
