<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\ClassLikeNameOptions;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\CodeLocation;
use Psalm\FileSource;
use Psalm\Issue\DocblockTypeContradiction;
use Psalm\Issue\RedundantIdentityWithTrue;
use Psalm\Issue\RedundantCondition;
use Psalm\Issue\RedundantConditionGivenDocblockType;
use Psalm\Issue\TypeDoesNotContainNull;
use Psalm\Issue\TypeDoesNotContainType;
use Psalm\Issue\UnevaluatedCode;
use Psalm\IssueBuffer;
use Psalm\Type;

use function substr;
use function count;
use function strtolower;
use function in_array;
use function strpos;
use function is_int;

/**
 * @internal
 */
class AssertionFinder
{
    public const ASSIGNMENT_TO_RIGHT = 1;
    public const ASSIGNMENT_TO_LEFT = -1;

    /**
     * Gets all the type assertions in a conditional
     *
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>
     */
    public static function scrapeAssertions(
        PhpParser\Node\Expr $conditional,
        ?string $this_class_name,
        FileSource $source,
        ?Codebase $codebase = null,
        bool $inside_negation = false,
        bool $cache = true,
        bool $inside_conditional = true
    ): array {
        $if_types = [];

        if ($conditional instanceof PhpParser\Node\Expr\Instanceof_) {
            return self::getInstanceofAssertions(
                $conditional,
                $codebase,
                $source,
                $this_class_name,
                $inside_negation
            );
        }

        if ($conditional instanceof PhpParser\Node\Expr\Assign) {
            $var_name = ExpressionIdentifier::getArrayVarId(
                $conditional->var,
                $this_class_name,
                $source
            );

            $candidate_if_types = $inside_conditional
                ? self::scrapeAssertions(
                    $conditional->expr,
                    $this_class_name,
                    $source,
                    $codebase,
                    $inside_negation,
                    $cache,
                    $inside_conditional
                )
                : [];

            if ($var_name) {
                if ($candidate_if_types) {
                    $if_types[$var_name] = [['>' . \json_encode($candidate_if_types[0])]];
                } else {
                    $if_types[$var_name] = [['!falsy']];
                }
            }

            return $if_types ? [$if_types] : [];
        }

        $var_name = ExpressionIdentifier::getArrayVarId(
            $conditional,
            $this_class_name,
            $source
        );

        if ($var_name) {
            $if_types[$var_name] = [['!falsy']];

            if (!$conditional instanceof PhpParser\Node\Expr\MethodCall
                && !$conditional instanceof PhpParser\Node\Expr\StaticCall
            ) {
                return [$if_types];
            }
        }

        if ($conditional instanceof PhpParser\Node\Expr\BooleanNot) {
            return [];
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical ||
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal
        ) {
            return self::scrapeEqualityAssertions(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                false,
                $cache,
                $inside_conditional
            );
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical ||
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
        ) {
            return self::scrapeInequalityAssertions(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                false,
                $cache,
                $inside_conditional
            );
        }

        //A nullsafe method call basically adds an assertion !null for the checked variable
        if ($conditional instanceof PhpParser\Node\Expr\NullsafeMethodCall) {
            $if_types = [];

            $var_name = ExpressionIdentifier::getArrayVarId(
                $conditional->var,
                $this_class_name,
                $source
            );

            if ($var_name) {
                $if_types[$var_name] = [['!null']];
            }

            //we may throw a RedundantNullsafeMethodCall here in the future if $var_name is never null

            return $if_types ? [$if_types] : [];
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Greater
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\GreaterOrEqual
        ) {
            return self::getGreaterAssertions(
                $conditional,
                $codebase,
                $source,
                $this_class_name
            );
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Smaller
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\SmallerOrEqual
        ) {
            return self::getSmallerAssertions(
                $conditional,
                $source,
                $this_class_name
            );
        }

        if ($conditional instanceof PhpParser\Node\Expr\FuncCall) {
            return self::processFunctionCall(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation
            );
        }

        if ($conditional instanceof PhpParser\Node\Expr\MethodCall
            || $conditional instanceof PhpParser\Node\Expr\StaticCall
        ) {
            $custom_assertions = self::processCustomAssertion($conditional, $this_class_name, $source);

            if ($custom_assertions) {
                return $custom_assertions;
            }

            return $if_types ? [$if_types] : [];
        }

        if ($conditional instanceof PhpParser\Node\Expr\Empty_) {
            $var_name = ExpressionIdentifier::getArrayVarId(
                $conditional->expr,
                $this_class_name,
                $source
            );

            if ($var_name) {
                if ($conditional->expr instanceof PhpParser\Node\Expr\Variable
                    && $source instanceof StatementsAnalyzer
                    && ($var_type = $source->node_data->getType($conditional->expr))
                    && !$var_type->isMixed()
                    && !$var_type->possibly_undefined
                ) {
                    $if_types[$var_name] = [['falsy']];
                } else {
                    $if_types[$var_name] = [['empty']];
                }
            }

            return $if_types ? [$if_types] : [];
        }

        if ($conditional instanceof PhpParser\Node\Expr\Isset_) {
            foreach ($conditional->vars as $isset_var) {
                $var_name = ExpressionIdentifier::getArrayVarId(
                    $isset_var,
                    $this_class_name,
                    $source
                );

                if ($var_name) {
                    if ($isset_var instanceof PhpParser\Node\Expr\Variable
                        && $source instanceof StatementsAnalyzer
                        && ($var_type = $source->node_data->getType($isset_var))
                        && !$var_type->isMixed()
                        && !$var_type->possibly_undefined
                        && !$var_type->possibly_undefined_from_try
                        && $var_name !== '$_SESSION'
                    ) {
                        $if_types[$var_name] = [['!null']];
                    } else {
                        $if_types[$var_name] = [['isset']];
                    }
                } else {
                    // look for any variables we *can* use for an isset assertion
                    $array_root = $isset_var;

                    while ($array_root instanceof PhpParser\Node\Expr\ArrayDimFetch && !$var_name) {
                        $array_root = $array_root->var;

                        $var_name = ExpressionIdentifier::getArrayVarId(
                            $array_root,
                            $this_class_name,
                            $source
                        );
                    }

                    if ($var_name) {
                        $if_types[$var_name] = [['=isset']];
                    }
                }
            }

            return $if_types ? [$if_types] : [];
        }

        return [];
    }

    /**
     * @param PhpParser\Node\Expr\BinaryOp\Identical|PhpParser\Node\Expr\BinaryOp $conditional
     *
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>
     */
    private static function scrapeEqualityAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        FileSource $source,
        ?Codebase $codebase = null,
        bool $inside_negation = false,
        bool $cache = true,
        bool $inside_conditional = true
    ): array {
        $null_position = self::hasNullVariable($conditional, $source);

        if ($null_position !== null) {
            return self::getNullEqualityAssertions(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $null_position
            );
        }

        $true_position = self::hasTrueVariable($conditional);

        if ($true_position) {
            return self::getTrueEqualityAssertions(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation,
                $cache,
                $true_position
            );
        }

        $false_position = self::hasFalseVariable($conditional);

        if ($false_position) {
            return self::getFalseEqualityAssertions(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation,
                $cache,
                $inside_conditional,
                $false_position
            );
        }

        $empty_array_position = self::hasEmptyArrayVariable($conditional);

        if ($empty_array_position !== null) {
            return self::getEmptyArrayEqualityAssertions(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $empty_array_position
            );
        }

        $gettype_position = self::hasGetTypeCheck($conditional);

        if ($gettype_position) {
            return self::getGettypeEqualityAssertions(
                $conditional,
                $this_class_name,
                $source,
                $gettype_position
            );
        }

        $get_debug_type_position = self::hasGetDebugTypeCheck($conditional);

        if ($get_debug_type_position) {
            return self::getGetdebugtypeEqualityAssertions(
                $conditional,
                $this_class_name,
                $source,
                $get_debug_type_position
            );
        }

        $min_count = null;
        $count_equality_position = self::hasNonEmptyCountEqualityCheck($conditional, $min_count);

        if ($count_equality_position) {
            $if_types = [];

            if ($count_equality_position === self::ASSIGNMENT_TO_RIGHT) {
                $count_expr = $conditional->left;
            } elseif ($count_equality_position === self::ASSIGNMENT_TO_LEFT) {
                $count_expr = $conditional->right;
            } else {
                throw new \UnexpectedValueException('$count_equality_position value');
            }

            /** @var PhpParser\Node\Expr\FuncCall $count_expr */
            $var_name = ExpressionIdentifier::getArrayVarId(
                $count_expr->args[0]->value,
                $this_class_name,
                $source
            );

            if ($var_name) {
                if ($min_count) {
                    $if_types[$var_name] = [['=has-at-least-' . $min_count]];
                } else {
                    $if_types[$var_name] = [['=non-empty-countable']];
                }
            }

            return $if_types ? [$if_types] : [];
        }

        if (!$source instanceof StatementsAnalyzer) {
            return [];
        }

        $getclass_position = self::hasGetClassCheck($conditional, $source);

        if ($getclass_position) {
            return self::getGetclassEqualityAssertions(
                $conditional,
                $this_class_name,
                $source,
                $getclass_position
            );
        }

        $typed_value_position = self::hasTypedValueComparison($conditional, $source);

        if ($typed_value_position) {
            return self::getTypedValueEqualityAssertions(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $typed_value_position
            );
        }

        $var_type = $source->node_data->getType($conditional->left);
        $other_type = $source->node_data->getType($conditional->right);

        if ($codebase
            && $var_type
            && $other_type
            && $conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
        ) {
            if (!UnionTypeComparator::canExpressionTypesBeIdentical($codebase, $var_type, $other_type)) {
                if (IssueBuffer::accepts(
                    new TypeDoesNotContainType(
                        $var_type->getId() . ' cannot be identical to ' . $other_type->getId(),
                        new CodeLocation($source, $conditional),
                        $var_type->getId() . ' ' . $other_type->getId()
                    ),
                    $source->getSuppressedIssues()
                )) {
                    // fall through
                }
            } else {
                // both side of the Identical can be asserted to the intersection of both
                $intersection_type = Type::intersectUnionTypes($var_type, $other_type, $codebase);

                if ($intersection_type !== null && $intersection_type->isSingle()) {
                    $assertion = $intersection_type->getAssertionString();

                    $if_types = [];

                    $var_name_left = ExpressionIdentifier::getArrayVarId(
                        $conditional->left,
                        $this_class_name,
                        $source
                    );

                    if ($var_name_left &&
                        (!$var_type->isSingle() || $var_type->getAssertionString() !== $assertion)) {
                        $if_types[$var_name_left] = [['~'.$assertion]];
                    }

                    $var_name_right = ExpressionIdentifier::getArrayVarId(
                        $conditional->right,
                        $this_class_name,
                        $source
                    );

                    if ($var_name_right &&
                        (!$other_type->isSingle() || $other_type->getAssertionString() !== $assertion)) {
                        $if_types[$var_name_right] = [['~'.$assertion]];
                    }

                    return $if_types ? [$if_types] : [];
                }
            }
        }

        return [];
    }

    /**
     * @param PhpParser\Node\Expr\BinaryOp\NotIdentical|PhpParser\Node\Expr\BinaryOp $conditional
     *
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>
     */
    private static function scrapeInequalityAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        FileSource $source,
        ?Codebase $codebase = null,
        bool $inside_negation = false,
        bool $cache = true,
        bool $inside_conditional = true
    ): array {
        $null_position = self::hasNullVariable($conditional, $source);

        if ($null_position !== null) {
            return self::getNullInequalityAssertions(
                $conditional,
                $source,
                $this_class_name,
                $codebase,
                $null_position
            );
        }

        $false_position = self::hasFalseVariable($conditional);

        if ($false_position) {
            return self::getFalseInequalityAssertions(
                $conditional,
                $cache,
                $this_class_name,
                $source,
                $inside_conditional,
                $codebase,
                $inside_negation,
                $false_position
            );
        }

        $true_position = self::hasTrueVariable($conditional);

        if ($true_position) {
            return self::getTrueInequalityAssertions(
                $true_position,
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation,
                $cache,
                $inside_conditional
            );
        }

        $count = null;
        $count_inequality_position = self::hasNotCountEqualityCheck($conditional, $count);

        if ($count_inequality_position) {
            $if_types = [];

            if ($count_inequality_position === self::ASSIGNMENT_TO_RIGHT) {
                $count_expr = $conditional->left;
            } elseif ($count_inequality_position === self::ASSIGNMENT_TO_LEFT) {
                $count_expr = $conditional->right;
            } else {
                throw new \UnexpectedValueException('$count_equality_position value');
            }

            /** @var PhpParser\Node\Expr\FuncCall $count_expr */
            $var_name = ExpressionIdentifier::getArrayVarId(
                $count_expr->args[0]->value,
                $this_class_name,
                $source
            );

            if ($var_name) {
                if ($count) {
                    $if_types[$var_name] = [['!has-exactly-' . $count]];
                } else {
                    $if_types[$var_name] = [['non-empty-countable']];
                }
            }

            return $if_types ? [$if_types] : [];
        }

        $empty_array_position = self::hasEmptyArrayVariable($conditional);

        if ($empty_array_position !== null) {
            return self::getEmptyInequalityAssertions(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $empty_array_position
            );
        }

        $gettype_position = self::hasGetTypeCheck($conditional);

        if ($gettype_position) {
            return self::getGettypeInequalityAssertions(
                $conditional,
                $this_class_name,
                $source,
                $gettype_position
            );
        }

        $get_debug_type_position = self::hasGetDebugTypeCheck($conditional);

        if ($get_debug_type_position) {
            return self::getGetdebugTypeInequalityAssertions(
                $conditional,
                $this_class_name,
                $source,
                $get_debug_type_position
            );
        }

        if (!$source instanceof StatementsAnalyzer) {
            return [];
        }

        $getclass_position = self::hasGetClassCheck($conditional, $source);

        if ($getclass_position) {
            return self::getGetclassInequalityAssertions(
                $conditional,
                $this_class_name,
                $source,
                $getclass_position
            );
        }

        $typed_value_position = self::hasTypedValueComparison($conditional, $source);

        if ($typed_value_position) {
            return self::getTypedValueInequalityAssertions(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $typed_value_position
            );
        }

        return [];
    }

    /**
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>
     */
    public static function processFunctionCall(
        PhpParser\Node\Expr\FuncCall $expr,
        ?string $this_class_name,
        FileSource $source,
        ?Codebase $codebase = null,
        bool $negate = false
    ): array {
        $first_var_name = isset($expr->args[0]->value)
            ? ExpressionIdentifier::getArrayVarId(
                $expr->args[0]->value,
                $this_class_name,
                $source
            )
            : null;

        $if_types = [];

        $first_var_type = isset($expr->args[0]->value)
            && $source instanceof StatementsAnalyzer
            ? $source->node_data->getType($expr->args[0]->value)
            : null;

        if (self::hasNullCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = [['null']];
            }
        } elseif ($source instanceof StatementsAnalyzer && self::hasIsACheck($expr, $source)) {
            return self::getIsaAssertions($expr, $source, $this_class_name, $first_var_name);
        } elseif (self::hasArrayCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = [['array']];
            } elseif ($first_var_type
                && $codebase
                && $source instanceof StatementsAnalyzer
            ) {
                self::processIrreconcilableFunctionCall(
                    $first_var_type,
                    Type::getArray(),
                    $expr,
                    $source,
                    $codebase,
                    $negate
                );
            }
        } elseif (self::hasBoolCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = [['bool']];
            } elseif ($first_var_type
                && $codebase
                && $source instanceof StatementsAnalyzer
            ) {
                self::processIrreconcilableFunctionCall(
                    $first_var_type,
                    Type::getBool(),
                    $expr,
                    $source,
                    $codebase,
                    $negate
                );
            }
        } elseif (self::hasStringCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = [['string']];
            } elseif ($first_var_type
                && $codebase
                && $source instanceof StatementsAnalyzer
            ) {
                self::processIrreconcilableFunctionCall(
                    $first_var_type,
                    Type::getString(),
                    $expr,
                    $source,
                    $codebase,
                    $negate
                );
            }
        } elseif (self::hasObjectCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = [['object']];
            } elseif ($first_var_type
                && $codebase
                && $source instanceof StatementsAnalyzer
            ) {
                self::processIrreconcilableFunctionCall(
                    $first_var_type,
                    Type::getObject(),
                    $expr,
                    $source,
                    $codebase,
                    $negate
                );
            }
        } elseif (self::hasNumericCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = [['numeric']];
            } elseif ($first_var_type
                && $codebase
                && $source instanceof StatementsAnalyzer
            ) {
                self::processIrreconcilableFunctionCall(
                    $first_var_type,
                    Type::getNumeric(),
                    $expr,
                    $source,
                    $codebase,
                    $negate
                );
            }
        } elseif (self::hasIntCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = [['int']];
            } elseif ($first_var_type
                && $codebase
                && $source instanceof StatementsAnalyzer
            ) {
                self::processIrreconcilableFunctionCall(
                    $first_var_type,
                    Type::getInt(),
                    $expr,
                    $source,
                    $codebase,
                    $negate
                );
            }
        } elseif (self::hasFloatCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = [['float']];
            } elseif ($first_var_type
                && $codebase
                && $source instanceof StatementsAnalyzer
            ) {
                self::processIrreconcilableFunctionCall(
                    $first_var_type,
                    Type::getFloat(),
                    $expr,
                    $source,
                    $codebase,
                    $negate
                );
            }
        } elseif (self::hasResourceCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = [['resource']];
            } elseif ($first_var_type
                && $codebase
                && $source instanceof StatementsAnalyzer
            ) {
                self::processIrreconcilableFunctionCall(
                    $first_var_type,
                    Type::getResource(),
                    $expr,
                    $source,
                    $codebase,
                    $negate
                );
            }
        } elseif (self::hasScalarCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = [['scalar']];
            } elseif ($first_var_type
                && $codebase
                && $source instanceof StatementsAnalyzer
            ) {
                self::processIrreconcilableFunctionCall(
                    $first_var_type,
                    Type::getScalar(),
                    $expr,
                    $source,
                    $codebase,
                    $negate
                );
            }
        } elseif (self::hasCallableCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = [['callable']];
            } elseif ($expr->args[0]->value instanceof PhpParser\Node\Expr\Array_
                && isset($expr->args[0]->value->items[0], $expr->args[0]->value->items[1])
                && $expr->args[0]->value->items[1]->value instanceof PhpParser\Node\Scalar\String_
            ) {
                $first_var_name_in_array_argument = ExpressionIdentifier::getArrayVarId(
                    $expr->args[0]->value->items[0]->value,
                    $this_class_name,
                    $source
                );
                if ($first_var_name_in_array_argument) {
                    $if_types[$first_var_name_in_array_argument] = [
                        ['hasmethod-' . $expr->args[0]->value->items[1]->value->value]
                    ];
                }
            }
        } elseif (self::hasIterableCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = [['iterable']];
            }
        } elseif (self::hasCountableCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = [['countable']];
            }
        } elseif ($class_exists_check_type = self::hasClassExistsCheck($expr)) {
            if ($first_var_name) {
                $class_string_type = ($class_exists_check_type === 1 ? 'loaded-' : '') . 'class-string';
                $if_types[$first_var_name] = [[$class_string_type]];
            }
        } elseif ($class_exists_check_type = self::hasTraitExistsCheck($expr)) {
            if ($first_var_name) {
                if ($class_exists_check_type === 2) {
                    $if_types[$first_var_name] = [['trait-string']];
                } else {
                    $if_types[$first_var_name] = [['=trait-string']];
                }
            }
        } elseif (self::hasInterfaceExistsCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = [['interface-string']];
            }
        } elseif (self::hasFunctionExistsCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = [['callable-string']];
            }
        } elseif ($expr->name instanceof PhpParser\Node\Name
            && strtolower($expr->name->parts[0]) === 'method_exists'
            && isset($expr->args[1])
            && $expr->args[1]->value instanceof PhpParser\Node\Scalar\String_
        ) {
            if ($first_var_name) {
                $if_types[$first_var_name] = [['hasmethod-' . $expr->args[1]->value->value]];
            }
        } elseif (self::hasInArrayCheck($expr) && $source instanceof StatementsAnalyzer) {
            return self::getInarrayAssertions($expr, $source, $first_var_name);
        } elseif (self::hasArrayKeyExistsCheck($expr)) {
            return self::getArrayKeyExistsAssertions(
                $expr,
                $first_var_type,
                $first_var_name,
                $source,
                $this_class_name
            );
        } elseif (self::hasNonEmptyCountCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = [['non-empty-countable']];
            }
        } else {
            return self::processCustomAssertion($expr, $this_class_name, $source);
        }

        return $if_types ? [$if_types] : [];
    }

    private static function processIrreconcilableFunctionCall(
        Type\Union $first_var_type,
        Type\Union $expected_type,
        PhpParser\Node\Expr $expr,
        StatementsAnalyzer $source,
        Codebase $codebase,
        bool $negate
    ) : void {
        if ($first_var_type->hasMixed()) {
            return;
        }

        if (!UnionTypeComparator::isContainedBy(
            $codebase,
            $first_var_type,
            $expected_type
        )) {
            return;
        }

        if (!$negate) {
            if ($first_var_type->from_docblock) {
                if (IssueBuffer::accepts(
                    new RedundantConditionGivenDocblockType(
                        'Docblock type ' . $first_var_type . ' always contains ' . $expected_type,
                        new CodeLocation($source, $expr),
                        $first_var_type . ' ' . $expected_type
                    ),
                    $source->getSuppressedIssues()
                )) {
                    // fall through
                }
            } else {
                if (IssueBuffer::accepts(
                    new RedundantCondition(
                        $first_var_type . ' always contains ' . $expected_type,
                        new CodeLocation($source, $expr),
                        $first_var_type . ' ' . $expected_type
                    ),
                    $source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        } else {
            if ($first_var_type->from_docblock) {
                if (IssueBuffer::accepts(
                    new DocblockTypeContradiction(
                        $first_var_type . ' does not contain ' . $expected_type,
                        new CodeLocation($source, $expr),
                        $first_var_type . ' ' . $expected_type
                    ),
                    $source->getSuppressedIssues()
                )) {
                    // fall through
                }
            } else {
                if (IssueBuffer::accepts(
                    new TypeDoesNotContainType(
                        $first_var_type . ' does not contain ' . $expected_type,
                        new CodeLocation($source, $expr),
                        $first_var_type . ' ' . $expected_type
                    ),
                    $source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        }
    }

    /**
     * @param  PhpParser\Node\Expr\FuncCall|PhpParser\Node\Expr\MethodCall|PhpParser\Node\Expr\StaticCall $expr
     *
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>
     */
    protected static function processCustomAssertion(
        PhpParser\Node\Expr $expr,
        ?string $this_class_name,
        FileSource $source
    ): array {
        if (!$source instanceof StatementsAnalyzer) {
            return [];
        }

        $if_true_assertions = $source->node_data->getIfTrueAssertions($expr);
        $if_false_assertions = $source->node_data->getIfFalseAssertions($expr);

        if ($if_true_assertions === null && $if_false_assertions === null) {
            return [];
        }

        $first_var_name = isset($expr->args[0]->value)
            ? ExpressionIdentifier::getArrayVarId(
                $expr->args[0]->value,
                $this_class_name,
                $source
            )
            : null;

        $anded_types = [];

        if ($if_true_assertions) {
            foreach ($if_true_assertions as $assertion) {
                $if_types = [];

                $assertion = clone $assertion;

                foreach ($assertion->rule as $i => $and_rules) {
                    foreach ($and_rules as $j => $rule) {
                        if (strpos($rule, 'class-constant(') === 0) {
                            $codebase = $source->getCodebase();

                            $assertion->rule[$i][$j] = \Psalm\Internal\Type\TypeExpander::expandUnion(
                                $codebase,
                                Type::parseString(substr($rule, 15, -1)),
                                null,
                                null,
                                null
                            )->getAssertionString();
                        }
                    }
                }

                if (is_int($assertion->var_id) && isset($expr->args[$assertion->var_id])) {
                    if ($assertion->var_id === 0) {
                        $var_name = $first_var_name;
                    } else {
                        $var_name = ExpressionIdentifier::getArrayVarId(
                            $expr->args[$assertion->var_id]->value,
                            $this_class_name,
                            $source
                        );
                    }

                    if ($var_name) {
                        $if_types[$var_name] = [[$assertion->rule[0][0]]];
                    }
                } elseif ($assertion->var_id === '$this' && $expr instanceof PhpParser\Node\Expr\MethodCall) {
                    $var_id = ExpressionIdentifier::getArrayVarId(
                        $expr->var,
                        $this_class_name,
                        $source
                    );

                    if ($var_id) {
                        $if_types[$var_id] = [[$assertion->rule[0][0]]];
                    }
                } elseif (\is_string($assertion->var_id)
                    && (
                        $expr instanceof PhpParser\Node\Expr\MethodCall
                        || $expr instanceof PhpParser\Node\Expr\StaticCall
                    )
                ) {
                    $var_id = $assertion->var_id;
                    if (strpos($var_id, 'self::') === 0) {
                        $var_id = $this_class_name . '::' . substr($var_id, 6);
                    }
                    $if_types[$var_id] = [[$assertion->rule[0][0]]];
                }

                if ($if_types) {
                    $anded_types[] = $if_types;
                }
            }
        }

        if ($if_false_assertions) {
            foreach ($if_false_assertions as $assertion) {
                $if_types = [];

                $assertion = clone $assertion;

                foreach ($assertion->rule as $i => $and_rules) {
                    foreach ($and_rules as $j => $rule) {
                        if (strpos($rule, 'class-constant(') === 0) {
                            $codebase = $source->getCodebase();

                            $assertion->rule[$i][$j] = \Psalm\Internal\Type\TypeExpander::expandUnion(
                                $codebase,
                                Type::parseString(substr($rule, 15, -1)),
                                null,
                                null,
                                null
                            )->getAssertionString();
                        }
                    }
                }

                if (is_int($assertion->var_id) && isset($expr->args[$assertion->var_id])) {
                    if ($assertion->var_id === 0) {
                        $var_name = $first_var_name;
                    } else {
                        $var_name = ExpressionIdentifier::getArrayVarId(
                            $expr->args[$assertion->var_id]->value,
                            $this_class_name,
                            $source
                        );
                    }

                    if ($var_name) {
                        if ('!' === $assertion->rule[0][0][0]) {
                            $if_types[$var_name] = [[substr($assertion->rule[0][0], 1)]];
                        } else {
                            $if_types[$var_name] = [['!' . $assertion->rule[0][0]]];
                        }
                    }
                } elseif ($assertion->var_id === '$this' && $expr instanceof PhpParser\Node\Expr\MethodCall) {
                    $var_id = ExpressionIdentifier::getArrayVarId(
                        $expr->var,
                        $this_class_name,
                        $source
                    );

                    if ($var_id) {
                        if ('!' === $assertion->rule[0][0][0]) {
                            $if_types[$var_id] = [[substr($assertion->rule[0][0], 1)]];
                        } else {
                            $if_types[$var_id] = [['!' . $assertion->rule[0][0]]];
                        }
                    }
                } elseif (\is_string($assertion->var_id)
                    && (
                        $expr instanceof PhpParser\Node\Expr\MethodCall
                        || $expr instanceof PhpParser\Node\Expr\StaticCall
                    )
                ) {
                    $var_id = $assertion->var_id;
                    if (strpos($var_id, 'self::') === 0) {
                        $var_id = $this_class_name . '::' . substr($var_id, 6);
                    }
                    $if_types[$var_id] = [['!' . $assertion->rule[0][0]]];
                }

                if ($if_types) {
                    $anded_types[] = $if_types;
                }
            }
        }

        return $anded_types;
    }

    /**
     * @return list<string>
     */
    protected static function getInstanceOfTypes(
        PhpParser\Node\Expr\Instanceof_ $stmt,
        ?string $this_class_name,
        FileSource $source
    ): array {
        if ($stmt->class instanceof PhpParser\Node\Name) {
            if (!in_array(strtolower($stmt->class->parts[0]), ['self', 'static', 'parent'], true)) {
                $instanceof_class = ClassLikeAnalyzer::getFQCLNFromNameObject(
                    $stmt->class,
                    $source->getAliases()
                );

                if ($source instanceof StatementsAnalyzer) {
                    $codebase = $source->getCodebase();
                    $instanceof_class = $codebase->classlikes->getUnAliasedName($instanceof_class);
                }

                return [$instanceof_class];
            } elseif ($this_class_name
                && (in_array(strtolower($stmt->class->parts[0]), ['self', 'static'], true))
            ) {
                if ($stmt->class->parts[0] === 'static') {
                    return ['=' . $this_class_name . '&static'];
                }

                return [$this_class_name];
            }
        } elseif ($source instanceof StatementsAnalyzer) {
            $stmt_class_type = $source->node_data->getType($stmt->class);

            if ($stmt_class_type) {
                $literal_class_strings = [];

                foreach ($stmt_class_type->getAtomicTypes() as $atomic_type) {
                    if ($atomic_type instanceof Type\Atomic\TLiteralClassString) {
                        $literal_class_strings[] = $atomic_type->value;
                    } elseif ($atomic_type instanceof Type\Atomic\TTemplateParamClass) {
                        $literal_class_strings[] = $atomic_type->param_name;
                    }
                }

                return $literal_class_strings;
            }
        }

        return [];
    }

    protected static function hasNullVariable(
        PhpParser\Node\Expr\BinaryOp $conditional,
        FileSource $source
    ): ?int {
        if ($conditional->right instanceof PhpParser\Node\Expr\ConstFetch
            && strtolower($conditional->right->name->parts[0]) === 'null'
        ) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\ConstFetch
            && strtolower($conditional->left->name->parts[0]) === 'null'
        ) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        if ($source instanceof StatementsAnalyzer
            && ($right_type = $source->node_data->getType($conditional->right))
            && $right_type->isNull()
        ) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        return null;
    }

    public static function hasFalseVariable(
        PhpParser\Node\Expr\BinaryOp $conditional
    ): ?int {
        if ($conditional->right instanceof PhpParser\Node\Expr\ConstFetch
            && strtolower($conditional->right->name->parts[0]) === 'false'
        ) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\ConstFetch
            && strtolower($conditional->left->name->parts[0]) === 'false'
        ) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return null;
    }

    public static function hasTrueVariable(
        PhpParser\Node\Expr\BinaryOp $conditional
    ): ?int {
        if ($conditional->right instanceof PhpParser\Node\Expr\ConstFetch
            && strtolower($conditional->right->name->parts[0]) === 'true'
        ) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\ConstFetch
            && strtolower($conditional->left->name->parts[0]) === 'true'
        ) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return null;
    }

    protected static function hasEmptyArrayVariable(
        PhpParser\Node\Expr\BinaryOp $conditional
    ): ?int {
        if ($conditional->right instanceof PhpParser\Node\Expr\Array_
            && !$conditional->right->items
        ) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\Array_
            && !$conditional->left->items
        ) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return null;
    }

    /**
     * @return  false|int
     */
    protected static function hasGetTypeCheck(
        PhpParser\Node\Expr\BinaryOp $conditional
    ) {
        if ($conditional->right instanceof PhpParser\Node\Expr\FuncCall
            && $conditional->right->name instanceof PhpParser\Node\Name
            && strtolower($conditional->right->name->parts[0]) === 'gettype'
            && $conditional->right->args
            && $conditional->left instanceof PhpParser\Node\Scalar\String_
        ) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\FuncCall
            && $conditional->left->name instanceof PhpParser\Node\Name
            && strtolower($conditional->left->name->parts[0]) === 'gettype'
            && $conditional->left->args
            && $conditional->right instanceof PhpParser\Node\Scalar\String_
        ) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return false;
    }

    /**
     * @return  false|int
     */
    protected static function hasGetDebugTypeCheck(
        PhpParser\Node\Expr\BinaryOp $conditional
    ) {
        if ($conditional->right instanceof PhpParser\Node\Expr\FuncCall
            && $conditional->right->name instanceof PhpParser\Node\Name
            && strtolower($conditional->right->name->parts[0]) === 'get_debug_type'
            && $conditional->right->args
            && ($conditional->left instanceof PhpParser\Node\Scalar\String_
                || $conditional->left instanceof PhpParser\Node\Expr\ClassConstFetch)
        ) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\FuncCall
            && $conditional->left->name instanceof PhpParser\Node\Name
            && strtolower($conditional->left->name->parts[0]) === 'get_debug_type'
            && $conditional->left->args
            && ($conditional->right instanceof PhpParser\Node\Scalar\String_
                || $conditional->right instanceof PhpParser\Node\Expr\ClassConstFetch)
        ) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return false;
    }

    /**
     * @return  false|int
     */
    protected static function hasGetClassCheck(
        PhpParser\Node\Expr\BinaryOp $conditional,
        FileSource $source
    ) {
        if (!$source instanceof StatementsAnalyzer) {
            return false;
        }

        $right_get_class = $conditional->right instanceof PhpParser\Node\Expr\FuncCall
            && $conditional->right->name instanceof PhpParser\Node\Name
            && strtolower($conditional->right->name->parts[0]) === 'get_class';

        $right_static_class = $conditional->right instanceof PhpParser\Node\Expr\ClassConstFetch
            && $conditional->right->class instanceof PhpParser\Node\Name
            && $conditional->right->class->parts === ['static']
            && $conditional->right->name instanceof PhpParser\Node\Identifier
            && strtolower($conditional->right->name->name) === 'class';

        $left_class_string = $conditional->left instanceof PhpParser\Node\Expr\ClassConstFetch
            && $conditional->left->class instanceof PhpParser\Node\Name
            && $conditional->left->name instanceof PhpParser\Node\Identifier
            && strtolower($conditional->left->name->name) === 'class';

        $left_type = $source->node_data->getType($conditional->left);

        $left_class_string_t = false;

        if ($left_type && $left_type->isSingle()) {
            foreach ($left_type->getAtomicTypes() as $type_part) {
                if ($type_part instanceof Type\Atomic\TClassString) {
                    $left_class_string_t = true;
                    break;
                }
            }
        }

        if (($right_get_class || $right_static_class) && ($left_class_string || $left_class_string_t)) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        $left_get_class = $conditional->left instanceof PhpParser\Node\Expr\FuncCall
            && $conditional->left->name instanceof PhpParser\Node\Name
            && strtolower($conditional->left->name->parts[0]) === 'get_class';

        $left_static_class = $conditional->left instanceof PhpParser\Node\Expr\ClassConstFetch
            && $conditional->left->class instanceof PhpParser\Node\Name
            && $conditional->left->class->parts === ['static']
            && $conditional->left->name instanceof PhpParser\Node\Identifier
            && strtolower($conditional->left->name->name) === 'class';

        $right_class_string = $conditional->right instanceof PhpParser\Node\Expr\ClassConstFetch
            && $conditional->right->class instanceof PhpParser\Node\Name
            && $conditional->right->name instanceof PhpParser\Node\Identifier
            && strtolower($conditional->right->name->name) === 'class';

        $right_type = $source->node_data->getType($conditional->right);

        $right_class_string_t = false;

        if ($right_type && $right_type->isSingle()) {
            foreach ($right_type->getAtomicTypes() as $type_part) {
                if ($type_part instanceof Type\Atomic\TClassString) {
                    $right_class_string_t = true;
                    break;
                }
            }
        }

        if (($left_get_class || $left_static_class) && ($right_class_string || $right_class_string_t)) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return false;
    }

    /**
     * @return  false|int
     */
    protected static function hasNonEmptyCountEqualityCheck(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?int &$min_count
    ) {
        $left_count = $conditional->left instanceof PhpParser\Node\Expr\FuncCall
            && $conditional->left->name instanceof PhpParser\Node\Name
            && strtolower($conditional->left->name->parts[0]) === 'count'
            && $conditional->left->args;

        $operator_greater_than_or_equal =
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Greater
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;

        if ($left_count
            && $conditional->right instanceof PhpParser\Node\Scalar\LNumber
            && $operator_greater_than_or_equal
            && $conditional->right->value >= (
                $conditional instanceof PhpParser\Node\Expr\BinaryOp\Greater
                ? 0
                : 1
            )
        ) {
            $min_count = $conditional->right->value +
                ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Greater ? 1 : 0);

            return self::ASSIGNMENT_TO_RIGHT;
        }

        $right_count = $conditional->right instanceof PhpParser\Node\Expr\FuncCall
            && $conditional->right->name instanceof PhpParser\Node\Name
            && strtolower($conditional->right->name->parts[0]) === 'count'
            && $conditional->right->args;

        $operator_less_than_or_equal =
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Smaller
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;

        if ($right_count
            && $conditional->left instanceof PhpParser\Node\Scalar\LNumber
            && $operator_less_than_or_equal
            && $conditional->left->value >= (
                $conditional instanceof PhpParser\Node\Expr\BinaryOp\Smaller ? 0 : 1
            )
        ) {
            $min_count = $conditional->left->value +
                ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Smaller ? 1 : 0);

            return self::ASSIGNMENT_TO_LEFT;
        }

        return false;
    }

    /**
     * @return  false|int
     */
    protected static function hasLessThanCountEqualityCheck(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?int &$max_count
    ) {
        $left_count = $conditional->left instanceof PhpParser\Node\Expr\FuncCall
            && $conditional->left->name instanceof PhpParser\Node\Name
            && strtolower($conditional->left->name->parts[0]) === 'count'
            && $conditional->left->args;

        $operator_less_than_or_equal =
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\SmallerOrEqual
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Smaller;

        if ($left_count
            && $operator_less_than_or_equal
            && $conditional->right instanceof PhpParser\Node\Scalar\LNumber
        ) {
            $max_count = $conditional->right->value -
                ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Smaller ? 1 : 0);

            return self::ASSIGNMENT_TO_RIGHT;
        }

        $right_count = $conditional->right instanceof PhpParser\Node\Expr\FuncCall
            && $conditional->right->name instanceof PhpParser\Node\Name
            && strtolower($conditional->right->name->parts[0]) === 'count'
            && $conditional->right->args;

        $operator_greater_than_or_equal =
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\GreaterOrEqual
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Greater;

        if ($right_count
            && $operator_greater_than_or_equal
            && $conditional->left instanceof PhpParser\Node\Scalar\LNumber
        ) {
            $max_count = $conditional->left->value -
                ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Greater ? 1 : 0);

            return self::ASSIGNMENT_TO_LEFT;
        }

        return false;
    }

    /**
     * @param  PhpParser\Node\Expr\BinaryOp\NotIdentical|PhpParser\Node\Expr\BinaryOp $conditional
     *
     * @return  false|int
     */
    protected static function hasNotCountEqualityCheck(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?int &$count
    ) {
        $left_count = $conditional->left instanceof PhpParser\Node\Expr\FuncCall
            && $conditional->left->name instanceof PhpParser\Node\Name
            && strtolower($conditional->left->name->parts[0]) === 'count'
            && $conditional->left->args;

        if ($left_count && $conditional->right instanceof PhpParser\Node\Scalar\LNumber) {
            $count = $conditional->right->value;

            return self::ASSIGNMENT_TO_RIGHT;
        }

        $right_count = $conditional->right instanceof PhpParser\Node\Expr\FuncCall
            && $conditional->right->name instanceof PhpParser\Node\Name
            && strtolower($conditional->right->name->parts[0]) === 'count'
            && $conditional->right->args;

        if ($right_count && $conditional->left instanceof PhpParser\Node\Scalar\LNumber) {
            $count = $conditional->left->value;

            return self::ASSIGNMENT_TO_LEFT;
        }

        return false;
    }

    /**
     * @return  false|int
     */
    protected static function hasPositiveNumberCheck(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?int &$min_count
    ) {
        $operator_greater_than_or_equal =
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Greater
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;

        if ($conditional->right instanceof PhpParser\Node\Scalar\LNumber
            && $operator_greater_than_or_equal
            && $conditional->right->value >= (
                $conditional instanceof PhpParser\Node\Expr\BinaryOp\Greater
                ? 0
                : 1
            )
        ) {
            $min_count = $conditional->right->value +
                ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Greater ? 1 : 0);

            return self::ASSIGNMENT_TO_RIGHT;
        }

        $operator_less_than_or_equal =
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Smaller
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;

        if ($conditional->left instanceof PhpParser\Node\Scalar\LNumber
            && $operator_less_than_or_equal
            && $conditional->left->value >= (
                $conditional instanceof PhpParser\Node\Expr\BinaryOp\Smaller ? 0 : 1
            )
        ) {
            $min_count = $conditional->left->value +
                ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Smaller ? 1 : 0);

            return self::ASSIGNMENT_TO_LEFT;
        }

        return false;
    }

    /**
     * @return  false|int
     */
    protected static function hasZeroCheck(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?int &$zero_count
    ) {
        $operator_greater_than_or_equal =
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Greater
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;

        if ($conditional->right instanceof PhpParser\Node\Scalar\LNumber
            && $operator_greater_than_or_equal
            && $conditional->right->value >= (
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\Greater
                ? -1
                : 0
            )
        ) {
            $zero_count = $conditional->right->value +
                ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Greater ? 1 : 0);

            return self::ASSIGNMENT_TO_RIGHT;
        }

        $operator_less_than_or_equal =
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Smaller
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;

        if ($conditional->left instanceof PhpParser\Node\Scalar\LNumber
            && $operator_less_than_or_equal
            && $conditional->left->value >= (
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\Smaller ? -1 : 0
            )
        ) {
            $zero_count = $conditional->left->value +
                ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Smaller ? 1 : 0);

            return self::ASSIGNMENT_TO_LEFT;
        }

        return false;
    }

    /**
     * @return  false|int
     */
    protected static function hasReconcilableNonEmptyCountEqualityCheck(
        PhpParser\Node\Expr\BinaryOp $conditional
    ) {
        $left_count = $conditional->left instanceof PhpParser\Node\Expr\FuncCall
            && $conditional->left->name instanceof PhpParser\Node\Name
            && strtolower($conditional->left->name->parts[0]) === 'count';

        $right_number = $conditional->right instanceof PhpParser\Node\Scalar\LNumber
            && $conditional->right->value === (
                $conditional instanceof PhpParser\Node\Expr\BinaryOp\Greater ? 0 : 1);

        $operator_greater_than_or_equal =
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Greater
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;

        if ($left_count && $right_number && $operator_greater_than_or_equal) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        $right_count = $conditional->right instanceof PhpParser\Node\Expr\FuncCall
            && $conditional->right->name instanceof PhpParser\Node\Name
            && strtolower($conditional->right->name->parts[0]) === 'count';

        $left_number = $conditional->left instanceof PhpParser\Node\Scalar\LNumber
            && $conditional->left->value === (
                $conditional instanceof PhpParser\Node\Expr\BinaryOp\Smaller ? 0 : 1);

        $operator_less_than_or_equal =
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Smaller
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;

        if ($right_count && $left_number && $operator_less_than_or_equal) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return false;
    }

    /**
     * @return  false|int
     */
    protected static function hasTypedValueComparison(
        PhpParser\Node\Expr\BinaryOp $conditional,
        FileSource $source
    ) {
        if (!$source instanceof StatementsAnalyzer) {
            return false;
        }

        if (($right_type = $source->node_data->getType($conditional->right))
            && ((!$conditional->right instanceof PhpParser\Node\Expr\Variable
                    && !$conditional->right instanceof PhpParser\Node\Expr\PropertyFetch
                    && !$conditional->right instanceof PhpParser\Node\Expr\StaticPropertyFetch)
                || $conditional->left instanceof PhpParser\Node\Expr\Variable
                || $conditional->left instanceof PhpParser\Node\Expr\PropertyFetch
                || $conditional->left instanceof PhpParser\Node\Expr\StaticPropertyFetch)
            && count($right_type->getAtomicTypes()) === 1
            && !$right_type->hasMixed()
        ) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if (($left_type = $source->node_data->getType($conditional->left))
            && !$conditional->left instanceof PhpParser\Node\Expr\Variable
            && !$conditional->left instanceof PhpParser\Node\Expr\PropertyFetch
            && !$conditional->left instanceof PhpParser\Node\Expr\StaticPropertyFetch
            && count($left_type->getAtomicTypes()) === 1
            && !$left_type->hasMixed()
        ) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return false;
    }

    protected static function hasNullCheck(PhpParser\Node\Expr\FuncCall $stmt): bool
    {
        if ($stmt->name instanceof PhpParser\Node\Name && strtolower($stmt->name->parts[0]) === 'is_null') {
            return true;
        }

        return false;
    }

    protected static function hasIsACheck(
        PhpParser\Node\Expr\FuncCall $stmt,
        StatementsAnalyzer $source
    ): bool {
        if ($stmt->name instanceof PhpParser\Node\Name
            && (strtolower($stmt->name->parts[0]) === 'is_a'
                || strtolower($stmt->name->parts[0]) === 'is_subclass_of')
            && isset($stmt->args[1])
        ) {
            $second_arg = $stmt->args[1]->value;

            if ($second_arg instanceof PhpParser\Node\Scalar\String_
                || (
                    $second_arg instanceof PhpParser\Node\Expr\ClassConstFetch
                    && $second_arg->class instanceof PhpParser\Node\Name
                    && $second_arg->name instanceof PhpParser\Node\Identifier
                    && strtolower($second_arg->name->name) === 'class'
                )
                || (($second_arg_type = $source->node_data->getType($second_arg))
                    && $second_arg_type->hasString())
            ) {
                return true;
            }
        }

        return false;
    }

    protected static function hasArrayCheck(PhpParser\Node\Expr\FuncCall $stmt): bool
    {
        if ($stmt->name instanceof PhpParser\Node\Name && strtolower($stmt->name->parts[0]) === 'is_array') {
            return true;
        }

        return false;
    }

    protected static function hasStringCheck(PhpParser\Node\Expr\FuncCall $stmt): bool
    {
        if ($stmt->name instanceof PhpParser\Node\Name && strtolower($stmt->name->parts[0]) === 'is_string') {
            return true;
        }

        return false;
    }

    protected static function hasBoolCheck(PhpParser\Node\Expr\FuncCall $stmt): bool
    {
        if ($stmt->name instanceof PhpParser\Node\Name && strtolower($stmt->name->parts[0]) === 'is_bool') {
            return true;
        }

        return false;
    }

    protected static function hasObjectCheck(PhpParser\Node\Expr\FuncCall $stmt): bool
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_object']) {
            return true;
        }

        return false;
    }

    protected static function hasNumericCheck(PhpParser\Node\Expr\FuncCall $stmt): bool
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_numeric']) {
            return true;
        }

        return false;
    }

    protected static function hasIterableCheck(PhpParser\Node\Expr\FuncCall $stmt): bool
    {
        if ($stmt->name instanceof PhpParser\Node\Name && strtolower($stmt->name->parts[0]) === 'is_iterable') {
            return true;
        }

        return false;
    }

    protected static function hasCountableCheck(PhpParser\Node\Expr\FuncCall $stmt): bool
    {
        if ($stmt->name instanceof PhpParser\Node\Name && strtolower($stmt->name->parts[0]) === 'is_countable') {
            return true;
        }

        return false;
    }

    /**
     * @return  0|1|2
     */
    protected static function hasClassExistsCheck(PhpParser\Node\Expr\FuncCall $stmt): int
    {
        if ($stmt->name instanceof PhpParser\Node\Name
            && strtolower($stmt->name->parts[0]) === 'class_exists'
        ) {
            if (!isset($stmt->args[1])) {
                return 2;
            }

            $second_arg = $stmt->args[1]->value;

            if ($second_arg instanceof PhpParser\Node\Expr\ConstFetch
                && strtolower($second_arg->name->parts[0]) === 'true'
            ) {
                return 2;
            }

            return 1;
        }

        return 0;
    }

    /**
     * @return  0|1|2
     */
    protected static function hasTraitExistsCheck(PhpParser\Node\Expr\FuncCall $stmt): int
    {
        if ($stmt->name instanceof PhpParser\Node\Name
            && strtolower($stmt->name->parts[0]) === 'trait_exists'
        ) {
            if (!isset($stmt->args[1])) {
                return 2;
            }

            $second_arg = $stmt->args[1]->value;

            if ($second_arg instanceof PhpParser\Node\Expr\ConstFetch
                && strtolower($second_arg->name->parts[0]) === 'true'
            ) {
                return 2;
            }

            return 1;
        }

        return 0;
    }

    protected static function hasInterfaceExistsCheck(PhpParser\Node\Expr\FuncCall $stmt): bool
    {
        if ($stmt->name instanceof PhpParser\Node\Name
            && strtolower($stmt->name->parts[0]) === 'interface_exists'
        ) {
            return true;
        }

        return false;
    }

    protected static function hasFunctionExistsCheck(PhpParser\Node\Expr\FuncCall $stmt): bool
    {
        if ($stmt->name instanceof PhpParser\Node\Name && strtolower($stmt->name->parts[0]) === 'function_exists') {
            return true;
        }

        return false;
    }

    protected static function hasIntCheck(PhpParser\Node\Expr\FuncCall $stmt): bool
    {
        if ($stmt->name instanceof PhpParser\Node\Name &&
            ($stmt->name->parts === ['is_int'] ||
                $stmt->name->parts === ['is_integer'] ||
                $stmt->name->parts === ['is_long'])
        ) {
            return true;
        }

        return false;
    }

    protected static function hasFloatCheck(PhpParser\Node\Expr\FuncCall $stmt): bool
    {
        if ($stmt->name instanceof PhpParser\Node\Name &&
            ($stmt->name->parts === ['is_float'] ||
                $stmt->name->parts === ['is_real'] ||
                $stmt->name->parts === ['is_double'])
        ) {
            return true;
        }

        return false;
    }

    protected static function hasResourceCheck(PhpParser\Node\Expr\FuncCall $stmt): bool
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_resource']) {
            return true;
        }

        return false;
    }

    protected static function hasScalarCheck(PhpParser\Node\Expr\FuncCall $stmt): bool
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_scalar']) {
            return true;
        }

        return false;
    }

    protected static function hasCallableCheck(PhpParser\Node\Expr\FuncCall $stmt): bool
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['is_callable']) {
            return true;
        }

        return false;
    }

    protected static function hasInArrayCheck(PhpParser\Node\Expr\FuncCall $stmt): bool
    {
        if ($stmt->name instanceof PhpParser\Node\Name
            && $stmt->name->parts === ['in_array']
            && isset($stmt->args[2])
        ) {
            $second_arg = $stmt->args[2]->value;

            if ($second_arg instanceof PhpParser\Node\Expr\ConstFetch
                && strtolower($second_arg->name->parts[0]) === 'true'
            ) {
                return true;
            }
        }

        return false;
    }

    protected static function hasNonEmptyCountCheck(PhpParser\Node\Expr\FuncCall $stmt): bool
    {
        if ($stmt->name instanceof PhpParser\Node\Name
            && $stmt->name->parts === ['count']
        ) {
            return true;
        }

        return false;
    }

    protected static function hasArrayKeyExistsCheck(PhpParser\Node\Expr\FuncCall $stmt): bool
    {
        if ($stmt->name instanceof PhpParser\Node\Name && $stmt->name->parts === ['array_key_exists']) {
            return true;
        }

        return false;
    }

    /**
     * @param int $null_position
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>
     */
    private static function getNullInequalityAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        FileSource $source,
        ?string $this_class_name,
        ?Codebase $codebase,
        int $null_position
    ): array {
        $if_types = [];

        if ($null_position === self::ASSIGNMENT_TO_RIGHT) {
            $base_conditional = $conditional->left;
        } elseif ($null_position === self::ASSIGNMENT_TO_LEFT) {
            $base_conditional = $conditional->right;
        } else {
            throw new \UnexpectedValueException('Bad null variable position');
        }

        $var_name = ExpressionIdentifier::getArrayVarId(
            $base_conditional,
            $this_class_name,
            $source
        );

        if ($var_name) {
            if ($base_conditional instanceof PhpParser\Node\Expr\Assign) {
                $var_name = '=' . $var_name;
            }

            if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical) {
                $if_types[$var_name] = [['!null']];
            } else {
                $if_types[$var_name] = [['!falsy']];
            }
        }

        if ($codebase
            && $source instanceof StatementsAnalyzer
            && ($var_type = $source->node_data->getType($base_conditional))
        ) {
            if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical) {
                $null_type = Type::getNull();

                if (!UnionTypeComparator::isContainedBy(
                    $codebase,
                    $var_type,
                    $null_type
                ) && !UnionTypeComparator::isContainedBy(
                    $codebase,
                    $null_type,
                    $var_type
                )) {
                    if ($var_type->from_docblock) {
                        if (IssueBuffer::accepts(
                            new RedundantConditionGivenDocblockType(
                                'Docblock-defined type ' . $var_type . ' can never contain null',
                                new CodeLocation($source, $conditional),
                                $var_type->getId() . ' null'
                            ),
                            $source->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    } else {
                        if (IssueBuffer::accepts(
                            new RedundantCondition(
                                $var_type . ' can never contain null',
                                new CodeLocation($source, $conditional),
                                $var_type->getId() . ' null'
                            ),
                            $source->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }
                }
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param int $false_position
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>
     */
    private static function getFalseInequalityAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        bool $cache,
        ?string $this_class_name,
        FileSource $source,
        bool $inside_conditional,
        ?Codebase $codebase,
        bool $inside_negation,
        int $false_position
    ) {
        $if_types = [];

        if ($false_position === self::ASSIGNMENT_TO_RIGHT) {
            $base_conditional = $conditional->left;
        } elseif ($false_position === self::ASSIGNMENT_TO_LEFT) {
            $base_conditional = $conditional->right;
        } else {
            throw new \UnexpectedValueException('Bad false variable position');
        }

        $var_name = ExpressionIdentifier::getArrayVarId(
            $base_conditional,
            $this_class_name,
            $source
        );

        if ($var_name) {
            if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical) {
                $if_types[$var_name] = [['!false']];
            } else {
                $if_types[$var_name] = [['!falsy']];
            }

            $if_types = [$if_types];
        } else {
            $if_types = null;

            if ($source instanceof StatementsAnalyzer && $cache) {
                $if_types = $source->node_data->getAssertions($base_conditional);
            }

            if ($if_types === null) {
                $if_types = self::scrapeAssertions(
                    $base_conditional,
                    $this_class_name,
                    $source,
                    $codebase,
                    $inside_negation,
                    $cache,
                    $inside_conditional
                );

                if ($source instanceof StatementsAnalyzer && $cache) {
                    $source->node_data->setAssertions($base_conditional, $if_types);
                }
            }
        }

        if ($codebase
            && $source instanceof StatementsAnalyzer
            && ($var_type = $source->node_data->getType($base_conditional))
        ) {
            if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical) {
                $false_type = Type::getFalse();

                if (!UnionTypeComparator::isContainedBy(
                    $codebase,
                    $var_type,
                    $false_type
                ) && !UnionTypeComparator::isContainedBy(
                    $codebase,
                    $false_type,
                    $var_type
                )) {
                    if ($var_type->from_docblock) {
                        if (IssueBuffer::accepts(
                            new RedundantConditionGivenDocblockType(
                                'Docblock-defined type ' . $var_type . ' can never contain false',
                                new CodeLocation($source, $conditional),
                                $var_type->getId() . ' false'
                            ),
                            $source->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    } else {
                        if (IssueBuffer::accepts(
                            new RedundantCondition(
                                $var_type . ' can never contain false',
                                new CodeLocation($source, $conditional),
                                $var_type->getId() . ' false'
                            ),
                            $source->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }
                }
            }
        }

        return $if_types;
    }

    /**
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>
     */
    private static function getTrueInequalityAssertions(
        int $true_position,
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        FileSource $source,
        ?Codebase $codebase,
        bool $inside_negation,
        bool $cache,
        bool $inside_conditional
    ): array {
        $if_types = [];

        if ($true_position === self::ASSIGNMENT_TO_RIGHT) {
            $base_conditional = $conditional->left;
        } elseif ($true_position === self::ASSIGNMENT_TO_LEFT) {
            $base_conditional = $conditional->right;
        } else {
            throw new \UnexpectedValueException('Bad null variable position');
        }

        if ($base_conditional instanceof PhpParser\Node\Expr\FuncCall) {
            $notif_types = self::processFunctionCall(
                $base_conditional,
                $this_class_name,
                $source,
                $codebase,
                !$inside_negation
            );
        } else {
            $var_name = ExpressionIdentifier::getArrayVarId(
                $base_conditional,
                $this_class_name,
                $source
            );

            if ($var_name) {
                if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical) {
                    $if_types[$var_name] = [['!true']];
                } else {
                    $if_types[$var_name] = [['falsy']];
                }

                $notif_types = [];
            } else {
                $notif_types = null;

                if ($source instanceof StatementsAnalyzer && $cache) {
                    $notif_types = $source->node_data->getAssertions($base_conditional);
                }

                if ($notif_types === null) {
                    $notif_types = self::scrapeAssertions(
                        $base_conditional,
                        $this_class_name,
                        $source,
                        $codebase,
                        $inside_negation,
                        $cache,
                        $inside_conditional
                    );

                    if ($source instanceof StatementsAnalyzer && $cache) {
                        $source->node_data->setAssertions($base_conditional, $notif_types);
                    }
                }
            }
        }

        if (count($notif_types) === 1) {
            $notif_types = $notif_types[0];

            if (count($notif_types) === 1) {
                $if_types = \Psalm\Internal\Algebra::negateTypes($notif_types);
            }
        }

        $if_types = $if_types ? [$if_types] : [];

        if ($codebase
            && $source instanceof StatementsAnalyzer
            && ($var_type = $source->node_data->getType($base_conditional))
        ) {
            if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical) {
                $true_type = Type::getTrue();

                if (!UnionTypeComparator::isContainedBy(
                    $codebase,
                    $var_type,
                    $true_type
                ) && !UnionTypeComparator::isContainedBy(
                    $codebase,
                    $true_type,
                    $var_type
                )) {
                    if ($var_type->from_docblock) {
                        if (IssueBuffer::accepts(
                            new RedundantConditionGivenDocblockType(
                                'Docblock-defined type ' . $var_type . ' can never contain true',
                                new CodeLocation($source, $conditional),
                                $var_type->getId() . ' true'
                            ),
                            $source->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    } else {
                        if (IssueBuffer::accepts(
                            new RedundantCondition(
                                $var_type . ' can never contain ' . $true_type,
                                new CodeLocation($source, $conditional),
                                $var_type->getId() . ' true'
                            ),
                            $source->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }
                }
            }
        }

        return $if_types;
    }

    /**
     * @param int $empty_array_position
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>
     */
    private static function getEmptyInequalityAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        FileSource $source,
        ?Codebase $codebase,
        int $empty_array_position
    ): array {
        $if_types = [];

        if ($empty_array_position === self::ASSIGNMENT_TO_RIGHT) {
            $base_conditional = $conditional->left;
        } elseif ($empty_array_position === self::ASSIGNMENT_TO_LEFT) {
            $base_conditional = $conditional->right;
        } else {
            throw new \UnexpectedValueException('Bad empty array variable position');
        }

        $var_name = ExpressionIdentifier::getArrayVarId(
            $base_conditional,
            $this_class_name,
            $source
        );

        if ($var_name) {
            if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical) {
                $if_types[$var_name] = [['non-empty-countable']];
            } else {
                $if_types[$var_name] = [['!falsy']];
            }
        }

        if ($codebase
            && $source instanceof StatementsAnalyzer
            && ($var_type = $source->node_data->getType($base_conditional))
        ) {
            if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical) {
                $empty_array_type = Type::getEmptyArray();

                if (!UnionTypeComparator::isContainedBy(
                    $codebase,
                    $var_type,
                    $empty_array_type
                ) && !UnionTypeComparator::isContainedBy(
                    $codebase,
                    $empty_array_type,
                    $var_type
                )) {
                    if ($var_type->from_docblock) {
                        if (IssueBuffer::accepts(
                            new RedundantConditionGivenDocblockType(
                                'Docblock-defined type ' . $var_type->getId() . ' can never contain null',
                                new CodeLocation($source, $conditional),
                                $var_type->getId() . ' null'
                            ),
                            $source->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    } else {
                        if (IssueBuffer::accepts(
                            new RedundantCondition(
                                $var_type->getId() . ' can never contain null',
                                new CodeLocation($source, $conditional),
                                $var_type->getId() . ' null'
                            ),
                            $source->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }
                }
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param int $gettype_position
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>
     */
    private static function getGettypeInequalityAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        FileSource $source,
        int $gettype_position
    ): array {
        $if_types = [];

        if ($gettype_position === self::ASSIGNMENT_TO_RIGHT) {
            $whichclass_expr = $conditional->left;
            $gettype_expr = $conditional->right;
        } elseif ($gettype_position === self::ASSIGNMENT_TO_LEFT) {
            $whichclass_expr = $conditional->right;
            $gettype_expr = $conditional->left;
        } else {
            throw new \UnexpectedValueException('$gettype_position value');
        }

        /** @var PhpParser\Node\Expr\FuncCall $gettype_expr */
        $var_name = ExpressionIdentifier::getArrayVarId(
            $gettype_expr->args[0]->value,
            $this_class_name,
            $source
        );

        if ($whichclass_expr instanceof PhpParser\Node\Scalar\String_) {
            $var_type = $whichclass_expr->value;
        } elseif ($whichclass_expr instanceof PhpParser\Node\Expr\ClassConstFetch
            && $whichclass_expr->class instanceof PhpParser\Node\Name
        ) {
            $var_type = ClassLikeAnalyzer::getFQCLNFromNameObject(
                $whichclass_expr->class,
                $source->getAliases()
            );
        } else {
            throw new \UnexpectedValueException('Shouldn’t get here');
        }

        if (!isset(ClassLikeAnalyzer::GETTYPE_TYPES[$var_type])) {
            if (IssueBuffer::accepts(
                new UnevaluatedCode(
                    'gettype cannot return this value',
                    new CodeLocation($source, $whichclass_expr)
                )
            )) {
                // fall through
            }
        } else {
            if ($var_name && $var_type) {
                $if_types[$var_name] = [['!' . $var_type]];
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param int $get_debug_type_position
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>
     */
    private static function getGetdebugTypeInequalityAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        FileSource $source,
        int $get_debug_type_position
    ): array {
        $if_types = [];

        if ($get_debug_type_position === self::ASSIGNMENT_TO_RIGHT) {
            $whichclass_expr = $conditional->left;
            $get_debug_type_expr = $conditional->right;
        } elseif ($get_debug_type_position === self::ASSIGNMENT_TO_LEFT) {
            $whichclass_expr = $conditional->right;
            $get_debug_type_expr = $conditional->left;
        } else {
            throw new \UnexpectedValueException('$gettype_position value');
        }

        /** @var PhpParser\Node\Expr\FuncCall $get_debug_type_expr */
        $var_name = ExpressionIdentifier::getArrayVarId(
            $get_debug_type_expr->args[0]->value,
            $this_class_name,
            $source
        );

        if ($whichclass_expr instanceof PhpParser\Node\Scalar\String_) {
            $var_type = $whichclass_expr->value;
        } elseif ($whichclass_expr instanceof PhpParser\Node\Expr\ClassConstFetch
            && $whichclass_expr->class instanceof PhpParser\Node\Name
        ) {
            $var_type = ClassLikeAnalyzer::getFQCLNFromNameObject(
                $whichclass_expr->class,
                $source->getAliases()
            );
        } else {
            throw new \UnexpectedValueException('Shouldn’t get here');
        }

        if ($var_name && $var_type) {
            if ($var_type === 'class@anonymous') {
                $if_types[$var_name] = [['!=object']];
            } elseif ($var_type === 'resource (closed)') {
                $if_types[$var_name] = [['!closed-resource']];
            } elseif (substr($var_type, 0, 10) === 'resource (') {
                $if_types[$var_name] = [['!=resource']];
            } else {
                $if_types[$var_name] = [['!' . $var_type]];
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param StatementsAnalyzer $source
     * @param int $getclass_position
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>
     */
    private static function getGetclassInequalityAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        StatementsAnalyzer $source,
        int $getclass_position
    ): array {
        $if_types = [];

        if ($getclass_position === self::ASSIGNMENT_TO_RIGHT) {
            $whichclass_expr = $conditional->left;
            $getclass_expr = $conditional->right;
        } elseif ($getclass_position === self::ASSIGNMENT_TO_LEFT) {
            $whichclass_expr = $conditional->right;
            $getclass_expr = $conditional->left;
        } else {
            throw new \UnexpectedValueException('$getclass_position value');
        }

        if ($getclass_expr instanceof PhpParser\Node\Expr\FuncCall) {
            $var_name = ExpressionIdentifier::getArrayVarId(
                $getclass_expr->args[0]->value,
                $this_class_name,
                $source
            );
        } else {
            $var_name = '$this';
        }

        if ($whichclass_expr instanceof PhpParser\Node\Scalar\String_) {
            $var_type = $whichclass_expr->value;
        } elseif ($whichclass_expr instanceof PhpParser\Node\Expr\ClassConstFetch
            && $whichclass_expr->class instanceof PhpParser\Node\Name
        ) {
            $var_type = ClassLikeAnalyzer::getFQCLNFromNameObject(
                $whichclass_expr->class,
                $source->getAliases()
            );

            if ($var_type === 'self' || $var_type === 'static') {
                $var_type = $this_class_name;
            } elseif ($var_type === 'parent') {
                $var_type = null;
            }
        } else {
            $type = $source->node_data->getType($whichclass_expr);

            if ($type && $var_name) {
                foreach ($type->getAtomicTypes() as $type_part) {
                    if ($type_part instanceof Type\Atomic\TTemplateParamClass) {
                        $if_types[$var_name] = [['!=' . $type_part->param_name]];
                    }
                }
            }

            return $if_types ? [$if_types] : [];
        }

        if ($var_type
            && ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                $source,
                $var_type,
                new CodeLocation($source, $whichclass_expr),
                null,
                null,
                $source->getSuppressedIssues()
            ) === false
        ) {
            // fall through
        } else {
            if ($var_name && $var_type) {
                $if_types[$var_name] = [['!=getclass-' . $var_type]];
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param StatementsAnalyzer $source
     * @param int $typed_value_position
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>
     */
    private static function getTypedValueInequalityAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        StatementsAnalyzer $source,
        ?Codebase $codebase,
        int $typed_value_position
    ): array {
        $if_types = [];

        if ($typed_value_position === self::ASSIGNMENT_TO_RIGHT) {
            $var_name = ExpressionIdentifier::getArrayVarId(
                $conditional->left,
                $this_class_name,
                $source
            );

            $other_type = $source->node_data->getType($conditional->left);
            $var_type = $source->node_data->getType($conditional->right);
        } elseif ($typed_value_position === self::ASSIGNMENT_TO_LEFT) {
            $var_name = ExpressionIdentifier::getArrayVarId(
                $conditional->right,
                $this_class_name,
                $source
            );

            $var_type = $source->node_data->getType($conditional->left);
            $other_type = $source->node_data->getType($conditional->right);
        } else {
            throw new \UnexpectedValueException('$typed_value_position value');
        }

        if ($var_type) {
            if ($var_name) {
                $not_identical = $conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical
                    || ($other_type
                        && (($var_type->isString() && $other_type->isString())
                            || ($var_type->isInt() && $other_type->isInt())
                            || ($var_type->isFloat() && $other_type->isFloat())
                        )
                    );

                if ($not_identical) {
                    $if_types[$var_name] = [['!=' . $var_type->getAssertionString()]];
                } else {
                    $if_types[$var_name] = [['!~' . $var_type->getAssertionString()]];
                }
            }

            if ($codebase
                && $other_type
                && $conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical
            ) {
                $parent_source = $source->getSource();

                if ($parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer
                    && (($var_type->isSingleStringLiteral()
                            && $var_type->getSingleStringLiteral()->value === $this_class_name)
                        || ($other_type->isSingleStringLiteral()
                            && $other_type->getSingleStringLiteral()->value === $this_class_name))
                ) {
                    // do nothing
                } elseif (!UnionTypeComparator::canExpressionTypesBeIdentical(
                    $codebase,
                    $other_type,
                    $var_type
                )) {
                    if ($var_type->from_docblock || $other_type->from_docblock) {
                        if (IssueBuffer::accepts(
                            new DocblockTypeContradiction(
                                $var_type . ' can never contain ' . $other_type->getId(),
                                new CodeLocation($source, $conditional),
                                $var_type . ' ' . $other_type
                            ),
                            $source->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    } else {
                        if (IssueBuffer::accepts(
                            new RedundantCondition(
                                $var_type->getId() . ' can never contain ' . $other_type->getId(),
                                new CodeLocation($source, $conditional),
                                $var_type->getId() . ' ' . $other_type->getId()
                            ),
                            $source->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }
                }
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param int $null_position
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>
     */
    private static function getNullEqualityAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        FileSource $source,
        ?Codebase $codebase,
        int $null_position
    ): array {
        $if_types = [];

        if ($null_position === self::ASSIGNMENT_TO_RIGHT) {
            $base_conditional = $conditional->left;
        } elseif ($null_position === self::ASSIGNMENT_TO_LEFT) {
            $base_conditional = $conditional->right;
        } else {
            throw new \UnexpectedValueException('$null_position value');
        }

        $var_name = ExpressionIdentifier::getArrayVarId(
            $base_conditional,
            $this_class_name,
            $source
        );

        if ($var_name && $base_conditional instanceof PhpParser\Node\Expr\Assign) {
            $var_name = '=' . $var_name;
        }

        if ($var_name) {
            if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                $if_types[$var_name] = [['null']];
            } else {
                $if_types[$var_name] = [['falsy']];
            }
        }

        if ($codebase
            && $source instanceof StatementsAnalyzer
            && ($var_type = $source->node_data->getType($base_conditional))
            && $conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
        ) {
            $null_type = Type::getNull();

            if (!UnionTypeComparator::isContainedBy(
                $codebase,
                $var_type,
                $null_type
            ) && !UnionTypeComparator::isContainedBy(
                $codebase,
                $null_type,
                $var_type
            )) {
                if ($var_type->from_docblock) {
                    if (IssueBuffer::accepts(
                        new DocblockTypeContradiction(
                            $var_type . ' does not contain null',
                            new CodeLocation($source, $conditional),
                            $var_type . ' null'
                        ),
                        $source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new TypeDoesNotContainNull(
                            $var_type . ' does not contain null',
                            new CodeLocation($source, $conditional),
                            $var_type->getId()
                        ),
                        $source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>
     */
    private static function getTrueEqualityAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        FileSource $source,
        ?Codebase $codebase,
        bool $inside_negation,
        bool $cache,
        int $true_position
    ) {
        $if_types = [];

        if ($true_position === self::ASSIGNMENT_TO_RIGHT) {
            $base_conditional = $conditional->left;
        } elseif ($true_position === self::ASSIGNMENT_TO_LEFT) {
            $base_conditional = $conditional->right;
        } else {
            throw new \UnexpectedValueException('Unrecognised position');
        }

        if ($base_conditional instanceof PhpParser\Node\Expr\FuncCall) {
            $if_types = self::processFunctionCall(
                $base_conditional,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation
            );
        } else {
            $var_name = ExpressionIdentifier::getArrayVarId(
                $base_conditional,
                $this_class_name,
                $source
            );

            if ($var_name) {
                if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                    $if_types[$var_name] = [['true']];
                } else {
                    $if_types[$var_name] = [['!falsy']];
                }

                $if_types = [$if_types];
            } else {
                $base_assertions = null;

                if ($source instanceof StatementsAnalyzer && $cache) {
                    $base_assertions = $source->node_data->getAssertions($base_conditional);
                }

                if ($base_assertions === null) {
                    $base_assertions = self::scrapeAssertions(
                        $base_conditional,
                        $this_class_name,
                        $source,
                        $codebase,
                        $inside_negation,
                        $cache
                    );

                    if ($source instanceof StatementsAnalyzer && $cache) {
                        $source->node_data->setAssertions($base_conditional, $base_assertions);
                    }
                }

                $if_types = $base_assertions;
            }
        }

        if ($codebase
            && $source instanceof StatementsAnalyzer
            && ($var_type = $source->node_data->getType($base_conditional))
            && $conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
        ) {
            $config = $source->getCodebase()->config;

            if ($config->strict_binary_operands
                && $var_type->isSingle()
                && $var_type->hasBool()
                && !$var_type->from_docblock
            ) {
                if (IssueBuffer::accepts(
                    new RedundantIdentityWithTrue(
                        'The "=== true" part of this comparison is redundant',
                        new CodeLocation($source, $conditional)
                    ),
                    $source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            $true_type = Type::getTrue();

            if (!UnionTypeComparator::canExpressionTypesBeIdentical(
                $codebase,
                $true_type,
                $var_type
            )) {
                if ($var_type->from_docblock) {
                    if (IssueBuffer::accepts(
                        new DocblockTypeContradiction(
                            $var_type . ' does not contain true',
                            new CodeLocation($source, $conditional),
                            $var_type . ' true'
                        ),
                        $source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new TypeDoesNotContainType(
                            $var_type . ' does not contain true',
                            new CodeLocation($source, $conditional),
                            $var_type . ' true'
                        ),
                        $source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }
        }

        return $if_types;
    }

    /**
     * @param int $false_position
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>
     */
    private static function getFalseEqualityAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        FileSource $source,
        ?Codebase $codebase,
        bool $inside_negation,
        bool $cache,
        bool $inside_conditional,
        int $false_position
    ): array {
        $if_types = [];

        if ($false_position === self::ASSIGNMENT_TO_RIGHT) {
            $base_conditional = $conditional->left;
        } elseif ($false_position === self::ASSIGNMENT_TO_LEFT) {
            $base_conditional = $conditional->right;
        } else {
            throw new \UnexpectedValueException('$false_position value');
        }

        if ($base_conditional instanceof PhpParser\Node\Expr\FuncCall) {
            $notif_types = self::processFunctionCall(
                $base_conditional,
                $this_class_name,
                $source,
                $codebase,
                !$inside_negation
            );
        } else {
            $var_name = ExpressionIdentifier::getArrayVarId(
                $base_conditional,
                $this_class_name,
                $source
            );

            if ($var_name) {
                if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                    $if_types[$var_name] = [['false']];
                } else {
                    $if_types[$var_name] = [['falsy']];
                }

                $notif_types = [];
            } else {
                $notif_types = null;

                if ($source instanceof StatementsAnalyzer && $cache) {
                    $notif_types = $source->node_data->getAssertions($base_conditional);
                }

                if ($notif_types === null) {
                    $notif_types = self::scrapeAssertions(
                        $base_conditional,
                        $this_class_name,
                        $source,
                        $codebase,
                        $inside_negation,
                        $cache,
                        $inside_conditional
                    );

                    if ($source instanceof StatementsAnalyzer && $cache) {
                        $source->node_data->setAssertions($base_conditional, $notif_types);
                    }
                }
            }
        }

        if (count($notif_types) === 1) {
            $notif_types = $notif_types[0];

            if (count($notif_types) === 1) {
                $if_types = \Psalm\Internal\Algebra::negateTypes($notif_types);
            }
        }

        $if_types = $if_types ? [$if_types] : [];

        if ($codebase
            && $source instanceof StatementsAnalyzer
            && ($var_type = $source->node_data->getType($base_conditional))
        ) {
            if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                $false_type = Type::getFalse();

                if (!UnionTypeComparator::canExpressionTypesBeIdentical(
                    $codebase,
                    $false_type,
                    $var_type
                )) {
                    if ($var_type->from_docblock) {
                        if (IssueBuffer::accepts(
                            new DocblockTypeContradiction(
                                $var_type . ' does not contain false',
                                new CodeLocation($source, $conditional),
                                $var_type . ' false'
                            ),
                            $source->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    } else {
                        if (IssueBuffer::accepts(
                            new TypeDoesNotContainType(
                                $var_type . ' does not contain false',
                                new CodeLocation($source, $conditional),
                                $var_type . ' false'
                            ),
                            $source->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }
                }
            }
        }

        return $if_types;
    }

    /**
     * @param int $empty_array_position
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>
     */
    private static function getEmptyArrayEqualityAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        FileSource $source,
        ?Codebase $codebase,
        int $empty_array_position
    ): array {
        $if_types = [];

        if ($empty_array_position === self::ASSIGNMENT_TO_RIGHT) {
            $base_conditional = $conditional->left;
        } elseif ($empty_array_position === self::ASSIGNMENT_TO_LEFT) {
            $base_conditional = $conditional->right;
        } else {
            throw new \UnexpectedValueException('$empty_array_position value');
        }

        $var_name = ExpressionIdentifier::getArrayVarId(
            $base_conditional,
            $this_class_name,
            $source
        );

        if ($var_name) {
            if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                $if_types[$var_name] = [['!non-empty-countable']];
            } else {
                $if_types[$var_name] = [['falsy']];
            }
        }

        if ($codebase
            && $source instanceof StatementsAnalyzer
            && ($var_type = $source->node_data->getType($base_conditional))
            && $conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
        ) {
            $empty_array_type = Type::getEmptyArray();

            if (!UnionTypeComparator::canExpressionTypesBeIdentical(
                $codebase,
                $empty_array_type,
                $var_type
            )) {
                if ($var_type->from_docblock) {
                    if (IssueBuffer::accepts(
                        new DocblockTypeContradiction(
                            $var_type . ' does not contain an empty array',
                            new CodeLocation($source, $conditional),
                            null
                        ),
                        $source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new TypeDoesNotContainType(
                            $var_type . ' does not contain empty array',
                            new CodeLocation($source, $conditional),
                            null
                        ),
                        $source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param int $gettype_position
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>
     */
    private static function getGettypeEqualityAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        FileSource $source,
        int $gettype_position
    ): array {
        $if_types = [];

        if ($gettype_position === self::ASSIGNMENT_TO_RIGHT) {
            $string_expr = $conditional->left;
            $gettype_expr = $conditional->right;
        } elseif ($gettype_position === self::ASSIGNMENT_TO_LEFT) {
            $string_expr = $conditional->right;
            $gettype_expr = $conditional->left;
        } else {
            throw new \UnexpectedValueException('$gettype_position value');
        }

        /** @var PhpParser\Node\Expr\FuncCall $gettype_expr */
        $var_name = ExpressionIdentifier::getArrayVarId(
            $gettype_expr->args[0]->value,
            $this_class_name,
            $source
        );

        /** @var PhpParser\Node\Scalar\String_ $string_expr */
        $var_type = $string_expr->value;

        if (!isset(ClassLikeAnalyzer::GETTYPE_TYPES[$var_type])) {
            if (IssueBuffer::accepts(
                new UnevaluatedCode(
                    'gettype cannot return this value',
                    new CodeLocation($source, $string_expr)
                )
            )) {
                // fall through
            }
        } else {
            if ($var_name && $var_type) {
                $if_types[$var_name] = [[$var_type]];
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param int $get_debug_type_position
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>
     */
    private static function getGetdebugtypeEqualityAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        FileSource $source,
        int $get_debug_type_position
    ): array {
        $if_types = [];

        if ($get_debug_type_position === self::ASSIGNMENT_TO_RIGHT) {
            $whichclass_expr = $conditional->left;
            $get_debug_type_expr = $conditional->right;
        } elseif ($get_debug_type_position === self::ASSIGNMENT_TO_LEFT) {
            $whichclass_expr = $conditional->right;
            $get_debug_type_expr = $conditional->left;
        } else {
            throw new \UnexpectedValueException('$gettype_position value');
        }

        /** @var PhpParser\Node\Expr\FuncCall $get_debug_type_expr */
        $var_name = ExpressionIdentifier::getArrayVarId(
            $get_debug_type_expr->args[0]->value,
            $this_class_name,
            $source
        );

        if ($whichclass_expr instanceof PhpParser\Node\Scalar\String_) {
            $var_type = $whichclass_expr->value;
        } elseif ($whichclass_expr instanceof PhpParser\Node\Expr\ClassConstFetch
            && $whichclass_expr->class instanceof PhpParser\Node\Name
        ) {
            $var_type = ClassLikeAnalyzer::getFQCLNFromNameObject(
                $whichclass_expr->class,
                $source->getAliases()
            );
        } else {
            throw new \UnexpectedValueException('Shouldn’t get here');
        }

        if ($var_name && $var_type) {
            if ($var_type === 'class@anonymous') {
                $if_types[$var_name] = [['=object']];
            } elseif ($var_type === 'resource (closed)') {
                $if_types[$var_name] = [['closed-resource']];
            } elseif (substr($var_type, 0, 10) === 'resource (') {
                $if_types[$var_name] = [['=resource']];
            } else {
                $if_types[$var_name] = [[$var_type]];
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param StatementsAnalyzer $source
     * @param int $getclass_position
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>
     */
    private static function getGetclassEqualityAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        StatementsAnalyzer $source,
        int $getclass_position
    ): array {
        $if_types = [];

        if ($getclass_position === self::ASSIGNMENT_TO_RIGHT) {
            $whichclass_expr = $conditional->left;
            $getclass_expr = $conditional->right;
        } elseif ($getclass_position === self::ASSIGNMENT_TO_LEFT) {
            $whichclass_expr = $conditional->right;
            $getclass_expr = $conditional->left;
        } else {
            throw new \UnexpectedValueException('$getclass_position value');
        }

        if ($getclass_expr instanceof PhpParser\Node\Expr\FuncCall && isset($getclass_expr->args[0])) {
            $var_name = ExpressionIdentifier::getArrayVarId(
                $getclass_expr->args[0]->value,
                $this_class_name,
                $source
            );
        } else {
            $var_name = '$this';
        }

        if ($whichclass_expr instanceof PhpParser\Node\Expr\ClassConstFetch
            && $whichclass_expr->class instanceof PhpParser\Node\Name
        ) {
            $var_type = ClassLikeAnalyzer::getFQCLNFromNameObject(
                $whichclass_expr->class,
                $source->getAliases()
            );

            if ($var_type === 'self' || $var_type === 'static') {
                $var_type = $this_class_name;
            } elseif ($var_type === 'parent') {
                $var_type = null;
            }

            if ($var_type) {
                if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                    $source,
                    $var_type,
                    new CodeLocation($source, $whichclass_expr),
                    null,
                    null,
                    $source->getSuppressedIssues(),
                    new ClassLikeNameOptions(true)
                ) === false
                ) {
                    return [];
                }
            }

            if ($var_name && $var_type) {
                $if_types[$var_name] = [['=getclass-' . $var_type]];
            }
        } else {
            $type = $source->node_data->getType($whichclass_expr);

            if ($type && $var_name) {
                foreach ($type->getAtomicTypes() as $type_part) {
                    if ($type_part instanceof Type\Atomic\TTemplateParamClass) {
                        $if_types[$var_name] = [['=' . $type_part->param_name]];
                    }
                }
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param StatementsAnalyzer $source
     * @param int $typed_value_position
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>
     */
    private static function getTypedValueEqualityAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        StatementsAnalyzer $source,
        ?Codebase $codebase,
        int $typed_value_position
    ): array {
        $if_types = [];

        if ($typed_value_position === self::ASSIGNMENT_TO_RIGHT) {
            $var_name = ExpressionIdentifier::getArrayVarId(
                $conditional->left,
                $this_class_name,
                $source
            );

            $other_type = $source->node_data->getType($conditional->left);
            $var_type = $source->node_data->getType($conditional->right);
        } elseif ($typed_value_position === self::ASSIGNMENT_TO_LEFT) {
            $var_name = ExpressionIdentifier::getArrayVarId(
                $conditional->right,
                $this_class_name,
                $source
            );

            $var_type = $source->node_data->getType($conditional->left);
            $other_type = $source->node_data->getType($conditional->right);
        } else {
            throw new \UnexpectedValueException('$typed_value_position value');
        }

        if ($var_name && $var_type) {
            $identical = $conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
                || ($other_type
                    && (($var_type->isString(true) && $other_type->isString(true))
                        || ($var_type->isInt(true) && $other_type->isInt(true))
                        || ($var_type->isFloat() && $other_type->isFloat())
                    )
                );

            if ($identical) {
                $if_types[$var_name] = [['=' . $var_type->getAssertionString(true)]];
            } else {
                $if_types[$var_name] = [['~' . $var_type->getAssertionString()]];
            }
        }

        if ($codebase
            && $other_type
            && $var_type
            && ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
                || ($other_type->isString()
                    && $var_type->isString())
            )
        ) {
            $parent_source = $source->getSource();

            if ($parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer
                && (($var_type->isSingleStringLiteral()
                        && $var_type->getSingleStringLiteral()->value === $this_class_name)
                    || ($other_type->isSingleStringLiteral()
                        && $other_type->getSingleStringLiteral()->value === $this_class_name))
            ) {
                // do nothing
            } elseif (!UnionTypeComparator::canExpressionTypesBeIdentical(
                $codebase,
                $other_type,
                $var_type
            )) {
                if ($var_type->from_docblock || $other_type->from_docblock) {
                    if (IssueBuffer::accepts(
                        new DocblockTypeContradiction(
                            $var_type->getId() . ' does not contain ' . $other_type->getId(),
                            new CodeLocation($source, $conditional),
                            $var_type->getId() . ' ' . $other_type->getId()
                        ),
                        $source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new TypeDoesNotContainType(
                            $var_type->getId() . ' cannot be identical to ' . $other_type->getId(),
                            new CodeLocation($source, $conditional),
                            $var_type->getId() . ' ' . $other_type->getId()
                        ),
                        $source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param PhpParser\Node\Expr\FuncCall $expr
     * @param StatementsAnalyzer $source
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>
     */
    private static function getIsaAssertions(
        PhpParser\Node\Expr\FuncCall $expr,
        StatementsAnalyzer $source,
        ?string $this_class_name,
        ?string $first_var_name
    ): array {
        $if_types = [];

        if ($expr->args[0]->value instanceof PhpParser\Node\Expr\ClassConstFetch
            && $expr->args[0]->value->name instanceof PhpParser\Node\Identifier
            && strtolower($expr->args[0]->value->name->name) === 'class'
            && $expr->args[0]->value->class instanceof PhpParser\Node\Name
            && count($expr->args[0]->value->class->parts) === 1
            && strtolower($expr->args[0]->value->class->parts[0]) === 'static'
        ) {
            $first_var_name = '$this';
        }

        if ($first_var_name) {
            $first_arg = $expr->args[0]->value;
            $second_arg = $expr->args[1]->value;
            $third_arg = isset($expr->args[2]->value) ? $expr->args[2]->value : null;

            if ($third_arg instanceof PhpParser\Node\Expr\ConstFetch) {
                if (!in_array(strtolower($third_arg->name->parts[0]), ['true', 'false'])) {
                    return [];
                }

                $third_arg_value = strtolower($third_arg->name->parts[0]);
            } else {
                $third_arg_value = $expr->name instanceof PhpParser\Node\Name
                && strtolower($expr->name->parts[0]) === 'is_subclass_of'
                    ? 'true'
                    : 'false';
            }

            $is_a_prefix = $third_arg_value === 'true' ? 'isa-string-' : 'isa-';

            if (($first_arg_type = $source->node_data->getType($first_arg))
                && $first_arg_type->isSingleStringLiteral()
                && $source->getSource()->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer
                && $first_arg_type->getSingleStringLiteral()->value === $this_class_name
            ) {
                // do nothing
            } else {
                if ($second_arg instanceof PhpParser\Node\Scalar\String_) {
                    $fq_class_name = $second_arg->value;
                    if ($fq_class_name[0] === '\\') {
                        $fq_class_name = substr($fq_class_name, 1);
                    }

                    $if_types[$first_var_name] = [[$is_a_prefix . $fq_class_name]];
                } elseif ($second_arg instanceof PhpParser\Node\Expr\ClassConstFetch
                    && $second_arg->class instanceof PhpParser\Node\Name
                    && $second_arg->name instanceof PhpParser\Node\Identifier
                    && strtolower($second_arg->name->name) === 'class'
                ) {
                    $class_node = $second_arg->class;

                    if ($class_node->parts === ['static']) {
                        if ($this_class_name) {
                            $if_types[$first_var_name] = [[$is_a_prefix . $this_class_name . '&static']];
                        }
                    } elseif ($class_node->parts === ['self']) {
                        if ($this_class_name) {
                            $if_types[$first_var_name] = [[$is_a_prefix . $this_class_name]];
                        }
                    } elseif ($class_node->parts === ['parent']) {
                        // do nothing
                    } else {
                        $if_types[$first_var_name] = [[
                            $is_a_prefix
                            . ClassLikeAnalyzer::getFQCLNFromNameObject(
                                $class_node,
                                $source->getAliases()
                            )
                        ]];
                    }
                } elseif (($second_arg_type = $source->node_data->getType($second_arg))
                    && $second_arg_type->hasString()
                ) {
                    $vals = [];

                    foreach ($second_arg_type->getAtomicTypes() as $second_arg_atomic_type) {
                        if ($second_arg_atomic_type instanceof Type\Atomic\TTemplateParamClass) {
                            $vals[] = [$is_a_prefix . $second_arg_atomic_type->param_name];
                        }
                    }

                    if ($vals) {
                        $if_types[$first_var_name] = $vals;
                    }
                }
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param PhpParser\Node\Expr\FuncCall $expr
     * @param StatementsAnalyzer $source
     * @param string|null $first_var_name
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>
     */
    private static function getInarrayAssertions(
        PhpParser\Node\Expr\FuncCall $expr,
        StatementsAnalyzer $source,
        ?string $first_var_name
    ): array {
        $if_types = [];

        if ($first_var_name
            && ($second_arg_type = $source->node_data->getType($expr->args[1]->value))
            && isset($expr->args[0]->value)
            && !$expr->args[0]->value instanceof PhpParser\Node\Expr\ClassConstFetch
        ) {
            foreach ($second_arg_type->getAtomicTypes() as $atomic_type) {
                if ($atomic_type instanceof Type\Atomic\TArray
                    || $atomic_type instanceof Type\Atomic\TKeyedArray
                    || $atomic_type instanceof Type\Atomic\TList
                ) {
                    if ($atomic_type instanceof Type\Atomic\TList) {
                        $value_type = $atomic_type->type_param;
                    } elseif ($atomic_type instanceof Type\Atomic\TKeyedArray) {
                        $value_type = $atomic_type->getGenericValueType();
                    } else {
                        $value_type = $atomic_type->type_params[1];
                    }

                    $array_literal_types = \array_filter(
                        $value_type->getAtomicTypes(),
                        function ($type) {
                            return $type instanceof Type\Atomic\TLiteralInt
                                || $type instanceof Type\Atomic\TLiteralString
                                || $type instanceof Type\Atomic\TLiteralFloat
                                || $type instanceof Type\Atomic\TEnumCase;
                        }
                    );

                    if ($array_literal_types
                        && count($value_type->getAtomicTypes())
                    ) {
                        $literal_assertions = [];

                        foreach ($array_literal_types as $array_literal_type) {
                            $literal_assertions[] = '=' . $array_literal_type->getAssertionString();
                        }

                        if ($value_type->isFalsable()) {
                            $literal_assertions[] = 'false';
                        }

                        if ($value_type->isNullable()) {
                            $literal_assertions[] = 'null';
                        }

                        $if_types[$first_var_name] = [$literal_assertions];
                    }
                }
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param PhpParser\Node\Expr\FuncCall $expr
     * @param Type\Union|null $first_var_type
     * @param string|null $first_var_name
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>
     */
    private static function getArrayKeyExistsAssertions(
        PhpParser\Node\Expr\FuncCall $expr,
        ?Type\Union $first_var_type,
        ?string $first_var_name,
        FileSource $source,
        ?string $this_class_name
    ): array {
        $if_types = [];

        $literal_assertions = [];

        if (isset($expr->args[0])
            && isset($expr->args[1])
            && $first_var_type
            && $first_var_name
            && !$expr->args[0]->value instanceof PhpParser\Node\Expr\ClassConstFetch
            && $source instanceof StatementsAnalyzer
            && ($second_var_type = $source->node_data->getType($expr->args[1]->value))
        ) {
            foreach ($second_var_type->getAtomicTypes() as $atomic_type) {
                if ($atomic_type instanceof Type\Atomic\TArray
                    || $atomic_type instanceof Type\Atomic\TKeyedArray
                ) {
                    if ($atomic_type instanceof Type\Atomic\TKeyedArray) {
                        $key_possibly_undefined = false;

                        foreach ($atomic_type->properties as $property_type) {
                            if ($property_type->possibly_undefined) {
                                $key_possibly_undefined = true;
                                break;
                            }
                        }

                        $key_type = $atomic_type->getGenericKeyType();

                        if ($key_possibly_undefined) {
                            $key_type->possibly_undefined = true;
                        }
                    } else {
                        $key_type = $atomic_type->type_params[0];
                    }

                    if ($key_type->allStringLiterals() && !$key_type->possibly_undefined) {
                        foreach ($key_type->getLiteralStrings() as $array_literal_type) {
                            $literal_assertions[] = '=' . $array_literal_type->getAssertionString();
                        }
                    } elseif ($key_type->allIntLiterals() && !$key_type->possibly_undefined) {
                        foreach ($key_type->getLiteralInts() as $array_literal_type) {
                            $literal_assertions[] = '~' . $array_literal_type->getAssertionString();
                        }
                    }
                }
            }
        }

        if ($literal_assertions && $first_var_name) {
            $if_types[$first_var_name] = [$literal_assertions];
        } else {
            $array_root = isset($expr->args[1]->value)
                ? ExpressionIdentifier::getArrayVarId(
                    $expr->args[1]->value,
                    $this_class_name,
                    $source
                )
                : null;

            if ($array_root) {
                if ($first_var_name === null && isset($expr->args[0])) {
                    $first_arg = $expr->args[0];

                    if ($first_arg->value instanceof PhpParser\Node\Scalar\String_) {
                        $first_var_name = '\'' . $first_arg->value->value . '\'';
                    } elseif ($first_arg->value instanceof PhpParser\Node\Scalar\LNumber) {
                        $first_var_name = (string)$first_arg->value->value;
                    }
                }

                if ($expr->args[0]->value instanceof PhpParser\Node\Expr\ClassConstFetch
                    && $expr->args[0]->value->name instanceof PhpParser\Node\Identifier
                    && $expr->args[0]->value->name->name !== 'class'
                ) {
                    $const_type = null;

                    if ($source instanceof StatementsAnalyzer) {
                        $const_type = $source->node_data->getType($expr->args[0]->value);
                    }

                    if ($const_type) {
                        if ($const_type->isSingleStringLiteral()) {
                            $first_var_name = $const_type->getSingleStringLiteral()->value;
                        } elseif ($const_type->isSingleIntLiteral()) {
                            $first_var_name = (string)$const_type->getSingleIntLiteral()->value;
                        } else {
                            $first_var_name = null;
                        }
                    } else {
                        $first_var_name = null;
                    }
                } elseif ($expr->args[0]->value instanceof PhpParser\Node\Expr\Variable
                    && $source instanceof StatementsAnalyzer
                    && ($first_var_type = $source->node_data->getType($expr->args[0]->value))
                ) {
                    foreach ($first_var_type->getLiteralStrings() as $array_literal_type) {
                        $if_types[$array_root . "['" . $array_literal_type->value . "']"] = [['array-key-exists']];
                    }
                    foreach ($first_var_type->getLiteralInts() as $array_literal_type) {
                        $if_types[$array_root . "[" . $array_literal_type->value . "]"] = [['array-key-exists']];
                    }
                }

                if ($first_var_name !== null
                    && !strpos($first_var_name, '->')
                    && !strpos($first_var_name, '[')
                ) {
                    $if_types[$array_root . '[' . $first_var_name . ']'] = [['array-key-exists']];
                }
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param PhpParser\Node\Expr\BinaryOp\Greater|PhpParser\Node\Expr\BinaryOp\GreaterOrEqual $conditional
     *
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>
     */
    private static function getGreaterAssertions(
        PhpParser\Node\Expr $conditional,
        ?Codebase $codebase,
        FileSource $source,
        ?string $this_class_name
    ): array {
        $if_types = [];

        $min_count = null;
        $count_equality_position = self::hasNonEmptyCountEqualityCheck($conditional, $min_count);
        $min_comparison = null;
        $positive_number_position = self::hasPositiveNumberCheck($conditional, $min_comparison);
        $zero_comparison = null;
        $zero_position = self::hasZeroCheck($conditional, $zero_comparison);
        $max_count = null;
        $count_inequality_position = self::hasLessThanCountEqualityCheck($conditional, $max_count);

        if ($count_equality_position) {
            if ($count_equality_position === self::ASSIGNMENT_TO_RIGHT) {
                $counted_expr = $conditional->left;
            } else {
                throw new \UnexpectedValueException('$count_equality_position value');
            }

            /** @var PhpParser\Node\Expr\FuncCall $counted_expr */
            $var_name = ExpressionIdentifier::getArrayVarId(
                $counted_expr->args[0]->value,
                $this_class_name,
                $source
            );

            if ($var_name) {
                if (self::hasReconcilableNonEmptyCountEqualityCheck($conditional)) {
                    $if_types[$var_name] = [['non-empty-countable']];
                } else {
                    if ($min_count) {
                        $if_types[$var_name] = [['=has-at-least-' . $min_count]];
                    } else {
                        $if_types[$var_name] = [['=non-empty-countable']];
                    }
                }
            }

            return $if_types ? [$if_types] : [];
        }

        if ($count_inequality_position) {
            if ($count_inequality_position === self::ASSIGNMENT_TO_LEFT) {
                $count_expr = $conditional->right;
            } else {
                throw new \UnexpectedValueException('$count_inequality_position value');
            }

            /** @var PhpParser\Node\Expr\FuncCall $count_expr */
            $var_name = ExpressionIdentifier::getArrayVarId(
                $count_expr->args[0]->value,
                $this_class_name,
                $source
            );

            if ($var_name) {
                if ($max_count) {
                    $if_types[$var_name] = [['!has-at-least-' . ($max_count + 1)]];
                } else {
                    $if_types[$var_name] = [['!non-empty-countable']];
                }
            }

            return $if_types ? [$if_types] : [];
        }

        if ($positive_number_position) {
            if ($positive_number_position === self::ASSIGNMENT_TO_RIGHT) {
                $var_name = ExpressionIdentifier::getArrayVarId(
                    $conditional->left,
                    $this_class_name,
                    $source
                );
                $value_node = $conditional->left;
            } else {
                $var_name = ExpressionIdentifier::getArrayVarId(
                    $conditional->right,
                    $this_class_name,
                    $source
                );
                $value_node = $conditional->right;
            }

            if ($codebase
                && $source instanceof StatementsAnalyzer
                && ($var_type = $source->node_data->getType($value_node))
                && $var_type->isSingle()
                && $var_type->hasBool()
                && $min_comparison > 1
            ) {
                if ($var_type->from_docblock) {
                    if (IssueBuffer::accepts(
                        new DocblockTypeContradiction(
                            $var_type . ' cannot be greater than ' . $min_comparison,
                            new CodeLocation($source, $conditional),
                            null
                        ),
                        $source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new TypeDoesNotContainType(
                            $var_type . ' cannot be greater than ' . $min_comparison,
                            new CodeLocation($source, $conditional),
                            null
                        ),
                        $source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }

            if ($var_name) {
                $if_types[$var_name] = [[($min_comparison === 1 ? '' : '=') . 'positive-numeric']];
            }

            return $if_types ? [$if_types] : [];
        }

        if ($zero_position) {
            if ($zero_position === self::ASSIGNMENT_TO_RIGHT) {
                $var_name = ExpressionIdentifier::getArrayVarId(
                    $conditional->left,
                    $this_class_name,
                    $source
                );
                $value_node = $conditional->left;
            } else {
                $var_name = ExpressionIdentifier::getArrayVarId(
                    $conditional->right,
                    $this_class_name,
                    $source
                );
                $value_node = $conditional->right;
            }


            if ($codebase
                && $source instanceof StatementsAnalyzer
                && ($var_type = $source->node_data->getType($value_node))
                && $var_type->isSingle()
                && $var_type->hasBool()
                && $zero_comparison > 1
            ) {
                if ($var_type->from_docblock) {
                    if (IssueBuffer::accepts(
                        new DocblockTypeContradiction(
                            $var_type . ' cannot be greater than ' . $zero_comparison,
                            new CodeLocation($source, $conditional),
                            null
                        ),
                        $source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new TypeDoesNotContainType(
                            $var_type . ' cannot be greater than ' . $zero_comparison,
                            new CodeLocation($source, $conditional),
                            null
                        ),
                        $source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }

            if ($var_name) {
                $if_types[$var_name] = [[($zero_comparison === 1 ? '' : '=') . 'positive-numeric', '=int(0)']];
            }

            return $if_types ? [$if_types] : [];
        }

        return [];
    }

    /**
     * @param PhpParser\Node\Expr\BinaryOp\Smaller|PhpParser\Node\Expr\BinaryOp\SmallerOrEqual $conditional
     *
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>
     */
    private static function getSmallerAssertions(
        PhpParser\Node\Expr $conditional,
        FileSource $source,
        ?string $this_class_name
    ): array {
        $if_types = [];
        $min_count = null;
        $count_equality_position = self::hasNonEmptyCountEqualityCheck($conditional, $min_count);
        $typed_value_position = self::hasTypedValueComparison($conditional, $source);

        $max_count = null;
        $count_inequality_position = self::hasLessThanCountEqualityCheck($conditional, $max_count);

        if ($count_equality_position) {
            if ($count_equality_position === self::ASSIGNMENT_TO_LEFT) {
                $count_expr = $conditional->right;
            } else {
                throw new \UnexpectedValueException('$count_equality_position value');
            }

            /** @var PhpParser\Node\Expr\FuncCall $count_expr */
            $var_name = ExpressionIdentifier::getArrayVarId(
                $count_expr->args[0]->value,
                $this_class_name,
                $source
            );

            if ($var_name) {
                if ($min_count) {
                    $if_types[$var_name] = [['=has-at-least-' . $min_count]];
                } else {
                    $if_types[$var_name] = [['=non-empty-countable']];
                }
            }

            return $if_types ? [$if_types] : [];
        }

        if ($count_inequality_position) {
            if ($count_inequality_position === self::ASSIGNMENT_TO_RIGHT) {
                $count_expr = $conditional->left;
            } else {
                throw new \UnexpectedValueException('$count_inequality_position value');
            }

            /** @var PhpParser\Node\Expr\FuncCall $count_expr */
            $var_name = ExpressionIdentifier::getArrayVarId(
                $count_expr->args[0]->value,
                $this_class_name,
                $source
            );

            if ($var_name) {
                if ($max_count) {
                    $if_types[$var_name] = [['!has-at-least-' . ($max_count + 1)]];
                } else {
                    $if_types[$var_name] = [['!non-empty-countable']];
                }
            }

            return $if_types ? [$if_types] : [];
        }

        if ($typed_value_position) {
            if ($typed_value_position === self::ASSIGNMENT_TO_RIGHT) {
                $var_name = ExpressionIdentifier::getArrayVarId(
                    $conditional->left,
                    $this_class_name,
                    $source
                );

                $expr = $conditional->right;
            } elseif ($typed_value_position === self::ASSIGNMENT_TO_LEFT) {
                $var_name = ExpressionIdentifier::getArrayVarId(
                    $conditional->right,
                    $this_class_name,
                    $source
                );

                $expr = $conditional->left;
            } else {
                throw new \UnexpectedValueException('$typed_value_position value');
            }

            $expr_type = $source instanceof StatementsAnalyzer
                ? $source->node_data->getType($expr)
                : null;

            if ($var_name
                && $expr_type
                && $expr_type->isSingleIntLiteral()
                && ($expr_type->getSingleIntLiteral()->value === 0)
            ) {
                $if_types[$var_name] = [['=isset']];
            }

            return $if_types ? [$if_types] : [];
        }

        return [];
    }

    /**
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<string>>>>
     */
    private static function getInstanceofAssertions(
        PhpParser\Node\Expr\Instanceof_ $conditional,
        ?Codebase $codebase,
        FileSource $source,
        ?string $this_class_name,
        bool $inside_negation
    ): array {
        $if_types = [];

        $instanceof_types = self::getInstanceOfTypes($conditional, $this_class_name, $source);

        if ($instanceof_types) {
            $var_name = ExpressionIdentifier::getArrayVarId(
                $conditional->expr,
                $this_class_name,
                $source
            );

            if ($var_name) {
                $if_types[$var_name] = [$instanceof_types];

                $var_type = $source instanceof StatementsAnalyzer
                    ? $source->node_data->getType($conditional->expr)
                    : null;

                foreach ($instanceof_types as $instanceof_type) {
                    if ($instanceof_type[0] === '=') {
                        $instanceof_type = substr($instanceof_type, 1);
                    }

                    if ($codebase
                        && $var_type
                        && $inside_negation
                        && $source instanceof StatementsAnalyzer
                    ) {
                        if ($codebase->interfaceExists($instanceof_type)) {
                            continue;
                        }

                        $instanceof_type = Type::parseString(
                            $instanceof_type,
                            null,
                            $source->getTemplateTypeMap() ?: []
                        );

                        if (!UnionTypeComparator::canExpressionTypesBeIdentical(
                            $codebase,
                            $instanceof_type,
                            $var_type
                        )) {
                            if ($var_type->from_docblock) {
                                if (IssueBuffer::accepts(
                                    new RedundantConditionGivenDocblockType(
                                        $var_type->getId() . ' does not contain '
                                        . $instanceof_type->getId(),
                                        new CodeLocation($source, $conditional),
                                        $var_type->getId() . ' ' . $instanceof_type->getId()
                                    ),
                                    $source->getSuppressedIssues()
                                )) {
                                    // fall through
                                }
                            } else {
                                if (IssueBuffer::accepts(
                                    new RedundantCondition(
                                        $var_type->getId() . ' cannot be identical to '
                                        . $instanceof_type->getId(),
                                        new CodeLocation($source, $conditional),
                                        $var_type->getId() . ' ' . $instanceof_type->getId()
                                    ),
                                    $source->getSuppressedIssues()
                                )) {
                                    // fall through
                                }
                            }
                        }
                    }
                }
            }
        }

        return $if_types ? [$if_types] : [];
    }
}
