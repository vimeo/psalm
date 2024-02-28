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

namespace Some\Ns;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\TaintKindGroup;

class BadSqlTainter implements AfterExpressionAnalysisInterface
{
    /**
     * Called after an expression has been checked
     *
     * @param  PhpParser\Node\Expr  $expr
     * @param  Context              $context
     * @param  FileManipulation[]   $file_replacements
     *
     * @return void
     */
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool {
        $expr = $event->getExpr();
        $statements_source = $event->getStatementsSource();
        $codebase = $event->getCodebase();
        if ($expr instanceof PhpParser\Node\Expr\Variable
            && $expr->name === 'bad_data'
        ) {
            $expr_type = $statements_source->getNodeTypeProvider()->getType($expr);

            // should be a globally unique id
            // you can use its line number/start offset
            $expr_identifier = '$bad_data'
                . '-' . $statements_source->getFileName()
                . ':' . $expr->getAttribute('startFilePos');

            if ($expr_type) {
                $codebase->addTaintSource(
                    $expr_type,
                    $expr_identifier,
                    TaintKindGroup::ALL_INPUT,
                    new CodeLocation($statements_source, $expr)
                );
            }
        }
        return null;
    }
}
```
