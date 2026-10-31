<?php

declare(strict_types=1);

namespace Psalm\Example\Plugin;

use Override;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use Psalm\CodeLocation;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\TaintKind;

use function strtolower;

/**
 * Models what `app\Page` does with the components given to `$page->add($component, $title)`, which the analyzed
 * code doesn't show: it keeps the title in its `$title` property, and outputs unescaped what the component's
 * `render($title)` returns, given that component and that title.
 */
final class TaintPageComponents implements AfterExpressionAnalysisInterface
{
    #[Override]
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        $call = $event->getExpr();
        if (!$call instanceof MethodCall
            || !$call->name instanceof Identifier
            || $call->name->toLowerString() !== 'add'
            || $call->isPartialFunctionApplication()
        ) {
            return null;
        }

        $source = $event->getStatementsSource();
        $codebase = $event->getCodebase();
        if (!$codebase->tracksTaintFlow($source)) {
            return null;
        }

        $node_types = $source->getNodeTypeProvider();
        $page_type = $node_types->getType($call->var);
        $page = $page_type !== null && $page_type->isSingle() ? $page_type->getSingleAtomic() : null;
        $args = $call->getArgs();
        $component_type = isset($args[0]) ? $node_types->getType($args[0]->value) : null;
        if (!$page instanceof TNamedObject
            || $component_type === null
            || (strtolower($page->value) !== 'app\page' && !$codebase->classExtends($page->value, 'app\Page'))
        ) {
            return null;
        }

        $location = new CodeLocation($source, $call);
        $title = $codebase->getTaintNode(
            $source,
            'page title',
            $location,
            isset($args[1]) ? $node_types->getType($args[1]->value) : null,
        );
        $codebase->addTaintPropertyAssignment($source, $title, $page->value . '::$title');

        $sink = $codebase->addTaintSink('page component', $location, TaintKind::INPUT_HTML);
        if ($sink === null) {
            return null;
        }

        foreach ($component_type->getAtomicTypes() as $component) {
            if (!$component instanceof TNamedObject) {
                continue;
            }

            // the call of render() the page makes: its result, its object and its first argument
            $render = $component->value . '::render';
            $output = $codebase->getTaintReturnNode($source, $render, $location);
            if ($output === null) {
                continue;
            }

            $object = $codebase->getTaintReceiverNode($source, $render, $location);
            if ($object !== null) {
                foreach ($component_type->parent_nodes as $parent_node) {
                    $codebase->addTaintPath($source, $parent_node, $object);
                }
            }

            $title_param = $codebase->getTaintArgumentNode($source, $render, 0, $location);
            if ($title_param !== null) {
                $codebase->addTaintPath($source, $title, $title_param, 'arg');
            }

            $codebase->addTaintPath($source, $output, $sink);
        }

        return null;
    }
}
