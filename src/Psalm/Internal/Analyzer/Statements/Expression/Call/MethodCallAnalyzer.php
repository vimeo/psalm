<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\Internal\Codebase\CallMap;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Issue\ImpureMethodCall;
use Psalm\Issue\InvalidMethodCall;
use Psalm\Issue\InvalidPropertyAssignmentValue;
use Psalm\Issue\InvalidScope;
use Psalm\Issue\MixedMethodCall;
use Psalm\Issue\MixedPropertyTypeCoercion;
use Psalm\Issue\NullReference;
use Psalm\Issue\PossiblyFalseReference;
use Psalm\Issue\PossiblyInvalidMethodCall;
use Psalm\Issue\PossiblyInvalidPropertyAssignmentValue;
use Psalm\Issue\PossiblyNullReference;
use Psalm\Issue\PossiblyUndefinedMethod;
use Psalm\Issue\PropertyTypeCoercion;
use Psalm\Issue\UndefinedInterfaceMethod;
use Psalm\Issue\UndefinedMagicMethod;
use Psalm\Issue\UndefinedMethod;
use Psalm\Issue\UndefinedThisPropertyAssignment;
use Psalm\Issue\UndefinedThisPropertyFetch;
use Psalm\IssueBuffer;
use Psalm\Storage\Assertion;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNamedObject;
use function is_string;
use function array_values;
use function array_shift;
use function array_unshift;
use function get_class;
use function strtolower;
use function array_map;
use function array_merge;
use function explode;
use function implode;
use function array_search;
use function array_keys;
use function in_array;
use Psalm\Internal\Taint\Source;
use Doctrine\Instantiator\Exception\UnexpectedValueException;

/**
 * @internal
 */
class MethodCallAnalyzer extends \Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer
{
    /**
     * @param   StatementsAnalyzer               $statements_analyzer
     * @param   PhpParser\Node\Expr\MethodCall  $stmt
     * @param   Context                         $context
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\MethodCall $stmt,
        Context $context,
        bool $real_method_call = true
    ) {
        $was_inside_call = $context->inside_call;

        $context->inside_call = true;

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->var, $context) === false) {
            return false;
        }

        $context->inside_call = $was_inside_call;

        if (!$stmt->name instanceof PhpParser\Node\Identifier) {
            $was_inside_call = $context->inside_call;
            $context->inside_call = true;
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->name, $context) === false) {
                return false;
            }
            $context->inside_call = $was_inside_call;
        }

        if ($stmt->var instanceof PhpParser\Node\Expr\Variable) {
            if (is_string($stmt->var->name) && $stmt->var->name === 'this' && !$statements_analyzer->getFQCLN()) {
                if (IssueBuffer::accepts(
                    new InvalidScope(
                        'Use of $this in non-class context',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    return false;
                }
            }
        }

        $lhs_var_id = ExpressionAnalyzer::getArrayVarId(
            $stmt->var,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        $class_type = $lhs_var_id && $context->hasVariable($lhs_var_id, $statements_analyzer)
            ? $context->vars_in_scope[$lhs_var_id]
            : null;

        if ($stmt_var_type = $statements_analyzer->node_data->getType($stmt->var)) {
            $class_type = $stmt_var_type;
        } elseif (!$class_type) {
            $statements_analyzer->node_data->setType($stmt, Type::getMixed());
        }

        if (!$context->check_classes) {
            if (self::checkFunctionArguments(
                $statements_analyzer,
                $stmt->args,
                null,
                null,
                $context
            ) === false) {
                return false;
            }

            return null;
        }

        if ($class_type
            && $stmt->name instanceof PhpParser\Node\Identifier
            && ($class_type->isNull() || $class_type->isVoid())
        ) {
            if (IssueBuffer::accepts(
                new NullReference(
                    'Cannot call method ' . $stmt->name->name . ' on null value',
                    new CodeLocation($statements_analyzer->getSource(), $stmt->name)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                return false;
            }

            return null;
        }

        if ($class_type
            && $stmt->name instanceof PhpParser\Node\Identifier
            && $class_type->isNullable()
            && !$class_type->ignore_nullable_issues
        ) {
            if (IssueBuffer::accepts(
                new PossiblyNullReference(
                    'Cannot call method ' . $stmt->name->name . ' on possibly null value',
                    new CodeLocation($statements_analyzer->getSource(), $stmt->name)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        if ($class_type
            && $stmt->name instanceof PhpParser\Node\Identifier
            && $class_type->isFalsable()
            && !$class_type->ignore_falsable_issues
        ) {
            if (IssueBuffer::accepts(
                new PossiblyFalseReference(
                    'Cannot call method ' . $stmt->name->name . ' on possibly false value',
                    new CodeLocation($statements_analyzer->getSource(), $stmt->name)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        $codebase = $statements_analyzer->getCodebase();

        $source = $statements_analyzer->getSource();

        if (!$class_type) {
            $class_type = Type::getMixed();
        }

        $lhs_types = $class_type->getAtomicTypes();

        $result = new AtomicMethodCallAnalysisResult();

        foreach ($lhs_types as $lhs_type_part) {
            AtomicMethodCallAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $codebase,
                $context,
                $lhs_type_part,
                $lhs_type_part instanceof Type\Atomic\TNamedObject
                    || $lhs_type_part instanceof Type\Atomic\TTemplateParam
                    ? $lhs_type_part
                    : null,
                false,
                $lhs_var_id,
                $result
            );
        }

        if ($result->invalid_method_call_types) {
            $invalid_class_type = $result->invalid_method_call_types[0];

            if ($result->has_valid_method_call_type || $result->has_mixed_method_call) {
                if (IssueBuffer::accepts(
                    new PossiblyInvalidMethodCall(
                        'Cannot call method on possible ' . $invalid_class_type . ' variable ' . $lhs_var_id,
                        new CodeLocation($source, $stmt->name)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // keep going
                }
            } else {
                if (IssueBuffer::accepts(
                    new InvalidMethodCall(
                        'Cannot call method on ' . $invalid_class_type . ' variable ' . $lhs_var_id,
                        new CodeLocation($source, $stmt->name)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // keep going
                }
            }
        }

        if ($result->non_existent_magic_method_ids) {
            if ($context->check_methods) {
                if (IssueBuffer::accepts(
                    new UndefinedMagicMethod(
                        'Magic method ' . $result->non_existent_magic_method_ids[0] . ' does not exist',
                        new CodeLocation($source, $stmt->name),
                        $result->non_existent_magic_method_ids[0]
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // keep going
                }
            }
        }

        if ($result->non_existent_class_method_ids) {
            if ($context->check_methods) {
                if ($result->existent_method_ids || $result->has_mixed_method_call) {
                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedMethod(
                            'Method ' . $result->non_existent_class_method_ids[0] . ' does not exist',
                            new CodeLocation($source, $stmt->name),
                            $result->non_existent_class_method_ids[0]
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // keep going
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new UndefinedMethod(
                            'Method ' . $result->non_existent_class_method_ids[0] . ' does not exist',
                            new CodeLocation($source, $stmt->name),
                            $result->non_existent_class_method_ids[0]
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // keep going
                    }
                }
            }

            return null;
        }

        if ($result->non_existent_interface_method_ids) {
            if ($context->check_methods) {
                if ($result->existent_method_ids || $result->has_mixed_method_call) {
                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedMethod(
                            'Method ' . $result->non_existent_interface_method_ids[0] . ' does not exist',
                            new CodeLocation($source, $stmt->name),
                            $result->non_existent_interface_method_ids[0]
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // keep going
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new UndefinedInterfaceMethod(
                            'Method ' . $result->non_existent_interface_method_ids[0] . ' does not exist',
                            new CodeLocation($source, $stmt->name),
                            $result->non_existent_interface_method_ids[0]
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // keep going
                    }
                }
            }

            return null;
        }

        $stmt_type = $result->return_type;

        if ($stmt_type) {
            $statements_analyzer->node_data->setType($stmt, $stmt_type);
        }

        if ($result->returns_by_ref) {
            if (!$stmt_type) {
                $stmt_type = Type::getMixed();
                $statements_analyzer->node_data->setType($stmt, $stmt_type);
            }

            $stmt_type->by_ref = $result->returns_by_ref;
        }

        if ($codebase->store_node_types
            && !$context->collect_initializations
            && !$context->collect_mutations
            && $stmt_type
        ) {
            $codebase->analyzer->addNodeType(
                $statements_analyzer->getFilePath(),
                $stmt->name,
                $stmt_type->getId(),
                $stmt
            );
        }

        if (!$result->existent_method_ids) {
            return self::checkMethodArgs(
                null,
                $stmt->args,
                null,
                $context,
                new CodeLocation($statements_analyzer->getSource(), $stmt),
                $statements_analyzer
            );
        }

        // if we called a method on this nullable variable, remove the nullable status here
        // because any further calls must have worked
        if ($lhs_var_id
            && !$class_type->isMixed()
            && $result->has_valid_method_call_type
            && !$result->has_mixed_method_call
            && !$result->invalid_method_call_types
            && ($class_type->from_docblock || $class_type->isNullable())
            && $real_method_call
        ) {
            $keys_to_remove = [];

            $class_type = clone $class_type;

            foreach ($class_type->getAtomicTypes() as $key => $type) {
                if (!$type instanceof TNamedObject) {
                    $keys_to_remove[] = $key;
                } else {
                    $type->from_docblock = false;
                }
            }

            foreach ($keys_to_remove as $key) {
                $class_type->removeType($key);
            }

            $class_type->from_docblock = false;

            $context->removeVarFromConflictingClauses($lhs_var_id, null, $statements_analyzer);

            $context->vars_in_scope[$lhs_var_id] = $class_type;
        }
    }

    /**
     * @param lowercase-string $method_name
     * @return array<string, array<string, array{Type\Union, 1?:int}>>|null
     */
    public static function getClassTemplateParams(
        Codebase $codebase,
        ClassLikeStorage $class_storage,
        string $static_fq_class_name,
        string $method_name = null,
        Type\Atomic $lhs_type_part = null,
        string $lhs_var_id = null
    ) {
        $static_class_storage = $codebase->classlike_storage_provider->get($static_fq_class_name);

        $non_trait_class_storage = $class_storage->is_trait
            ? $static_class_storage
            : $class_storage;

        $template_types = $class_storage->template_types;

        $candidate_class_storages = [$class_storage];

        if ($static_class_storage->template_type_extends
            && $method_name
            && !empty($non_trait_class_storage->overridden_method_ids[$method_name])
            && isset($class_storage->methods[$method_name])
            && (!isset($non_trait_class_storage->methods[$method_name]->return_type)
                || $class_storage->methods[$method_name]->inherited_return_type)
        ) {
            foreach ($non_trait_class_storage->overridden_method_ids[$method_name] as $overridden_method_id) {
                $overridden_storage = $codebase->methods->getStorage($overridden_method_id);

                if (!$overridden_storage->return_type) {
                    continue;
                }

                if ($overridden_storage->return_type->isNull()) {
                    continue;
                }

                $fq_overridden_class = $overridden_method_id->fq_class_name;

                $overridden_class_storage = $codebase->classlike_storage_provider->get($fq_overridden_class);

                $overridden_template_types = $overridden_class_storage->template_types;

                if (!$template_types) {
                    $template_types = $overridden_template_types;
                } elseif ($overridden_template_types) {
                    foreach ($overridden_template_types as $template_name => $template_map) {
                        if (isset($template_types[$template_name])) {
                            $template_types[$template_name] = array_merge(
                                $template_types[$template_name],
                                $template_map
                            );
                        } else {
                            $template_types[$template_name] = $template_map;
                        }
                    }
                }

                $candidate_class_storages[] = $overridden_class_storage;
            }
        }

        if (!$template_types) {
            return null;
        }

        $class_template_params = [];
        $e = $static_class_storage->template_type_extends;

        if ($lhs_type_part instanceof TGenericObject) {
            if ($class_storage === $static_class_storage && $static_class_storage->template_types) {
                $i = 0;

                foreach ($static_class_storage->template_types as $type_name => $_) {
                    if (isset($lhs_type_part->type_params[$i])) {
                        if ($lhs_var_id !== '$this' || $static_fq_class_name !== $static_class_storage->name) {
                            $class_template_params[$type_name][$static_class_storage->name] = [
                                $lhs_type_part->type_params[$i]
                            ];
                        }
                    }

                    $i++;
                }
            }

            $i = 0;
            foreach ($template_types as $type_name => $_) {
                if (isset($class_template_params[$type_name])) {
                    $i++;
                    continue;
                }

                if ($class_storage !== $static_class_storage
                    && isset($e[$class_storage->name][$type_name])
                ) {
                    $input_type_extends = $e[$class_storage->name][$type_name];

                    $output_type_extends = null;

                    foreach ($input_type_extends->getAtomicTypes() as $type_extends_atomic) {
                        if ($type_extends_atomic instanceof Type\Atomic\TTemplateParam) {
                            if (isset($static_class_storage->template_types[$type_extends_atomic->param_name])) {
                                $mapped_offset = array_search(
                                    $type_extends_atomic->param_name,
                                    array_keys($static_class_storage->template_types)
                                );

                                if (isset($lhs_type_part->type_params[(int) $mapped_offset])) {
                                    $candidate_type = $lhs_type_part->type_params[(int) $mapped_offset];

                                    if (!$output_type_extends) {
                                        $output_type_extends = $candidate_type;
                                    } else {
                                        $output_type_extends = Type::combineUnionTypes(
                                            $candidate_type,
                                            $output_type_extends
                                        );
                                    }
                                }
                            } elseif (isset(
                                $static_class_storage
                                    ->template_type_extends
                                        [$type_extends_atomic->defining_class]
                                        [$type_extends_atomic->param_name]
                            )) {
                                $mapped_offset = array_search(
                                    $type_extends_atomic->param_name,
                                    array_keys($static_class_storage
                                    ->template_type_extends
                                        [$type_extends_atomic->defining_class])
                                );

                                if (isset($lhs_type_part->type_params[(int) $mapped_offset])) {
                                    $candidate_type = $lhs_type_part->type_params[(int) $mapped_offset];

                                    if (!$output_type_extends) {
                                        $output_type_extends = $candidate_type;
                                    } else {
                                        $output_type_extends = Type::combineUnionTypes(
                                            $candidate_type,
                                            $output_type_extends
                                        );
                                    }
                                }
                            }
                        } else {
                            if (!$output_type_extends) {
                                $output_type_extends = new Type\Union([$type_extends_atomic]);
                            } else {
                                $output_type_extends = Type::combineUnionTypes(
                                    new Type\Union([$type_extends_atomic]),
                                    $output_type_extends
                                );
                            }
                        }
                    }

                    if ($lhs_var_id !== '$this' || $static_fq_class_name !== $class_storage->name) {
                        $class_template_params[$type_name][$class_storage->name] = [
                            $output_type_extends ?: Type::getMixed()
                        ];
                    }
                }

                if (($lhs_var_id !== '$this' || $static_fq_class_name !== $class_storage->name)
                    && !isset($class_template_params[$type_name])
                ) {
                    $class_template_params[$type_name] = [
                        $class_storage->name => [Type::getMixed()]
                    ];
                }

                $i++;
            }
        }

        foreach ($template_types as $type_name => $type_map) {
            foreach ($type_map as list($type)) {
                foreach ($candidate_class_storages as $candidate_class_storage) {
                    if ($candidate_class_storage !== $static_class_storage
                        && isset($e[$candidate_class_storage->name][$type_name])
                        && !isset($class_template_params[$type_name][$candidate_class_storage->name])
                    ) {
                        $class_template_params[$type_name][$candidate_class_storage->name] = [
                            new Type\Union(
                                self::expandType(
                                    $e[$candidate_class_storage->name][$type_name],
                                    $e,
                                    $static_class_storage->name,
                                    $static_class_storage->template_types
                                )
                            )
                        ];
                    }
                }

                if ($lhs_var_id !== '$this') {
                    if (!isset($class_template_params[$type_name])) {
                        $class_template_params[$type_name][$class_storage->name] = [$type];
                    }
                }
            }
        }

        return $class_template_params;
    }

    /**
     * @param array<string, array<int|string, Type\Union>> $e
     * @return non-empty-list<Type\Atomic>
     */
    private static function expandType(
        Type\Union $input_type_extends,
        array $e,
        string $static_fq_class_name,
        ?array $static_template_types
    ) : array {
        $output_type_extends = [];

        foreach ($input_type_extends->getAtomicTypes() as $type_extends_atomic) {
            if ($type_extends_atomic instanceof Type\Atomic\TTemplateParam
                && ($static_fq_class_name !== $type_extends_atomic->defining_class
                    || !isset($static_template_types[$type_extends_atomic->param_name]))
                && isset($e[$type_extends_atomic->defining_class][$type_extends_atomic->param_name])
            ) {
                $output_type_extends = array_merge(
                    $output_type_extends,
                    self::expandType(
                        $e[$type_extends_atomic->defining_class][$type_extends_atomic->param_name],
                        $e,
                        $static_fq_class_name,
                        $static_template_types
                    )
                );
            } else {
                $output_type_extends[] = $type_extends_atomic;
            }
        }

        return $output_type_extends;
    }
}
