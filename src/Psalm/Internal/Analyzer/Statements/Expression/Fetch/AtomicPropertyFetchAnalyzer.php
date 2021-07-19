<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Fetch;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\NamespaceAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Assignment\InstancePropertyAssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Issue\DeprecatedProperty;
use Psalm\Issue\ImpurePropertyFetch;
use Psalm\Issue\InternalProperty;
use Psalm\Issue\MissingPropertyType;
use Psalm\Issue\NoInterfaceProperties;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedDocblockClass;
use Psalm\Issue\UndefinedMagicPropertyFetch;
use Psalm\Issue\UndefinedPropertyFetch;
use Psalm\Issue\UndefinedThisPropertyFetch;
use Psalm\IssueBuffer;
use Psalm\Node\Expr\VirtualMethodCall;
use Psalm\Node\Scalar\VirtualString;
use Psalm\Node\VirtualArg;
use Psalm\Node\VirtualIdentifier;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TObjectWithProperties;

use function array_keys;
use function array_values;
use function in_array;
use function is_int;
use function is_string;
use function strtolower;

/**
 * @internal
 */
class AtomicPropertyFetchAnalyzer
{
    /**
     * @param array<string> $invalid_fetch_types $invalid_fetch_types
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\PropertyFetch $stmt,
        Context $context,
        bool $in_assignment,
        ?string $var_id,
        ?string $stmt_var_id,
        Type\Union $stmt_var_type,
        Type\Atomic $lhs_type_part,
        string $prop_name,
        bool &$has_valid_fetch_type,
        array &$invalid_fetch_types,
        bool $is_static_access = false
    ) : void {
        if ($lhs_type_part instanceof TNull) {
            return;
        }

        if ($lhs_type_part instanceof Type\Atomic\TMixed) {
            $statements_analyzer->node_data->setType($stmt, Type::getMixed());
            return;
        }

        if ($lhs_type_part instanceof Type\Atomic\TFalse && $stmt_var_type->ignore_falsable_issues) {
            return;
        }

        if (!$lhs_type_part instanceof TNamedObject && !$lhs_type_part instanceof TObject) {
            $invalid_fetch_types[] = (string)$lhs_type_part;

            return;
        }

        $has_valid_fetch_type = true;

        if ($lhs_type_part instanceof TObjectWithProperties
            && isset($lhs_type_part->properties[$prop_name])
        ) {
            if ($stmt_type = $statements_analyzer->node_data->getType($stmt)) {
                $statements_analyzer->node_data->setType(
                    $stmt,
                    Type::combineUnionTypes(
                        $lhs_type_part->properties[$prop_name],
                        $stmt_type
                    )
                );
            } else {
                $statements_analyzer->node_data->setType($stmt, $lhs_type_part->properties[$prop_name]);
            }

            return;
        }

        // stdClass and SimpleXMLElement are special cases where we cannot infer the return types
        // but we don't want to throw an error
        // Hack has a similar issue: https://github.com/facebook/hhvm/issues/5164
        if ($lhs_type_part instanceof TObject
            || in_array(strtolower($lhs_type_part->value), Config::getInstance()->getUniversalObjectCrates(), true)
        ) {
            $statements_analyzer->node_data->setType($stmt, Type::getMixed());

            return;
        }

        if (ExpressionAnalyzer::isMock($lhs_type_part->value)) {
            $statements_analyzer->node_data->setType($stmt, Type::getMixed());
            return;
        }

        $intersection_types = $lhs_type_part->getIntersectionTypes() ?: [];

        $fq_class_name = $lhs_type_part->value;

        $override_property_visibility = false;

        $has_magic_getter = false;

        $class_exists = false;

        $codebase = $statements_analyzer->getCodebase();

        if (!$codebase->classExists($lhs_type_part->value)
            && !$codebase->classlikes->enumExists($lhs_type_part->value)
        ) {
            $interface_exists = false;

            self::handleNonExistentClass(
                $statements_analyzer,
                $codebase,
                $stmt,
                $lhs_type_part,
                $intersection_types,
                $class_exists,
                $interface_exists,
                $fq_class_name,
                $override_property_visibility
            );

            if (!$class_exists && !$interface_exists) {
                return;
            }
        } else {
            $class_exists = true;
        }

        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

        $config = $statements_analyzer->getProjectAnalyzer()->getConfig();

        $property_id = $fq_class_name . '::$' . $prop_name;

        if ($lhs_type_part instanceof Type\Atomic\TEnumCase) {
            if ($prop_name !== 'value'
                || $class_storage->enum_type === null
                || !$class_storage->enum_cases
            ) {
                self::handleNonExistentProperty(
                    $statements_analyzer,
                    $codebase,
                    $stmt,
                    $context,
                    $config,
                    $class_storage,
                    $prop_name,
                    $lhs_type_part,
                    $fq_class_name,
                    $property_id,
                    $in_assignment,
                    $stmt_var_id,
                    $has_magic_getter,
                    $var_id
                );
            } else {
                $case_values = [];

                foreach ($class_storage->enum_cases as $enum_case) {
                    if (is_string($enum_case->value)) {
                        $case_values[] = new Type\Atomic\TLiteralString($enum_case->value);
                    } elseif (is_int($enum_case->value)) {
                        $case_values[] = new Type\Atomic\TLiteralInt($enum_case->value);
                    } else {
                        // this should never happen
                        $case_values[] = new Type\Atomic\TMixed();
                    }
                }

                $statements_analyzer->node_data->setType(
                    $stmt,
                    new Type\Union($case_values)
                );
            }

            return;
        }

        $naive_property_exists = $codebase->properties->propertyExists(
            $property_id,
            true,
            $statements_analyzer,
            $context,
            $codebase->collect_locations ? new CodeLocation($statements_analyzer->getSource(), $stmt) : null
        );

        // add method before changing fq_class_name
        $get_method_id = new \Psalm\Internal\MethodIdentifier($fq_class_name, '__get');

        if (!$naive_property_exists
            && $class_storage->namedMixins
        ) {
            foreach ($class_storage->namedMixins as $mixin) {
                $new_property_id = $mixin->value . '::$' . $prop_name;

                try {
                    $new_class_storage = $codebase->classlike_storage_provider->get($mixin->value);
                } catch (\InvalidArgumentException $e) {
                    $new_class_storage = null;
                }

                if ($new_class_storage
                    && ($codebase->properties->propertyExists(
                        $new_property_id,
                        true,
                        $statements_analyzer,
                        $context,
                        $codebase->collect_locations
                                ? new CodeLocation($statements_analyzer->getSource(), $stmt)
                                : null
                    )
                        || isset($new_class_storage->pseudo_property_get_types['$' . $prop_name]))
                ) {
                    $fq_class_name = $mixin->value;
                    $lhs_type_part = clone $mixin;
                    $class_storage = $new_class_storage;

                    if (!isset($new_class_storage->pseudo_property_get_types['$' . $prop_name])) {
                        $naive_property_exists = true;
                    }

                    $property_id = $new_property_id;
                }
            }
        }

        $declaring_property_class = $codebase->properties->getDeclaringClassForProperty(
            $property_id,
            true,
            $statements_analyzer
        );

        if (self::propertyFetchCanBeAnalyzed(
            $statements_analyzer,
            $codebase,
            $stmt,
            $context,
            $fq_class_name,
            $prop_name,
            $lhs_type_part,
            $property_id,
            $has_magic_getter,
            $stmt_var_id,
            $naive_property_exists,
            $override_property_visibility,
            $class_exists,
            $declaring_property_class,
            $class_storage,
            $get_method_id,
            $in_assignment
        ) === false) {
            return;
        }

        if ($codebase->store_node_types
            && !$context->collect_initializations
            && !$context->collect_mutations
        ) {
            $codebase->analyzer->addNodeReference(
                $statements_analyzer->getFilePath(),
                $stmt->name,
                $property_id
            );
        }

        if (!$naive_property_exists
            && $fq_class_name !== $context->self
            && $context->self
            && $codebase->classlikes->classExtends($fq_class_name, $context->self)
            && $codebase->properties->propertyExists(
                $context->self . '::$' . $prop_name,
                true,
                $statements_analyzer,
                $context,
                $codebase->collect_locations
                    ? new CodeLocation($statements_analyzer->getSource(), $stmt)
                    : null
            )
        ) {
            $property_id = $context->self . '::$' . $prop_name;
        } elseif (!$naive_property_exists
            || (!$is_static_access
                // when property existence is asserted by a plugin it doesn't necessarily has storage
                && $codebase->properties->hasStorage($property_id)
                && $codebase->properties->getStorage($property_id)->is_static
            )
        ) {
            self::handleNonExistentProperty(
                $statements_analyzer,
                $codebase,
                $stmt,
                $context,
                $config,
                $class_storage,
                $prop_name,
                $lhs_type_part,
                $declaring_property_class,
                $property_id,
                $in_assignment,
                $stmt_var_id,
                $has_magic_getter,
                $var_id
            );

            return;
        }

        if (!$override_property_visibility) {
            if (ClassLikeAnalyzer::checkPropertyVisibility(
                $property_id,
                $context,
                $statements_analyzer,
                new CodeLocation($statements_analyzer->getSource(), $stmt),
                $statements_analyzer->getSuppressedIssues()
            ) === false) {
                return;
            }
        }

        // FIXME: the following line look superfluous, but removing it makes
        // Psalm\Tests\PropertyTypeTest::testValidCode with data set "callInParentContext"
        // fail
        $declaring_property_class = $codebase->properties->getDeclaringClassForProperty(
            $property_id,
            true,
            $statements_analyzer
        );

        if ($declaring_property_class === null) {
            return;
        }

        if ($codebase->properties_to_rename) {
            $declaring_property_id = strtolower($declaring_property_class) . '::$' . $prop_name;

            foreach ($codebase->properties_to_rename as $original_property_id => $new_property_name) {
                if ($declaring_property_id === $original_property_id) {
                    $file_manipulations = [
                        new \Psalm\FileManipulation(
                            (int) $stmt->name->getAttribute('startFilePos'),
                            (int) $stmt->name->getAttribute('endFilePos') + 1,
                            $new_property_name
                        )
                    ];

                    \Psalm\Internal\FileManipulation\FileManipulationBuffer::add(
                        $statements_analyzer->getFilePath(),
                        $file_manipulations
                    );
                }
            }
        }

        $declaring_class_storage = $codebase->classlike_storage_provider->get(
            $declaring_property_class
        );

        if (isset($declaring_class_storage->properties[$prop_name])) {
            self::checkPropertyDeprecation($prop_name, $declaring_property_class, $stmt, $statements_analyzer);

            $property_storage = $declaring_class_storage->properties[$prop_name];

            if ($context->self && !NamespaceAnalyzer::isWithin($context->self, $property_storage->internal)) {
                if (IssueBuffer::accepts(
                    new InternalProperty(
                        $property_id . ' is internal to ' . $property_storage->internal
                            . ' but called from ' . $context->self,
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $property_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($context->inside_unset) {
                InstancePropertyAssignmentAnalyzer::trackPropertyImpurity(
                    $statements_analyzer,
                    $stmt,
                    $property_id,
                    $property_storage,
                    $declaring_class_storage,
                    $context
                );
            }
        }

        $class_property_type = self::getClassPropertyType(
            $statements_analyzer,
            $codebase,
            $config,
            $context,
            $stmt,
            $class_storage,
            $declaring_class_storage,
            $property_id,
            $fq_class_name,
            $prop_name,
            $lhs_type_part
        );

        if (!$context->collect_mutations
            && !$context->collect_initializations
            && !($class_storage->external_mutation_free
                && $class_property_type->allow_mutations)
        ) {
            if ($context->pure) {
                if (IssueBuffer::accepts(
                    new ImpurePropertyFetch(
                        'Cannot access a property on a mutable object from a pure context',
                        new CodeLocation($statements_analyzer, $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            } elseif ($statements_analyzer->getSource() instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer
                && $statements_analyzer->getSource()->track_mutations
            ) {
                $statements_analyzer->getSource()->inferred_impure = true;
            }
        }

        self::processTaints(
            $statements_analyzer,
            $stmt,
            $class_property_type,
            $property_id,
            $class_storage,
            $in_assignment,
            $context
        );

        if ($class_storage->mutation_free) {
            $class_property_type->has_mutations = false;
        }

        if ($stmt_type = $statements_analyzer->node_data->getType($stmt)) {
            $statements_analyzer->node_data->setType(
                $stmt,
                Type::combineUnionTypes($class_property_type, $stmt_type)
            );
        } else {
            $statements_analyzer->node_data->setType($stmt, $class_property_type);
        }
    }

    public static function checkPropertyDeprecation(
        string $prop_name,
        string $declaring_property_class,
        PhpParser\Node\Expr\PropertyFetch $stmt,
        StatementsAnalyzer $statements_analyzer
    ): void {
        $property_id = $declaring_property_class . '::$' . $prop_name;
        $codebase = $statements_analyzer->getCodebase();
        $declaring_class_storage = $codebase->classlike_storage_provider->get(
            $declaring_property_class
        );

        if (isset($declaring_class_storage->properties[$prop_name])) {
            $property_storage = $declaring_class_storage->properties[$prop_name];

            if ($property_storage->deprecated) {
                if (IssueBuffer::accepts(
                    new DeprecatedProperty(
                        $property_id . ' is marked deprecated',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $property_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        }
    }

    private static function propertyFetchCanBeAnalyzed(
        StatementsAnalyzer $statements_analyzer,
        \Psalm\Codebase $codebase,
        PhpParser\Node\Expr\PropertyFetch $stmt,
        Context $context,
        string $fq_class_name,
        string $prop_name,
        Type\Atomic\TNamedObject $lhs_type_part,
        string &$property_id,
        bool &$has_magic_getter,
        ?string $stmt_var_id,
        bool $naive_property_exists,
        bool $override_property_visibility,
        bool $class_exists,
        ?string $declaring_property_class,
        \Psalm\Storage\ClassLikeStorage $class_storage,
        \Psalm\Internal\MethodIdentifier $get_method_id,
        bool $in_assignment
    ) : bool {
        if ((!$naive_property_exists
                || ($stmt_var_id !== '$this'
                    && $fq_class_name !== $context->self
                    && ClassLikeAnalyzer::checkPropertyVisibility(
                        $property_id,
                        $context,
                        $statements_analyzer,
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $statements_analyzer->getSuppressedIssues(),
                        false
                    ) !== true)
            )
            && $codebase->methods->methodExists(
                $get_method_id,
                $context->calling_method_id,
                $codebase->collect_locations
                    ? new CodeLocation($statements_analyzer->getSource(), $stmt)
                    : null,
                !$context->collect_initializations
                    && !$context->collect_mutations
                    ? $statements_analyzer
                    : null,
                $statements_analyzer->getFilePath()
            )
        ) {
            $has_magic_getter = true;

            if (isset($class_storage->pseudo_property_get_types['$' . $prop_name])) {
                $stmt_type = TypeExpander::expandUnion(
                    $codebase,
                    clone $class_storage->pseudo_property_get_types['$' . $prop_name],
                    $class_storage->name,
                    $class_storage->name,
                    $class_storage->parent_class
                );

                if ($class_storage->template_types) {
                    if (!$lhs_type_part instanceof TGenericObject) {
                        $type_params = [];

                        foreach ($class_storage->template_types as $type_map) {
                            $type_params[] = clone array_values($type_map)[0];
                        }

                        $lhs_type_part = new TGenericObject($lhs_type_part->value, $type_params);
                    }

                    $stmt_type = self::localizePropertyType(
                        $codebase,
                        $stmt_type,
                        $lhs_type_part,
                        $class_storage,
                        $declaring_property_class
                            ? $codebase->classlike_storage_provider->get(
                                $declaring_property_class
                            ) : $class_storage
                    );
                }

                $statements_analyzer->node_data->setType($stmt, $stmt_type);

                self::processTaints(
                    $statements_analyzer,
                    $stmt,
                    $stmt_type,
                    $property_id,
                    $class_storage,
                    $in_assignment,
                    $context
                );

                return false;
            }

            $old_data_provider = $statements_analyzer->node_data;

            $statements_analyzer->node_data = clone $statements_analyzer->node_data;

            $statements_analyzer->node_data->setType($stmt->var, new Type\Union([$lhs_type_part]));

            $fake_method_call = new VirtualMethodCall(
                $stmt->var,
                new VirtualIdentifier('__get', $stmt->name->getAttributes()),
                [
                    new VirtualArg(
                        new VirtualString(
                            $prop_name,
                            $stmt->name->getAttributes()
                        )
                    )
                ]
            );

            $suppressed_issues = $statements_analyzer->getSuppressedIssues();

            if (!in_array('InternalMethod', $suppressed_issues, true)) {
                $statements_analyzer->addSuppressedIssues(['InternalMethod']);
            }

            \Psalm\Internal\Analyzer\Statements\Expression\Call\MethodCallAnalyzer::analyze(
                $statements_analyzer,
                $fake_method_call,
                $context,
                false
            );

            if (!in_array('InternalMethod', $suppressed_issues, true)) {
                $statements_analyzer->removeSuppressedIssues(['InternalMethod']);
            }

            $fake_method_call_type = $statements_analyzer->node_data->getType($fake_method_call);

            $statements_analyzer->node_data = $old_data_provider;

            if ($fake_method_call_type) {
                if ($stmt_type = $statements_analyzer->node_data->getType($stmt)) {
                    $statements_analyzer->node_data->setType(
                        $stmt,
                        Type::combineUnionTypes($fake_method_call_type, $stmt_type)
                    );
                } else {
                    $statements_analyzer->node_data->setType($stmt, $fake_method_call_type);
                }
            } else {
                $statements_analyzer->node_data->setType($stmt, Type::getMixed());
            }

            $property_id = $lhs_type_part->value . '::$' . $prop_name;

            /*
             * If we have an explicit list of all allowed magic properties on the class, and we're
             * not in that list, fall through
             */
            if (!$class_storage->sealed_properties && !$override_property_visibility) {
                return false;
            }

            if (!$class_exists) {
                $property_id = $lhs_type_part->value . '::$' . $prop_name;

                if (IssueBuffer::accepts(
                    new UndefinedMagicPropertyFetch(
                        'Magic instance property ' . $property_id . ' is not defined',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $property_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }

                return false;
            }
        }

        return true;
    }

    public static function localizePropertyType(
        \Psalm\Codebase $codebase,
        Type\Union $class_property_type,
        TGenericObject $lhs_type_part,
        ClassLikeStorage $property_class_storage,
        ClassLikeStorage $property_declaring_class_storage
    ) : Type\Union {
        $template_types = CallAnalyzer::getTemplateTypesForCall(
            $codebase,
            $property_declaring_class_storage,
            $property_declaring_class_storage->name,
            $property_class_storage,
            $property_class_storage->template_types ?: []
        );

        $extended_types = $property_class_storage->template_extended_params;

        if ($template_types) {
            if ($property_class_storage->template_types) {
                foreach ($lhs_type_part->type_params as $param_offset => $lhs_param_type) {
                    $i = -1;

                    foreach ($property_class_storage->template_types as $calling_param_name => $_) {
                        $i++;

                        if ($i === $param_offset) {
                            $template_types[$calling_param_name][$property_class_storage->name] = $lhs_param_type;
                            break;
                        }
                    }
                }
            }

            foreach ($template_types as $type_name => $_) {
                if (isset($extended_types[$property_declaring_class_storage->name][$type_name])) {
                    $mapped_type = $extended_types[$property_declaring_class_storage->name][$type_name];

                    foreach ($mapped_type->getAtomicTypes() as $mapped_type_atomic) {
                        if (!$mapped_type_atomic instanceof Type\Atomic\TTemplateParam) {
                            continue;
                        }

                        $param_name = $mapped_type_atomic->param_name;

                        $position = false;

                        if (isset($property_class_storage->template_types[$param_name])) {
                            $position = \array_search(
                                $param_name,
                                array_keys($property_class_storage->template_types)
                            );
                        }

                        if ($position !== false && isset($lhs_type_part->type_params[$position])) {
                            $template_types[$type_name][$property_declaring_class_storage->name]
                                = $lhs_type_part->type_params[$position];
                        }
                    }
                }
            }

            TemplateInferredTypeReplacer::replace(
                $class_property_type,
                new TemplateResult([], $template_types),
                $codebase
            );
        }

        return $class_property_type;
    }

    public static function processTaints(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\PropertyFetch $stmt,
        Type\Union $type,
        string $property_id,
        \Psalm\Storage\ClassLikeStorage $class_storage,
        bool $in_assignment,
        ?Context $context = null
    ) : void {
        if (!$statements_analyzer->data_flow_graph) {
            return;
        }

        $data_flow_graph = $statements_analyzer->data_flow_graph;

        $var_location = new CodeLocation($statements_analyzer->getSource(), $stmt->var);
        $property_location = new CodeLocation($statements_analyzer->getSource(), $stmt);

        $added_taints = [];
        $removed_taints = [];

        if ($context) {
            $codebase = $statements_analyzer->getCodebase();
            $event = new AddRemoveTaintsEvent($stmt, $context, $statements_analyzer, $codebase);

            $added_taints = $codebase->config->eventDispatcher->dispatchAddTaints($event);
            $removed_taints = $codebase->config->eventDispatcher->dispatchRemoveTaints($event);
        }

        if ($class_storage->specialize_instance) {
            $var_id = ExpressionIdentifier::getArrayVarId(
                $stmt->var,
                null,
                $statements_analyzer
            );

            $var_property_id = ExpressionIdentifier::getArrayVarId(
                $stmt,
                null,
                $statements_analyzer
            );

            if ($var_id) {
                $var_type = $statements_analyzer->node_data->getType($stmt->var);

                if ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph
                    && $var_type
                    && \in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
                ) {
                    $var_type->parent_nodes = [];
                    return;
                }

                $var_node = DataFlowNode::getForAssignment(
                    $var_id,
                    $var_location
                );

                $data_flow_graph->addNode($var_node);

                $property_node = DataFlowNode::getForAssignment(
                    $var_property_id ?: $var_id . '->$property',
                    $property_location
                );

                $data_flow_graph->addNode($property_node);

                $data_flow_graph->addPath(
                    $var_node,
                    $property_node,
                    'property-fetch'
                        . ($stmt->name instanceof PhpParser\Node\Identifier ? '-' . $stmt->name : ''),
                    $added_taints,
                    $removed_taints
                );

                if ($var_type && $var_type->parent_nodes) {
                    foreach ($var_type->parent_nodes as $parent_node) {
                        $data_flow_graph->addPath(
                            $parent_node,
                            $var_node,
                            '=',
                            $added_taints,
                            $removed_taints
                        );
                    }
                }

                $type->parent_nodes = [$property_node->id => $property_node];
            }
        } else {
            $var_property_id = ExpressionIdentifier::getArrayVarId(
                $stmt,
                null,
                $statements_analyzer
            );

            $localized_property_node = DataFlowNode::getForAssignment(
                $var_property_id
                    ?: $property_id . '-' . $property_location->file_name . ':' . $property_location->raw_file_start,
                $property_location
            );

            $data_flow_graph->addNode($localized_property_node);

            $property_node = new DataFlowNode(
                $property_id,
                $property_id,
                null,
                null
            );

            $data_flow_graph->addNode($property_node);

            if ($in_assignment) {
                $data_flow_graph->addPath(
                    $localized_property_node,
                    $property_node,
                    'property-assignment',
                    $added_taints,
                    $removed_taints
                );
            } else {
                $data_flow_graph->addPath(
                    $property_node,
                    $localized_property_node,
                    'property-fetch',
                    $added_taints,
                    $removed_taints
                );
            }

            $type->parent_nodes = [$localized_property_node->id => $localized_property_node];
        }
    }

    private static function handleUndefinedProperty(
        Context $context,
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\PropertyFetch $stmt,
        ?string $stmt_var_id,
        string $property_id,
        bool $has_magic_getter,
        ?string $var_id
    ): void {
        if ($context->inside_isset || $context->collect_initializations) {
            if ($context->pure) {
                if (IssueBuffer::accepts(
                    new ImpurePropertyFetch(
                        'Cannot access a property on a mutable object from a pure context',
                        new CodeLocation($statements_analyzer, $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            } elseif ($context->inside_isset
                && $statements_analyzer->getSource()
                instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer
                && $statements_analyzer->getSource()->track_mutations
            ) {
                $statements_analyzer->getSource()->inferred_impure = true;
            }

            return;
        }

        if ($stmt_var_id === '$this') {
            if (IssueBuffer::accepts(
                new UndefinedThisPropertyFetch(
                    'Instance property ' . $property_id . ' is not defined',
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                    $property_id
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }
        } else {
            if ($has_magic_getter) {
                if (IssueBuffer::accepts(
                    new UndefinedMagicPropertyFetch(
                        'Magic instance property ' . $property_id . ' is not defined',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $property_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            } else {
                if (IssueBuffer::accepts(
                    new UndefinedPropertyFetch(
                        'Instance property ' . $property_id . ' is not defined',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $property_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        }

        $stmt_type = Type::getMixed();

        $statements_analyzer->node_data->setType($stmt, $stmt_type);

        if ($var_id) {
            $context->vars_in_scope[$var_id] = $stmt_type;
        }
    }

    /**
     * @param  array<Type\Atomic>     $intersection_types
     */
    private static function handleNonExistentClass(
        StatementsAnalyzer $statements_analyzer,
        \Psalm\Codebase $codebase,
        PhpParser\Node\Expr\PropertyFetch $stmt,
        TNamedObject $lhs_type_part,
        array $intersection_types,
        bool &$class_exists,
        bool &$interface_exists,
        string &$fq_class_name,
        bool &$override_property_visibility
    ): void {
        if ($codebase->interfaceExists($lhs_type_part->value)) {
            $interface_exists = true;
            $interface_storage = $codebase->classlike_storage_provider->get($lhs_type_part->value);

            $override_property_visibility = $interface_storage->override_property_visibility;

            foreach ($intersection_types as $intersection_type) {
                if ($intersection_type instanceof TNamedObject
                    && $codebase->classExists($intersection_type->value)
                ) {
                    $fq_class_name = $intersection_type->value;
                    $class_exists = true;
                    return;
                }
            }

            if (!$class_exists) {
                if (IssueBuffer::accepts(
                    new NoInterfaceProperties(
                        'Interfaces cannot have properties',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $lhs_type_part->value
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    return;
                }

                if (!$codebase->methodExists($fq_class_name . '::__set')) {
                    return;
                }
            }
        }

        if (!$class_exists && !$interface_exists) {
            if ($lhs_type_part->from_docblock) {
                if (IssueBuffer::accepts(
                    new UndefinedDocblockClass(
                        'Cannot get properties of undefined docblock class ' . $lhs_type_part->value,
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $lhs_type_part->value
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            } else {
                if (IssueBuffer::accepts(
                    new UndefinedClass(
                        'Cannot get properties of undefined class ' . $lhs_type_part->value,
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $lhs_type_part->value
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        }
    }

    private static function handleNonExistentProperty(
        StatementsAnalyzer $statements_analyzer,
        \Psalm\Codebase $codebase,
        PhpParser\Node\Expr\PropertyFetch $stmt,
        Context $context,
        Config $config,
        ClassLikeStorage $class_storage,
        string $prop_name,
        TNamedObject $lhs_type_part,
        ?string $declaring_property_class,
        string $property_id,
        bool $in_assignment,
        ?string $stmt_var_id,
        bool $has_magic_getter,
        ?string $var_id
    ): void {
        if ($config->use_phpdoc_property_without_magic_or_parent
            && isset($class_storage->pseudo_property_get_types['$' . $prop_name])
        ) {
            $stmt_type = clone $class_storage->pseudo_property_get_types['$' . $prop_name];

            if ($class_storage->template_types) {
                if (!$lhs_type_part instanceof TGenericObject) {
                    $type_params = [];

                    foreach ($class_storage->template_types as $type_map) {
                        $type_params[] = clone array_values($type_map)[0];
                    }

                    $lhs_type_part = new TGenericObject($lhs_type_part->value, $type_params);
                }

                $stmt_type = self::localizePropertyType(
                    $codebase,
                    $stmt_type,
                    $lhs_type_part,
                    $class_storage,
                    $declaring_property_class
                        ? $codebase->classlike_storage_provider->get(
                            $declaring_property_class
                        ) : $class_storage
                );
            }

            $statements_analyzer->node_data->setType($stmt, $stmt_type);

            self::processTaints(
                $statements_analyzer,
                $stmt,
                $stmt_type,
                $property_id,
                $class_storage,
                $in_assignment,
                $context
            );

            return;
        }

        if ($class_storage->is_interface) {
            return;
        }

        self::handleUndefinedProperty(
            $context,
            $statements_analyzer,
            $stmt,
            $stmt_var_id,
            $property_id,
            $has_magic_getter,
            $var_id
        );

        return;
    }

    private static function getClassPropertyType(
        StatementsAnalyzer $statements_analyzer,
        \Psalm\Codebase $codebase,
        Config $config,
        Context $context,
        PhpParser\Node\Expr\PropertyFetch $stmt,
        ClassLikeStorage $class_storage,
        ClassLikeStorage $declaring_class_storage,
        string $property_id,
        string $fq_class_name,
        string $prop_name,
        TNamedObject $lhs_type_part
    ): Type\Union {
        $class_property_type = $codebase->properties->getPropertyType(
            $property_id,
            false,
            $statements_analyzer,
            $context
        );

        if (!$class_property_type) {
            if ($declaring_class_storage->location
                && $config->isInProjectDirs(
                    $declaring_class_storage->location->file_path
                )
            ) {
                if (IssueBuffer::accepts(
                    new MissingPropertyType(
                        'Property ' . $fq_class_name . '::$' . $prop_name
                        . ' does not have a declared type',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $property_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            $class_property_type = Type::getMixed();
        } else {
            $class_property_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
                $codebase,
                clone $class_property_type,
                $declaring_class_storage->name,
                $declaring_class_storage->name,
                $declaring_class_storage->parent_class
            );

            if ($declaring_class_storage->template_types) {
                if (!$lhs_type_part instanceof TGenericObject) {
                    $type_params = [];

                    foreach ($declaring_class_storage->template_types as $type_map) {
                        $type_params[] = clone array_values($type_map)[0];
                    }

                    $lhs_type_part = new TGenericObject($lhs_type_part->value, $type_params);
                }

                $class_property_type = self::localizePropertyType(
                    $codebase,
                    $class_property_type,
                    $lhs_type_part,
                    $class_storage,
                    $declaring_class_storage
                );
            } elseif ($lhs_type_part instanceof TGenericObject) {
                $class_property_type = self::localizePropertyType(
                    $codebase,
                    $class_property_type,
                    $lhs_type_part,
                    $class_storage,
                    $declaring_class_storage
                );
            }
        }

        return $class_property_type;
    }
}
