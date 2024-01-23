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
use Psalm\Plugin\EventHandler\AddTaintsInterface;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Type\TaintKindGroup;

/**
 * Add input taints to all variables named 'bad_data'
 */
class TaintBadDataPlugin implements AddTaintsInterface
{
    /**
     * Called to see what taints should be added
     *
     * @return list<string>
     */
    public static function addTaints(AddRemoveTaintsEvent $event): array
    {
        $expr = $event->getExpr();

        if ($expr instanceof Variable && $expr->name === 'bad_data') {
            return TaintKindGroup::ALL_INPUT;
        }

        return [];
    }
}
```
