<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use PhpParser\Node\Expr;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Type\Union;

use function explode;
use function strtolower;

/**
 * The taints `@psalm-taint-source` gives what is read from a property: on the property (`@psalm-taint-source input`
 * above it, or above a promoted constructor parameter), or on a class for one of its properties, magic or declared
 * (`@psalm-taint-source input $name`). A read gets those of the class it reads the property from and of the classes,
 * interfaces and traits it extends, implements or uses, as a method gets the sources of the methods it overrides.
 *
 * @internal
 */
final class PropertyTaintSources
{
    /**
     * @psalm-capabilities read-props
     */
    public static function getTaints(Codebase $codebase, string $fq_class_name, string $property_name): int
    {
        if (!$codebase->classlike_storage_provider->has($fq_class_name)) {
            return 0;
        }

        $storage = $codebase->classlike_storage_provider->get($fq_class_name);

        $taints = 0;
        foreach ([$storage->name => true]
            + $storage->parent_classes
            + $storage->class_implements
            + $storage->used_traits as $classlike_name => $_
        ) {
            if (!$codebase->classlike_storage_provider->has($classlike_name)) {
                continue;
            }

            $classlike_storage = $codebase->classlike_storage_provider->get($classlike_name);

            $taints |= ($classlike_storage->properties[$property_name] ?? null)?->taint_sources ?? 0;
            $taints |= $classlike_storage->property_taint_sources['$' . $property_name] ?? 0;
        }

        return $taints;
    }

    /**
     * Makes what $stmt reads from the property $property_id (`Class::$name`) a source of the taints declared for
     * it, but $removed_taints.
     */
    public static function addSource(
        StatementsAnalyzer $statements_analyzer,
        Expr $stmt,
        Union &$type,
        string $property_id,
        int $removed_taints,
    ): void {
        $graph = $statements_analyzer->taint_flow_graph;
        if ($graph === null) {
            return;
        }

        [$fq_class_name, $property_name] = self::splitPropertyId($property_id);
        if ($property_name === null) {
            return;
        }

        $taints = self::getTaints($statements_analyzer->getCodebase(), $fq_class_name, $property_name)
            & ~$removed_taints;
        if ($taints === 0) {
            return;
        }

        $source = DataFlowNode::getForTaint(
            strtolower($property_id),
            new CodeLocation($statements_analyzer->getSource(), $stmt),
            $taints,
        );
        $graph->addSource($source);

        $type = $type->addParentNodes([$source->id => $source]);
    }

    /**
     * @return array{string, ?string}
     * @psalm-pure
     */
    private static function splitPropertyId(string $property_id): array
    {
        $parts = explode('::$', $property_id, 2);

        return [$parts[0], $parts[1] ?? null];
    }
}
