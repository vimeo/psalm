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

use function array_keys;
use function dirname;
use function in_array;
use function strtolower;

/**
 * The builtin objects keep data given to their methods, and give it back through their methods: what flows into and
 * out of the object a method of a builtin class is called on is described by
 * dictionaries/InternalTaintObjectFlowMap.php, and follows the object as it would an array.
 *
 * @psalm-type ObjectFlow = array{in?: non-empty-array<int, non-empty-string>, out?: non-empty-string, key?: int}
 * @internal
 */
final class NativeClassTaintAnalyzer
{
    /** @var ?array<lowercase-string, ObjectFlow> */
    private static ?array $object_flow_map = null;

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

        $flow = self::getObjectFlow($statements_analyzer, $method_id);

        if ($flow === null) {
            return $return_type;
        }

        $key_offset = $flow['key'] ?? null;

        if (isset($flow['in'])) {
            self::taintObject($statements_analyzer, $graph, $stmt, $context, $args, $flow['in'], $key_offset);
        }

        $object_type = $statements_analyzer->node_data->getType($stmt->var);

        if (!isset($flow['out']) || !$object_type || !$object_type->parent_nodes) {
            return $return_type;
        }

        $output_node = DataFlowNode::getForAssignment(
            'call to ' . (string) $method_id,
            new CodeLocation($statements_analyzer, $stmt->name),
        );

        $graph->addNode($output_node);

        $output = self::addLiteralKey($statements_analyzer, $flow['out'], $args, $key_offset);

        foreach ($object_type->parent_nodes as $parent_node) {
            $graph->addPath($parent_node, $output_node, $output);
        }

        return $return_type->addParentNodes([$output_node->id => $output_node]);
    }

    /**
     * The entry of dictionaries/InternalTaintObjectFlowMap.php for the method, or for the method of the closest parent
     * class having one
     *
     * @return ?ObjectFlow
     */
    private static function getObjectFlow(StatementsAnalyzer $statements_analyzer, MethodIdentifier $method_id): ?array
    {
        if (self::$object_flow_map === null) {
            /** @var array<lowercase-string, ObjectFlow> */
            self::$object_flow_map = require(dirname(__DIR__, 8) . '/dictionaries/InternalTaintObjectFlowMap.php');
        }

        $class_storage_provider = $statements_analyzer->getCodebase()->classlike_storage_provider;

        $class_names = [strtolower($method_id->fq_class_name)];

        if ($class_storage_provider->has($method_id->fq_class_name)) {
            $class_names = [
                ...$class_names,
                ...array_keys($class_storage_provider->get($method_id->fq_class_name)->parent_classes),
            ];
        }

        foreach ($class_names as $class_name) {
            if (isset(self::$object_flow_map[$class_name . '::' . $method_id->method_name])) {
                return self::$object_flow_map[$class_name . '::' . $method_id->method_name];
            }
        }

        return null;
    }

    /**
     * A value put at or taken from a literal offset (the argument at $key_offset) is only that offset's, as with an
     * array
     *
     * @param list<PhpParser\Node\Arg> $args
     */
    private static function addLiteralKey(
        StatementsAnalyzer $statements_analyzer,
        string $path_type,
        array $args,
        ?int $key_offset,
    ): string {
        $key_type = $key_offset !== null && isset($args[$key_offset])
            ? $statements_analyzer->node_data->getType($args[$key_offset]->value)
            : null;

        if ($key_type === null) {
            return $path_type;
        }

        if ($key_type->isSingleStringLiteral()) {
            return $path_type . '-\'' . $key_type->getSingleStringLiteral()->value . '\'';
        }

        if ($key_type->isSingleIntLiteral()) {
            return $path_type . '-\'' . $key_type->getSingleIntLiteral()->value . '\'';
        }

        return $path_type;
    }

    /**
     * What the arguments put into the object flows into the variable or the property holding it
     *
     * @param list<PhpParser\Node\Arg> $args
     * @param array<int, string> $inputs
     */
    private static function taintObject(
        StatementsAnalyzer $statements_analyzer,
        DataFlowGraph $graph,
        PhpParser\Node\Expr\MethodCall $stmt,
        Context $context,
        array $args,
        array $inputs,
        ?int $key_offset,
    ): void {
        $object_id = ExpressionIdentifier::getExtendedVarId($stmt->var, null, $statements_analyzer);

        $object_node = DataFlowNode::getForAssignment(
            $object_id ?? 'object',
            new CodeLocation($statements_analyzer, $stmt->var),
        );

        $has_input = false;

        foreach ($inputs as $offset => $path_type) {
            if ($path_type === 'arrayvalue-assignment') {
                $path_type = self::addLiteralKey($statements_analyzer, $path_type, $args, $key_offset);
            }

            $arg_type = isset($args[$offset])
                ? $statements_analyzer->node_data->getType($args[$offset]->value)
                : null;

            foreach ($arg_type->parent_nodes ?? [] as $parent_node) {
                $graph->addPath($parent_node, $object_node, $path_type);
                $has_input = true;
            }
        }

        if (!$has_input) {
            return;
        }

        $graph->addNode($object_node);

        $object_type = $object_id !== null && isset($context->vars_in_scope[$object_id])
            ? $context->vars_in_scope[$object_id]
            : $statements_analyzer->node_data->getType($stmt->var);

        foreach ($object_type->parent_nodes ?? [] as $parent_node) {
            $graph->addPath($parent_node, $object_node, '=');
        }

        if ($object_id !== null && isset($context->vars_in_scope[$object_id])) {
            $context->vars_in_scope[$object_id] = $context->vars_in_scope[$object_id]->setParentNodes(
                [$object_node->id => $object_node],
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
                Type::getMixed()->setParentNodes([$object_node->id => $object_node]),
                $context,
                $object_id,
            );
        }
    }

    /**
     * The properties the object is held in, when it is a property: the property node takes what is put into it
     *
     * @return array<string, ClassLikeStorage>
     */
    private static function getPropertyIds(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $object_expr,
        Context $context,
    ): array {
        $codebase = $statements_analyzer->getCodebase();

        if (!$object_expr instanceof PhpParser\Node\Expr\PropertyFetch
            && !$object_expr instanceof PhpParser\Node\Expr\StaticPropertyFetch
        ) {
            return [];
        }

        if (!$object_expr->name instanceof PhpParser\Node\Identifier) {
            return [];
        }

        $class_names = [];

        if ($object_expr instanceof PhpParser\Node\Expr\PropertyFetch) {
            $object_type = $statements_analyzer->node_data->getType($object_expr->var);

            foreach ($object_type ? $object_type->getAtomicTypes() : [] as $atomic_type) {
                if ($atomic_type instanceof TNamedObject) {
                    $class_names[] = $atomic_type->value;
                }
            }
        } elseif ($object_expr->class instanceof PhpParser\Node\Name) {
            $class_names[] = in_array($object_expr->class->toLowerString(), ['self', 'static'], true)
                ? $context->self
                : ClassLikeAnalyzer::getFQCLNFromNameObject($object_expr->class, $statements_analyzer->getAliases());
        }

        $property_ids = [];

        foreach ($class_names as $class_name) {
            if ($class_name === null || !$codebase->classOrInterfaceExists($class_name)) {
                continue;
            }

            $class_storage = $codebase->classlike_storage_provider->get($class_name);

            // the objects held by a specialized instance are tracked through the variables holding it
            if (!$class_storage->specialize_instance) {
                $property_ids[$class_storage->name . '::$' . $object_expr->name->name] = $class_storage;
            }
        }

        return $property_ids;
    }
}
