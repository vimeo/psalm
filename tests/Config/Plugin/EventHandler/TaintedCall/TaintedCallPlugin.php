<?php

declare(strict_types=1);

namespace Psalm\Example\Plugin;

use Override;
use Psalm\Plugin\EventHandler\Event\AddRemoveCallTaintsEvent;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Plugin\EventHandler\RemoveCallTaintsInterface;
use Psalm\Type;
use Psalm\Type\TaintKind;
use Psalm\Type\Union;

use function array_values;
use function strtolower;

/**
 * `call_through('function_name', ...$args)` calls the function-like it names with the other arguments, as a template
 * engine would: Codebase::addTaintedCall() wires it. What `clean()` is given loses its HTML taints, whoever calls it.
 *
 * @psalm-suppress UnusedClass
 */
final class TaintedCallPlugin implements FunctionReturnTypeProviderInterface, RemoveCallTaintsInterface
{
    /**
     * @return list<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['call_through'];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $source = $event->getStatementsSource();
        $codebase = $source->getCodebase();
        $args = $event->getCallArgs();

        $target = isset($args[0]) ? $source->getNodeTypeProvider()->getType($args[0]->value) : null;
        if ($target === null || !$target->isSingleStringLiteral()) {
            return null;
        }

        $argument_nodes = [];
        $argument_types = [];
        foreach ($args as $offset => $arg) {
            if ($offset === 0) {
                continue;
            }

            $type = $source->getNodeTypeProvider()->getType($arg->value);
            $argument_nodes[] = array_values($type->parent_nodes ?? []);
            if ($type !== null) {
                $argument_types[$offset - 1] = $type;
            }
        }

        $node = $codebase->addTaintedCall(
            $target->getSingleStringLiteral()->value,
            $argument_nodes,
            $argument_types,
            $event->getCodeLocation(),
            $source,
        );

        $type = Type::getString();

        return $node === null ? $type : $type->addParentNodes([$node->id => $node]);
    }

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public static function removeCallTaints(AddRemoveCallTaintsEvent $event): int
    {
        return strtolower($event->getFunctionId()) === 'clean' && $event->getArgumentOffset() === 0
            ? TaintKind::INPUT_HTML
            : 0;
    }
}
