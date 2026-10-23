<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Type\Union;

/**
 * The properties of objects holding them in an array property under their names (models of ORMs, property bags):
 * with `@psalm-taint-property-bag $properties` on the class, `$this->properties['title']` in its methods and those of
 * its subclasses is `$this->title`, and `$this->properties['title'] = $value` sets `$this->title`, through the nodes
 * ReceiverPropertyTaints uses, so that they meet `$object->title`, `@property` tags and the receiver flows of
 * `@psalm-flow` (`$this->$name`, `$this->{key}`).
 *
 * Only an element under a literal key is a property: what is fetched or assigned under another key, and the whole
 * array, stay the array property's.
 *
 * @internal
 */
final class PropertyBagTaints
{
    /**
     * Makes $stmt_type, fetched from $var under $offset_type, get the property the key names, when $var is the property
     * bag of `$this`
     */
    public static function taintFetch(
        StatementsAnalyzer $statements_analyzer,
        Expr $var,
        Union $offset_type,
        Union &$stmt_type,
        ?Context $context,
    ): void {
        $graph = $statements_analyzer->getTaintFlowGraphWithSuppressed();
        $name = $offset_type->isSingleStringLiteral() ? $offset_type->getSingleStringLiteral()->value : null;
        if ($graph === null
            || $context === null
            || $name === null
            || !self::isPropertyBagOfThis($statements_analyzer->getCodebase(), $var, $context)
        ) {
            return;
        }

        $fetch_node = DataFlowNode::getForAssignment(
            '$this->' . $name . ' in property bag',
            new CodeLocation($statements_analyzer->getSource(), $var),
        );

        ReceiverPropertyTaints::addReadOfThis(
            $statements_analyzer->getCodebase(),
            $graph,
            (string) $context->self,
            $name,
            $fetch_node,
            $context,
        );

        $stmt_type = $stmt_type->addParentNodes([$fetch_node->id => $fetch_node]);
    }

    /**
     * Sets the property the first key of $stmt names to $value_type, under the keys after it, when the array assigned
     * to is the property bag of `$this`
     */
    public static function taintAssignment(
        StatementsAnalyzer $statements_analyzer,
        ArrayDimFetch $stmt,
        Union $value_type,
        Context $context,
    ): void {
        $graph = $statements_analyzer->getTaintFlowGraphWithSuppressed();
        if ($graph === null || $value_type->parent_nodes === []) {
            return;
        }

        // the keys under which the value goes into the property, the outermost first
        $inner_dims = [];
        $property_fetch = $stmt;
        while ($property_fetch->var instanceof ArrayDimFetch) {
            $inner_dims[] = $property_fetch->dim;
            $property_fetch = $property_fetch->var;
        }

        $name_type = $property_fetch->dim ? $statements_analyzer->node_data->getType($property_fetch->dim) : null;
        if (!$name_type
            || !$name_type->isSingleStringLiteral()
            || !self::isPropertyBagOfThis($statements_analyzer->getCodebase(), $property_fetch->var, $context)
        ) {
            return;
        }

        $location = new CodeLocation($statements_analyzer->getSource(), $stmt);
        $name = $name_type->getSingleStringLiteral()->value;

        $value_nodes = $value_type->parent_nodes;
        foreach ($inner_dims as $offset => $dim) {
            $dim_type = $dim ? $statements_analyzer->node_data->getType($dim) : null;
            $key = $dim_type?->isSingleStringLiteral()
                ? $dim_type->getSingleStringLiteral()->value
                : ($dim_type?->isSingleIntLiteral() ? $dim_type->getSingleIntLiteral()->value : null);

            $element_node = DataFlowNode::getForAssignment(
                '$this->' . $name . ' in property bag element ' . $offset,
                $location,
            );
            $graph->addNode($element_node);

            foreach ($value_nodes as $value_node) {
                $graph->addPath(
                    $value_node,
                    $element_node,
                    'arrayvalue-assignment' . ($key === null ? '' : '-\'' . $key . '\''),
                );
            }

            $value_nodes = [$element_node->id => $element_node];
        }

        $write_node = DataFlowNode::getForAssignment('$this->' . $name . ' in property bag', $location);
        foreach ($value_nodes as $value_node) {
            $graph->addPath($value_node, $write_node, '=');
        }

        ReceiverPropertyTaints::addWriteOfThis(
            $statements_analyzer->getCodebase(),
            $graph,
            (string) $context->self,
            $name,
            $write_node,
            $context,
            $location,
        );
    }

    /**
     * Whether $var is `$this->properties` in a method of a class whose property bag is `$properties`
     */
    private static function isPropertyBagOfThis(Codebase $codebase, Expr $var, Context $context): bool
    {
        if (!$var instanceof PropertyFetch
            || !$var->var instanceof Variable
            || $var->var->name !== 'this'
            || !$var->name instanceof Identifier
            || $context->self === null
            || !isset($context->vars_in_scope['$this'])
            || !$codebase->classlike_storage_provider->has($context->self)
        ) {
            return false;
        }

        return $codebase->classlike_storage_provider->get($context->self)->taint_property_bag === $var->name->name;
    }
}
