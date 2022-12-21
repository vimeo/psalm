<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Greater;
use PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotEqual;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\BinaryOp\Smaller;
use PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Expr\UnaryPlus;
use PhpParser\Node\Scalar\LNumber;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\FileSource;
use Psalm\Internal\Algebra;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\ClassLikeNameOptions;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Issue\DocblockTypeContradiction;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\RedundantCondition;
use Psalm\Issue\RedundantConditionGivenDocblockType;
use Psalm\Issue\RedundantIdentityWithTrue;
use Psalm\Issue\TypeDoesNotContainNull;
use Psalm\Issue\TypeDoesNotContainType;
use Psalm\Issue\UnevaluatedCode;
use Psalm\IssueBuffer;
use Psalm\Node\Expr\BinaryOp\VirtualIdentical;
use Psalm\Node\Expr\BinaryOp\VirtualNotIdentical;
use Psalm\Storage\Assertion;
use Psalm\Storage\Assertion\ArrayKeyExists;
use Psalm\Storage\Assertion\DoesNotHaveAtLeastCount;
use Psalm\Storage\Assertion\DoesNotHaveExactCount;
use Psalm\Storage\Assertion\Empty_;
use Psalm\Storage\Assertion\Falsy;
use Psalm\Storage\Assertion\HasAtLeastCount;
use Psalm\Storage\Assertion\HasExactCount;
use Psalm\Storage\Assertion\HasMethod;
use Psalm\Storage\Assertion\InArray;
use Psalm\Storage\Assertion\IsAClass;
use Psalm\Storage\Assertion\IsClassEqual;
use Psalm\Storage\Assertion\IsClassNotEqual;
use Psalm\Storage\Assertion\IsCountable;
use Psalm\Storage\Assertion\IsEqualIsset;
use Psalm\Storage\Assertion\IsGreaterThan;
use Psalm\Storage\Assertion\IsGreaterThanOrEqualTo;
use Psalm\Storage\Assertion\IsIdentical;
use Psalm\Storage\Assertion\IsIsset;
use Psalm\Storage\Assertion\IsLessThan;
use Psalm\Storage\Assertion\IsLessThanOrEqualTo;
use Psalm\Storage\Assertion\IsLooselyEqual;
use Psalm\Storage\Assertion\IsNotIdentical;
use Psalm\Storage\Assertion\IsNotLooselyEqual;
use Psalm\Storage\Assertion\IsNotType;
use Psalm\Storage\Assertion\IsType;
use Psalm\Storage\Assertion\NestedAssertions;
use Psalm\Storage\Assertion\NonEmptyCountable;
use Psalm\Storage\Assertion\NotNonEmptyCountable;
use Psalm\Storage\Assertion\Truthy;
use Psalm\Storage\Possibilities;
use Psalm\Storage\PropertyStorage;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TClosedResource;
use Psalm\Type\Atomic\TEnumCase;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Atomic\TTraitString;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Reconciler;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_key_exists;
use function assert;
use function count;
use function explode;
use function in_array;
use function is_int;
use function is_numeric;
use function is_string;
use function sprintf;
use function str_replace;
use function strpos;
use function strtolower;
use function substr;

/**
 * @internal
 * This class transform conditions in code into "assertions" that will be reconciled with the type already known of a
 * given variable to narrow the type or find paradox.
 * For example if $a is an int, if($a > 0) will be turned into an assertion to make psalm understand that in the
 * if block, $a is a positive-int
 */
class AssertionFinder
{
    public const ASSIGNMENT_TO_RIGHT = 1;
    public const ASSIGNMENT_TO_LEFT = -1;

    /**
     * Gets all the type assertions in a conditional
     *
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
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
            return self::getAndCheckInstanceofAssertions(
                $conditional,
                $codebase,
                $source,
                $this_class_name,
                $inside_negation,
            );
        }

        if ($conditional instanceof PhpParser\Node\Expr\Assign) {
            $var_name = ExpressionIdentifier::getExtendedVarId(
                $conditional->var,
                $this_class_name,
                $source,
            );

            $candidate_if_types = $inside_conditional
                ? self::scrapeAssertions(
                    $conditional->expr,
                    $this_class_name,
                    $source,
                    $codebase,
                    $inside_negation,
                    $cache,
                    $inside_conditional,
                )
                : [];

            if ($var_name) {
                if ($candidate_if_types) {
                    $if_types[$var_name] = [[new NestedAssertions($candidate_if_types[0])]];
                } else {
                    $if_types[$var_name] = [[new Truthy()]];
                }
            }

            return $if_types ? [$if_types] : [];
        }

        $var_name = ExpressionIdentifier::getExtendedVarId(
            $conditional,
            $this_class_name,
            $source,
        );

        if ($var_name) {
            $if_types[$var_name] = [[new Truthy()]];

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
                $cache,
                $inside_conditional,
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
                $cache,
                $inside_conditional,
            );
        }

        //A nullsafe method call basically adds an assertion !null for the checked variable
        if ($conditional instanceof PhpParser\Node\Expr\NullsafeMethodCall) {
            $if_types = [];

            $var_name = ExpressionIdentifier::getExtendedVarId(
                $conditional->var,
                $this_class_name,
                $source,
            );

            if ($var_name) {
                $if_types[$var_name] = [[new IsNotType(new TNull())]];
            }

            //we may throw a RedundantNullsafeMethodCall here in the future if $var_name is never null

            return $if_types ? [$if_types] : [];
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Greater
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\GreaterOrEqual
        ) {
            return self::getGreaterAssertions(
                $conditional,
                $source,
                $this_class_name,
            );
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Smaller
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\SmallerOrEqual
        ) {
            return self::getSmallerAssertions(
                $conditional,
                $source,
                $this_class_name,
            );
        }

        if ($conditional instanceof PhpParser\Node\Expr\FuncCall && !$conditional->isFirstClassCallable()) {
            return self::processFunctionCall(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation,
            );
        }

        if (($conditional instanceof PhpParser\Node\Expr\MethodCall
            || $conditional instanceof PhpParser\Node\Expr\StaticCall)
            && !$conditional->isFirstClassCallable()
        ) {
            $custom_assertions = self::processCustomAssertion($conditional, $this_class_name, $source);

            if ($custom_assertions) {
                return $custom_assertions;
            }

            return $if_types ? [$if_types] : [];
        }

        if ($conditional instanceof PhpParser\Node\Expr\Empty_) {
            $var_name = ExpressionIdentifier::getExtendedVarId(
                $conditional->expr,
                $this_class_name,
                $source,
            );

            if ($var_name) {
                if ($conditional->expr instanceof PhpParser\Node\Expr\Variable
                    && $source instanceof StatementsAnalyzer
                    && ($var_type = $source->node_data->getType($conditional->expr))
                    && !$var_type->isMixed()
                    && !$var_type->possibly_undefined
                ) {
                    $if_types[$var_name] = [[new Falsy()]];
                } else {
                    $if_types[$var_name] = [[new Empty_()]];
                }
            }

            return $if_types ? [$if_types] : [];
        }

        if ($conditional instanceof PhpParser\Node\Expr\Isset_) {
            foreach ($conditional->vars as $isset_var) {
                $var_name = ExpressionIdentifier::getExtendedVarId(
                    $isset_var,
                    $this_class_name,
                    $source,
                );

                if ($var_name) {
                    if ($isset_var instanceof PhpParser\Node\Expr\Variable
                        && $source instanceof StatementsAnalyzer
                        && ($var_type = $source->node_data->getType($isset_var))
                        && !$var_type->isMixed()
                        && !$var_type->possibly_undefined
                        && !$var_type->possibly_undefined_from_try
                    ) {
                        $if_types[$var_name] = [[new IsNotType(new TNull())]];
                    } else {
                        $if_types[$var_name] = [[new IsIsset]];
                    }
                } else {
                    // look for any variables we *can* use for an isset assertion
                    $array_root = $isset_var;

                    while ($array_root instanceof PhpParser\Node\Expr\ArrayDimFetch && !$var_name) {
                        $array_root = $array_root->var;

                        $var_name = ExpressionIdentifier::getExtendedVarId(
                            $array_root,
                            $this_class_name,
                            $source,
                        );
                    }

                    if ($var_name) {
                        $if_types[$var_name] = [[new IsEqualIsset]];
                    }
                }
            }

            return $if_types ? [$if_types] : [];
        }

        return [];
    }

    /**
     * @param PhpParser\Node\Expr\BinaryOp\Identical|PhpParser\Node\Expr\BinaryOp\Equal $conditional
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function scrapeEqualityAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        FileSource $source,
        ?Codebase $codebase = null,
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
                $null_position,
            );
        }

        $false_position = self::hasFalseVariable($conditional);

        if ($false_position) {
            return self::getFalseEqualityAssertions(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $false_position,
                $cache,
                $inside_conditional,
            );
        }

        $true_position = self::hasTrueVariable($conditional);

        if ($true_position) {
            return self::getTrueEqualityAssertions(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $true_position,
                $cache,
                $inside_conditional,
            );
        }

        $empty_array_position = self::hasEmptyArrayVariable($conditional);

        if ($empty_array_position !== null) {
            return self::getEmptyArrayEqualityAssertions(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $empty_array_position,
            );
        }

        $gettype_position = self::hasGetTypeCheck($conditional);

        if ($gettype_position) {
            return self::getGettypeEqualityAssertions(
                $conditional,
                $this_class_name,
                $source,
                $gettype_position,
            );
        }

        $get_debug_type_position = self::hasGetDebugTypeCheck($conditional);

        if ($get_debug_type_position) {
            return self::getGetdebugtypeEqualityAssertions(
                $conditional,
                $this_class_name,
                $source,
                $get_debug_type_position,
            );
        }



        $count = null;
        $count_equality_position = self::hasCountEqualityCheck($conditional, $count);

        if ($count_equality_position) {
            $if_types = [];

            if ($count_equality_position === self::ASSIGNMENT_TO_RIGHT) {
                $count_expr = $conditional->left;
            } elseif ($count_equality_position === self::ASSIGNMENT_TO_LEFT) {
                $count_expr = $conditional->right;
            } else {
                throw new UnexpectedValueException('$count_equality_position value');
            }

            /** @var PhpParser\Node\Expr\FuncCall $count_expr */
            $var_name = ExpressionIdentifier::getExtendedVarId(
                $count_expr->getArgs()[0]->value,
                $this_class_name,
                $source,
            );

            if ($source instanceof StatementsAnalyzer) {
                $var_type = $source->node_data->getType($conditional->left);
                $other_type = $source->node_data->getType($conditional->right);

                if ($codebase
                    && $other_type
                    && $var_type
                    && $conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
                ) {
                    self::handleParadoxicalAssertions(
                        $source,
                        $var_type,
                        $this_class_name,
                        $other_type,
                        $codebase,
                        $conditional,
                    );
                }
            }

            if ($var_name) {
                if ($count > 0) {
                    $if_types[$var_name] = [[new HasExactCount($count)]];
                } else {
                    $if_types[$var_name] = [[new NotNonEmptyCountable()]];
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
                $getclass_position,
            );
        }

        $typed_value_position = self::hasTypedValueComparison($conditional, $source);

        if ($typed_value_position) {
            return self::getTypedValueEqualityAssertions(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $typed_value_position,
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
                IssueBuffer::maybeAdd(
                    new TypeDoesNotContainType(
                        $var_type->getId() . ' cannot be identical to ' . $other_type->getId(),
                        new CodeLocation($source, $conditional),
                        $var_type->getId() . ' ' . $other_type->getId(),
                    ),
                    $source->getSuppressedIssues(),
                );
            } else {
                // both side of the Identical can be asserted to the intersection of both
                $intersection_type = Type::intersectUnionTypes($var_type, $other_type, $codebase);

                if ($intersection_type !== null && $intersection_type->isSingle()) {
                    $if_types = [];

                    $var_name_left = ExpressionIdentifier::getExtendedVarId(
                        $conditional->left,
                        $this_class_name,
                        $source,
                    );

                    $var_assertion_different = $var_type->getId() !== $intersection_type->getId();

                    if ($var_name_left && $var_assertion_different) {
                        $if_types[$var_name_left] = [[new IsIdentical($intersection_type->getSingleAtomic())]];
                    }

                    $var_name_right = ExpressionIdentifier::getExtendedVarId(
                        $conditional->right,
                        $this_class_name,
                        $source,
                    );

                    $other_assertion_different = $other_type->getId() !== $intersection_type->getId();

                    if ($var_name_right && $other_assertion_different) {
                        $if_types[$var_name_right] = [[new IsIdentical($intersection_type->getSingleAtomic())]];
                    }

                    return $if_types ? [$if_types] : [];
                }
            }
        }

        return [];
    }

    /**
     * @param PhpParser\Node\Expr\BinaryOp\NotIdentical|PhpParser\Node\Expr\BinaryOp\NotEqual $conditional
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function scrapeInequalityAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        FileSource $source,
        ?Codebase $codebase = null,
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
                $null_position,
            );
        }

        $false_position = self::hasFalseVariable($conditional);

        if ($false_position) {
            return self::getFalseInequalityAssertions(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $false_position,
                $cache,
                $inside_conditional,
            );
        }

        $true_position = self::hasTrueVariable($conditional);

        if ($true_position) {
            return self::getTrueInequalityAssertions(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $true_position,
                $cache,
                $inside_conditional,
            );
        }

        $empty_array_position = self::hasEmptyArrayVariable($conditional);

        if ($empty_array_position !== null) {
            return self::getEmptyInequalityAssertions(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $empty_array_position,
            );
        }

        $gettype_position = self::hasGetTypeCheck($conditional);

        if ($gettype_position) {
            return self::getGettypeInequalityAssertions(
                $conditional,
                $this_class_name,
                $source,
                $gettype_position,
            );
        }

        $get_debug_type_position = self::hasGetDebugTypeCheck($conditional);

        if ($get_debug_type_position) {
            return self::getGetdebugTypeInequalityAssertions(
                $conditional,
                $this_class_name,
                $source,
                $get_debug_type_position,
            );
        }

        $count = null;
        $count_inequality_position = self::hasCountEqualityCheck($conditional, $count);

        if ($count_inequality_position) {
            $if_types = [];

            if ($count_inequality_position === self::ASSIGNMENT_TO_RIGHT) {
                $count_expr = $conditional->left;
            } elseif ($count_inequality_position === self::ASSIGNMENT_TO_LEFT) {
                $count_expr = $conditional->right;
            } else {
                throw new UnexpectedValueException('$count_inequality_position value');
            }

            /** @var PhpParser\Node\Expr\FuncCall $count_expr */
            $var_name = ExpressionIdentifier::getExtendedVarId(
                $count_expr->getArgs()[0]->value,
                $this_class_name,
                $source,
            );

            if ($source instanceof StatementsAnalyzer) {
                $var_type = $source->node_data->getType($conditional->left);
                $other_type = $source->node_data->getType($conditional->right);

                if ($codebase
                    && $other_type
                    && $var_type
                    && $conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical
                ) {
                    self::handleParadoxicalAssertions(
                        $source,
                        $var_type,
                        $this_class_name,
                        $other_type,
                        $codebase,
                        $conditional,
                    );
                }
            }

            if ($var_name) {
                if ($count > 0) {
                    $if_types[$var_name] = [[new DoesNotHaveExactCount($count)]];
                } else {
                    $if_types[$var_name] = [[new NonEmptyCountable(true)]];
                }
            }

            return $if_types ? [$if_types] : [];
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
                $getclass_position,
            );
        }

        $typed_value_position = self::hasTypedValueComparison($conditional, $source);

        if ($typed_value_position) {
            return self::getTypedValueInequalityAssertions(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $typed_value_position,
            );
        }

        return [];
    }

    /**
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    public static function processFunctionCall(
        PhpParser\Node\Expr\FuncCall $expr,
        ?string $this_class_name,
        FileSource $source,
        ?Codebase $codebase = null,
        bool $negate = false
    ): array {
        $first_var_name = isset($expr->getArgs()[0]->value)
            ? ExpressionIdentifier::getExtendedVarId(
                $expr->getArgs()[0]->value,
                $this_class_name,
                $source,
            )
            : null;

        $if_types = [];

        $first_var_type = isset($expr->getArgs()[0]->value)
            && $source instanceof StatementsAnalyzer
            ? $source->node_data->getType($expr->getArgs()[0]->value)
            : null;

        if ($tmp_if_types = self::handleIsTypeCheck(
            $codebase,
            $source,
            $expr,
            $first_var_name,
            $first_var_type,
            $expr,
            $negate,
        )) {
            $if_types = $tmp_if_types;
        } elseif ($source instanceof StatementsAnalyzer && self::hasIsACheck($expr, $source)) {
            return self::getIsaAssertions($expr, $source, $this_class_name, $first_var_name);
        } elseif (self::hasCallableCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = [[new IsType(new TCallable())]];
            } elseif ($expr->getArgs()[0]->value instanceof PhpParser\Node\Expr\Array_
                && isset($expr->getArgs()[0]->value->items[0], $expr->getArgs()[0]->value->items[1])
                && $expr->getArgs()[0]->value->items[1]->value instanceof PhpParser\Node\Scalar\String_
            ) {
                $first_var_name_in_array_argument = ExpressionIdentifier::getExtendedVarId(
                    $expr->getArgs()[0]->value->items[0]->value,
                    $this_class_name,
                    $source,
                );
                if ($first_var_name_in_array_argument) {
                    $if_types[$first_var_name_in_array_argument] = [
                        [new HasMethod($expr->getArgs()[0]->value->items[1]->value->value)],
                    ];
                }
            }
        } elseif ($class_exists_check_type = self::hasClassExistsCheck($expr)) {
            if ($first_var_name) {
                $class_string_type = new TClassString('object', null, $class_exists_check_type === 1);
                $if_types[$first_var_name] = [[new IsType($class_string_type)]];
            }
        } elseif ($class_exists_check_type = self::hasTraitExistsCheck($expr)) {
            if ($first_var_name) {
                if ($class_exists_check_type === 2) {
                    $if_types[$first_var_name] = [[new IsType(new TTraitString())]];
                } else {
                    $if_types[$first_var_name] = [[new IsIdentical(new TTraitString())]];
                }
            }
        } elseif (self::hasEnumExistsCheck($expr)) {
            if ($first_var_name) {
                $class_string = new TClassString('object', null, false, false, true);
                $if_types[$first_var_name] = [[new IsType($class_string)]];
            }
        } elseif (self::hasInterfaceExistsCheck($expr)) {
            if ($first_var_name) {
                $class_string = new TClassString('object', null, false, true, false);
                $if_types[$first_var_name] = [[new IsType($class_string)]];
            }
        } elseif (self::hasFunctionExistsCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = [[new IsType(new TCallableString())]];
            }
        } elseif ($expr->name instanceof PhpParser\Node\Name
            && strtolower($expr->name->parts[0]) === 'method_exists'
            && isset($expr->getArgs()[1])
            && $expr->getArgs()[1]->value instanceof PhpParser\Node\Scalar\String_
        ) {
            if ($first_var_name) {
                $if_types[$first_var_name] = [[new HasMethod($expr->getArgs()[1]->value->value)]];
            }
        } elseif (self::hasInArrayCheck($expr) && $source instanceof StatementsAnalyzer) {
            return self::getInarrayAssertions($expr, $source, $first_var_name);
        } elseif (self::hasArrayKeyExistsCheck($expr)) {
            return self::getArrayKeyExistsAssertions(
                $expr,
                $first_var_type,
                $first_var_name,
                $source,
                $this_class_name,
            );
        } elseif (self::hasNonEmptyCountCheck($expr)) {
            if ($first_var_name) {
                $if_types[$first_var_name] = [[new NonEmptyCountable(true)]];
            }
        } else {
            return self::processCustomAssertion($expr, $this_class_name, $source);
        }

        return $if_types ? [$if_types] : [];
    }

    private static function processIrreconcilableFunctionCall(
        Union $first_var_type,
        Union $expected_type,
        PhpParser\Node\Expr $expr,
        StatementsAnalyzer $source,
        Codebase $codebase,
        bool $negate
    ): void {
        if ($first_var_type->hasMixed()) {
            return;
        }

        if (!UnionTypeComparator::isContainedBy(
            $codebase,
            $first_var_type,
            $expected_type,
        )) {
            return;
        }

        if (!$negate) {
            if ($first_var_type->from_docblock) {
                IssueBuffer::maybeAdd(
                    new RedundantConditionGivenDocblockType(
                        'Docblock type ' . $first_var_type . ' always contains ' . $expected_type,
                        new CodeLocation($source, $expr),
                        $first_var_type . ' ' . $expected_type,
                    ),
                    $source->getSuppressedIssues(),
                );
            } else {
                IssueBuffer::maybeAdd(
                    new RedundantCondition(
                        $first_var_type . ' always contains ' . $expected_type,
                        new CodeLocation($source, $expr),
                        $first_var_type . ' ' . $expected_type,
                    ),
                    $source->getSuppressedIssues(),
                );
            }
        } else {
            if ($first_var_type->from_docblock) {
                IssueBuffer::maybeAdd(
                    new DocblockTypeContradiction(
                        'Docblock type !' . $first_var_type . ' does not contain ' . $expected_type,
                        new CodeLocation($source, $expr),
                        $first_var_type . ' ' . $expected_type,
                    ),
                    $source->getSuppressedIssues(),
                );
            } else {
                IssueBuffer::maybeAdd(
                    new TypeDoesNotContainType(
                        '!' . $first_var_type . ' does not contain ' . $expected_type,
                        new CodeLocation($source, $expr),
                        $first_var_type . ' ' . $expected_type,
                    ),
                    $source->getSuppressedIssues(),
                );
            }
        }
    }

    /**
     * @param  PhpParser\Node\Expr\FuncCall|PhpParser\Node\Expr\MethodCall|PhpParser\Node\Expr\StaticCall $expr
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
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

        $first_var_name = isset($expr->getArgs()[0]->value)
            ? ExpressionIdentifier::getExtendedVarId(
                $expr->getArgs()[0]->value,
                $this_class_name,
                $source,
            )
            : null;

        $anded_types = [];

        if ($if_true_assertions) {
            foreach ($if_true_assertions as $assertion) {
                $if_types = [];

                $newRules = [];

                foreach ($assertion->rule as $rule) {
                    $rule_type = $rule->getAtomicType();

                    if ($rule_type instanceof TClassConstant) {
                        $codebase = $source->getCodebase();

                        $newRules[] = $rule->setAtomicType(
                            TypeExpander::expandAtomic(
                                $codebase,
                                $rule_type,
                                null,
                                null,
                                null,
                            )[0],
                        );
                    } else {
                        $newRules []= $rule;
                    }
                }

                $assertion = new Possibilities($assertion->var_id, $newRules);

                if (is_int($assertion->var_id) && isset($expr->getArgs()[$assertion->var_id])) {
                    if ($assertion->var_id === 0) {
                        $var_name = $first_var_name;
                    } else {
                        $var_name = ExpressionIdentifier::getExtendedVarId(
                            $expr->getArgs()[$assertion->var_id]->value,
                            $this_class_name,
                            $source,
                        );
                    }

                    if ($var_name) {
                        $if_types[$var_name] = [[$assertion->rule[0]]];
                    }
                } elseif ($assertion->var_id === '$this') {
                    if (!$expr instanceof PhpParser\Node\Expr\MethodCall) {
                        IssueBuffer::maybeAdd(
                            new InvalidDocblock(
                                'Assertion of $this can be done only on method of a class',
                                new CodeLocation($source, $expr),
                            ),
                        );
                        continue;
                    }

                    $var_id = ExpressionIdentifier::getExtendedVarId(
                        $expr->var,
                        $this_class_name,
                        $source,
                    );

                    if ($var_id) {
                        $if_types[$var_id] = [[$assertion->rule[0]]];
                    }
                } elseif (is_string($assertion->var_id)) {
                    $is_function = substr($assertion->var_id, -2) === '()';
                    $exploded_id = explode('->', $assertion->var_id);
                    $var_id   = $exploded_id[0] ?? null;
                    $property = $exploded_id[1] ?? null;

                    if (is_numeric($var_id) && null !== $property && !$is_function) {
                        $var_id_int = (int) $var_id;
                        assert($var_id_int >= 0);
                        $args = $expr->getArgs();

                        if (!array_key_exists($var_id_int, $args)) {
                            IssueBuffer::maybeAdd(
                                new InvalidDocblock(
                                    'Variable '.$var_id.' is not an argument so cannot be asserted',
                                    new CodeLocation($source, $expr),
                                ),
                            );
                            continue;
                        }

                        $arg_value = $args[$var_id_int]->value;
                        assert($arg_value instanceof PhpParser\Node\Expr\Variable);

                        $arg_var_id = ExpressionIdentifier::getExtendedVarId($arg_value, null, $source);

                        if (null === $arg_var_id) {
                            IssueBuffer::maybeAdd(
                                new InvalidDocblock(
                                    'Variable being asserted as argument ' . ($var_id+1) .  ' cannot be found
                                    in local scope',
                                    new CodeLocation($source, $expr),
                                ),
                            );
                            continue;
                        }

                        if (count($exploded_id) === 2) {
                            $failedMessage = self::isPropertyImmutableOnArgument(
                                $property,
                                $source->getNodeTypeProvider(),
                                $source->getCodebase()->classlike_storage_provider,
                                $arg_value,
                            );

                            if (null !== $failedMessage) {
                                IssueBuffer::maybeAdd(
                                    new InvalidDocblock($failedMessage, new CodeLocation($source, $expr)),
                                );
                                continue;
                            }
                        }

                        $assertion_var_id = str_replace($var_id, $arg_var_id, $assertion->var_id);
                    } elseif (!$expr instanceof PhpParser\Node\Expr\FuncCall) {
                        $assertion_var_id = $assertion->var_id;

                        if (strpos($assertion_var_id, 'self::') === 0) {
                            $assertion_var_id = $this_class_name.'::'.substr($assertion_var_id, 6);
                        }
                    } else {
                        IssueBuffer::maybeAdd(
                            new InvalidDocblock(
                                sprintf('Assertion of variable "%s" cannot be recognized', $assertion->var_id),
                                new CodeLocation($source, $expr),
                            ),
                        );
                        continue;
                    }
                    $if_types[$assertion_var_id] = [[$assertion->rule[0]]];
                }

                if ($if_types) {
                    $anded_types[] = $if_types;
                }
            }
        }

        if ($if_false_assertions) {
            foreach ($if_false_assertions as $assertion) {
                $if_types = [];

                $newRules = [];

                foreach ($assertion->rule as $rule) {
                    $rule_type = $rule->getAtomicType();

                    if ($rule_type instanceof TClassConstant) {
                        $codebase = $source->getCodebase();

                        $newRules []= $rule->setAtomicType(
                            TypeExpander::expandAtomic(
                                $codebase,
                                $rule_type,
                                null,
                                null,
                                null,
                            )[0],
                        );
                    } else {
                        $newRules []= $rule;
                    }
                }

                $assertion = new Possibilities($assertion->var_id, $newRules);

                if (is_int($assertion->var_id) && isset($expr->getArgs()[$assertion->var_id])) {
                    if ($assertion->var_id === 0) {
                        $var_name = $first_var_name;
                    } else {
                        $var_name = ExpressionIdentifier::getExtendedVarId(
                            $expr->getArgs()[$assertion->var_id]->value,
                            $this_class_name,
                            $source,
                        );
                    }

                    if ($var_name) {
                        $if_types[$var_name] = [[$assertion->rule[0]->getNegation()]];
                    }
                } elseif ($assertion->var_id === '$this' && $expr instanceof PhpParser\Node\Expr\MethodCall) {
                    $var_id = ExpressionIdentifier::getExtendedVarId(
                        $expr->var,
                        $this_class_name,
                        $source,
                    );

                    if ($var_id) {
                        $if_types[$var_id] = [[$assertion->rule[0]->getNegation()]];
                    }
                } elseif (is_string($assertion->var_id)) {
                    $is_function = substr($assertion->var_id, -2) === '()';
                    $exploded_id = explode('->', $assertion->var_id);
                    $var_id   = $exploded_id[0] ?? null;
                    $property = $exploded_id[1] ?? null;

                    if (is_numeric($var_id) && null !== $property && !$is_function) {
                        $args = $expr->getArgs();
                        $var_id_int = (int) $var_id;

                        if (!array_key_exists($var_id_int, $args)) {
                            IssueBuffer::maybeAdd(
                                new InvalidDocblock(
                                    'Variable '.$var_id.' is not an argument so cannot be asserted',
                                    new CodeLocation($source, $expr),
                                ),
                            );
                            continue;
                        }
                        /** @var PhpParser\Node\Expr\Variable $arg_value */
                        $arg_value = $args[$var_id_int]->value;

                        $arg_var_id = ExpressionIdentifier::getExtendedVarId($arg_value, null, $source);

                        if (null === $arg_var_id) {
                            IssueBuffer::maybeAdd(
                                new InvalidDocblock(
                                    'Variable being asserted as argument ' . ($var_id+1) .  ' cannot be found
                                     in local scope',
                                    new CodeLocation($source, $expr),
                                ),
                            );
                            continue;
                        }

                        if (count($exploded_id) === 2) {
                            $failedMessage = self::isPropertyImmutableOnArgument(
                                $property,
                                $source->getNodeTypeProvider(),
                                $source->getCodebase()->classlike_storage_provider,
                                $arg_value,
                            );

                            if (null !== $failedMessage) {
                                IssueBuffer::maybeAdd(
                                    new InvalidDocblock($failedMessage, new CodeLocation($source, $expr)),
                                );
                                continue;
                            }
                        }

                        $rule = $assertion->rule[0]->getNegation();

                        $assertion_var_id = str_replace($var_id, $arg_var_id, $assertion->var_id);

                        $if_types[$assertion_var_id] = [[$rule]];
                    } elseif (!$expr instanceof PhpParser\Node\Expr\FuncCall) {
                        $var_id = $assertion->var_id;
                        if (strpos($var_id, 'self::') === 0) {
                            $var_id = $this_class_name.'::'.substr($var_id, 6);
                        }
                        $if_types[$var_id] = [[$assertion->rule[0]->getNegation()]];
                    } else {
                        IssueBuffer::maybeAdd(
                            new InvalidDocblock(
                                sprintf('Assertion of variable "%s" cannot be recognized', $assertion->var_id),
                                new CodeLocation($source, $expr),
                            ),
                        );
                    }
                }

                if ($if_types) {
                    $anded_types[] = $if_types;
                }
            }
        }

        return $anded_types;
    }

    /**
     * @return list<Assertion>
     */
    protected static function getInstanceOfAssertions(
        PhpParser\Node\Expr\Instanceof_ $stmt,
        ?string $this_class_name,
        FileSource $source
    ): array {
        if ($stmt->class instanceof PhpParser\Node\Name) {
            if (!in_array(strtolower($stmt->class->parts[0]), ['self', 'static', 'parent'], true)) {
                $instanceof_class = ClassLikeAnalyzer::getFQCLNFromNameObject(
                    $stmt->class,
                    $source->getAliases(),
                );

                if ($source instanceof StatementsAnalyzer) {
                    $codebase = $source->getCodebase();
                    $instanceof_class = $codebase->classlikes->getUnAliasedName($instanceof_class);
                }

                return [new IsType(new TNamedObject($instanceof_class))];
            }

            if ($this_class_name
                && (in_array(strtolower($stmt->class->parts[0]), ['self', 'static'], true))) {
                $is_static = $stmt->class->parts[0] === 'static';
                $named_object = new TNamedObject($this_class_name, $is_static);

                if ($is_static) {
                    return [new IsIdentical($named_object)];
                }

                return [new IsType($named_object)];
            }
        } elseif ($source instanceof StatementsAnalyzer) {
            $stmt_class_type = $source->node_data->getType($stmt->class);

            if ($stmt_class_type) {
                $literal_class_strings = [];

                foreach ($stmt_class_type->getAtomicTypes() as $atomic_type) {
                    if ($atomic_type instanceof TLiteralClassString) {
                        $literal_class_strings[] = new IsType(new TNamedObject($atomic_type->value));
                    } elseif ($atomic_type instanceof TTemplateParamClass) {
                        $literal_class_strings[] = new IsType(
                            new TTemplateParam(
                                $atomic_type->param_name,
                                new Union([$atomic_type->as_type ?: new TObject()]),
                                $atomic_type->defining_class,
                            ),
                        );
                    } elseif ($atomic_type instanceof TClassString && $atomic_type->as !== 'object') {
                        $literal_class_strings[] = new IsType(
                            $atomic_type->as_type ?: new TNamedObject($atomic_type->as),
                        );
                    }
                }

                return $literal_class_strings;
            }
        }

        return [];
    }

    /**
     * @param Identical|Equal|NotIdentical|NotEqual $conditional
     */
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

    /**
     * @param Identical|Equal|NotIdentical|NotEqual $conditional
     */
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

    /**
     * @param Identical|Equal|NotIdentical|NotEqual $conditional
     */
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

    /**
     * @param Identical|Equal|NotIdentical|NotEqual $conditional
     */
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
     * @param Identical|Equal|NotIdentical|NotEqual $conditional
     * @return false|int
     */
    protected static function hasGetTypeCheck(
        PhpParser\Node\Expr\BinaryOp $conditional
    ) {
        if ($conditional->right instanceof PhpParser\Node\Expr\FuncCall
            && $conditional->right->name instanceof PhpParser\Node\Name
            && strtolower($conditional->right->name->parts[0]) === 'gettype'
            && $conditional->right->getArgs()
            && $conditional->left instanceof PhpParser\Node\Scalar\String_
        ) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\FuncCall
            && $conditional->left->name instanceof PhpParser\Node\Name
            && strtolower($conditional->left->name->parts[0]) === 'gettype'
            && $conditional->left->getArgs()
            && $conditional->right instanceof PhpParser\Node\Scalar\String_
        ) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return false;
    }

    /**
     * @param Identical|Equal|NotIdentical|NotEqual $conditional
     * @return false|int
     */
    protected static function hasGetDebugTypeCheck(
        PhpParser\Node\Expr\BinaryOp $conditional
    ) {
        if ($conditional->right instanceof PhpParser\Node\Expr\FuncCall
            && $conditional->right->name instanceof PhpParser\Node\Name
            && strtolower($conditional->right->name->parts[0]) === 'get_debug_type'
            && $conditional->right->getArgs()
            && ($conditional->left instanceof PhpParser\Node\Scalar\String_
                || $conditional->left instanceof PhpParser\Node\Expr\ClassConstFetch)
        ) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\FuncCall
            && $conditional->left->name instanceof PhpParser\Node\Name
            && strtolower($conditional->left->name->parts[0]) === 'get_debug_type'
            && $conditional->left->getArgs()
            && ($conditional->right instanceof PhpParser\Node\Scalar\String_
                || $conditional->right instanceof PhpParser\Node\Expr\ClassConstFetch)
        ) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return false;
    }

    /**
     * @param Identical|Equal|NotIdentical|NotEqual $conditional
     * @return false|int
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

        $right_variable_class_const = $conditional->right instanceof PhpParser\Node\Expr\ClassConstFetch
            && $conditional->right->class instanceof PhpParser\Node\Expr\Variable
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
                if ($type_part instanceof TClassString) {
                    $left_class_string_t = true;
                    break;
                }
            }
        }

        if (($right_get_class || $right_static_class || $right_variable_class_const)
            && ($left_class_string || $left_class_string_t)
        ) {
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

        $left_variable_class_const = $conditional->left instanceof PhpParser\Node\Expr\ClassConstFetch
            && $conditional->left->class instanceof PhpParser\Node\Expr\Variable
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
                if ($type_part instanceof TClassString) {
                    $right_class_string_t = true;
                    break;
                }
            }
        }

        if (($left_get_class || $left_static_class || $left_variable_class_const)
            && ($right_class_string || $right_class_string_t)
        ) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return false;
    }

    /**
     * @param Greater|GreaterOrEqual|Smaller|SmallerOrEqual $conditional
     * @return false|int
     */
    protected static function hasNonEmptyCountEqualityCheck(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?int &$min_count
    ) {
        if ($conditional->left instanceof PhpParser\Node\Expr\FuncCall
            && $conditional->left->name instanceof PhpParser\Node\Name
            && strtolower($conditional->left->name->parts[0]) === 'count'
            && $conditional->left->getArgs()
            && ($conditional instanceof BinaryOp\Greater || $conditional instanceof BinaryOp\GreaterOrEqual)
        ) {
            $assignment_to = self::ASSIGNMENT_TO_RIGHT;
            $compare_to = $conditional->right;
            $comparison_adjustment = $conditional instanceof BinaryOp\Greater ? 1 : 0;
        } elseif ($conditional->right instanceof PhpParser\Node\Expr\FuncCall
            && $conditional->right->name instanceof PhpParser\Node\Name
            && strtolower($conditional->right->name->parts[0]) === 'count'
            && $conditional->right->getArgs()
            && ($conditional instanceof BinaryOp\Smaller || $conditional instanceof BinaryOp\SmallerOrEqual)
        ) {
            $assignment_to = self::ASSIGNMENT_TO_LEFT;
            $compare_to = $conditional->left;
            $comparison_adjustment = $conditional instanceof BinaryOp\Smaller ? 1 : 0;
        } else {
            return false;
        }

        // TODO get node type provider here somehow and check literal ints and int ranges
        if ($compare_to instanceof PhpParser\Node\Scalar\LNumber
            && $compare_to->value > (-1 * $comparison_adjustment)
        ) {
            $min_count = $compare_to->value + $comparison_adjustment;

            return $assignment_to;
        }

        return false;
    }

    /**
     * @param Greater|GreaterOrEqual|Smaller|SmallerOrEqual $conditional
     * @return false|int
     */
    protected static function hasLessThanCountEqualityCheck(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?int &$max_count
    ) {
        $left_count = $conditional->left instanceof PhpParser\Node\Expr\FuncCall
            && $conditional->left->name instanceof PhpParser\Node\Name
            && strtolower($conditional->left->name->parts[0]) === 'count'
            && $conditional->left->getArgs();

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
            && $conditional->right->getArgs();

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
     * @param Equal|Identical|NotEqual|NotIdentical $conditional
     * @return false|int
     */
    protected static function hasCountEqualityCheck(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?int &$count
    ) {
        $left_count = $conditional->left instanceof PhpParser\Node\Expr\FuncCall
            && $conditional->left->name instanceof PhpParser\Node\Name
            && strtolower($conditional->left->name->parts[0]) === 'count'
            && $conditional->left->getArgs();

        if ($left_count && $conditional->right instanceof PhpParser\Node\Scalar\LNumber) {
            $count = $conditional->right->value;

            return self::ASSIGNMENT_TO_RIGHT;
        }

        $right_count = $conditional->right instanceof PhpParser\Node\Expr\FuncCall
            && $conditional->right->name instanceof PhpParser\Node\Name
            && strtolower($conditional->right->name->parts[0]) === 'count'
            && $conditional->right->getArgs();

        if ($right_count && $conditional->left instanceof PhpParser\Node\Scalar\LNumber) {
            $count = $conditional->left->value;

            return self::ASSIGNMENT_TO_LEFT;
        }

        return false;
    }

    /**
     * @param PhpParser\Node\Expr\BinaryOp\Greater|PhpParser\Node\Expr\BinaryOp\GreaterOrEqual $conditional
     * @return false|int
     */
    protected static function hasSuperiorNumberCheck(
        FileSource $source,
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?int &$literal_value_comparison
    ) {
        $right_assignment = false;
        $value_right = null;
        if ($source instanceof StatementsAnalyzer
            && ($type = $source->node_data->getType($conditional->right))
            && $type->isSingleIntLiteral()
        ) {
            $right_assignment = true;
            $value_right = $type->getSingleIntLiteral()->value;
        } elseif ($conditional->right instanceof LNumber) {
            $right_assignment = true;
            $value_right = $conditional->right->value;
        } elseif ($conditional->right instanceof UnaryMinus && $conditional->right->expr instanceof LNumber) {
            $right_assignment = true;
            $value_right = -$conditional->right->expr->value;
        } elseif ($conditional->right instanceof UnaryPlus && $conditional->right->expr instanceof LNumber) {
            $right_assignment = true;
            $value_right = $conditional->right->expr->value;
        }
        if ($right_assignment === true && $value_right !== null) {
            $literal_value_comparison = $value_right;

            return self::ASSIGNMENT_TO_RIGHT;
        }

        $left_assignment = false;
        $value_left = null;
        if ($source instanceof StatementsAnalyzer
            && ($type = $source->node_data->getType($conditional->left))
            && $type->isSingleIntLiteral()
        ) {
            $left_assignment = true;
            $value_left = $type->getSingleIntLiteral()->value;
        } elseif ($conditional->left instanceof LNumber) {
            $left_assignment = true;
            $value_left = $conditional->left->value;
        } elseif ($conditional->left instanceof UnaryMinus && $conditional->left->expr instanceof LNumber) {
            $left_assignment = true;
            $value_left = -$conditional->left->expr->value;
        } elseif ($conditional->left instanceof UnaryPlus && $conditional->left->expr instanceof LNumber) {
            $left_assignment = true;
            $value_left = $conditional->left->expr->value;
        }
        if ($left_assignment === true && $value_left !== null) {
            $literal_value_comparison = $value_left;

            return self::ASSIGNMENT_TO_LEFT;
        }

        return false;
    }

    /**
     * @param PhpParser\Node\Expr\BinaryOp\Smaller|PhpParser\Node\Expr\BinaryOp\SmallerOrEqual $conditional
     * @return false|int
     */
    protected static function hasInferiorNumberCheck(
        FileSource $source,
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?int &$literal_value_comparison
    ) {
        $right_assignment = false;
        $value_right = null;
        if ($source instanceof StatementsAnalyzer
            && ($type = $source->node_data->getType($conditional->right))
            && $type->isSingleIntLiteral()
        ) {
            $right_assignment = true;
            $value_right = $type->getSingleIntLiteral()->value;
        } elseif ($conditional->right instanceof LNumber) {
            $right_assignment = true;
            $value_right = $conditional->right->value;
        } elseif ($conditional->right instanceof UnaryMinus && $conditional->right->expr instanceof LNumber) {
            $right_assignment = true;
            $value_right = -$conditional->right->expr->value;
        } elseif ($conditional->right instanceof UnaryPlus && $conditional->right->expr instanceof LNumber) {
            $right_assignment = true;
            $value_right = $conditional->right->expr->value;
        }
        if ($right_assignment === true && $value_right !== null) {
            $literal_value_comparison = $value_right;

            return self::ASSIGNMENT_TO_RIGHT;
        }

        $left_assignment = false;
        $value_left = null;
        if ($source instanceof StatementsAnalyzer
            && ($type = $source->node_data->getType($conditional->left))
            && $type->isSingleIntLiteral()
        ) {
            $left_assignment = true;
            $value_left = $type->getSingleIntLiteral()->value;
        } elseif ($conditional->left instanceof LNumber) {
            $left_assignment = true;
            $value_left = $conditional->left->value;
        } elseif ($conditional->left instanceof UnaryMinus && $conditional->left->expr instanceof LNumber) {
            $left_assignment = true;
            $value_left = -$conditional->left->expr->value;
        } elseif ($conditional->left instanceof UnaryPlus && $conditional->left->expr instanceof LNumber) {
            $left_assignment = true;
            $value_left = $conditional->left->expr->value;
        }
        if ($left_assignment === true && $value_left !== null) {
            $literal_value_comparison = $value_left;

            return self::ASSIGNMENT_TO_LEFT;
        }

        return false;
    }

    /**
     * @param PhpParser\Node\Expr\BinaryOp\Greater|PhpParser\Node\Expr\BinaryOp\GreaterOrEqual $conditional
     * @return false|int
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

        if ($left_count && $right_number) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        return false;
    }

    /**
     * @param Identical|Equal|NotIdentical|NotEqual $conditional
     * @return false|int
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

    protected static function hasIsACheck(
        PhpParser\Node\Expr\FuncCall $stmt,
        StatementsAnalyzer $source
    ): bool {
        if ($stmt->name instanceof PhpParser\Node\Name
            && (strtolower($stmt->name->parts[0]) === 'is_a'
                || strtolower($stmt->name->parts[0]) === 'is_subclass_of')
            && isset($stmt->getArgs()[1])
        ) {
            $second_arg = $stmt->getArgs()[1]->value;

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

    private static function getIsAssertion(string $function_name): ?Assertion
    {
        switch ($function_name) {
            case 'is_string':
                return new IsType(new Atomic\TString());
            case 'is_int':
            case 'is_integer':
                return new IsType(new Atomic\TInt());
            case 'is_float':
            case 'is_long':
            case 'is_double':
            case 'is_real':
                return new IsType(new Atomic\TFloat());
            case 'is_scalar':
                return new IsType(new Atomic\TScalar());
            case 'is_bool':
                return new IsType(new Atomic\TBool());
            case 'is_resource':
                return new IsType(new Atomic\TResource());
            case 'is_object':
                return new IsType(new Atomic\TObject());
            case 'array_is_list':
                return new IsType(Type::getListAtomic(Type::getMixed()));
            case 'is_array':
                return new IsType(new Atomic\TArray([Type::getArrayKey(), Type::getMixed()]));
            case 'is_numeric':
                return new IsType(new Atomic\TNumeric());
            case 'is_null':
                return new IsType(new Atomic\TNull());
            case 'is_iterable':
                return new IsType(new Atomic\TIterable());
            case 'is_countable':
                return new IsCountable();
            case 'ctype_digit':
                return new IsType(new Atomic\TNumericString);
            case 'ctype_lower':
                return new IsType(new Atomic\TNonEmptyLowercaseString);
        }

        return null;
    }

    /**
     * @return array<string, non-empty-list<non-empty-list<Assertion>>>
     */
    private static function handleIsTypeCheck(
        ?Codebase $codebase,
        FileSource $source,
        PhpParser\Node\Expr\FuncCall $stmt,
        ?string $first_var_name,
        ?Union $first_var_type,
        PhpParser\Node\Expr\FuncCall $expr,
        bool $negate
    ): array {
        $if_types = [];
        if ($stmt->name instanceof PhpParser\Node\Name
            && ($function_name = strtolower($stmt->name->parts[0]))
            && ($assertion_type = self::getIsAssertion($function_name))
            && $source instanceof StatementsAnalyzer
            && ($source->getNamespace() === null //either the namespace is null
                || $stmt->name instanceof PhpParser\Node\Name\FullyQualified //or we have a FQ to base function
                || isset($source->getAliases()->functions[$function_name]) //or it is imported
                || ($codebase && !$codebase->functions->functionExists(
                    $source,
                    strtolower($source->getNamespace()."\\".$function_name),
                )) //or this function name does not exist in current namespace
            )
        ) {
            if ($first_var_name) {
                $if_types[$first_var_name] = [[$assertion_type]];
            } elseif ($first_var_type
                && $codebase
                && $assertion_type instanceof IsType
            ) {
                self::processIrreconcilableFunctionCall(
                    $first_var_type,
                    new Union([$assertion_type->type]),
                    $expr,
                    $source,
                    $codebase,
                    $negate,
                );
            }
        }

        return $if_types;
    }

    protected static function hasCallableCheck(PhpParser\Node\Expr\FuncCall $stmt): bool
    {
        return $stmt->name instanceof PhpParser\Node\Name && strtolower($stmt->name->parts[0]) === 'is_callable';
    }

    /**
     * @return Reconciler::RECONCILIATION_*
     */
    protected static function hasClassExistsCheck(PhpParser\Node\Expr\FuncCall $stmt): int
    {
        if ($stmt->name instanceof PhpParser\Node\Name
            && strtolower($stmt->name->parts[0]) === 'class_exists'
        ) {
            if (!isset($stmt->getArgs()[1])) {
                return 2;
            }

            $second_arg = $stmt->getArgs()[1]->value;

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
            if (!isset($stmt->getArgs()[1])) {
                return 2;
            }

            $second_arg = $stmt->getArgs()[1]->value;

            if ($second_arg instanceof PhpParser\Node\Expr\ConstFetch
                && strtolower($second_arg->name->parts[0]) === 'true'
            ) {
                return 2;
            }

            return 1;
        }

        return 0;
    }

    protected static function hasEnumExistsCheck(PhpParser\Node\Expr\FuncCall $stmt): bool
    {
        return $stmt->name instanceof PhpParser\Node\Name && strtolower($stmt->name->parts[0]) === 'enum_exists';
    }

    protected static function hasInterfaceExistsCheck(PhpParser\Node\Expr\FuncCall $stmt): bool
    {
        return $stmt->name instanceof PhpParser\Node\Name && strtolower($stmt->name->parts[0]) === 'interface_exists';
    }

    protected static function hasFunctionExistsCheck(PhpParser\Node\Expr\FuncCall $stmt): bool
    {
        return $stmt->name instanceof PhpParser\Node\Name && strtolower($stmt->name->parts[0]) === 'function_exists';
    }

    protected static function hasInArrayCheck(PhpParser\Node\Expr\FuncCall $stmt): bool
    {
        if ($stmt->name instanceof PhpParser\Node\Name
            && strtolower($stmt->name->parts[0]) === 'in_array'
            && isset($stmt->getArgs()[2])
        ) {
            $second_arg = $stmt->getArgs()[2]->value;

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
        return $stmt->name instanceof PhpParser\Node\Name && strtolower($stmt->name->parts[0]) === 'count';
    }

    protected static function hasArrayKeyExistsCheck(PhpParser\Node\Expr\FuncCall $stmt): bool
    {
        return $stmt->name instanceof PhpParser\Node\Name && strtolower($stmt->name->parts[0]) === 'array_key_exists';
    }

    /**
     * @param PhpParser\Node\Expr\BinaryOp\NotIdentical|PhpParser\Node\Expr\BinaryOp\NotEqual $conditional
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
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
            throw new UnexpectedValueException('Bad null variable position');
        }

        $var_name = ExpressionIdentifier::getExtendedVarId(
            $base_conditional,
            $this_class_name,
            $source,
        );

        if ($var_name) {
            if ($base_conditional instanceof PhpParser\Node\Expr\Assign) {
                $var_name = '=' . $var_name;
            }

            if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical) {
                $if_types[$var_name] = [[new IsNotType(new TNull())]];
            } else {
                $if_types[$var_name] = [[new Truthy()]];
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
                    $null_type,
                ) && !UnionTypeComparator::isContainedBy(
                    $codebase,
                    $null_type,
                    $var_type,
                )) {
                    if ($var_type->from_docblock) {
                        IssueBuffer::maybeAdd(
                            new RedundantConditionGivenDocblockType(
                                'Docblock-defined type ' . $var_type . ' can never contain null',
                                new CodeLocation($source, $conditional),
                                $var_type->getId() . ' null',
                            ),
                            $source->getSuppressedIssues(),
                        );
                    } else {
                        IssueBuffer::maybeAdd(
                            new RedundantCondition(
                                $var_type . ' can never contain null',
                                new CodeLocation($source, $conditional),
                                $var_type->getId() . ' null',
                            ),
                            $source->getSuppressedIssues(),
                        );
                    }
                }
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param PhpParser\Node\Expr\BinaryOp\NotIdentical|PhpParser\Node\Expr\BinaryOp\NotEqual $conditional
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function getFalseInequalityAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        FileSource $source,
        ?Codebase $codebase,
        int $false_position,
        bool $cache,
        bool $inside_conditional
    ): array {
        $if_types = [];

        if ($false_position === self::ASSIGNMENT_TO_RIGHT) {
            $base_conditional = $conditional->left;
        } elseif ($false_position === self::ASSIGNMENT_TO_LEFT) {
            $base_conditional = $conditional->right;
        } else {
            throw new UnexpectedValueException('Bad false variable position');
        }

        $var_name = ExpressionIdentifier::getExtendedVarId(
            $base_conditional,
            $this_class_name,
            $source,
        );

        if ($var_name) {
            if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical) {
                $if_types[$var_name] = [[new IsNotType(new TFalse())]];
            } else {
                $if_types[$var_name] = [[new Truthy()]];
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
                    false,
                    $cache,
                    $inside_conditional,
                );

                if ($source instanceof StatementsAnalyzer && $cache) {
                    $source->node_data->setAssertions($base_conditional, $if_types);
                }
            }
        }

        if ($codebase
            && $source instanceof StatementsAnalyzer
            && ($var_type = $source->node_data->getType($base_conditional))
            && $conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical
        ) {
            $config = $source->getCodebase()->config;

            if ($config->strict_binary_operands
                && $var_type->isSingle()
                && $var_type->hasBool()
                && !$var_type->from_docblock
                && !$conditional instanceof VirtualNotIdentical
            ) {
                IssueBuffer::maybeAdd(
                    new RedundantIdentityWithTrue(
                        'The "!== false" part of this comparison is redundant',
                        new CodeLocation($source, $conditional),
                    ),
                    $source->getSuppressedIssues(),
                );
            }

            $false_type = Type::getFalse();

            if (!UnionTypeComparator::isContainedBy(
                $codebase,
                $var_type,
                $false_type,
            ) && !UnionTypeComparator::isContainedBy(
                $codebase,
                $false_type,
                $var_type,
            )) {
                if ($var_type->from_docblock) {
                    IssueBuffer::maybeAdd(
                        new RedundantConditionGivenDocblockType(
                            'Docblock-defined type ' . $var_type . ' can never contain false',
                            new CodeLocation($source, $conditional),
                            $var_type->getId() . ' false',
                        ),
                        $source->getSuppressedIssues(),
                    );
                } else {
                    IssueBuffer::maybeAdd(
                        new RedundantCondition(
                            $var_type . ' can never contain false',
                            new CodeLocation($source, $conditional),
                            $var_type->getId() . ' false',
                        ),
                        $source->getSuppressedIssues(),
                    );
                }
            }
        }

        return $if_types;
    }

    /**
     * @param PhpParser\Node\Expr\BinaryOp\NotIdentical|PhpParser\Node\Expr\BinaryOp\NotEqual $conditional
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function getTrueInequalityAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        FileSource $source,
        ?Codebase $codebase,
        int $true_position,
        bool $cache,
        bool $inside_conditional
    ): array {
        $if_types = [];

        if ($true_position === self::ASSIGNMENT_TO_RIGHT) {
            $base_conditional = $conditional->left;
        } elseif ($true_position === self::ASSIGNMENT_TO_LEFT) {
            $base_conditional = $conditional->right;
        } else {
            throw new UnexpectedValueException('Bad null variable position');
        }

        if ($base_conditional instanceof PhpParser\Node\Expr\FuncCall) {
            $notif_types = self::processFunctionCall(
                $base_conditional,
                $this_class_name,
                $source,
                $codebase,
                true,
            );
        } else {
            $var_name = ExpressionIdentifier::getExtendedVarId(
                $base_conditional,
                $this_class_name,
                $source,
            );

            if ($var_name) {
                if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical) {
                    $if_types[$var_name] = [[new IsNotType(new TTrue())]];
                } else {
                    $if_types[$var_name] = [[new Falsy()]];
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
                        false,
                        $cache,
                        $inside_conditional,
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
                $if_types = Algebra::negateTypes($notif_types);
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
                    $true_type,
                ) && !UnionTypeComparator::isContainedBy(
                    $codebase,
                    $true_type,
                    $var_type,
                )) {
                    if ($var_type->from_docblock) {
                        IssueBuffer::maybeAdd(
                            new RedundantConditionGivenDocblockType(
                                'Docblock-defined type ' . $var_type . ' can never contain true',
                                new CodeLocation($source, $conditional),
                                $var_type->getId() . ' true',
                            ),
                            $source->getSuppressedIssues(),
                        );
                    } else {
                        IssueBuffer::maybeAdd(
                            new RedundantCondition(
                                $var_type . ' can never contain ' . $true_type,
                                new CodeLocation($source, $conditional),
                                $var_type->getId() . ' true',
                            ),
                            $source->getSuppressedIssues(),
                        );
                    }
                }
            }
        }

        return $if_types;
    }

    /**
     * @param PhpParser\Node\Expr\BinaryOp\NotIdentical|PhpParser\Node\Expr\BinaryOp\NotEqual $conditional
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
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
            throw new UnexpectedValueException('Bad empty array variable position');
        }

        $var_name = ExpressionIdentifier::getExtendedVarId(
            $base_conditional,
            $this_class_name,
            $source,
        );

        if ($var_name) {
            if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical) {
                $if_types[$var_name] = [[new NonEmptyCountable(true)]];
            } else {
                $if_types[$var_name] = [[new Truthy()]];
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
                    $empty_array_type,
                ) && !UnionTypeComparator::isContainedBy(
                    $codebase,
                    $empty_array_type,
                    $var_type,
                )) {
                    if ($var_type->from_docblock) {
                        IssueBuffer::maybeAdd(
                            new RedundantConditionGivenDocblockType(
                                'Docblock-defined type ' . $var_type->getId() . ' can never contain null',
                                new CodeLocation($source, $conditional),
                                $var_type->getId() . ' null',
                            ),
                            $source->getSuppressedIssues(),
                        );
                    } else {
                        IssueBuffer::maybeAdd(
                            new RedundantCondition(
                                $var_type->getId() . ' can never contain null',
                                new CodeLocation($source, $conditional),
                                $var_type->getId() . ' null',
                            ),
                            $source->getSuppressedIssues(),
                        );
                    }
                }
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param PhpParser\Node\Expr\BinaryOp\NotIdentical|PhpParser\Node\Expr\BinaryOp\NotEqual $conditional
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
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
            throw new UnexpectedValueException('$gettype_position value');
        }

        /** @var PhpParser\Node\Expr\FuncCall $gettype_expr */
        $var_name = ExpressionIdentifier::getExtendedVarId(
            $gettype_expr->getArgs()[0]->value,
            $this_class_name,
            $source,
        );

        if ($whichclass_expr instanceof PhpParser\Node\Scalar\String_) {
            $var_type = $whichclass_expr->value;
        } elseif ($whichclass_expr instanceof PhpParser\Node\Expr\ClassConstFetch
            && $whichclass_expr->class instanceof PhpParser\Node\Name
        ) {
            $var_type = ClassLikeAnalyzer::getFQCLNFromNameObject(
                $whichclass_expr->class,
                $source->getAliases(),
            );
        } else {
            throw new UnexpectedValueException('Shouldn’t get here');
        }

        if (!isset(ClassLikeAnalyzer::GETTYPE_TYPES[$var_type])) {
            IssueBuffer::maybeAdd(
                new UnevaluatedCode(
                    'gettype cannot return this value',
                    new CodeLocation($source, $whichclass_expr),
                ),
            );
        } else {
            if ($var_name && $var_type) {
                if ($var_type === 'class@anonymous') {
                    $if_types[$var_name] = [[new IsNotIdentical(new TObject())]];
                } elseif ($var_type === 'resource (closed)') {
                    $if_types[$var_name] = [[new IsNotType(new TClosedResource())]];
                } elseif (strpos($var_type, 'resource (') === 0) {
                    $if_types[$var_name] = [[new IsNotIdentical(new TResource())]];
                } else {
                    $if_types[$var_name] = [[new IsNotType(Atomic::create($var_type))]];
                }
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param PhpParser\Node\Expr\BinaryOp\NotIdentical|PhpParser\Node\Expr\BinaryOp\NotEqual $conditional
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
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
            throw new UnexpectedValueException('$gettype_position value');
        }

        /** @var PhpParser\Node\Expr\FuncCall $get_debug_type_expr */
        $var_name = ExpressionIdentifier::getExtendedVarId(
            $get_debug_type_expr->getArgs()[0]->value,
            $this_class_name,
            $source,
        );

        if ($whichclass_expr instanceof PhpParser\Node\Scalar\String_) {
            $var_type = $whichclass_expr->value;
        } elseif ($whichclass_expr instanceof PhpParser\Node\Expr\ClassConstFetch
            && $whichclass_expr->class instanceof PhpParser\Node\Name
        ) {
            $var_type = ClassLikeAnalyzer::getFQCLNFromNameObject(
                $whichclass_expr->class,
                $source->getAliases(),
            );
        } else {
            throw new UnexpectedValueException('Shouldn’t get here');
        }

        if ($var_name && $var_type) {
            if ($var_type === 'class@anonymous') {
                $if_types[$var_name] = [[new IsNotIdentical(new TObject())]];
            } elseif ($var_type === 'resource (closed)') {
                $if_types[$var_name] = [[new IsNotType(new TClosedResource())]];
            } elseif (strpos($var_type, 'resource (') === 0) {
                $if_types[$var_name] = [[new IsNotIdentical(new TResource())]];
            } else {
                $if_types[$var_name] = [[new IsNotType(Atomic::create($var_type))]];
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param PhpParser\Node\Expr\BinaryOp\NotIdentical|PhpParser\Node\Expr\BinaryOp\NotEqual $conditional
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
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
            throw new UnexpectedValueException('$getclass_position value');
        }

        if ($getclass_expr instanceof PhpParser\Node\Expr\FuncCall) {
            $var_name = ExpressionIdentifier::getExtendedVarId(
                $getclass_expr->getArgs()[0]->value,
                $this_class_name,
                $source,
            );
        } elseif ($getclass_expr instanceof PhpParser\Node\Expr\ClassConstFetch
            && $getclass_expr->class instanceof PhpParser\Node\Expr
        ) {
            $var_name = ExpressionIdentifier::getExtendedVarId(
                $getclass_expr->class,
                $this_class_name,
                $source,
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
                $source->getAliases(),
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
                    if ($type_part instanceof TTemplateParamClass) {
                        $if_types[$var_name] = [[new IsNotIdentical(
                            new TTemplateParam(
                                $type_part->param_name,
                                new Union([$type_part->as_type ?: new TObject()]),
                                $type_part->defining_class,
                            ),
                        )]];
                    }
                }
            }

            return $if_types ? [$if_types] : [];
        }

        if (!$var_type
            || ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                $source,
                $var_type,
                new CodeLocation($source, $whichclass_expr),
                null,
                null,
                $source->getSuppressedIssues(),
            ) !== false
        ) {
            if ($var_name && $var_type) {
                $if_types[$var_name] = [[new IsClassNotEqual($var_type)]];
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param PhpParser\Node\Expr\BinaryOp\NotIdentical|PhpParser\Node\Expr\BinaryOp\NotEqual $conditional
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
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
            $var_name = ExpressionIdentifier::getExtendedVarId(
                $conditional->left,
                $this_class_name,
                $source,
            );

            $other_type = $source->node_data->getType($conditional->left);
            $var_type = $source->node_data->getType($conditional->right);
        } elseif ($typed_value_position === self::ASSIGNMENT_TO_LEFT) {
            $var_name = ExpressionIdentifier::getExtendedVarId(
                $conditional->right,
                $this_class_name,
                $source,
            );

            $var_type = $source->node_data->getType($conditional->left);
            $other_type = $source->node_data->getType($conditional->right);
        } else {
            throw new UnexpectedValueException('$typed_value_position value');
        }

        if ($var_type) {
            if ($var_name) {
                $not_identical = $conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical
                    || ($other_type
                        && (($var_type->isInt() && $other_type->isInt())
                            || ($var_type->isFloat() && $other_type->isFloat()))
                    );

                $anded_types = [];

                foreach ($var_type->getAtomicTypes() as $atomic_var_type) {
                    if ($not_identical) {
                        $anded_types[] = [new IsNotIdentical($atomic_var_type)];
                    } else {
                        $anded_types[] = [new IsNotLooselyEqual($atomic_var_type)];
                    }
                }

                $if_types[$var_name] = $anded_types;
            }

            if ($codebase
                && $other_type
                && $conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical
            ) {
                self::handleParadoxicalAssertions(
                    $source,
                    $var_type,
                    $this_class_name,
                    $other_type,
                    $codebase,
                    $conditional,
                );
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param PhpParser\Node\Expr\BinaryOp\Identical|PhpParser\Node\Expr\BinaryOp\Equal $conditional
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
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
            throw new UnexpectedValueException('$null_position value');
        }

        $var_name = ExpressionIdentifier::getExtendedVarId(
            $base_conditional,
            $this_class_name,
            $source,
        );

        if ($var_name && $base_conditional instanceof PhpParser\Node\Expr\Assign) {
            $var_name = '=' . $var_name;
        }

        if ($var_name) {
            if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                $if_types[$var_name] = [[new IsType(new TNull())]];
            } else {
                $if_types[$var_name] = [[new Falsy()]];
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
                $null_type,
            ) && !UnionTypeComparator::isContainedBy(
                $codebase,
                $null_type,
                $var_type,
            )) {
                if ($var_type->from_docblock) {
                    IssueBuffer::maybeAdd(
                        new DocblockTypeContradiction(
                            $var_type . ' does not contain null',
                            new CodeLocation($source, $conditional),
                            $var_type . ' null',
                        ),
                        $source->getSuppressedIssues(),
                    );
                } else {
                    IssueBuffer::maybeAdd(
                        new TypeDoesNotContainNull(
                            $var_type . ' does not contain null',
                            new CodeLocation($source, $conditional),
                            $var_type->getId(),
                        ),
                        $source->getSuppressedIssues(),
                    );
                }
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param PhpParser\Node\Expr\BinaryOp\Identical|PhpParser\Node\Expr\BinaryOp\Equal $conditional
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function getTrueEqualityAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        FileSource $source,
        ?Codebase $codebase,
        int $true_position,
        bool $cache,
        bool $inside_conditional
    ): array {
        $if_types = [];

        if ($true_position === self::ASSIGNMENT_TO_RIGHT) {
            $base_conditional = $conditional->left;
        } elseif ($true_position === self::ASSIGNMENT_TO_LEFT) {
            $base_conditional = $conditional->right;
        } else {
            throw new UnexpectedValueException('Unrecognised position');
        }

        if ($base_conditional instanceof PhpParser\Node\Expr\FuncCall) {
            $if_types = self::processFunctionCall(
                $base_conditional,
                $this_class_name,
                $source,
                $codebase,
                false,
            );
        } else {
            $var_name = ExpressionIdentifier::getExtendedVarId(
                $base_conditional,
                $this_class_name,
                $source,
            );

            if ($var_name) {
                if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                    $if_types[$var_name] = [[new IsType(new TTrue())]];
                } else {
                    $if_types[$var_name] = [[new Truthy()]];
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
                        false,
                        $cache,
                        $inside_conditional,
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
                && !$conditional instanceof VirtualIdentical
            ) {
                IssueBuffer::maybeAdd(
                    new RedundantIdentityWithTrue(
                        'The "=== true" part of this comparison is redundant',
                        new CodeLocation($source, $conditional),
                    ),
                    $source->getSuppressedIssues(),
                );
            }

            $true_type = Type::getTrue();

            if (!UnionTypeComparator::canExpressionTypesBeIdentical(
                $codebase,
                $true_type,
                $var_type,
            )) {
                if ($var_type->from_docblock) {
                    IssueBuffer::maybeAdd(
                        new DocblockTypeContradiction(
                            $var_type . ' does not contain true',
                            new CodeLocation($source, $conditional),
                            $var_type . ' true',
                        ),
                        $source->getSuppressedIssues(),
                    );
                } else {
                    IssueBuffer::maybeAdd(
                        new TypeDoesNotContainType(
                            $var_type . ' does not contain true',
                            new CodeLocation($source, $conditional),
                            $var_type . ' true',
                        ),
                        $source->getSuppressedIssues(),
                    );
                }
            }
        }

        return $if_types;
    }

    /**
     * @param PhpParser\Node\Expr\BinaryOp\Identical|PhpParser\Node\Expr\BinaryOp\Equal $conditional
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function getFalseEqualityAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        FileSource $source,
        ?Codebase $codebase,
        int $false_position,
        bool $cache,
        bool $inside_conditional
    ): array {
        $if_types = [];

        if ($false_position === self::ASSIGNMENT_TO_RIGHT) {
            $base_conditional = $conditional->left;
        } elseif ($false_position === self::ASSIGNMENT_TO_LEFT) {
            $base_conditional = $conditional->right;
        } else {
            throw new UnexpectedValueException('$false_position value');
        }

        if ($base_conditional instanceof PhpParser\Node\Expr\FuncCall) {
            $notif_types = self::processFunctionCall(
                $base_conditional,
                $this_class_name,
                $source,
                $codebase,
                true,
            );
        } else {
            $var_name = ExpressionIdentifier::getExtendedVarId(
                $base_conditional,
                $this_class_name,
                $source,
            );

            if ($var_name) {
                if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                    $if_types[$var_name] = [[new IsType(new TFalse())]];
                } else {
                    $if_types[$var_name] = [[new Falsy()]];
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
                        false,
                        $cache,
                        $inside_conditional,
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
                $if_types = Algebra::negateTypes($notif_types);
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
                    $var_type,
                )) {
                    if ($var_type->from_docblock) {
                        IssueBuffer::maybeAdd(
                            new DocblockTypeContradiction(
                                $var_type . ' does not contain false',
                                new CodeLocation($source, $conditional),
                                $var_type . ' false',
                            ),
                            $source->getSuppressedIssues(),
                        );
                    } else {
                        IssueBuffer::maybeAdd(
                            new TypeDoesNotContainType(
                                $var_type . ' does not contain false',
                                new CodeLocation($source, $conditional),
                                $var_type . ' false',
                            ),
                            $source->getSuppressedIssues(),
                        );
                    }
                }
            }
        }

        return $if_types;
    }

    /**
     * @param PhpParser\Node\Expr\BinaryOp\Identical|PhpParser\Node\Expr\BinaryOp\Equal $conditional
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
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
            throw new UnexpectedValueException('$empty_array_position value');
        }

        $var_name = ExpressionIdentifier::getExtendedVarId(
            $base_conditional,
            $this_class_name,
            $source,
        );

        if ($var_name) {
            if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical) {
                $if_types[$var_name] = [[new NotNonEmptyCountable()]];
            } else {
                $if_types[$var_name] = [[new Falsy()]];
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
                $var_type,
            )) {
                if ($var_type->from_docblock) {
                    IssueBuffer::maybeAdd(
                        new DocblockTypeContradiction(
                            $var_type . ' does not contain an empty array',
                            new CodeLocation($source, $conditional),
                            $var_type . ' !== []',
                        ),
                        $source->getSuppressedIssues(),
                    );
                } else {
                    IssueBuffer::maybeAdd(
                        new TypeDoesNotContainType(
                            $var_type . ' does not contain empty array',
                            new CodeLocation($source, $conditional),
                            $var_type . ' !== []',
                        ),
                        $source->getSuppressedIssues(),
                    );
                }
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param PhpParser\Node\Expr\BinaryOp\Identical|PhpParser\Node\Expr\BinaryOp\Equal $conditional
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
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
            throw new UnexpectedValueException('$gettype_position value');
        }

        /** @var PhpParser\Node\Expr\FuncCall $gettype_expr */
        $var_name = ExpressionIdentifier::getExtendedVarId(
            $gettype_expr->getArgs()[0]->value,
            $this_class_name,
            $source,
        );

        /** @var PhpParser\Node\Scalar\String_ $string_expr */
        $var_type = $string_expr->value;

        if (!isset(ClassLikeAnalyzer::GETTYPE_TYPES[$var_type])) {
            IssueBuffer::maybeAdd(
                new UnevaluatedCode(
                    'gettype cannot return this value',
                    new CodeLocation($source, $string_expr),
                ),
            );
        } else {
            if ($var_name && $var_type) {
                if ($var_type === 'class@anonymous') {
                    $if_types[$var_name] = [[new IsIdentical(new TObject())]];
                } elseif ($var_type === 'resource (closed)') {
                    $if_types[$var_name] = [[new IsType(new TClosedResource())]];
                } elseif (strpos($var_type, 'resource (') === 0) {
                    $if_types[$var_name] = [[new IsIdentical(new TResource())]];
                } elseif ($var_type === 'integer') {
                    $if_types[$var_name] = [[new IsType(new Atomic\TInt())]];
                } elseif ($var_type === 'double') {
                    $if_types[$var_name] = [[new IsType(new Atomic\TFloat())]];
                } elseif ($var_type === 'boolean') {
                    $if_types[$var_name] = [[new IsType(new Atomic\TBool())]];
                } else {
                    $if_types[$var_name] = [[new IsType(Atomic::create($var_type))]];
                }
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param PhpParser\Node\Expr\BinaryOp\Identical|PhpParser\Node\Expr\BinaryOp\Equal $conditional
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
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
            throw new UnexpectedValueException('$gettype_position value');
        }

        /** @var PhpParser\Node\Expr\FuncCall $get_debug_type_expr */
        $var_name = ExpressionIdentifier::getExtendedVarId(
            $get_debug_type_expr->getArgs()[0]->value,
            $this_class_name,
            $source,
        );

        if ($whichclass_expr instanceof PhpParser\Node\Scalar\String_) {
            $var_type = $whichclass_expr->value;
        } elseif ($whichclass_expr instanceof PhpParser\Node\Expr\ClassConstFetch
            && $whichclass_expr->class instanceof PhpParser\Node\Name
        ) {
            $var_type = ClassLikeAnalyzer::getFQCLNFromNameObject(
                $whichclass_expr->class,
                $source->getAliases(),
            );
        } else {
            throw new UnexpectedValueException('Shouldn’t get here');
        }

        if ($var_name && $var_type) {
            if ($var_type === 'class@anonymous') {
                $if_types[$var_name] = [[new IsIdentical(new TObject())]];
            } elseif ($var_type === 'resource (closed)') {
                $if_types[$var_name] = [[new IsType(new TClosedResource())]];
            } elseif (strpos($var_type, 'resource (') === 0) {
                $if_types[$var_name] = [[new IsIdentical(new TResource())]];
            } elseif ($var_type === 'integer') {
                $if_types[$var_name] = [[new IsType(new Atomic\TInt())]];
            } elseif ($var_type === 'double') {
                $if_types[$var_name] = [[new IsType(new Atomic\TFloat())]];
            } elseif ($var_type === 'boolean') {
                $if_types[$var_name] = [[new IsType(new Atomic\TBool())]];
            } else {
                $if_types[$var_name] = [[new IsType(Atomic::create($var_type))]];
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param PhpParser\Node\Expr\BinaryOp\Identical|PhpParser\Node\Expr\BinaryOp\Equal $conditional
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
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
            throw new UnexpectedValueException('$getclass_position value');
        }

        if ($getclass_expr instanceof PhpParser\Node\Expr\FuncCall && isset($getclass_expr->getArgs()[0])) {
            $var_name = ExpressionIdentifier::getExtendedVarId(
                $getclass_expr->getArgs()[0]->value,
                $this_class_name,
                $source,
            );
        } elseif ($getclass_expr instanceof PhpParser\Node\Expr\ClassConstFetch
            && $getclass_expr->class instanceof PhpParser\Node\Expr
        ) {
            $var_name = ExpressionIdentifier::getExtendedVarId(
                $getclass_expr->class,
                $this_class_name,
                $source,
            );
        } else {
            $var_name = '$this';
        }

        if ($whichclass_expr instanceof PhpParser\Node\Expr\ClassConstFetch
            && $whichclass_expr->class instanceof PhpParser\Node\Name
        ) {
            $var_type = ClassLikeAnalyzer::getFQCLNFromNameObject(
                $whichclass_expr->class,
                $source->getAliases(),
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
                    new ClassLikeNameOptions(true),
                ) === false
                ) {
                    return [];
                }
            }

            if ($var_name && $var_type) {
                $if_types[$var_name] = [[new IsClassEqual($var_type)]];
            }
        } else {
            $type = $source->node_data->getType($whichclass_expr);

            if ($type && $var_name) {
                foreach ($type->getAtomicTypes() as $type_part) {
                    if ($type_part instanceof TTemplateParamClass) {
                        $if_types[$var_name] = [[
                            new IsIdentical(new TTemplateParam(
                                $type_part->param_name,
                                $type_part->as_type
                                    ? new Union([$type_part->as_type])
                                    : Type::getObject(),
                                $type_part->defining_class,
                            )),
                        ]];
                    }
                }
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param PhpParser\Node\Expr\BinaryOp\Identical|PhpParser\Node\Expr\BinaryOp\Equal $conditional
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
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
            $var_name = ExpressionIdentifier::getExtendedVarId(
                $conditional->left,
                $this_class_name,
                $source,
            );

            $other_var_name = ExpressionIdentifier::getExtendedVarId(
                $conditional->right,
                $this_class_name,
                $source,
            );

            $other_type = $source->node_data->getType($conditional->left);
            $var_type = $source->node_data->getType($conditional->right);
        } elseif ($typed_value_position === self::ASSIGNMENT_TO_LEFT) {
            $var_name = ExpressionIdentifier::getExtendedVarId(
                $conditional->right,
                $this_class_name,
                $source,
            );

            $other_var_name = ExpressionIdentifier::getExtendedVarId(
                $conditional->left,
                $this_class_name,
                $source,
            );

            $var_type = $source->node_data->getType($conditional->left);
            $other_type = $source->node_data->getType($conditional->right);
        } else {
            throw new UnexpectedValueException('$typed_value_position value');
        }

        if ($var_name && $var_type) {
            $identical = $conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
                || ($other_type
                    && (($var_type->isString(true) && $other_type->isString(true))
                        || ($var_type->isInt(true) && $other_type->isInt(true))
                        || ($var_type->isFloat() && $other_type->isFloat())
                    )
                );

            if (count($var_type->getAtomicTypes()) === 1) {
                $orred_types = [];

                foreach ($var_type->getAtomicTypes() as $atomic_var_type) {
                    if ($identical) {
                        $orred_types[] = new IsIdentical($atomic_var_type);
                    } else {
                        $orred_types[] = new IsLooselyEqual($atomic_var_type);
                    }
                }

                $if_types[$var_name] = [$orred_types];
            }

            if ($other_var_name
                && $other_type
                && !$other_type->isMixed()
                && count($other_type->getAtomicTypes()) === 1
            ) {
                $orred_types = [];

                foreach ($other_type->getAtomicTypes() as $atomic_other_type) {
                    if ($identical) {
                        $orred_types[] = new IsIdentical($atomic_other_type);
                    } else {
                        $orred_types[] = new IsLooselyEqual($atomic_other_type);
                    }
                }

                $if_types[$other_var_name] = [$orred_types];
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
            self::handleParadoxicalAssertions(
                $source,
                $var_type,
                $this_class_name,
                $other_type,
                $codebase,
                $conditional,
            );
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function getIsaAssertions(
        PhpParser\Node\Expr\FuncCall $expr,
        StatementsAnalyzer $source,
        ?string $this_class_name,
        ?string $first_var_name
    ): array {
        $if_types = [];

        if ($expr->getArgs()[0]->value instanceof PhpParser\Node\Expr\ClassConstFetch
            && $expr->getArgs()[0]->value->name instanceof PhpParser\Node\Identifier
            && strtolower($expr->getArgs()[0]->value->name->name) === 'class'
            && $expr->getArgs()[0]->value->class instanceof PhpParser\Node\Name
            && count($expr->getArgs()[0]->value->class->parts) === 1
            && strtolower($expr->getArgs()[0]->value->class->parts[0]) === 'static'
        ) {
            $first_var_name = '$this';
        }

        if ($first_var_name) {
            $first_arg = $expr->getArgs()[0]->value;
            $second_arg = $expr->getArgs()[1]->value;
            $third_arg = $expr->getArgs()[2]->value ?? null;

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

            if (($first_arg_type = $source->node_data->getType($first_arg))
                && $first_arg_type->isSingleStringLiteral()
                && $source->getSource()->getSource() instanceof TraitAnalyzer
                && $first_arg_type->getSingleStringLiteral()->value === $this_class_name
            ) {
                // do nothing
            } else {
                if ($second_arg instanceof PhpParser\Node\Scalar\String_) {
                    $fq_class_name = $second_arg->value;
                    if ($fq_class_name[0] === '\\') {
                        $fq_class_name = substr($fq_class_name, 1);
                    }

                    $obj = new TNamedObject($fq_class_name);
                    $if_types[$first_var_name] = [[new IsAClass($obj, $third_arg_value === 'true')]];
                } elseif ($second_arg instanceof PhpParser\Node\Expr\ClassConstFetch
                    && $second_arg->class instanceof PhpParser\Node\Name
                    && $second_arg->name instanceof PhpParser\Node\Identifier
                    && strtolower($second_arg->name->name) === 'class'
                ) {
                    $class_node = $second_arg->class;

                    if ($class_node->parts === ['static']) {
                        if ($this_class_name) {
                            $object = new TNamedObject($this_class_name, true);

                            $if_types[$first_var_name] = [[new IsAClass($object, $third_arg_value === 'true')]];
                        }
                    } elseif ($class_node->parts === ['self']) {
                        if ($this_class_name) {
                            $object = new TNamedObject($this_class_name);
                            $if_types[$first_var_name] = [[new IsAClass($object, $third_arg_value === 'true')]];
                        }
                    } elseif ($class_node->parts === ['parent']) {
                        // do nothing
                    } else {
                        $object = new TNamedObject(
                            ClassLikeAnalyzer::getFQCLNFromNameObject(
                                $class_node,
                                $source->getAliases(),
                            ),
                        );
                        $if_types[$first_var_name] = [[new IsAClass($object, $third_arg_value === 'true')]];
                    }
                } elseif (($second_arg_type = $source->node_data->getType($second_arg))
                    && $second_arg_type->hasString()
                ) {
                    $vals = [];

                    foreach ($second_arg_type->getAtomicTypes() as $second_arg_atomic_type) {
                        if ($second_arg_atomic_type instanceof TTemplateParamClass) {
                            $vals[] = [new IsAClass($second_arg_atomic_type, $third_arg_value === 'true')];
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
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function getInarrayAssertions(
        PhpParser\Node\Expr\FuncCall $expr,
        StatementsAnalyzer $source,
        ?string $first_var_name
    ): array {
        $if_types = [];

        if ($first_var_name
            && ($second_arg_type = $source->node_data->getType($expr->getArgs()[1]->value))
            && isset($expr->getArgs()[0]->value)
            && !$expr->getArgs()[0]->value instanceof PhpParser\Node\Expr\ClassConstFetch
        ) {
            foreach ($second_arg_type->getAtomicTypes() as $atomic_type) {
                if ($atomic_type instanceof TList) {
                    $atomic_type = $atomic_type->getKeyedArray();
                }
                if ($atomic_type instanceof TArray
                    || $atomic_type instanceof TKeyedArray
                ) {
                    $is_sealed = false;
                    if ($atomic_type instanceof TKeyedArray) {
                        $value_type = $atomic_type->getGenericValueType();
                        $is_sealed = $atomic_type->fallback_params === null;
                    } else {
                        $value_type = $atomic_type->type_params[1];
                    }

                    $assertions = [];

                    if (!$is_sealed) {
                        // `in-array-*` has special handling in the detection of paradoxical
                        // conditions and the fact the negation doesn't imply anything.
                        //
                        // In the vast majority of cases, the negation of `in-array-*`
                        // (`Algebra::negateType`) doesn't imply anything because:
                        // - The array can be empty, or
                        // - The array may have one of the types but not the others.
                        //
                        // NOTE: the negation of the negation is the original assertion.
                        if ($value_type->getId() !== '' && !$value_type->isMixed() && !$value_type->hasTemplate()) {
                            $assertions[] = new InArray($value_type);
                        }
                    } else {
                        foreach ($value_type->getAtomicTypes() as $atomic_value_type) {
                            if ($atomic_value_type instanceof TLiteralInt
                                || $atomic_value_type instanceof TLiteralString
                                || $atomic_value_type instanceof TLiteralFloat
                                || $atomic_value_type instanceof TEnumCase
                            ) {
                                $assertions[] = new IsIdentical($atomic_value_type);
                            } elseif ($atomic_value_type instanceof TFalse
                                || $atomic_value_type instanceof TTrue
                                || $atomic_value_type instanceof TNull
                            ) {
                                $assertions[] = new IsType($atomic_value_type);
                            } elseif (!$atomic_value_type instanceof TMixed) {
                                // mixed doesn't tell us anything and can be omitted.
                                //
                                // For the meaning of in-array, see the above comment.
                                $assertions[] = new InArray($value_type);
                            }
                        }
                    }

                    if ($assertions !== []) {
                        $if_types[$first_var_name] = [$assertions];
                    }
                }
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function getArrayKeyExistsAssertions(
        PhpParser\Node\Expr\FuncCall $expr,
        ?Union $first_var_type,
        ?string $first_var_name,
        FileSource $source,
        ?string $this_class_name
    ): array {
        $if_types = [];

        $literal_assertions = [];

        $safe_to_track_literals = true;

        if (isset($expr->getArgs()[0])
            && isset($expr->getArgs()[1])
            && $first_var_type
            && $first_var_name
            && !$expr->getArgs()[0]->value instanceof PhpParser\Node\Expr\ClassConstFetch
            && $source instanceof StatementsAnalyzer
            && ($second_var_type = $source->node_data->getType($expr->getArgs()[1]->value))
        ) {
            foreach ($second_var_type->getAtomicTypes() as $atomic_type) {
                if ($atomic_type instanceof TList) {
                    $atomic_type = $atomic_type->getKeyedArray();
                }

                if ($atomic_type instanceof TArray
                    || $atomic_type instanceof TKeyedArray
                ) {
                    if ($atomic_type instanceof TKeyedArray) {
                        $key_type = $atomic_type->getGenericKeyType(
                            !$atomic_type->allShapeKeysAlwaysDefined(),
                        );
                    } else {
                        $key_type = $atomic_type->type_params[0];
                    }

                    if ($key_type->allStringLiterals() && !$key_type->possibly_undefined) {
                        foreach ($key_type->getLiteralStrings() as $array_literal_type) {
                            $literal_assertions[] = new IsIdentical($array_literal_type);
                        }
                    } elseif ($key_type->allIntLiterals() && !$key_type->possibly_undefined) {
                        foreach ($key_type->getLiteralInts() as $array_literal_type) {
                            $literal_assertions[] = new IsLooselyEqual($array_literal_type);
                        }
                    } else {
                        $safe_to_track_literals = false;
                    }
                }
            }
        }

        if ($literal_assertions && $first_var_name && $safe_to_track_literals) {
            $if_types[$first_var_name] = [$literal_assertions];
        } else {
            $array_root = isset($expr->getArgs()[1]->value)
                ? ExpressionIdentifier::getExtendedVarId(
                    $expr->getArgs()[1]->value,
                    $this_class_name,
                    $source,
                )
                : null;

            if ($array_root && isset($expr->getArgs()[0])) {
                if ($first_var_name === null) {
                    $first_arg = $expr->getArgs()[0];

                    if ($first_arg->value instanceof PhpParser\Node\Scalar\String_) {
                        $first_var_name = '\'' . $first_arg->value->value . '\'';
                    } elseif ($first_arg->value instanceof PhpParser\Node\Scalar\LNumber) {
                        $first_var_name = (string)$first_arg->value->value;
                    }
                }

                if ($expr->getArgs()[0]->value instanceof PhpParser\Node\Expr\ClassConstFetch
                    && $expr->getArgs()[0]->value->name instanceof PhpParser\Node\Identifier
                    && $expr->getArgs()[0]->value->name->name !== 'class'
                ) {
                    $const_type = null;

                    if ($source instanceof StatementsAnalyzer) {
                        $const_type = $source->node_data->getType($expr->getArgs()[0]->value);
                    }

                    if ($const_type) {
                        if ($const_type->isSingleStringLiteral()) {
                            $first_var_name = '\''.$const_type->getSingleStringLiteral()->value.'\'';
                        } elseif ($const_type->isSingleIntLiteral()) {
                            $first_var_name = (string)$const_type->getSingleIntLiteral()->value;
                        } else {
                            $first_var_name = null;
                        }
                    } else {
                        $first_var_name = null;
                    }
                } elseif (($expr->getArgs()[0]->value instanceof PhpParser\Node\Expr\Variable
                        || $expr->getArgs()[0]->value instanceof PhpParser\Node\Expr\PropertyFetch
                        || $expr->getArgs()[0]->value instanceof PhpParser\Node\Expr\StaticPropertyFetch
                    )
                    && $source instanceof StatementsAnalyzer
                    && ($first_var_type = $source->node_data->getType($expr->getArgs()[0]->value))
                ) {
                    foreach ($first_var_type->getLiteralStrings() as $array_literal_type) {
                        $if_types[$array_root . "['" . $array_literal_type->value . "']"] = [[new ArrayKeyExists()]];
                    }
                    foreach ($first_var_type->getLiteralInts() as $array_literal_type) {
                        $if_types[$array_root . "[" . $array_literal_type->value . "]"] = [[new ArrayKeyExists()]];
                    }
                }

                if ($first_var_name !== null
                    && !strpos($first_var_name, '->')
                    && !strpos($first_var_name, '[')
                ) {
                    $if_types[$array_root . '[' . $first_var_name . ']'] = [[new ArrayKeyExists()]];
                }
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param PhpParser\Node\Expr\BinaryOp\Greater|PhpParser\Node\Expr\BinaryOp\GreaterOrEqual $conditional
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function getGreaterAssertions(
        PhpParser\Node\Expr $conditional,
        FileSource $source,
        ?string $this_class_name
    ): array {
        $if_types = [];

        $min_count = null;
        $count_equality_position = self::hasNonEmptyCountEqualityCheck($conditional, $min_count);
        $max_count = null;
        $count_inequality_position = self::hasLessThanCountEqualityCheck($conditional, $max_count);
        $superior_value_comparison = null;
        $superior_value_position = self::hasSuperiorNumberCheck(
            $source,
            $conditional,
            $superior_value_comparison,
        );

        if ($count_equality_position) {
            if ($count_equality_position === self::ASSIGNMENT_TO_RIGHT) {
                $counted_expr = $conditional->left;
            } else {
                throw new UnexpectedValueException('$count_equality_position value');
            }

            /** @var PhpParser\Node\Expr\FuncCall $counted_expr */
            $var_name = ExpressionIdentifier::getExtendedVarId(
                $counted_expr->getArgs()[0]->value,
                $this_class_name,
                $source,
            );

            if ($var_name) {
                if (self::hasReconcilableNonEmptyCountEqualityCheck($conditional)) {
                    $if_types[$var_name] = [[new NonEmptyCountable(true)]];
                } else {
                    if ($min_count > 0) {
                        $if_types[$var_name] = [[new HasAtLeastCount($min_count)]];
                    } else {
                        $if_types[$var_name] = [[new NonEmptyCountable(false)]];
                    }
                }
            }

            return $if_types ? [$if_types] : [];
        }

        if ($count_inequality_position) {
            if ($count_inequality_position === self::ASSIGNMENT_TO_LEFT) {
                $count_expr = $conditional->right;
            } else {
                throw new UnexpectedValueException('$count_inequality_position value');
            }

            /** @var PhpParser\Node\Expr\FuncCall $count_expr */
            $var_name = ExpressionIdentifier::getExtendedVarId(
                $count_expr->getArgs()[0]->value,
                $this_class_name,
                $source,
            );

            if ($var_name) {
                if ($max_count > 0) {
                    $if_types[$var_name] = [[new DoesNotHaveAtLeastCount($max_count + 1)]];
                } else {
                    $if_types[$var_name] = [[new NotNonEmptyCountable()]];
                }
            }

            return $if_types ? [$if_types] : [];
        }

        if ($superior_value_position && $superior_value_comparison !== null) {
            if ($superior_value_position === self::ASSIGNMENT_TO_RIGHT) {
                $var_name = ExpressionIdentifier::getExtendedVarId(
                    $conditional->left,
                    $this_class_name,
                    $source,
                );
            } else {
                $var_name = ExpressionIdentifier::getExtendedVarId(
                    $conditional->right,
                    $this_class_name,
                    $source,
                );
            }

            if ($var_name !== null) {
                if ($superior_value_position === self::ASSIGNMENT_TO_RIGHT) {
                    if ($conditional instanceof GreaterOrEqual) {
                        $if_types[$var_name] = [[new IsGreaterThanOrEqualTo($superior_value_comparison)]];
                    } else {
                        $if_types[$var_name] = [[new IsGreaterThan($superior_value_comparison)]];
                    }
                } else {
                    if ($conditional instanceof GreaterOrEqual) {
                        $if_types[$var_name] = [[new IsLessThanOrEqualTo($superior_value_comparison)]];
                    } else {
                        $if_types[$var_name] = [[new IsLessThan($superior_value_comparison)]];
                    }
                }
            }

            return $if_types ? [$if_types] : [];
        }

        return [];
    }

    /**
     * @param PhpParser\Node\Expr\BinaryOp\Smaller|PhpParser\Node\Expr\BinaryOp\SmallerOrEqual $conditional
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function getSmallerAssertions(
        PhpParser\Node\Expr $conditional,
        FileSource $source,
        ?string $this_class_name
    ): array {
        $if_types = [];
        $min_count = null;
        $count_equality_position = self::hasNonEmptyCountEqualityCheck($conditional, $min_count);
        $max_count = null;
        $count_inequality_position = self::hasLessThanCountEqualityCheck($conditional, $max_count);
        $inferior_value_comparison = null;
        $inferior_value_position = self::hasInferiorNumberCheck(
            $source,
            $conditional,
            $inferior_value_comparison,
        );

        if ($count_equality_position) {
            if ($count_equality_position === self::ASSIGNMENT_TO_LEFT) {
                $count_expr = $conditional->right;
            } else {
                throw new UnexpectedValueException('$count_equality_position value');
            }

            /** @var PhpParser\Node\Expr\FuncCall $count_expr */
            $var_name = ExpressionIdentifier::getExtendedVarId(
                $count_expr->getArgs()[0]->value,
                $this_class_name,
                $source,
            );

            if ($var_name) {
                if ($min_count > 0) {
                    $if_types[$var_name] = [[new HasAtLeastCount($min_count)]];
                } else {
                    $if_types[$var_name] = [[new NonEmptyCountable(false)]];
                }
            }

            return $if_types ? [$if_types] : [];
        }

        if ($count_inequality_position) {
            if ($count_inequality_position === self::ASSIGNMENT_TO_RIGHT) {
                $count_expr = $conditional->left;
            } else {
                throw new UnexpectedValueException('$count_inequality_position value');
            }

            /** @var PhpParser\Node\Expr\FuncCall $count_expr */
            $var_name = ExpressionIdentifier::getExtendedVarId(
                $count_expr->getArgs()[0]->value,
                $this_class_name,
                $source,
            );

            if ($var_name) {
                if ($max_count > 0) {
                    $if_types[$var_name] = [[new DoesNotHaveAtLeastCount($max_count + 1)]];
                } else {
                    $if_types[$var_name] = [[new NotNonEmptyCountable()]];
                }
            }

            return $if_types ? [$if_types] : [];
        }

        if ($inferior_value_position) {
            if ($inferior_value_position === self::ASSIGNMENT_TO_RIGHT) {
                $var_name = ExpressionIdentifier::getExtendedVarId(
                    $conditional->left,
                    $this_class_name,
                    $source,
                );
            } else {
                $var_name = ExpressionIdentifier::getExtendedVarId(
                    $conditional->right,
                    $this_class_name,
                    $source,
                );
            }


            if ($var_name !== null && $inferior_value_comparison !== null) {
                if ($inferior_value_position === self::ASSIGNMENT_TO_RIGHT) {
                    if ($conditional instanceof SmallerOrEqual) {
                        $if_types[$var_name] = [[new IsLessThanOrEqualTo($inferior_value_comparison)]];
                    } else {
                        $if_types[$var_name] = [[new IsLessThan($inferior_value_comparison)]];
                    }
                } else {
                    if ($conditional instanceof SmallerOrEqual) {
                        $if_types[$var_name] = [[new IsGreaterThanOrEqualTo($inferior_value_comparison)]];
                    } else {
                        $if_types[$var_name] = [[new IsGreaterThan($inferior_value_comparison)]];
                    }
                }
            }

            return $if_types ? [$if_types] : [];
        }

        return [];
    }

    /**
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function getAndCheckInstanceofAssertions(
        PhpParser\Node\Expr\Instanceof_ $conditional,
        ?Codebase $codebase,
        FileSource $source,
        ?string $this_class_name,
        bool $inside_negation
    ): array {
        $if_types = [];

        $instanceof_assertions = self::getInstanceOfAssertions($conditional, $this_class_name, $source);

        if ($instanceof_assertions) {
            $var_name = ExpressionIdentifier::getExtendedVarId(
                $conditional->expr,
                $this_class_name,
                $source,
            );

            if ($var_name) {
                $if_types[$var_name] = [$instanceof_assertions];

                $var_type = $source instanceof StatementsAnalyzer
                    ? $source->node_data->getType($conditional->expr)
                    : null;

                foreach ($instanceof_assertions as $instanceof_assertion) {
                    $instanceof_type = $instanceof_assertion->getAtomicType();

                    if (!$instanceof_type instanceof TNamedObject) {
                        continue;
                    }

                    if ($codebase
                        && $var_type
                        && $inside_negation
                        && $source instanceof StatementsAnalyzer
                    ) {
                        if ($codebase->interfaceExists($instanceof_type->value)) {
                            continue;
                        }

                        $instanceof_type = new Union([$instanceof_type]);

                        if (!UnionTypeComparator::canExpressionTypesBeIdentical(
                            $codebase,
                            $instanceof_type,
                            $var_type,
                        )) {
                            if ($var_type->from_docblock) {
                                IssueBuffer::maybeAdd(
                                    new RedundantConditionGivenDocblockType(
                                        $var_type->getId() . ' does not contain '
                                        . $instanceof_type->getId(),
                                        new CodeLocation($source, $conditional),
                                        $var_type->getId() . ' ' . $instanceof_type->getId(),
                                    ),
                                    $source->getSuppressedIssues(),
                                );
                            } else {
                                IssueBuffer::maybeAdd(
                                    new RedundantCondition(
                                        $var_type->getId() . ' cannot be identical to '
                                        . $instanceof_type->getId(),
                                        new CodeLocation($source, $conditional),
                                        $var_type->getId() . ' ' . $instanceof_type->getId(),
                                    ),
                                    $source->getSuppressedIssues(),
                                );
                            }
                        }
                    }
                }
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param NotIdentical|NotEqual|Identical|Equal $conditional
     */
    private static function handleParadoxicalAssertions(
        StatementsAnalyzer $source,
        Union $var_type,
        ?string $this_class_name,
        Union $other_type,
        Codebase $codebase,
        PhpParser\Node\Expr\BinaryOp $conditional
    ): void {
        $parent_source = $source->getSource();

        if ($parent_source->getSource() instanceof TraitAnalyzer
            && (($var_type->isSingleStringLiteral()
                    && $var_type->getSingleStringLiteral()->value === $this_class_name)
                || ($other_type->isSingleStringLiteral()
                    && $other_type->getSingleStringLiteral()->value === $this_class_name))
        ) {
            // do nothing
        } elseif (!UnionTypeComparator::canExpressionTypesBeIdentical(
            $codebase,
            $other_type,
            $var_type,
        )) {
            if ($var_type->from_docblock || $other_type->from_docblock) {
                IssueBuffer::maybeAdd(
                    new DocblockTypeContradiction(
                        $var_type->getId() . ' does not contain ' . $other_type->getId(),
                        new CodeLocation($source, $conditional),
                        $var_type->getId() . ' ' . $other_type->getId(),
                    ),
                    $source->getSuppressedIssues(),
                );
            } else {
                if ($conditional instanceof NotEqual || $conditional instanceof NotIdentical) {
                    IssueBuffer::maybeAdd(
                        new RedundantCondition(
                            $var_type->getId() . ' can never contain ' . $other_type->getId(),
                            new CodeLocation($source, $conditional),
                            $var_type->getId() . ' ' . $other_type->getId(),
                        ),
                        $source->getSuppressedIssues(),
                    );
                } else {
                    IssueBuffer::maybeAdd(
                        new TypeDoesNotContainType(
                            $var_type->getId() . ' cannot be identical to ' . $other_type->getId(),
                            new CodeLocation($source, $conditional),
                            $var_type->getId() . ' ' . $other_type->getId(),
                        ),
                        $source->getSuppressedIssues(),
                    );
                }
            }
        }
    }

    public static function isPropertyImmutableOnArgument(
        string                       $property,
        NodeDataProvider             $node_provider,
        ClassLikeStorageProvider     $class_provider,
        PhpParser\Node\Expr\Variable $arg_expr
    ): ?string {
        $type = $node_provider->getType($arg_expr);
        /** @var string $name */
        $name = $arg_expr->name;

        if (null === $type) {
            return 'Cannot resolve a type of variable ' . $name;
        }

        foreach ($type->getAtomicTypes() as $type) {
            if (!$type instanceof TNamedObject) {
                return 'Variable ' . $name . ' is not an object so the assertion cannot be applied';
            }

            $class_definition = $class_provider->get($type->value);
            $property_definition = $class_definition->properties[$property] ?? null;

            if (!$property_definition instanceof PropertyStorage) {
                $magic_type = $class_definition->pseudo_property_get_types['$' . $property] ?? null;
                if ($magic_type === null) {
                    return sprintf(
                        'Property %s is not defined on variable %s so the assertion cannot be applied',
                        $property,
                        $name,
                    );
                }

                $magic_getter = $class_definition->methods['__get'] ?? null;
                if ($magic_getter === null || !$magic_getter->mutation_free) {
                    return "{$class_definition->name}::__get is not mutation-free, so the assertion cannot be applied";
                }
            }
        }

        return null;
    }
}
