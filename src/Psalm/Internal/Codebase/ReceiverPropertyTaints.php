<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Variable;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\Assignment\ArrayAssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Assignment\InstancePropertyAssignmentAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Type\Union;

use function array_keys;
use function array_pop;
use function count;
use function is_string;
use function str_starts_with;
use function strtolower;
use function substr;

/**
 * Flows into and out of the properties of the object a method is called on, for objects whose properties are set and
 * got by name (models of ORMs, property bags): `@psalm-flow ($value) -> $this->$name` sets the property named by what
 * is given to `$name`, as `$object->name = $value` does, and `@psalm-flow $this->$name -> return` returns it, as
 * `$object->name` does. `$this->*` is any property. `@psalm-flow ($values) -> $this->{key}` sets each property to what
 * is under its name in the array given to `$values`, and `@psalm-flow $this->{key} -> return[key]` returns an array of
 * the properties under their names: the paths of the keys (`arrayvalue-fetch-'name'`, `arrayvalue-assignment-'name'`)
 * keep what is under one key from the others. `@psalm-flow ($value) -> $this->data` sets the property the annotation
 * names, and `@psalm-flow ($value) -> $this->data[$key]` the element of that array property under the key given to
 * `$key`, as `$object->data[$key] = $value` does (`$this->data[*]` under any key), and the flows out of them into
 * `return` return them. A constructor sets them on the object it constructs.
 *
 * A literal string names that property. A name the analysis only knows the start of is each property starting with
 * it, and one it knows nothing of is each property: those declared and those of `@property` tags, of the class of the
 * object and of the classes extending it, which the object may be one of. Such a name may also be one of none of
 * them: a write of it, and one of a name known exactly that is none of them, also goes into a node of the class and
 * of the classes and interfaces it extends, which every read through these flows reads. That node holds them under
 * their names, as an array does.
 *
 * What is set is set through the class as `$object->name = $value` sets it, and through its parent classes and
 * interfaces: what reads the property through them gets it. A read gets what is set through the class and what an
 * object of a subclass gets set as one of its parent classes. A property of `@property` tags isn't inherited the way
 * declared properties are: a write also sets it in the subclasses with the tag, and a read also gets what is set
 * through them and through the parent classes with the tag.
 *
 * The properties of an object whose taints are kept per instance (`@psalm-taint-specialize`) are that object's: what
 * is set goes into the variable holding it, and what is read comes from it. Their flows by key are those of any
 * property: the names a property of the object may have aren't known.
 *
 * @internal
 */
final class ReceiverPropertyTaints
{
    /**
     * Records a flow into a property of `$this` (`($value) -> $this->$name`) or out of it (`$this->$name -> return`)
     *
     * @psalm-capabilities read-props|write-props
     */
    public static function addFlow(MethodStorage $storage, TaintFlowAnnotation $flow): void
    {
        if ($flow->property !== null) {
            self::addNamedPropertyFlow($storage, $flow, $flow->property);

            return;
        }

        if ($flow->property_by_key && $flow->kind === TaintFlowAnnotation::PROPERTY_READ) {
            $storage->taint_keyed_property_read = true;

            return;
        }

        $name = null;
        if ($flow->store_key !== null) {
            $name = TaintStore::getParamOffset($storage, $flow->store_key);
            if ($name === null) {
                // reported as naming no parameter
                return;
            }
        }

        if ($flow->kind === TaintFlowAnnotation::PROPERTY_READ) {
            $storage->taint_property_reads[] = ['name' => $name, 'path_type' => $flow->path_type];

            return;
        }

        $params = [];
        foreach ($flow->sources as $source_param) {
            $offset = TaintStore::getParamOffset($storage, $source_param);
            if ($offset !== null) {
                $params[] = $offset;
            }
        }

        if ($params === []) {
            return;
        }

        if ($flow->property_by_key) {
            $storage->taint_keyed_property_writes[] = $params;
        } else {
            $storage->taint_property_writes[] = ['name' => $name, 'params' => $params, 'path_type' => $flow->path_type];
        }
    }

    /**
     * Records a flow into or out of a property the annotation names (`$this->data`), or an element of it
     * (`$this->data[$key]`, `$this->data[*]`)
     *
     * @psalm-capabilities read-props|write-props
     */
    private static function addNamedPropertyFlow(
        MethodStorage $storage,
        TaintFlowAnnotation $flow,
        string $property,
    ): void {
        $element = $flow->any_element ? '*' : null;
        if ($flow->element_key !== null) {
            $element = TaintStore::getParamOffset($storage, $flow->element_key);
            if ($element === null) {
                // reported as naming no parameter
                return;
            }
        }

        if ($flow->kind === TaintFlowAnnotation::PROPERTY_READ) {
            $storage->taint_named_property_reads[] = [
                'property' => $property,
                'element' => $element,
                'path_type' => $flow->path_type,
            ];

            return;
        }

        $params = [];
        foreach ($flow->sources as $source_param) {
            $offset = TaintStore::getParamOffset($storage, $source_param);
            if ($offset !== null) {
                $params[] = $offset;
            }
        }

        if ($params !== []) {
            $storage->taint_named_property_writes[] = [
                'property' => $property,
                'element' => $element,
                'params' => $params,
                'path_type' => $flow->path_type,
            ];
        }
    }

    /**
     * Connects what the call of a method sets to the properties of the object it is called on, and returns
     * $return_type holding what the call reads from them
     *
     * @param array<array-key, Arg> $args
     * @param string $receiver_class the class the method is called on
     * @param array<string, DataFlowNode> $receiver_parent_nodes the nodes of the object the method is called on
     * @param ?string $var_id the variable holding the object, whose taints the call changes when they are kept per
     *                        instance
     */
    public static function taintCall(
        StatementsAnalyzer $statements_analyzer,
        TaintFlowGraph $graph,
        FunctionLikeStorage $storage,
        array $args,
        string $receiver_class,
        array $receiver_parent_nodes,
        ?string $var_id,
        Context $context,
        CodeLocation $call_location,
        Union $return_type,
    ): Union {
        if ($storage->taint_property_writes === []
            && $storage->taint_property_reads === []
            && $storage->taint_keyed_property_writes === []
            && !$storage->taint_keyed_property_read
            && $storage->taint_named_property_writes === []
            && $storage->taint_named_property_reads === []
        ) {
            return $return_type;
        }

        $codebase = $statements_analyzer->getCodebase();
        if (!$codebase->classlike_storage_provider->has($receiver_class)) {
            return $return_type;
        }

        $class_storage = $codebase->classlike_storage_provider->get($receiver_class);

        // an unpacked or unknown named argument may be given to any parameter: any of them may be the value, and the
        // name may be any
        $args_by_param = TaintStore::getArgsByParam($storage, $args);

        foreach ($storage->taint_named_property_reads as $index => $read) {
            $fetch_node = DataFlowNode::getForAssignment(
                '$this->' . $read['property'] . ' fetch ' . $index,
                $call_location,
            );
            $read_node = DataFlowNode::getForAssignment(
                '$this->' . $read['property'] . ' read ' . $index,
                $call_location,
            );

            $graph->addNode($fetch_node);
            $graph->addNode($read_node);

            foreach (self::getElementPathTypes(
                $statements_analyzer,
                'fetch',
                $read['element'],
                $read['path_type'],
                $args_by_param,
            ) as $path_type) {
                $graph->addPath($fetch_node, $read_node, $path_type);
            }

            if ($class_storage->specialize_instance) {
                foreach ($receiver_parent_nodes as $parent_node) {
                    $graph->addPath($parent_node, $fetch_node, 'property-fetch-' . $read['property']);
                }
            } else {
                self::addRead($codebase, $graph, $class_storage, [[true, $read['property']]], $fetch_node);
            }

            $return_type = $return_type->addParentNodes([$read_node->id => $read_node]);
        }

        foreach ($storage->taint_property_reads as $index => $read) {
            $fetch_node = DataFlowNode::getForAssignment('$this->property fetch ' . $index, $call_location);
            $read_node = DataFlowNode::getForAssignment('$this->property read ' . $index, $call_location);

            $graph->addNode($fetch_node);
            $graph->addNode($read_node);
            $graph->addPath($fetch_node, $read_node, $read['path_type']);

            $names = self::getNames($statements_analyzer, $read['name'], $args_by_param);

            if ($class_storage->specialize_instance) {
                // what the object holds: the property of a name known exactly, any of them else
                foreach ($names as [$is_exact, $known]) {
                    foreach ($receiver_parent_nodes as $parent_node) {
                        $graph->addPath(
                            $parent_node,
                            $fetch_node,
                            $is_exact ? 'property-fetch-' . $known : 'property-fetch',
                        );
                    }
                }
            } else {
                self::addRead($codebase, $graph, $class_storage, $names, $fetch_node);
            }

            $return_type = $return_type->addParentNodes([$read_node->id => $read_node]);
        }

        if ($storage->taint_keyed_property_read) {
            $read_node = DataFlowNode::getForAssignment('$this->properties read', $call_location);
            $graph->addNode($read_node);

            if ($class_storage->specialize_instance) {
                $fetch_node = DataFlowNode::getForAssignment('$this->properties fetch', $call_location);
                $graph->addNode($fetch_node);
                $graph->addPath($fetch_node, $read_node, 'arrayvalue-assignment');

                foreach ($receiver_parent_nodes as $parent_node) {
                    $graph->addPath($parent_node, $fetch_node, 'property-fetch');
                }
            } else {
                self::addKeyedRead($codebase, $graph, $class_storage, $read_node);
            }

            $return_type = $return_type->addParentNodes([$read_node->id => $read_node]);
        }

        self::taintWrites(
            $statements_analyzer,
            $graph,
            $storage,
            $class_storage,
            $args,
            $args_by_param,
            $var_id,
            null,
            $context,
            $call_location,
        );

        return $return_type;
    }

    /**
     * Connects what a constructor sets to the properties of the object it constructs (see taintCall()), and returns
     * $object_type holding what is set, when the taints of the properties of the class are kept per instance
     *
     * @param array<array-key, Arg> $args
     */
    public static function taintConstruction(
        StatementsAnalyzer $statements_analyzer,
        TaintFlowGraph $graph,
        MethodStorage $storage,
        array $args,
        string $class,
        CodeLocation $call_location,
        Union $object_type,
    ): Union {
        if ($storage->taint_property_writes === []
            && $storage->taint_keyed_property_writes === []
            && $storage->taint_named_property_writes === []
        ) {
            return $object_type;
        }

        $codebase = $statements_analyzer->getCodebase();
        if (!$codebase->classlike_storage_provider->has($class)) {
            return $object_type;
        }

        $class_storage = $codebase->classlike_storage_provider->get($class);
        $object_node = DataFlowNode::getForAssignment('new object with properties', $call_location);

        if (self::taintWrites(
            $statements_analyzer,
            $graph,
            $storage,
            $class_storage,
            $args,
            TaintStore::getArgsByParam($storage, $args),
            null,
            $object_node,
            null,
            $call_location,
        ) && $class_storage->specialize_instance) {
            $graph->addNode($object_node);
            $object_type = $object_type->addParentNodes([$object_node->id => $object_node]);
        }

        return $object_type;
    }

    /**
     * Connects what a call sets to the properties of the object it is called on, or that it constructs: the object in
     * $var_id or $object_node, when their taints are kept per instance. Returns whether it sets any.
     *
     * @param array<array-key, Arg> $args
     * @param array<int, list<Arg>>|null $args_by_param null when which arguments the parameters get is not known
     */
    private static function taintWrites(
        StatementsAnalyzer $statements_analyzer,
        TaintFlowGraph $graph,
        FunctionLikeStorage $storage,
        ClassLikeStorage $class_storage,
        array $args,
        ?array $args_by_param,
        ?string $var_id,
        ?DataFlowNode $object_node,
        ?Context $context,
        CodeLocation $call_location,
    ): bool {
        $codebase = $statements_analyzer->getCodebase();
        $sets_any = false;

        foreach ($storage->taint_named_property_writes as $index => $write) {
            $write_node = DataFlowNode::getForAssignment(
                '$this->' . $write['property'] . ' write ' . $index,
                $call_location,
            );

            $has_value = false;
            foreach (self::getElementPathTypes(
                $statements_analyzer,
                'assignment',
                $write['element'],
                $write['path_type'],
                $args_by_param,
            ) as $path_type) {
                $has_value = self::addValuePaths(
                    $statements_analyzer,
                    $graph,
                    $args,
                    $args_by_param,
                    $write['params'],
                    $write_node,
                    $path_type,
                ) || $has_value;
            }

            if (!$has_value) {
                continue;
            }

            $sets_any = true;
            $names = [[true, $write['property']]];

            if ($class_storage->specialize_instance) {
                self::addInstanceWrite(
                    $graph,
                    $context,
                    $var_id,
                    $object_node,
                    '$this->' . $write['property'] . ' write ' . $index,
                    $names,
                    $write_node,
                    $call_location,
                );
            } else {
                self::addWrite($codebase, $graph, $class_storage, $names, $write_node);
            }
        }

        foreach ($storage->taint_property_writes as $index => $write) {
            $write_node = DataFlowNode::getForAssignment('$this->property write ' . $index, $call_location);

            // `foreach ($values as $name => $value) $object->set($name, $value)`: each element of $values is set to
            // the property of its key, as `($values) -> $this->{key}` sets them
            $copied_array_nodes = $class_storage->specialize_instance
                ? null
                : self::getElementCopySource($statements_analyzer, $context, $args_by_param, $write);
            if ($copied_array_nodes !== null) {
                $graph->addNode($write_node);
                foreach ($copied_array_nodes as $array_node) {
                    $graph->addPath($array_node, $write_node, '=');
                }

                self::addKeyedWrite($codebase, $graph, $class_storage, $write_node);

                continue;
            }

            if (!self::addValuePaths(
                $statements_analyzer,
                $graph,
                $args,
                $args_by_param,
                $write['params'],
                $write_node,
                $write['path_type'],
            )) {
                continue;
            }

            $sets_any = true;
            $names = self::getNames($statements_analyzer, $write['name'], $args_by_param);

            if ($class_storage->specialize_instance) {
                self::addInstanceWrite(
                    $graph,
                    $context,
                    $var_id,
                    $object_node,
                    'property write ' . $index,
                    $names,
                    $write_node,
                    $call_location,
                );
            } else {
                self::addWrite($codebase, $graph, $class_storage, $names, $write_node);
            }
        }

        foreach ($storage->taint_keyed_property_writes as $index => $params) {
            $write_node = DataFlowNode::getForAssignment('$this->properties write ' . $index, $call_location);

            if ($class_storage->specialize_instance) {
                if (self::addValuePaths(
                    $statements_analyzer,
                    $graph,
                    $args,
                    $args_by_param,
                    $params,
                    $write_node,
                    'arrayvalue-fetch',
                )) {
                    $sets_any = true;
                    self::addInstanceWrite(
                        $graph,
                        $context,
                        $var_id,
                        $object_node,
                        'properties write ' . $index,
                        [[false, '']],
                        $write_node,
                        $call_location,
                    );
                }
            } elseif (self::addValuePaths($statements_analyzer, $graph, $args, $args_by_param, $params, $write_node)) {
                $sets_any = true;
                self::addKeyedWrite($codebase, $graph, $class_storage, $write_node);
            }
        }

        return $sets_any;
    }

    /**
     * The paths into ($kind `assignment`) or out of ($kind `fetch`) a property under $element: the key of the element
     * given to the parameter at that offset, as the analysis knows it, any key for `*`, and $path_type for the
     * property itself
     *
     * @param 'assignment'|'fetch' $kind
     * @param int|'*'|null $element
     * @param array<int, list<Arg>>|null $args_by_param null when which arguments the parameters get is not known
     * @return list<string>
     */
    private static function getElementPathTypes(
        StatementsAnalyzer $statements_analyzer,
        string $kind,
        int|string|null $element,
        string $path_type,
        ?array $args_by_param,
    ): array {
        if ($element === null) {
            return [$path_type];
        }

        $path_types = [];
        $names = $element === '*' ? [[false, '']] : self::getNames($statements_analyzer, $element, $args_by_param);
        foreach ($names as [$is_exact, $known]) {
            $path_types['arrayvalue-' . $kind . match (true) {
                $is_exact => '-\'' . $known . '\'',
                $known !== '' => '-\'' . $known . '\'*',
                default => '',
            }] = true;
        }

        return array_keys($path_types);
    }

    /**
     * The parent nodes of the array whose elements the write of a property by name $write copies under their own key,
     * if it does: the call is in the body of a foreach loop over the array, and given its key variable as the name and
     * its value variable as the value, not reassigned since (see Context::$foreach_element_copies and
     * ArrayAssignmentAnalyzer::getElementCopySource())
     *
     * @param array<int, list<Arg>>|null $args_by_param null when which arguments the parameters get is not known
     * @param array{name: ?int, params: list<int>, path_type: string} $write
     * @return array<string, DataFlowNode>|null
     * @psalm-capabilities read-props
     */
    private static function getElementCopySource(
        StatementsAnalyzer $statements_analyzer,
        ?Context $context,
        ?array $args_by_param,
        array $write,
    ): ?array {
        if ($write['name'] === null
            || $context === null
            || $args_by_param === null
            || $write['path_type'] !== 'arg'
            || count($write['params']) !== 1
        ) {
            return null;
        }

        $name_args = $args_by_param[$write['name']] ?? [];
        $value_args = $args_by_param[$write['params'][0]] ?? [];
        if (count($name_args) !== 1 || count($value_args) !== 1) {
            return null;
        }

        $name = $name_args[0]->value;
        $value = $value_args[0]->value;
        if (!$name instanceof Variable
            || !is_string($name->name)
            || !$value instanceof Variable
            || !is_string($value->name)
            || !isset($context->foreach_element_copies['$' . $value->name])
        ) {
            return null;
        }

        [$key_var_id, $key_node_ids, $value_node_ids, $array_nodes] =
            $context->foreach_element_copies['$' . $value->name];

        $value_type = $statements_analyzer->node_data->getType($value);

        // what the fetch of the element holds that the array doesn't (see ArrayAssignmentAnalyzer)
        foreach ($value_type->parent_nodes ?? [] as $value_node) {
            if ($statements_analyzer->taint_flow_graph?->isSource($value_node)) {
                return null;
            }
        }

        $name_type = $statements_analyzer->node_data->getType($name);

        return $key_var_id === '$' . $name->name
            && ArrayAssignmentAnalyzer::getValueNodeIds($name_type) === $key_node_ids
            && ArrayAssignmentAnalyzer::getValueNodeIds($value_type) === $value_node_ids
            ? $array_nodes
            : null;
    }

    /**
     * Sets the property $name of `$this`, in a method of $class, to what reaches $write_node, as `$this->name = $value`
     * does through these flows
     *
     * @psalm-capabilities read-props|write-props
     */
    public static function addWriteOfThis(
        Codebase $codebase,
        TaintFlowGraph $graph,
        string $class,
        string $name,
        DataFlowNode $write_node,
        Context $context,
        CodeLocation $location,
    ): void {
        if (!$codebase->classlike_storage_provider->has($class)) {
            return;
        }

        $class_storage = $codebase->classlike_storage_provider->get($class);
        $graph->addNode($write_node);

        if ($class_storage->specialize_instance) {
            self::addInstanceWrite(
                $graph,
                $context,
                '$this',
                null,
                'property bag write',
                [[true, $name]],
                $write_node,
                $location,
            );
        } else {
            self::addWrite($codebase, $graph, $class_storage, [[true, $name]], $write_node);
        }
    }

    /**
     * Makes $fetch_node get the property $name of `$this`, in a method of $class, as `$this->name` does through these
     * flows
     *
     * @psalm-capabilities read-props|write-props
     */
    public static function addReadOfThis(
        Codebase $codebase,
        TaintFlowGraph $graph,
        string $class,
        string $name,
        DataFlowNode $fetch_node,
        Context $context,
    ): void {
        if (!$codebase->classlike_storage_provider->has($class)) {
            return;
        }

        $class_storage = $codebase->classlike_storage_provider->get($class);
        $graph->addNode($fetch_node);

        if ($class_storage->specialize_instance) {
            foreach ($context->vars_in_scope['$this']->parent_nodes ?? [] as $parent_node) {
                $graph->addPath($parent_node, $fetch_node, 'property-fetch-' . $name);
            }
        } else {
            self::addRead($codebase, $graph, $class_storage, [[true, $name]], $fetch_node);
        }
    }

    /**
     * Adds paths from the arguments given to the parameters at the offsets $params into $write_node, and $write_node
     * if there are any
     *
     * @param array<array-key, Arg> $args
     * @param array<int, list<Arg>>|null $args_by_param null when which arguments the parameters get is not known
     * @param list<int> $params
     * @psalm-capabilities read-props|write-props
     */
    private static function addValuePaths(
        StatementsAnalyzer $statements_analyzer,
        TaintFlowGraph $graph,
        array $args,
        ?array $args_by_param,
        array $params,
        DataFlowNode $write_node,
        string $path_type = 'arg',
    ): bool {
        $value_args = $args;
        if ($args_by_param !== null) {
            $value_args = [];
            foreach ($params as $offset) {
                $value_args = [...$value_args, ...($args_by_param[$offset] ?? [])];
            }
        }

        $has_value = false;
        foreach ($value_args as $arg) {
            $arg_type = $statements_analyzer->node_data->getType($arg->value);
            foreach ($arg_type?->parent_nodes ?? [] as $parent_node) {
                $graph->addPath($parent_node, $write_node, $path_type);
                $has_value = true;
            }
        }

        if ($has_value) {
            $graph->addNode($write_node);
        }

        return $has_value;
    }

    /**
     * Sets the properties the names can be of the object in $var_id, or of $object_node, whose taints are kept per
     * instance, to what reaches $write_node: the object holds it from now on, as an assignment to its property makes it
     *
     * @param list<array{bool, string}> $names
     * @psalm-capabilities read-props|write-props
     */
    private static function addInstanceWrite(
        TaintFlowGraph $graph,
        ?Context $context,
        ?string $var_id,
        ?DataFlowNode $object_node,
        string $write_label,
        array $names,
        DataFlowNode $write_node,
        CodeLocation $call_location,
    ): void {
        if ($object_node !== null) {
            foreach ($names as [$is_exact, $known]) {
                $graph->addPath(
                    $write_node,
                    $object_node,
                    $is_exact ? 'property-assignment-' . $known : 'property-assignment',
                );
            }

            return;
        }

        if ($var_id === null || $context === null || !isset($context->vars_in_scope[$var_id])) {
            return;
        }

        $var_node = DataFlowNode::getForAssignment($var_id . ' after ' . $write_label, $call_location);
        $graph->addNode($var_node);

        foreach ($context->vars_in_scope[$var_id]->parent_nodes as $parent_node) {
            $graph->addPath($parent_node, $var_node, '=');
        }

        foreach ($names as [$is_exact, $known]) {
            $graph->addPath(
                $write_node,
                $var_node,
                $is_exact ? 'property-assignment-' . $known : 'property-assignment',
            );
        }

        $context->vars_in_scope[$var_id] = $context->vars_in_scope[$var_id]->setParentNodes(
            [$var_node->id => $var_node],
        );
    }

    /**
     * What the analysis knows of the name given to the parameter at offset $name (see TaintStore::getNames())
     *
     * @param array<int, list<Arg>>|null $args_by_param null when which arguments the parameters get is not known
     * @return list<array{bool, string}>
     */
    private static function getNames(StatementsAnalyzer $statements_analyzer, ?int $name, ?array $args_by_param): array
    {
        if ($name === null || $args_by_param === null) {
            return [[false, '']];
        }

        return TaintStore::getNames($statements_analyzer, $args_by_param[$name][0]->value ?? null);
    }

    /**
     * @param list<array{bool, string}> $names
     * @psalm-capabilities read-props|write-props
     */
    private static function addWrite(
        Codebase $codebase,
        TaintFlowGraph $graph,
        ClassLikeStorage $class_storage,
        array $names,
        DataFlowNode $write_node,
    ): void {
        $ancestors = self::getAncestors($codebase, $class_storage);

        foreach (self::getPropertyNames($codebase, $class_storage, $names) as $prop_name) {
            self::addPropertyWrite(
                $codebase,
                $graph,
                $class_storage,
                $ancestors,
                $prop_name,
                $write_node,
                'property-assignment',
            );
        }

        // a name not known exactly may be that of a property none of the classes has, and so is one known exactly
        // that isn't one of theirs
        $other_names = [];
        $known_names = null;
        foreach ($names as $name) {
            if ($name[0]) {
                $known_names ??= self::getKnownPropertyNames($codebase, $class_storage);
                if (isset($known_names[$name[1]])) {
                    continue;
                }
            }

            $other_names[] = $name;
        }

        if ($other_names !== []) {
            $path_type = self::getOtherPropertiesPathType('assignment', $other_names);
            foreach (self::getOtherPropertiesNodes($graph, [$class_storage]) as $other_properties_node) {
                $graph->addPath($write_node, $other_properties_node, $path_type);
            }
        }
    }

    /**
     * Sets each property to what is under its name in the arrays reaching $write_node
     *
     * @psalm-capabilities read-props|write-props
     */
    private static function addKeyedWrite(
        Codebase $codebase,
        TaintFlowGraph $graph,
        ClassLikeStorage $class_storage,
        DataFlowNode $write_node,
    ): void {
        $ancestors = self::getAncestors($codebase, $class_storage);

        foreach (self::getKnownPropertyNames($codebase, $class_storage) as $prop_name => $_) {
            self::addPropertyWrite(
                $codebase,
                $graph,
                $class_storage,
                $ancestors,
                $prop_name,
                $write_node,
                'arrayvalue-fetch-\'' . $prop_name . '\'',
            );
        }

        // the keys may name properties none of the classes has: the arrays are what they hold, under their names
        foreach (self::getOtherPropertiesNodes($graph, [$class_storage]) as $other_properties_node) {
            $graph->addPath($write_node, $other_properties_node, '=');
        }
    }

    /**
     * @param list<ClassLikeStorage> $ancestors
     * @psalm-capabilities read-props|write-props
     */
    private static function addPropertyWrite(
        Codebase $codebase,
        TaintFlowGraph $graph,
        ClassLikeStorage $class_storage,
        array $ancestors,
        string $prop_name,
        DataFlowNode $write_node,
        string $path_type,
    ): void {
        $property_storages = [
            $class_storage,
            ...$ancestors,
            ...self::getSubclassesHolding($codebase, $class_storage, $prop_name),
        ];

        foreach ($property_storages as $storage) {
            $property_node = DataFlowNode::getForPropertyFetch($storage->name . '::$' . $prop_name);
            $graph->addNode($property_node);
            $graph->addPath($write_node, $property_node, $path_type);
        }

        // what the subclasses read of a property they inherit (see InstancePropertyAssignmentAnalyzer)
        if (!$class_storage->final) {
            $inherited_property_node = DataFlowNode::getForInheritedProperty($class_storage->name . '::$' . $prop_name);
            $graph->addNode($inherited_property_node);
            $graph->addPath($write_node, $inherited_property_node, $path_type);
        }
    }

    /**
     * @param list<array{bool, string}> $names
     * @psalm-capabilities read-props|write-props
     */
    private static function addRead(
        Codebase $codebase,
        TaintFlowGraph $graph,
        ClassLikeStorage $class_storage,
        array $names,
        DataFlowNode $fetch_node,
    ): void {
        $ancestors = self::getAncestors($codebase, $class_storage);

        foreach (self::getPropertyNames($codebase, $class_storage, $names) as $prop_name) {
            self::addPropertyRead(
                $codebase,
                $graph,
                $class_storage,
                $ancestors,
                $prop_name,
                $fetch_node,
                'property-fetch',
            );
        }

        $path_type = self::getOtherPropertiesPathType('fetch', $names);
        foreach (self::getOtherPropertiesNodes(
            $graph,
            self::getOtherPropertiesClasses($codebase, $class_storage, $ancestors),
        ) as $other_properties_node) {
            $graph->addPath($other_properties_node, $fetch_node, $path_type);
        }
    }

    /**
     * Makes $read_node an array of the properties under their names
     *
     * @psalm-capabilities read-props|write-props
     */
    private static function addKeyedRead(
        Codebase $codebase,
        TaintFlowGraph $graph,
        ClassLikeStorage $class_storage,
        DataFlowNode $read_node,
    ): void {
        $ancestors = self::getAncestors($codebase, $class_storage);

        foreach (self::getKnownPropertyNames($codebase, $class_storage) as $prop_name => $_) {
            self::addPropertyRead(
                $codebase,
                $graph,
                $class_storage,
                $ancestors,
                $prop_name,
                $read_node,
                'arrayvalue-assignment-\'' . $prop_name . '\'',
            );
        }

        foreach (self::getOtherPropertiesNodes(
            $graph,
            self::getOtherPropertiesClasses($codebase, $class_storage, $ancestors),
        ) as $other_properties_node) {
            $graph->addPath($other_properties_node, $read_node, '=');
        }
    }

    /**
     * @param list<ClassLikeStorage> $ancestors
     * @psalm-capabilities read-props|write-props
     */
    private static function addPropertyRead(
        Codebase $codebase,
        TaintFlowGraph $graph,
        ClassLikeStorage $class_storage,
        array $ancestors,
        string $prop_name,
        DataFlowNode $fetch_node,
        string $path_type,
    ): void {
        $property_storages = [$class_storage, ...self::getSubclassesHolding($codebase, $class_storage, $prop_name)];

        // a property of `@property` tags of the parent classes, which they set without the subclasses
        foreach ($ancestors as $ancestor) {
            if (isset($ancestor->pseudo_property_get_types['$' . $prop_name])
                || isset($ancestor->pseudo_property_set_types['$' . $prop_name])
            ) {
                $property_storages[] = $ancestor;
            }
        }

        foreach ($property_storages as $storage) {
            $property_node = DataFlowNode::getForPropertyFetch($storage->name . '::$' . $prop_name);
            $graph->addNode($property_node);
            $graph->addPath($property_node, $fetch_node, $path_type);

            // what is set to the objects of the class through its parent classes (see
            // AtomicPropertyFetchAnalyzer::processUnspecialTaints())
            foreach (InstancePropertyAssignmentAnalyzer::getPropertyAncestors(
                $codebase,
                $storage->name,
                $prop_name,
            ) as $ancestor) {
                $inherited_property_node = DataFlowNode::getForInheritedProperty($ancestor . '::$' . $prop_name);
                $graph->addNode($inherited_property_node);
                $graph->addPath($inherited_property_node, $fetch_node, $path_type);
            }
        }
    }

    /**
     * The properties the names can be: one known exactly is that property, one whose start only is known is each
     * instance property starting with it of the class and of the classes extending it
     *
     * @param list<array{bool, string}> $names
     * @return list<string>
     * @psalm-capabilities read-props
     */
    private static function getPropertyNames(Codebase $codebase, ClassLikeStorage $class_storage, array $names): array
    {
        $property_names = [];
        $known_names = null;

        foreach ($names as [$is_exact, $known]) {
            if ($is_exact) {
                $property_names[$known] = true;

                continue;
            }

            $known_names ??= self::getKnownPropertyNames($codebase, $class_storage);
            foreach ($known_names as $prop_name => $_) {
                if (str_starts_with($prop_name, $known)) {
                    $property_names[$prop_name] = true;
                }
            }
        }

        return array_keys($property_names);
    }

    /**
     * The names of the instance properties of the class and of the classes extending it, declared or of `@property`
     * tags, and those of `@property` tags of the classes and interfaces it extends
     *
     * @return array<string, true>
     * @psalm-capabilities read-props
     */
    private static function getKnownPropertyNames(Codebase $codebase, ClassLikeStorage $class_storage): array
    {
        $names = [];
        $subclasses = self::getSubclasses($codebase, $class_storage);

        foreach ([$class_storage, ...$subclasses] as $storage) {
            foreach ($storage->declaring_property_ids as $prop_name => $declaring_class) {
                $declaring_storage = $codebase->classlike_storage_provider->has($declaring_class)
                    ? $codebase->classlike_storage_provider->get($declaring_class)
                    : null;

                if (!($declaring_storage?->properties[$prop_name]->is_static ?? false)) {
                    $names[$prop_name] = true;
                }
            }
        }

        foreach ([$class_storage, ...$subclasses, ...self::getAncestors($codebase, $class_storage)] as $storage) {
            foreach ([...$storage->pseudo_property_get_types, ...$storage->pseudo_property_set_types] as $name => $_) {
                $names[substr($name, 1)] = true;
            }
        }

        return $names;
    }

    /**
     * The subclasses of the class whose objects hold the property without its node of the class (see
     * InstancePropertyAssignmentAnalyzer::taintInheritedProperty()): those with a `@property` tag of it, and those
     * declaring it when the class doesn't
     *
     * @return list<ClassLikeStorage>
     * @psalm-capabilities read-props
     */
    private static function getSubclassesHolding(
        Codebase $codebase,
        ClassLikeStorage $class_storage,
        string $prop_name,
    ): array {
        $declared = isset($class_storage->declaring_property_ids[$prop_name]);

        $subclasses = [];
        foreach (self::getSubclasses($codebase, $class_storage) as $storage) {
            if (isset($storage->pseudo_property_get_types['$' . $prop_name])
                || isset($storage->pseudo_property_set_types['$' . $prop_name])
                || (!$declared && isset($storage->declaring_property_ids[$prop_name]))
            ) {
                $subclasses[] = $storage;
            }
        }

        return $subclasses;
    }

    /**
     * The classes and interfaces extending or implementing the class
     *
     * @return list<ClassLikeStorage>
     * @psalm-capabilities read-props
     */
    private static function getSubclasses(Codebase $codebase, ClassLikeStorage $class_storage): array
    {
        $subclasses = [];
        foreach ($class_storage->dependent_classlikes as $dependent_classlike => $_) {
            if ($codebase->classlike_storage_provider->has($dependent_classlike)) {
                $subclasses[] = $codebase->classlike_storage_provider->get($dependent_classlike);
            }
        }

        return $subclasses;
    }

    /**
     * The classes and interfaces the class extends or implements
     *
     * @return list<ClassLikeStorage>
     * @psalm-capabilities read-props
     */
    private static function getAncestors(Codebase $codebase, ClassLikeStorage $class_storage): array
    {
        $ancestors = [];
        foreach ([
            ...$class_storage->parent_classes,
            ...$class_storage->parent_interfaces,
            ...$class_storage->class_implements,
        ] as $ancestor_lc => $_) {
            if ($codebase->classlike_storage_provider->has($ancestor_lc)) {
                $ancestors[] = $codebase->classlike_storage_provider->get($ancestor_lc);
            }
        }

        return $ancestors;
    }

    /**
     * The classes whose objects an object read through the class may be, or whose type a write of it may have been
     * made through: the class, its ancestors and the classes extending or implementing it, at any depth. What is set
     * under a name not known only goes to the class of the object written, so that it doesn't reach the objects of
     * the other classes extending the same ancestor
     *
     * @param list<ClassLikeStorage> $ancestors
     * @return list<ClassLikeStorage>
     * @psalm-capabilities read-props
     */
    private static function getOtherPropertiesClasses(
        Codebase $codebase,
        ClassLikeStorage $class_storage,
        array $ancestors,
    ): array {
        $classes = [$class_storage, ...$ancestors];
        $seen = [strtolower($class_storage->name) => true];
        $queue = [$class_storage];
        while (($storage = array_pop($queue)) !== null) {
            foreach ($storage->dependent_classlikes as $dependent_classlike => $_) {
                if (!isset($seen[$dependent_classlike])
                    && $codebase->classlike_storage_provider->has($dependent_classlike)
                ) {
                    $seen[$dependent_classlike] = true;
                    $descendant = $codebase->classlike_storage_provider->get($dependent_classlike);
                    $classes[] = $descendant;
                    $queue[] = $descendant;
                }
            }
        }

        return $classes;
    }

    /**
     * The nodes, added to the graph, of what is set through the classes to properties whose names aren't known or
     * that they don't have, held under their names as in an array
     *
     * @param list<ClassLikeStorage> $storages
     * @return list<DataFlowNode>
     * @psalm-capabilities read-props|write-props
     */
    private static function getOtherPropertiesNodes(TaintFlowGraph $graph, array $storages): array
    {
        $nodes = [];
        foreach ($storages as $storage) {
            $node = DataFlowNode::getForPropertyFetch($storage->name . '::$*');
            $graph->addNode($node);
            $nodes[] = $node;
        }

        return $nodes;
    }

    /**
     * The path into ($kind `assignment`) or out of ($kind `fetch`) the nodes of getOtherPropertiesNodes() under the
     * names: the key of a name known exactly or by its start, any key else
     *
     * @param 'assignment'|'fetch' $kind
     * @param list<array{bool, string}> $names
     * @psalm-pure
     */
    private static function getOtherPropertiesPathType(string $kind, array $names): string
    {
        if (count($names) === 1) {
            [$is_exact, $known] = $names[0];

            if ($is_exact) {
                return 'arrayvalue-' . $kind . '-\'' . $known . '\'';
            }

            if ($known !== '') {
                return 'arrayvalue-' . $kind . '-\'' . $known . '\'*';
            }
        }

        return 'arrayvalue-' . $kind;
    }
}
