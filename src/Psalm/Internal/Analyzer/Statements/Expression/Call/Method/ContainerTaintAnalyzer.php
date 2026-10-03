<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call\Method;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Assignment\InstancePropertyAssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\DataFlowGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\MethodIdentifier;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

use function in_array;
use function strtolower;

/**
 * The containers of the standard library hold what they are given (in their constructors, see the stubs, and through
 * their methods), and give it back through their methods: what a method puts in flows into the container, as into an
 * array, and what a method gives back comes from the container, as from an array.
 *
 * @internal
 */
final class ContainerTaintAnalyzer
{
    /**
     * The methods putting a value into a container (or each of its subclasses) => the offset of the argument => how it
     * flows into the container
     */
    private const INPUTS = [
        'arrayiterator' => [
            'append' => [0 => 'arrayvalue-assignment'],
            'offsetset' => [0 => 'arraykey-assignment', 1 => 'arrayvalue-assignment'],
        ],
        'arrayobject' => [
            'append' => [0 => 'arrayvalue-assignment'],
            'exchangearray' => [0 => '='],
            'offsetset' => [0 => 'arraykey-assignment', 1 => 'arrayvalue-assignment'],
        ],
        'appenditerator' => [
            'append' => [0 => '='],
        ],
        'spldoublylinkedlist' => [
            'add' => [1 => 'arrayvalue-assignment'],
            'offsetset' => [1 => 'arrayvalue-assignment'],
            'push' => [0 => 'arrayvalue-assignment'],
            'unshift' => [0 => 'arrayvalue-assignment'],
        ],
        'splqueue' => [
            'enqueue' => [0 => 'arrayvalue-assignment'],
        ],
        'splfixedarray' => [
            'offsetset' => [1 => 'arrayvalue-assignment'],
        ],
        'splheap' => [
            'insert' => [0 => 'arrayvalue-assignment'],
        ],
        'splpriorityqueue' => [
            'insert' => [0 => 'arrayvalue-assignment'],
        ],
        'splobjectstorage' => [
            'addall' => [0 => '='],
            'attach' => [0 => 'arraykey-assignment', 1 => 'arrayvalue-assignment'],
            'offsetset' => [0 => 'arraykey-assignment', 1 => 'arrayvalue-assignment'],
            'setinfo' => [0 => 'arrayvalue-assignment'],
        ],
    ];

    /**
     * The methods giving back what a container (or each of its subclasses) holds => how it flows out of the container
     */
    private const OUTPUTS = [
        'arrayiterator' => [
            'current' => 'arrayvalue-fetch',
            'getarraycopy' => '=',
            'key' => 'arraykey-fetch',
            'offsetget' => 'arrayvalue-fetch',
        ],
        'arrayobject' => [
            'exchangearray' => '=',
            'getarraycopy' => '=',
            'getiterator' => '=',
            'offsetget' => 'arrayvalue-fetch',
        ],
        'iteratoriterator' => [
            'current' => 'arrayvalue-fetch',
            'getinneriterator' => '=',
            'key' => 'arraykey-fetch',
        ],
        'recursiveiteratoriterator' => [
            'current' => 'arrayvalue-fetch',
            'getinneriterator' => '=',
            'key' => 'arraykey-fetch',
        ],
        'spldoublylinkedlist' => [
            'bottom' => 'arrayvalue-fetch',
            'current' => 'arrayvalue-fetch',
            'offsetget' => 'arrayvalue-fetch',
            'pop' => 'arrayvalue-fetch',
            'shift' => 'arrayvalue-fetch',
            'top' => 'arrayvalue-fetch',
        ],
        'splqueue' => [
            'dequeue' => 'arrayvalue-fetch',
        ],
        'splfixedarray' => [
            'current' => 'arrayvalue-fetch',
            'offsetget' => 'arrayvalue-fetch',
            'toarray' => '=',
        ],
        'splheap' => [
            'current' => 'arrayvalue-fetch',
            'extract' => 'arrayvalue-fetch',
            'top' => 'arrayvalue-fetch',
        ],
        'splpriorityqueue' => [
            'current' => 'arrayvalue-fetch',
            'extract' => 'arrayvalue-fetch',
            'top' => 'arrayvalue-fetch',
        ],
        'splobjectstorage' => [
            'current' => 'arraykey-fetch',
            'getinfo' => 'arrayvalue-fetch',
            'offsetget' => 'arrayvalue-fetch',
        ],
    ];

    /**
     * @param list<PhpParser\Node\Arg> $args
     */
    public static function taint(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\MethodCall $stmt,
        Context $context,
        MethodIdentifier $method_id,
        array $args,
        Union $return_type,
    ): Union {
        $graph = $statements_analyzer->getDataFlowGraphWithSuppressed();

        if (!$graph) {
            return $return_type;
        }

        foreach (self::getContainerClasses($statements_analyzer, $method_id->fq_class_name) as $container_class) {
            $inputs = self::INPUTS[$container_class][$method_id->method_name] ?? null;

            if ($inputs !== null) {
                self::taintContainer($statements_analyzer, $graph, $stmt, $context, $args, $inputs);
            }

            $output = self::OUTPUTS[$container_class][$method_id->method_name] ?? null;

            $container_type = $statements_analyzer->node_data->getType($stmt->var);

            if ($output !== null && $container_type && $container_type->parent_nodes) {
                $output_node = DataFlowNode::getForAssignment(
                    'call to ' . (string) $method_id,
                    new CodeLocation($statements_analyzer, $stmt->name),
                );

                $graph->addNode($output_node);

                if ($method_id->method_name === 'offsetget') {
                    $output = self::addLiteralKey($statements_analyzer, $output, $args);
                }

                foreach ($container_type->parent_nodes as $parent_node) {
                    $graph->addPath($parent_node, $output_node, $output);
                }

                $return_type = $return_type->addParentNodes([$output_node->id => $output_node]);
            }
        }

        return $return_type;
    }

    /**
     * A value put at or taken from a literal offset (the first argument) is only that offset's, as with an array
     *
     * @param list<PhpParser\Node\Arg> $args
     */
    private static function addLiteralKey(
        StatementsAnalyzer $statements_analyzer,
        string $path_type,
        array $args,
    ): string {
        $offset_type = isset($args[0]) ? $statements_analyzer->node_data->getType($args[0]->value) : null;

        if ($offset_type === null) {
            return $path_type;
        }

        if ($offset_type->isSingleStringLiteral()) {
            return $path_type . '-\'' . $offset_type->getSingleStringLiteral()->value . '\'';
        }

        if ($offset_type->isSingleIntLiteral()) {
            return $path_type . '-\'' . $offset_type->getSingleIntLiteral()->value . '\'';
        }

        return $path_type;
    }

    /**
     * The containers of the standard library the class is or extends
     *
     * @return list<string>
     */
    private static function getContainerClasses(StatementsAnalyzer $statements_analyzer, string $fq_class_name): array
    {
        $codebase = $statements_analyzer->getCodebase();

        $container_classes = [];

        foreach (self::OUTPUTS + self::INPUTS as $container_class => $_) {
            if (strtolower($fq_class_name) === $container_class
                || ($codebase->classExists($fq_class_name)
                    && $codebase->classExtends($fq_class_name, $container_class))
            ) {
                $container_classes[] = $container_class;
            }
        }

        return $container_classes;
    }

    /**
     * What the arguments put into the container flows into the variable or the property holding it
     *
     * @param list<PhpParser\Node\Arg> $args
     * @param array<int, string> $inputs
     */
    private static function taintContainer(
        StatementsAnalyzer $statements_analyzer,
        DataFlowGraph $graph,
        PhpParser\Node\Expr\MethodCall $stmt,
        Context $context,
        array $args,
        array $inputs,
    ): void {
        $container_id = ExpressionIdentifier::getExtendedVarId($stmt->var, null, $statements_analyzer);

        $container_node = DataFlowNode::getForAssignment(
            $container_id ?? 'container',
            new CodeLocation($statements_analyzer, $stmt->var),
        );

        $has_input = false;

        foreach ($inputs as $offset => $path_type) {
            if ($offset === 1 && $path_type === 'arrayvalue-assignment') {
                $path_type = self::addLiteralKey($statements_analyzer, $path_type, $args);
            }

            $arg_type = isset($args[$offset])
                ? $statements_analyzer->node_data->getType($args[$offset]->value)
                : null;

            foreach ($arg_type->parent_nodes ?? [] as $parent_node) {
                $graph->addPath($parent_node, $container_node, $path_type);
                $has_input = true;
            }
        }

        if (!$has_input) {
            return;
        }

        $graph->addNode($container_node);

        $container_type = $container_id !== null && isset($context->vars_in_scope[$container_id])
            ? $context->vars_in_scope[$container_id]
            : $statements_analyzer->node_data->getType($stmt->var);

        foreach ($container_type->parent_nodes ?? [] as $parent_node) {
            $graph->addPath($parent_node, $container_node, '=');
        }

        if ($container_id !== null && isset($context->vars_in_scope[$container_id])) {
            $context->vars_in_scope[$container_id] = $context->vars_in_scope[$container_id]->setParentNodes(
                [$container_node->id => $container_node],
            );
        }

        $property_ids = self::getPropertyIds($statements_analyzer, $stmt->var, $context);

        foreach ($property_ids as $property_id => $class_storage) {
            InstancePropertyAssignmentAnalyzer::taintUnspecializedProperty(
                $statements_analyzer,
                $graph,
                $stmt->var,
                $property_id,
                $class_storage,
                Type::getMixed()->setParentNodes([$container_node->id => $container_node]),
                $context,
                $container_id,
            );
        }
    }

    /**
     * The properties the container is held in, when it is a property: the property node takes what is put into it
     *
     * @return array<string, ClassLikeStorage>
     */
    private static function getPropertyIds(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $container_expr,
        Context $context,
    ): array {
        $codebase = $statements_analyzer->getCodebase();

        if (!$container_expr instanceof PhpParser\Node\Expr\PropertyFetch
            && !$container_expr instanceof PhpParser\Node\Expr\StaticPropertyFetch
        ) {
            return [];
        }

        if (!$container_expr->name instanceof PhpParser\Node\Identifier) {
            return [];
        }

        $class_names = [];

        if ($container_expr instanceof PhpParser\Node\Expr\PropertyFetch) {
            $object_type = $statements_analyzer->node_data->getType($container_expr->var);

            foreach ($object_type ? $object_type->getAtomicTypes() : [] as $atomic_type) {
                if ($atomic_type instanceof TNamedObject) {
                    $class_names[] = $atomic_type->value;
                }
            }
        } elseif ($container_expr->class instanceof PhpParser\Node\Name) {
            $class_names[] = in_array($container_expr->class->toLowerString(), ['self', 'static'], true)
                ? $context->self
                : ClassLikeAnalyzer::getFQCLNFromNameObject($container_expr->class, $statements_analyzer->getAliases());
        }

        $property_ids = [];

        foreach ($class_names as $class_name) {
            if ($class_name === null || !$codebase->classOrInterfaceExists($class_name)) {
                continue;
            }

            $class_storage = $codebase->classlike_storage_provider->get($class_name);

            // the containers of a specialized instance are tracked through the variables holding it
            if (!$class_storage->specialize_instance) {
                $property_ids[$class_storage->name . '::$' . $container_expr->name->name] = $class_storage;
            }
        }

        return $property_ids;
    }
}
