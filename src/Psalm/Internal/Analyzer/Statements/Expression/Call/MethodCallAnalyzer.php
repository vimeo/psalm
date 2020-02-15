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

        $has_mock = false;

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

        $non_existent_class_method_ids = [];
        $non_existent_interface_method_ids = [];
        $non_existent_magic_method_ids = [];
        $existent_method_ids = [];
        $has_mixed_method_call = false;

        $invalid_method_call_types = [];
        $has_valid_method_call_type = false;

        $source = $statements_analyzer->getSource();

        $returns_by_ref = false;

        $no_method_id = false;

        if (!$class_type) {
            $class_type = Type::getMixed();
        }

        $return_type = null;

        $lhs_types = $class_type->getAtomicTypes();

        foreach ($lhs_types as $lhs_type_part) {
            $result = self::analyzeAtomicCall(
                $statements_analyzer,
                $stmt,
                $codebase,
                $context,
                $lhs_type_part,
                $lhs_type_part instanceof Type\Atomic\TNamedObject
                    || $lhs_type_part instanceof Type\Atomic\TTemplateParam
                    ? $lhs_type_part
                    : null,
                $lhs_var_id,
                $return_type,
                $returns_by_ref,
                $has_mock,
                $has_valid_method_call_type,
                $has_mixed_method_call,
                $invalid_method_call_types,
                $existent_method_ids,
                $non_existent_class_method_ids,
                $non_existent_interface_method_ids,
                $non_existent_magic_method_ids
            );

            if ($result === false) {
                return false;
            }

            if ($result === true) {
                $no_method_id = true;
            }
        }

        if ($invalid_method_call_types) {
            $invalid_class_type = $invalid_method_call_types[0];

            if ($has_valid_method_call_type || $has_mixed_method_call) {
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

        if ($non_existent_magic_method_ids) {
            if ($context->check_methods) {
                if (IssueBuffer::accepts(
                    new UndefinedMagicMethod(
                        'Magic method ' . $non_existent_magic_method_ids[0] . ' does not exist',
                        new CodeLocation($source, $stmt->name),
                        $non_existent_magic_method_ids[0]
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // keep going
                }
            }
        }

        if ($non_existent_class_method_ids) {
            if ($context->check_methods) {
                if ($existent_method_ids || $has_mixed_method_call) {
                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedMethod(
                            'Method ' . $non_existent_class_method_ids[0] . ' does not exist',
                            new CodeLocation($source, $stmt->name),
                            $non_existent_class_method_ids[0]
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // keep going
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new UndefinedMethod(
                            'Method ' . $non_existent_class_method_ids[0] . ' does not exist',
                            new CodeLocation($source, $stmt->name),
                            $non_existent_class_method_ids[0]
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // keep going
                    }
                }
            }

            return null;
        }

        if ($non_existent_interface_method_ids) {
            if ($context->check_methods) {
                if ($existent_method_ids || $has_mixed_method_call) {
                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedMethod(
                            'Method ' . $non_existent_interface_method_ids[0] . ' does not exist',
                            new CodeLocation($source, $stmt->name),
                            $non_existent_interface_method_ids[0]
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // keep going
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new UndefinedInterfaceMethod(
                            'Method ' . $non_existent_interface_method_ids[0] . ' does not exist',
                            new CodeLocation($source, $stmt->name),
                            $non_existent_interface_method_ids[0]
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // keep going
                    }
                }
            }

            return null;
        }

        $stmt_type = $return_type;

        if ($stmt_type) {
            $statements_analyzer->node_data->setType($stmt, $stmt_type);
        }

        if ($returns_by_ref) {
            if (!$stmt_type) {
                $stmt_type = Type::getMixed();
                $statements_analyzer->node_data->setType($stmt, $stmt_type);
            }

            $stmt_type->by_ref = $returns_by_ref;
        }

        if ($codebase->store_node_types
            && !$context->collect_initializations
            && !$context->collect_mutations
            && $stmt_type
        ) {
            $codebase->analyzer->addNodeType(
                $statements_analyzer->getFilePath(),
                $stmt->name,
                (string) $stmt_type,
                $stmt
            );
        }

        if ($no_method_id) {
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
            && $has_valid_method_call_type
            && !$has_mixed_method_call
            && !$invalid_method_call_types
            && $existent_method_ids
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
     * [analyzeAtomicCall description]
     * @param  StatementsAnalyzer             $statements_analyzer
     * @param  PhpParser\Node\Expr\MethodCall $stmt
     * @param  Codebase                       $codebase
     * @param  Context                        $context
     * @param  Type\Atomic\TNamedObject|Type\Atomic\TTemplateParam  $static_type
     * @param  ?string                        $lhs_var_id
     * @param  ?Type\Union                    &$return_type
     * @param  bool                           &$returns_by_ref
     * @param  bool                           &$has_mock
     * @param  bool                           &$has_valid_method_call_type
     * @param  bool                           &$has_mixed_method_call
     * @param  array<string>                  &$invalid_method_call_types
     * @param  array<string>                  &$existent_method_ids
     * @param  array<string>                  &$non_existent_class_method_ids
     * @param  array<string>                  &$non_existent_interface_method_ids
     * @param  array<string>                  &$non_existent_magic_method_ids
     * @return null|bool
     */
    private static function analyzeAtomicCall(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\MethodCall $stmt,
        Codebase $codebase,
        Context $context,
        Type\Atomic $lhs_type_part,
        ?Type\Atomic $static_type,
        $lhs_var_id,
        &$return_type,
        &$returns_by_ref,
        &$has_mock,
        &$has_valid_method_call_type,
        &$has_mixed_method_call,
        &$invalid_method_call_types,
        &$existent_method_ids,
        &$non_existent_class_method_ids,
        &$non_existent_interface_method_ids,
        &$non_existent_magic_method_ids,
        bool &$check_visibility = true
    ) {
        $config = $codebase->config;

        if ($lhs_type_part instanceof Type\Atomic\TTemplateParam
            && !$lhs_type_part->as->isMixed()
        ) {
            $extra_types = $lhs_type_part->extra_types;

            $lhs_type_part = array_values(
                $lhs_type_part->as->getAtomicTypes()
            )[0];

            $lhs_type_part->from_docblock = true;

            if ($lhs_type_part instanceof TNamedObject) {
                $lhs_type_part->extra_types = $extra_types;
            } elseif ($lhs_type_part instanceof Type\Atomic\TObject && $extra_types) {
                $lhs_type_part = array_shift($extra_types);
                if ($extra_types) {
                    $lhs_type_part->extra_types = $extra_types;
                }
            }

            $has_mixed_method_call = true;
        }

        $source = $statements_analyzer->getSource();

        if (!$lhs_type_part instanceof TNamedObject) {
            switch (get_class($lhs_type_part)) {
                case Type\Atomic\TNull::class:
                case Type\Atomic\TFalse::class:
                    // handled above
                    return;

                case Type\Atomic\TInt::class:
                case Type\Atomic\TLiteralInt::class:
                case Type\Atomic\TFloat::class:
                case Type\Atomic\TLiteralFloat::class:
                case Type\Atomic\TBool::class:
                case Type\Atomic\TTrue::class:
                case Type\Atomic\TArray::class:
                case Type\Atomic\TNonEmptyArray::class:
                case Type\Atomic\ObjectLike::class:
                case Type\Atomic\TString::class:
                case Type\Atomic\TSingleLetter::class:
                case Type\Atomic\TLiteralString::class:
                case Type\Atomic\TLiteralClassString::class:
                case Type\Atomic\TTemplateParamClass::class:
                case Type\Atomic\TNumericString::class:
                case Type\Atomic\THtmlEscapedString::class:
                case Type\Atomic\TClassString::class:
                case Type\Atomic\TIterable::class:
                    $invalid_method_call_types[] = (string)$lhs_type_part;
                    return;

                case Type\Atomic\TTemplateParam::class:
                case Type\Atomic\TEmptyMixed::class:
                case Type\Atomic\TEmpty::class:
                case Type\Atomic\TMixed::class:
                case Type\Atomic\TNonEmptyMixed::class:
                case Type\Atomic\TObject::class:
                case Type\Atomic\TObjectWithProperties::class:
                    if (!$context->collect_initializations
                        && !$context->collect_mutations
                        && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                        && (!(($parent_source = $statements_analyzer->getSource())
                                instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                            || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
                    ) {
                        $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
                    }

                    $has_mixed_method_call = true;

                    if ($lhs_type_part instanceof Type\Atomic\TObjectWithProperties
                        && $stmt->name instanceof PhpParser\Node\Identifier
                        && isset($lhs_type_part->methods[$stmt->name->name])
                    ) {
                        $existent_method_ids[] = $lhs_type_part->methods[$stmt->name->name];
                    } else {
                        if ($stmt->name instanceof PhpParser\Node\Identifier) {
                            $codebase->analyzer->addMixedMemberName(
                                strtolower($stmt->name->name),
                                $context->calling_function_id ?: $statements_analyzer->getFileName()
                            );
                        }

                        if ($context->check_methods) {
                            $message = 'Cannot determine the type of the object'
                                . ' on the left hand side of this expression';

                            if ($lhs_var_id) {
                                $message = 'Cannot determine the type of ' . $lhs_var_id;

                                if ($stmt->name instanceof PhpParser\Node\Identifier) {
                                    $message .= ' when calling method ' . $stmt->name->name;
                                }
                            }

                            if (IssueBuffer::accepts(
                                new MixedMethodCall(
                                    $message,
                                    new CodeLocation($source, $stmt->name)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        }
                    }

                    if (self::checkFunctionArguments(
                        $statements_analyzer,
                        $stmt->args,
                        null,
                        null,
                        $context
                    ) === false) {
                        return false;
                    }

                    $return_type = Type::getMixed();
                    return;
            }

            return;
        }

        if (!$context->collect_initializations
            && !$context->collect_mutations
            && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
            && (!(($parent_source = $statements_analyzer->getSource())
                    instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
        ) {
            $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getFilePath());
        }

        $has_valid_method_call_type = true;

        $fq_class_name = $lhs_type_part->value;

        $is_mock = ExpressionAnalyzer::isMock($fq_class_name);

        $has_mock = $has_mock || $is_mock;

        if ($fq_class_name === 'static') {
            $fq_class_name = (string) $context->self;
        }

        if ($is_mock ||
            $context->isPhantomClass($fq_class_name)
        ) {
            $return_type = Type::getMixed();

            if (self::checkFunctionArguments(
                $statements_analyzer,
                $stmt->args,
                null,
                null,
                $context
            ) === false) {
                return false;
            }

            return;
        }

        if ($lhs_var_id === '$this') {
            $does_class_exist = true;
        } else {
            $does_class_exist = ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                $statements_analyzer,
                $fq_class_name,
                new CodeLocation($source, $stmt->var),
                $context->self,
                $statements_analyzer->getSuppressedIssues(),
                true,
                false,
                true,
                $lhs_type_part->from_docblock
            );
        }

        if (!$does_class_exist) {
            $non_existent_class_method_ids[] = $fq_class_name . '::*';
            return false;
        }

        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

        $check_visibility = $check_visibility && !$class_storage->override_method_visibility;

        $intersection_types = $lhs_type_part->getIntersectionTypes();

        $all_intersection_return_type = null;
        $all_intersection_existent_method_ids = [];

        if ($intersection_types) {
            foreach ($intersection_types as $intersection_type) {
                $i_non_existent_class_method_ids = [];
                $i_non_existent_interface_method_ids = [];
                $i_non_existent_magic_method_ids = [];

                $intersection_return_type = null;

                self::analyzeAtomicCall(
                    $statements_analyzer,
                    $stmt,
                    $codebase,
                    $context,
                    $intersection_type,
                    $lhs_type_part,
                    $lhs_var_id,
                    $intersection_return_type,
                    $returns_by_ref,
                    $has_mock,
                    $has_valid_method_call_type,
                    $has_mixed_method_call,
                    $invalid_method_call_types,
                    $all_intersection_existent_method_ids,
                    $i_non_existent_class_method_ids,
                    $i_non_existent_interface_method_ids,
                    $i_non_existent_magic_method_ids,
                    $check_visibility
                );

                if ($intersection_return_type) {
                    if (!$all_intersection_return_type || $all_intersection_return_type->isMixed()) {
                        $all_intersection_return_type = $intersection_return_type;
                    } else {
                        $all_intersection_return_type = Type::intersectUnionTypes(
                            $all_intersection_return_type,
                            $intersection_return_type
                        ) ?: Type::getMixed();
                    }
                }
            }
        }

        if (!$stmt->name instanceof PhpParser\Node\Identifier) {
            if (!$context->ignore_variable_method) {
                $codebase->analyzer->addMixedMemberName(
                    strtolower($fq_class_name) . '::',
                    $context->calling_function_id ?: $statements_analyzer->getFileName()
                );
            }

            $return_type = Type::getMixed();
            return true;
        }

        $method_name_lc = strtolower($stmt->name->name);

        $method_id = new \Psalm\Internal\MethodIdentifier($fq_class_name, $method_name_lc);
        $cased_method_id = $fq_class_name . '::' . $stmt->name->name;

        $intersection_method_id = $intersection_types
            ? '(' . $lhs_type_part . ')'  . '::' . $stmt->name->name
            : null;

        $args = $stmt->args;

        $old_node_data = null;

        if (!$codebase->methods->methodExists(
            $method_id,
            $context->calling_function_id,
            $codebase->collect_references ? new CodeLocation($source, $stmt->name) : null,
            null,
            $statements_analyzer->getFilePath()
        )
            || !MethodAnalyzer::isMethodVisible(
                $method_id,
                $context,
                $statements_analyzer->getSource()
            )
        ) {
            $interface_has_method = false;

            if ($class_storage->abstract && $class_storage->class_implements) {
                foreach ($class_storage->class_implements as $interface_fqcln_lc => $_) {
                    $interface_storage = $codebase->classlike_storage_provider->get($interface_fqcln_lc);

                    if (isset($interface_storage->methods[$method_name_lc])) {
                        $interface_has_method = true;
                        $fq_class_name = $interface_storage->name;
                        $method_id = new \Psalm\Internal\MethodIdentifier(
                            $fq_class_name,
                            $method_name_lc
                        );
                        break;
                    }
                }
            }

            if (!$interface_has_method
                && $codebase->methods->methodExists(
                    new \Psalm\Internal\MethodIdentifier($fq_class_name, '__call'),
                    $context->calling_function_id
                )
            ) {
                if (isset($class_storage->pseudo_methods[$method_name_lc])) {
                    $has_valid_method_call_type = true;
                    $existent_method_ids[] = $method_id;

                    $pseudo_method_storage = $class_storage->pseudo_methods[$method_name_lc];

                    if (self::checkFunctionArguments(
                        $statements_analyzer,
                        $args,
                        $pseudo_method_storage->params,
                        (string) $method_id,
                        $context
                    ) === false) {
                        return false;
                    }

                    if (self::checkFunctionLikeArgumentsMatch(
                        $statements_analyzer,
                        $args,
                        null,
                        $pseudo_method_storage->params,
                        $pseudo_method_storage,
                        null,
                        null,
                        new CodeLocation($source, $stmt),
                        $context
                    ) === false) {
                        return false;
                    }

                    if ($pseudo_method_storage->return_type) {
                        $return_type_candidate = clone $pseudo_method_storage->return_type;

                        $return_type_candidate = ExpressionAnalyzer::fleshOutType(
                            $codebase,
                            $return_type_candidate,
                            $fq_class_name,
                            $fq_class_name,
                            $class_storage->parent_class
                        );

                        if ($all_intersection_return_type) {
                            $return_type_candidate = Type::intersectUnionTypes(
                                $all_intersection_return_type,
                                $return_type_candidate
                            ) ?: Type::getMixed();
                        }

                        if (!$return_type) {
                            $return_type = $return_type_candidate;
                        } else {
                            $return_type = Type::combineUnionTypes($return_type_candidate, $return_type);
                        }

                        return;
                    }
                } else {
                    if (self::checkFunctionArguments(
                        $statements_analyzer,
                        $args,
                        null,
                        null,
                        $context
                    ) === false) {
                        return false;
                    }

                    if ($class_storage->sealed_methods) {
                        $non_existent_magic_method_ids[] = $method_id;
                        return true;
                    }
                }

                $has_valid_method_call_type = true;
                $existent_method_ids[] = $method_id;

                $array_values = array_map(
                    /**
                     * @return PhpParser\Node\Expr\ArrayItem
                     */
                    function (PhpParser\Node\Arg $arg) {
                        return new PhpParser\Node\Expr\ArrayItem($arg->value);
                    },
                    $args
                );

                $old_node_data = $statements_analyzer->node_data;
                $statements_analyzer->node_data = clone $statements_analyzer->node_data;

                $args = [
                    new PhpParser\Node\Arg(new PhpParser\Node\Scalar\String_($method_name_lc)),
                    new PhpParser\Node\Arg(new PhpParser\Node\Expr\Array_($array_values)),
                ];

                $method_id = new \Psalm\Internal\MethodIdentifier(
                    $fq_class_name,
                    '__call'
                );
            }
        }

        $source_source = $statements_analyzer->getSource();

        /**
         * @var \Psalm\Internal\Analyzer\ClassLikeAnalyzer|null
         */
        $classlike_source = $source_source->getSource();
        $classlike_source_fqcln = $classlike_source ? $classlike_source->getFQCLN() : null;

        if ($lhs_var_id === '$this'
            && $context->self
            && $classlike_source_fqcln
            && $fq_class_name !== $context->self
            && $codebase->methods->methodExists(
                new \Psalm\Internal\MethodIdentifier($context->self, $method_name_lc)
            )
        ) {
            $method_id = new \Psalm\Internal\MethodIdentifier($context->self, $method_name_lc);
            $cased_method_id = $context->self . '::' . $stmt->name->name;
            $fq_class_name = $context->self;
        }

        $is_interface = false;

        if ($codebase->interfaceExists($fq_class_name)) {
            $is_interface = true;
        }

        $source_method_id = $source instanceof FunctionLikeAnalyzer
            ? $source->getId()
            : null;

        if (!$codebase->methods->methodExists(
            $method_id,
            $context->calling_function_id,
            $method_id !== $source_method_id ? new CodeLocation($source, $stmt->name) : null
        )
            || ($config->use_phpdoc_method_without_magic_or_parent
                && isset($class_storage->pseudo_methods[$method_name_lc]))
        ) {
            $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

            if (($is_interface || $config->use_phpdoc_method_without_magic_or_parent)
                && isset($class_storage->pseudo_methods[$method_name_lc])
            ) {
                $has_valid_method_call_type = true;
                $existent_method_ids[] = $method_id;

                $pseudo_method_storage = $class_storage->pseudo_methods[$method_name_lc];

                if (self::checkFunctionArguments(
                    $statements_analyzer,
                    $args,
                    $pseudo_method_storage->params,
                    (string) $method_id,
                    $context
                ) === false) {
                    return false;
                }

                if (self::checkFunctionLikeArgumentsMatch(
                    $statements_analyzer,
                    $args,
                    null,
                    $pseudo_method_storage->params,
                    $pseudo_method_storage,
                    null,
                    null,
                    new CodeLocation($source, $stmt->name),
                    $context
                ) === false) {
                    return false;
                }

                if ($pseudo_method_storage->return_type) {
                    $return_type_candidate = clone $pseudo_method_storage->return_type;

                    if ($all_intersection_return_type) {
                        $return_type_candidate = Type::intersectUnionTypes(
                            $all_intersection_return_type,
                            $return_type_candidate
                        ) ?: Type::getMixed();
                    }

                    if (!$return_type) {
                        $return_type = $return_type_candidate;
                    } else {
                        $return_type = Type::combineUnionTypes($return_type_candidate, $return_type);
                    }

                    return;
                }

                $return_type = Type::getMixed();

                return;
            }

            if (self::checkFunctionArguments(
                $statements_analyzer,
                $args,
                null,
                null,
                $context
            ) === false) {
                return false;
            }

            if ($all_intersection_return_type && $all_intersection_existent_method_ids) {
                $existent_method_ids = array_merge($existent_method_ids, $all_intersection_existent_method_ids);

                if (!$return_type) {
                    $return_type = $all_intersection_return_type;
                } else {
                    $return_type = Type::combineUnionTypes($all_intersection_return_type, $return_type);
                }

                return;
            }

            if ($is_interface) {
                $non_existent_interface_method_ids[] = $intersection_method_id ?: $method_id;
            } else {
                $non_existent_class_method_ids[] = $intersection_method_id ?: $method_id;
            }

            return true;
        }

        if ($codebase->store_node_types
            && !$context->collect_initializations
            && !$context->collect_mutations
        ) {
            $codebase->analyzer->addNodeReference(
                $statements_analyzer->getFilePath(),
                $stmt->name,
                $method_id . '()'
            );
        }

        if ($context->collect_initializations && $context->calling_function_id) {
            list($calling_method_class) = explode('::', $context->calling_function_id);
            $codebase->file_reference_provider->addMethodReferenceToClassMember(
                $calling_method_class . '::__construct',
                strtolower((string) $method_id)
            );
        }

        $existent_method_ids[] = $method_id;

        if ($stmt->var instanceof PhpParser\Node\Expr\Variable
            && ($context->collect_initializations || $context->collect_mutations)
            && $stmt->var->name === 'this'
            && $source instanceof FunctionLikeAnalyzer
        ) {
            self::collectSpecialInformation($source, $stmt->name->name, $context);
        }

        $fq_class_name = $codebase->classlikes->getUnAliasedName($fq_class_name);

        $parent_source = $statements_analyzer->getSource();

        $class_template_params = self::getClassTemplateParams(
            $codebase,
            $codebase->methods->getClassLikeStorageForMethod($method_id),
            $fq_class_name,
            $method_name_lc,
            $lhs_type_part,
            $lhs_var_id
        );

        if ($lhs_var_id === '$this' && $parent_source instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer) {
            $grandparent_source = $parent_source->getSource();

            if ($grandparent_source instanceof \Psalm\Internal\Analyzer\TraitAnalyzer) {
                $fq_trait_name = $grandparent_source->getFQCLN();

                $fq_trait_name_lc = strtolower($fq_trait_name);

                $trait_storage = $codebase->classlike_storage_provider->get($fq_trait_name_lc);

                if (isset($trait_storage->methods[$method_name_lc])) {
                    $trait_method_id = new \Psalm\Internal\MethodIdentifier($trait_storage->name, $method_name_lc);

                    $class_template_params = self::getClassTemplateParams(
                        $codebase,
                        $codebase->methods->getClassLikeStorageForMethod($trait_method_id),
                        $fq_class_name,
                        $method_name_lc,
                        $lhs_type_part,
                        $lhs_var_id
                    );
                }
            }
        }

        $template_result = new \Psalm\Internal\Type\TemplateResult([], $class_template_params ?: []);

        if ($codebase->store_node_types
            && !$context->collect_initializations
            && !$context->collect_mutations
        ) {
            ArgumentMapPopulator::recordArgumentPositions(
                $statements_analyzer,
                $stmt,
                $codebase,
                (string) $method_id
            );
        }

        if (self::checkMethodArgs(
            $method_id,
            $args,
            $template_result,
            $context,
            new CodeLocation($source, $stmt->name),
            $statements_analyzer
        ) === false) {
            return false;
        }

        $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

        $call_map_id = $declaring_method_id ?: $method_id;

        $can_memoize = false;

        $return_type_candidate = null;

        if ($codebase->methods->return_type_provider->has($fq_class_name)) {
            $return_type_candidate = $codebase->methods->return_type_provider->getReturnType(
                $statements_analyzer,
                $fq_class_name,
                $stmt->name->name,
                $stmt->args,
                $context,
                new CodeLocation($statements_analyzer->getSource(), $stmt->name),
                $lhs_type_part instanceof TGenericObject ? $lhs_type_part->type_params : null
            );
        }

        if (!$return_type_candidate && $declaring_method_id && $declaring_method_id !== $method_id) {
            $declaring_fq_class_name = $declaring_method_id->fq_class_name;
            $declaring_method_name = $declaring_method_id->method_name;

            if ($codebase->methods->return_type_provider->has($declaring_fq_class_name)) {
                $return_type_candidate = $codebase->methods->return_type_provider->getReturnType(
                    $statements_analyzer,
                    $declaring_fq_class_name,
                    $declaring_method_name,
                    $stmt->args,
                    $context,
                    new CodeLocation($statements_analyzer->getSource(), $stmt->name),
                    $lhs_type_part instanceof TGenericObject ? $lhs_type_part->type_params : null,
                    $fq_class_name,
                    $stmt->name->name
                );
            }
        }

        $class_storage = $codebase->methods->getClassLikeStorageForMethod($method_id);

        if (!$return_type_candidate) {
            if (CallMap::inCallMap((string) $call_map_id)) {
                if (($template_result->generic_params || $class_storage->stubbed)
                    && isset($class_storage->methods[$method_name_lc])
                    && ($method_storage = $class_storage->methods[$method_name_lc])
                    && $method_storage->return_type
                ) {
                    $return_type_candidate = clone $method_storage->return_type;

                    if ($template_result->generic_params) {
                        $return_type_candidate->replaceTemplateTypesWithArgTypes(
                            $template_result->generic_params,
                            $codebase
                        );
                    }
                } else {
                    $callmap_callables = CallMap::getCallablesFromCallMap((string) $call_map_id);

                    if (!$callmap_callables || $callmap_callables[0]->return_type === null) {
                        throw new \UnexpectedValueException('Shouldn’t get here');
                    }

                    $return_type_candidate = $callmap_callables[0]->return_type;
                }

                if ($return_type_candidate->isFalsable()) {
                    $return_type_candidate->ignore_falsable_issues = true;
                }

                $return_type_candidate = ExpressionAnalyzer::fleshOutType(
                    $codebase,
                    $return_type_candidate,
                    $fq_class_name,
                    $static_type,
                    $class_storage->parent_class
                );

                if ($fq_class_name === 'DateTimeImmutable'
                    && !$context->inside_conditional
                    && !$context->inside_unset
                ) {
                    if (!$context->inside_assignment && !$context->inside_call) {
                        if (IssueBuffer::accepts(
                            new \Psalm\Issue\UnusedMethodCall(
                                'The call to ' . $cased_method_id . ' is not used',
                                new CodeLocation($statements_analyzer, $stmt->name),
                                (string) $method_id
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    } else {
                        /** @psalm-suppress UndefinedPropertyAssignment */
                        $stmt->pure = true;
                    }
                }
            } else {
                $name_code_location = new CodeLocation($source, $stmt->name);

                if ($check_visibility) {
                    if (MethodAnalyzer::checkMethodVisibility(
                        $method_id,
                        $context,
                        $statements_analyzer->getSource(),
                        $name_code_location,
                        $statements_analyzer->getSuppressedIssues()
                    ) === false) {
                        return false;
                    }
                }

                if (MethodAnalyzer::checkMethodNotDeprecatedOrInternal(
                    $codebase,
                    $context,
                    $method_id,
                    $name_code_location,
                    $statements_analyzer->getSuppressedIssues()
                ) === false) {
                    return false;
                }

                if (!self::checkMagicGetterOrSetterProperty(
                    $statements_analyzer,
                    $stmt,
                    $context,
                    $fq_class_name
                )) {
                    return false;
                }

                $self_fq_class_name = $fq_class_name;

                $return_type_candidate = $codebase->methods->getMethodReturnType(
                    $method_id,
                    $self_fq_class_name,
                    $statements_analyzer,
                    $args
                );

                if ($stmt_type = $statements_analyzer->node_data->getType($stmt)) {
                    $return_type_candidate = $stmt_type;
                }

                if ($return_type_candidate) {
                    $return_type_candidate = clone $return_type_candidate;

                    if ($template_result->template_types) {
                        $bindable_template_types = $return_type_candidate->getTemplateTypes();

                        foreach ($bindable_template_types as $template_type) {
                            if ($template_type->defining_class !== $fq_class_name
                                && !isset(
                                    $template_result->generic_params
                                        [$template_type->param_name]
                                        [$template_type->defining_class]
                                )
                            ) {
                                $template_result->generic_params[$template_type->param_name] = [
                                    ($template_type->defining_class) => [Type::getEmpty(), 0]
                                ];
                            }
                        }
                    }

                    if ($template_result->generic_params) {
                        $return_type_candidate->replaceTemplateTypesWithArgTypes(
                            $template_result->generic_params,
                            $codebase
                        );
                    }

                    $return_type_candidate = ExpressionAnalyzer::fleshOutType(
                        $codebase,
                        $return_type_candidate,
                        $self_fq_class_name,
                        $static_type,
                        $class_storage->parent_class
                    );

                    $return_type_candidate->sources = [
                        new Source(
                            strtolower((string) $method_id),
                            $cased_method_id,
                            new CodeLocation($source, $stmt->name)
                        )
                    ];

                    $return_type_location = $codebase->methods->getMethodReturnTypeLocation(
                        $method_id,
                        $secondary_return_type_location
                    );

                    if ($secondary_return_type_location) {
                        $return_type_location = $secondary_return_type_location;
                    }

                    // only check the type locally if it's defined externally
                    if ($return_type_location && !$config->isInProjectDirs($return_type_location->file_path)) {
                        $return_type_candidate->check(
                            $statements_analyzer,
                            new CodeLocation($source, $stmt),
                            $statements_analyzer->getSuppressedIssues(),
                            $context->phantom_classes
                        );
                    }
                } else {
                    $returns_by_ref =
                        $returns_by_ref
                            || $codebase->methods->getMethodReturnsByRef($method_id);
                }

                $method_storage = $codebase->methods->getUserMethodStorage($method_id);

                if ($method_storage) {
                    if (!$context->collect_mutations && !$context->collect_initializations) {
                        $method_pure_compatible = $method_storage->external_mutation_free
                            && $statements_analyzer->node_data->isPureCompatible($stmt->var);

                        if ($context->pure
                            && !$method_storage->mutation_free
                            && !$method_pure_compatible
                        ) {
                            if (IssueBuffer::accepts(
                                new ImpureMethodCall(
                                    'Cannot call an mutation-free method '
                                        . $cased_method_id . ' from a pure context',
                                    new CodeLocation($source, $stmt->name)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        } elseif ($context->mutation_free
                            && !$method_storage->mutation_free
                            && !$method_pure_compatible
                        ) {
                            if (IssueBuffer::accepts(
                                new ImpureMethodCall(
                                    'Cannot call an possibly-mutating method '
                                        . $cased_method_id . ' from a mutation-free context',
                                    new CodeLocation($source, $stmt->name)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        } elseif ($context->external_mutation_free
                            && !$method_storage->mutation_free
                            && $fq_class_name !== $context->self
                            && !$method_pure_compatible
                        ) {
                            if (IssueBuffer::accepts(
                                new ImpureMethodCall(
                                    'Cannot call an possibly-mutating method '
                                        . $cased_method_id . ' from a mutation-free context',
                                    new CodeLocation($source, $stmt->name)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        } elseif (($method_storage->mutation_free
                                || ($method_storage->external_mutation_free
                                    && (isset($stmt->var->external_mutation_free) || isset($stmt->var->pure))))
                            && !$context->inside_unset
                        ) {
                            if ($method_storage->mutation_free && !$method_storage->mutation_free_inferred) {
                                if ($context->inside_conditional) {
                                    /** @psalm-suppress UndefinedPropertyAssignment */
                                    $stmt->pure = true;
                                }

                                $can_memoize = true;
                            }

                            if ($codebase->find_unused_variables && !$context->inside_conditional) {
                                if (!$context->inside_assignment && !$context->inside_call) {
                                    if (IssueBuffer::accepts(
                                        new \Psalm\Issue\UnusedMethodCall(
                                            'The call to ' . $cased_method_id . ' is not used',
                                            new CodeLocation($statements_analyzer, $stmt->name),
                                            (string) $method_id
                                        ),
                                        $statements_analyzer->getSuppressedIssues()
                                    )) {
                                        // fall through
                                    }
                                } elseif (!$method_storage->mutation_free_inferred) {
                                    /** @psalm-suppress UndefinedPropertyAssignment */
                                    $stmt->pure = true;
                                }
                            }
                        }

                        if (!$config->remember_property_assignments_after_call
                            && !$method_storage->mutation_free
                            && !$method_pure_compatible
                        ) {
                            $context->removeAllObjectVars();
                        } elseif ($method_storage->this_property_mutations) {
                            foreach ($method_storage->this_property_mutations as $name => $_) {
                                $mutation_var_id = $lhs_var_id . '->' . $name;

                                $this_property_didnt_exist = $lhs_var_id === '$this'
                                    && isset($context->vars_in_scope[$mutation_var_id])
                                    && !isset($class_storage->declaring_property_ids[$name]);

                                $context->remove($mutation_var_id);

                                if ($this_property_didnt_exist) {
                                    $context->vars_in_scope[$mutation_var_id] = Type::getMixed();
                                }
                            }
                        }
                    }

                    $class_template_params = $template_result->generic_params;

                    if ($method_storage->assertions) {
                        self::applyAssertionsToContext(
                            $stmt->name,
                            ExpressionAnalyzer::getArrayVarId($stmt->var, null, $statements_analyzer),
                            $method_storage->assertions,
                            $args,
                            $class_template_params,
                            $context,
                            $statements_analyzer
                        );
                    }

                    if ($method_storage->if_true_assertions) {
                        $statements_analyzer->node_data->setIfTrueAssertions(
                            $stmt,
                            array_map(
                                function (Assertion $assertion) use (
                                    $class_template_params,
                                    $lhs_var_id
                                ) : Assertion {
                                    return $assertion->getUntemplatedCopy(
                                        $class_template_params ?: [],
                                        $lhs_var_id
                                    );
                                },
                                $method_storage->if_true_assertions
                            )
                        );
                    }

                    if ($method_storage->if_false_assertions) {
                        $statements_analyzer->node_data->setIfFalseAssertions(
                            $stmt,
                            array_map(
                                function (Assertion $assertion) use (
                                    $class_template_params,
                                    $lhs_var_id
                                ) : Assertion {
                                    return $assertion->getUntemplatedCopy(
                                        $class_template_params ?: [],
                                        $lhs_var_id
                                    );
                                },
                                $method_storage->if_false_assertions
                            )
                        );
                    }
                }
            }
        }

        if ($old_node_data) {
            $statements_analyzer->node_data = $old_node_data;
        }

        if (!$args && $lhs_var_id) {
            if ($config->memoize_method_calls || $can_memoize) {
                $method_var_id = $lhs_var_id . '->' . $method_name_lc . '()';

                if (isset($context->vars_in_scope[$method_var_id])) {
                    $return_type_candidate = clone $context->vars_in_scope[$method_var_id];

                    if ($can_memoize) {
                        /** @psalm-suppress UndefinedPropertyAssignment */
                        $stmt->pure = true;
                    }
                } elseif ($return_type_candidate) {
                    $context->vars_in_scope[$method_var_id] = $return_type_candidate;
                }
            }
        }

        if ($codebase->methods_to_rename) {
            $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

            foreach ($codebase->methods_to_rename as $original_method_id => $new_method_name) {
                if ($declaring_method_id && (strtolower((string) $declaring_method_id)) === $original_method_id) {
                    $file_manipulations = [
                        new \Psalm\FileManipulation(
                            (int) $stmt->name->getAttribute('startFilePos'),
                            (int) $stmt->name->getAttribute('endFilePos') + 1,
                            $new_method_name
                        )
                    ];

                    \Psalm\Internal\FileManipulation\FileManipulationBuffer::add(
                        $statements_analyzer->getFilePath(),
                        $file_manipulations
                    );
                }
            }
        }

        if ($config->after_method_checks) {
            $file_manipulations = [];

            $appearing_method_id = $codebase->methods->getAppearingMethodId($method_id);
            $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

            if ($appearing_method_id !== null && $declaring_method_id !== null) {
                foreach ($config->after_method_checks as $plugin_fq_class_name) {
                    $plugin_fq_class_name::afterMethodCallAnalysis(
                        $stmt,
                        (string) $method_id,
                        (string) $appearing_method_id,
                        (string) $declaring_method_id,
                        $context,
                        $source,
                        $codebase,
                        $file_manipulations,
                        $return_type_candidate
                    );
                }
            }

            if ($file_manipulations) {
                FileManipulationBuffer::add($statements_analyzer->getFilePath(), $file_manipulations);
            }
        }

        if ($return_type_candidate) {
            if ($all_intersection_return_type) {
                $return_type_candidate = Type::intersectUnionTypes(
                    $all_intersection_return_type,
                    $return_type_candidate
                ) ?: Type::getMixed();
            }

            if (!$return_type) {
                $return_type = $return_type_candidate;
            } else {
                $return_type = Type::combineUnionTypes($return_type_candidate, $return_type);
            }
        } elseif ($all_intersection_return_type) {
            if (!$return_type) {
                $return_type = $all_intersection_return_type;
            } else {
                $return_type = Type::combineUnionTypes($all_intersection_return_type, $return_type);
            }
        } elseif (strtolower($stmt->name->name) === '__tostring') {
            $return_type = Type::getString();
        } else {
            $return_type = Type::getMixed();
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

        $static_template_types = $static_class_storage->template_types;

        foreach ($template_types as $type_name => $type_map) {
            foreach ($type_map as list($type)) {
                foreach ($candidate_class_storages as $candidate_class_storage) {
                    if ($candidate_class_storage !== $static_class_storage
                        && isset($e[$candidate_class_storage->name][$type_name])
                        && !isset($class_template_params[$type_name][$candidate_class_storage->name])
                    ) {
                        $input_type_extends = $e[$candidate_class_storage->name][$type_name];

                        $output_type_extends = null;

                        foreach ($input_type_extends->getAtomicTypes() as $type_extends_atomic) {
                            if ($type_extends_atomic instanceof Type\Atomic\TTemplateParam) {
                                if ($static_class_storage->name === $type_extends_atomic->defining_class
                                    && isset($static_template_types[$type_extends_atomic->param_name])
                                ) {
                                    if (!$output_type_extends) {
                                        $output_type_extends = new Type\Union([$type_extends_atomic]);
                                    } else {
                                        $output_type_extends = Type::combineUnionTypes(
                                            new Type\Union([$type_extends_atomic]),
                                            $output_type_extends
                                        );
                                    }
                                } elseif (!$output_type_extends) {
                                    $output_type_extends = $type_extends_atomic->as;
                                } else {
                                    $output_type_extends = Type::combineUnionTypes(
                                        $type_extends_atomic->as,
                                        $output_type_extends
                                    );
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

                        $class_template_params[$type_name][$candidate_class_storage->name] = [
                            $output_type_extends
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
     * Check properties accessed with magic getters and setters.
     * If `@psalm-seal-properties` is set, they must be defined.
     * If an `@property` annotation is specified, the setter must set something with the correct
     * type.
     *
     * @param StatementsAnalyzer $statements_analyzer
     * @param PhpParser\Node\Expr\MethodCall $stmt
     * @param string $fq_class_name
     *
     * @return bool
     */
    private static function checkMagicGetterOrSetterProperty(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\MethodCall $stmt,
        Context $context,
        $fq_class_name
    ) {
        if (!$stmt->name instanceof PhpParser\Node\Identifier) {
            return true;
        }

        $method_name = strtolower($stmt->name->name);
        if (!in_array($method_name, ['__get', '__set'], true)) {
            return true;
        }

        $codebase = $statements_analyzer->getCodebase();

        $first_arg_value = $stmt->args[0]->value;
        if (!$first_arg_value instanceof PhpParser\Node\Scalar\String_) {
            return true;
        }

        $prop_name = $first_arg_value->value;
        $property_id = $fq_class_name . '::$' . $prop_name;

        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

        $codebase->properties->propertyExists(
            $property_id,
            $method_name === '__get',
            $statements_analyzer,
            $context,
            new CodeLocation($statements_analyzer->getSource(), $stmt)
        );

        switch ($method_name) {
            case '__set':
                // If `@psalm-seal-properties` is set, the property must be defined with
                // a `@property` annotation
                if ($class_storage->sealed_properties
                    && !isset($class_storage->pseudo_property_set_types['$' . $prop_name])
                    && IssueBuffer::accepts(
                        new UndefinedThisPropertyAssignment(
                            'Instance property ' . $property_id . ' is not defined',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $property_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )
                ) {
                    // fall through
                }

                // If a `@property` annotation is set, the type of the value passed to the
                // magic setter must match the annotation.
                $second_arg_type = $statements_analyzer->node_data->getType($stmt->args[1]->value);

                if (isset($class_storage->pseudo_property_set_types['$' . $prop_name]) && $second_arg_type) {
                    $pseudo_set_type = ExpressionAnalyzer::fleshOutType(
                        $codebase,
                        $class_storage->pseudo_property_set_types['$' . $prop_name],
                        $fq_class_name,
                        new Type\Atomic\TNamedObject($fq_class_name),
                        $class_storage->parent_class
                    );

                    $union_comparison_results = new \Psalm\Internal\Analyzer\TypeComparisonResult();

                    $type_match_found = TypeAnalyzer::isContainedBy(
                        $codebase,
                        $second_arg_type,
                        $pseudo_set_type,
                        $second_arg_type->ignore_nullable_issues,
                        $second_arg_type->ignore_falsable_issues,
                        $union_comparison_results
                    );

                    if ($union_comparison_results->type_coerced) {
                        if ($union_comparison_results->type_coerced_from_mixed) {
                            if (IssueBuffer::accepts(
                                new MixedPropertyTypeCoercion(
                                    $prop_name . ' expects \'' . $pseudo_set_type . '\', '
                                        . ' parent type `' . $second_arg_type . '` provided',
                                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                                    $property_id
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // keep soldiering on
                            }
                        } else {
                            if (IssueBuffer::accepts(
                                new PropertyTypeCoercion(
                                    $prop_name . ' expects \'' . $pseudo_set_type . '\', '
                                        . ' parent type `' . $second_arg_type . '` provided',
                                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                                    $property_id
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // keep soldiering on
                            }
                        }
                    }

                    if (!$type_match_found && !$union_comparison_results->type_coerced_from_mixed) {
                        if (TypeAnalyzer::canBeContainedBy(
                            $codebase,
                            $second_arg_type,
                            $pseudo_set_type
                        )) {
                            if (IssueBuffer::accepts(
                                new PossiblyInvalidPropertyAssignmentValue(
                                    $prop_name . ' with declared type \''
                                    . $pseudo_set_type
                                    . '\' cannot be assigned possibly different type \'' . $second_arg_type . '\'',
                                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                                    $property_id
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        } else {
                            if (IssueBuffer::accepts(
                                new InvalidPropertyAssignmentValue(
                                    $prop_name . ' with declared type \''
                                    . $pseudo_set_type
                                    . '\' cannot be assigned type \'' . $second_arg_type . '\'',
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
                break;

            case '__get':
                // If `@psalm-seal-properties` is set, the property must be defined with
                // a `@property` annotation
                if ($class_storage->sealed_properties
                    && !isset($class_storage->pseudo_property_get_types['$' . $prop_name])
                    && IssueBuffer::accepts(
                        new UndefinedThisPropertyFetch(
                            'Instance property ' . $property_id . ' is not defined',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $property_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )
                ) {
                    // fall through
                }

                if (isset($class_storage->pseudo_property_get_types['$' . $prop_name])) {
                    $statements_analyzer->node_data->setType(
                        $stmt,
                        clone $class_storage->pseudo_property_get_types['$' . $prop_name]
                    );
                }

                break;
        }

        return true;
    }
}
