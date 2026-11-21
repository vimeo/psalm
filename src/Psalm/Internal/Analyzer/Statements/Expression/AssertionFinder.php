<?php

declare(strict_types=1);

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
use PhpParser\Node\Scalar\Int_;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Exception\TypeParseTreeException;
use Psalm\FileSource;
use Psalm\Internal\Algebra;
use Psalm\Internal\Algebra\FormulaGenerator;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\ClassLikeNameOptions;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ArrayFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ConstFetchAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Codebase\StringContext;
use Psalm\Internal\Codebase\TaintValidations;
use Psalm\Internal\Codebase\UrlHosts;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\PatternCharacters;
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
use Psalm\Node\Expr\VirtualArrayDimFetch;
use Psalm\Storage\Assertion;
use Psalm\Storage\Assertion\Any;
use Psalm\Storage\Assertion\ArrayKeyExists;
use Psalm\Storage\Assertion\DoesNotHaveAtLeastCount;
use Psalm\Storage\Assertion\Empty_;
use Psalm\Storage\Assertion\Falsy;
use Psalm\Storage\Assertion\HasAtLeastCount;
use Psalm\Storage\Assertion\HasExactCount;
use Psalm\Storage\Assertion\HasMethod;
use Psalm\Storage\Assertion\InArray;
use Psalm\Storage\Assertion\IsAClass;
use Psalm\Storage\Assertion\IsClassEqual;
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
use Psalm\Storage\Assertion\IsNotType;
use Psalm\Storage\Assertion\IsType;
use Psalm\Storage\Assertion\MatchesPattern;
use Psalm\Storage\Assertion\NestedAssertions;
use Psalm\Storage\Assertion\NonEmptyCountable;
use Psalm\Storage\Assertion\NotNonEmptyCountable;
use Psalm\Storage\Assertion\Truthy;
use Psalm\Storage\Possibilities;
use Psalm\Storage\PropertyStorage;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TClassStringMap;
use Psalm\Type\Atomic\TClosedResource;
use Psalm\Type\Atomic\TEnumCase;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateKeyOf;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Atomic\TTemplateValueOf;
use Psalm\Type\Atomic\TTraitString;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\TaintKind;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_all;
use function array_map;
use function array_push;
use function assert;
use function count;
use function explode;
use function in_array;
use function is_int;
use function is_numeric;
use function is_string;
use function ltrim;
use function preg_match;
use function sprintf;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function strpos;
use function strtolower;
use function substr;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * @internal
 * This class transform conditions in code into "assertions" that will be reconciled with the type already known of a
 * given variable to narrow the type or find paradox.
 * For example if $a is an int, if($a > 0) will be turned into an assertion to make psalm understand that in the
 * if block, $a is a positive-int
 */
final class AssertionFinder
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
        bool $inside_conditional = true,
    ): array {
        // what the result of a validation tells of the value it validated, which the negation of the condition must
        // tell alone: it would be one of several possibilities next to what the condition says of the result
        if ($source instanceof StatementsAnalyzer
            && ($validation = TaintValidations::getAssertions($source, $conditional)) !== []
        ) {
            return $validation;
        }

        return self::scrapeAssertionsOfConditional(
            $conditional,
            $this_class_name,
            $source,
            $codebase,
            $inside_negation,
            $cache,
            $inside_conditional,
        );
    }

    /**
     * Gets all the type assertions in a conditional
     *
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function scrapeAssertionsOfConditional(
        PhpParser\Node\Expr $conditional,
        ?string $this_class_name,
        FileSource $source,
        ?Codebase $codebase = null,
        bool $inside_negation = false,
        bool $cache = true,
        bool $inside_conditional = true,
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

        if ($conditional instanceof PhpParser\Node\Expr\Assign
            || $conditional instanceof PhpParser\Node\Expr\AssignRef
        ) {
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
        } elseif ($conditional instanceof PhpParser\Node\Expr\NullsafePropertyFetch) {
            $if_types = self::getNullsafeAssertions($conditional, new Truthy(), $this_class_name, $source);

            if ($if_types) {
                return [$if_types];
            }
        }

        if ($conditional instanceof PhpParser\Node\Expr\BooleanNot) {
            return [];
        }

        $string_length_assertions = self::getStringLengthAssertions($conditional, $this_class_name, $source);
        if ($string_length_assertions) {
            return $string_length_assertions;
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
        ) {
            return self::scrapeComparisonAssertions(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $cache,
                $inside_conditional,
                $conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical
                    || $conditional instanceof PhpParser\Node\Expr\BinaryOp\NotEqual,
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
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Smaller
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\SmallerOrEqual
        ) {
            return self::getOrderComparisonAssertions(
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
     * @param Identical|Equal|NotIdentical|NotEqual $conditional
     * @param bool $negated whether the comparison is `!==` or `!=`
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function scrapeComparisonAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        FileSource $source,
        ?Codebase $codebase,
        bool $cache,
        bool $inside_conditional,
        bool $negated,
    ): array {
        if ($source instanceof StatementsAnalyzer
            && ($pattern_match = self::getPatternMatchComparedToOne($conditional))
        ) {
            return self::getPatternMatchAssertions($pattern_match, $source, $this_class_name, $negated);
        }

        $null_position = self::hasNullVariable($conditional, $source);

        if ($null_position !== null) {
            return self::getNullComparisonAssertions(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $null_position,
                $negated,
            );
        }

        $false_position = self::hasFalseVariable($conditional);

        if ($false_position) {
            return self::getBoolComparisonAssertions(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $false_position,
                false,
                $negated,
                $cache,
                $inside_conditional,
            );
        }

        $true_position = self::hasTrueVariable($conditional);

        if ($true_position) {
            return self::getBoolComparisonAssertions(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $true_position,
                true,
                $negated,
                $cache,
                $inside_conditional,
            );
        }

        $empty_array_position = self::hasEmptyArrayVariable($conditional);

        if ($empty_array_position !== null) {
            return self::getEmptyArrayComparisonAssertions(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $empty_array_position,
                $negated,
            );
        }

        $gettype_position = self::hasGetTypeCheck($conditional, $source);

        if ($gettype_position) {
            return self::getGettypeComparisonAssertions(
                $conditional,
                $this_class_name,
                $source,
                $gettype_position,
                $negated,
            );
        }

        $get_debug_type_position = self::hasGetDebugTypeCheck($conditional, $source);

        if ($get_debug_type_position) {
            return self::getGetdebugtypeComparisonAssertions(
                $conditional,
                $this_class_name,
                $source,
                $get_debug_type_position,
                $negated,
            );
        }

        $count = null;
        $count_equality_position = self::hasCountEqualityCheck($conditional, $source, $count);

        if ($count_equality_position) {
            $if_types = [];

            [$count_expr] = self::getOperands($conditional, $count_equality_position);

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
                    && self::isStrictComparison($conditional)
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
                $if_types[$var_name] = [[self::withPolarity(
                    $count > 0 ? new HasExactCount($count) : new NotNonEmptyCountable(),
                    $negated,
                )]];
            }

            return $if_types ? [$if_types] : [];
        }

        if (!$source instanceof StatementsAnalyzer) {
            return [];
        }

        $getclass_position = self::hasGetClassCheck($conditional, $source);

        if ($getclass_position) {
            return self::getGetclassComparisonAssertions(
                $conditional,
                $this_class_name,
                $source,
                $getclass_position,
                $negated,
            );
        }

        $typed_value_position = self::hasTypedValueComparison($conditional, $source);

        if ($typed_value_position) {
            return self::getTypedValueComparisonAssertions(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $typed_value_position,
                $negated,
            );
        }

        return self::getOperandTypeComparisonAssertions($conditional, $this_class_name, $source, $codebase);
    }

    /**
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function getOperandTypeComparisonAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        StatementsAnalyzer $source,
        ?Codebase $codebase,
    ): array {
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
                $intersection_type = Type::intersectUnionTypes($var_type, $other_type, $codebase, false, false);

                if ($intersection_type !== null) {
                    $if_types = [];

                    $var_name_left = ExpressionIdentifier::getExtendedVarId(
                        $conditional->left,
                        $this_class_name,
                        $source,
                    );

                    $var_name_right = ExpressionIdentifier::getExtendedVarId(
                        $conditional->right,
                        $this_class_name,
                        $source,
                    );

                    if ($var_name_left && $var_type->getId() !== $intersection_type->getId()) {
                        $if_types[$var_name_left] = [self::getIdenticalAssertions(
                            $intersection_type,
                            $var_name_right ?? self::getOperandId($conditional->right, $this_class_name, $source),
                        )];
                    }

                    if ($var_name_right && $other_type->getId() !== $intersection_type->getId()) {
                        $if_types[$var_name_right] = [self::getIdenticalAssertions(
                            $intersection_type,
                            $var_name_left ?? self::getOperandId($conditional->left, $this_class_name, $source),
                        )];
                    }

                    return $if_types ? [$if_types] : [];
                }
            }
        }

        if ($codebase
            && $var_type
            && $other_type
            && $conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical
            && UnionTypeComparator::canExpressionTypesBeIdentical($codebase, $var_type, $other_type)
        ) {
            // where they are not not identical, both sides have the intersection of both
            $intersection_type = Type::intersectUnionTypes($var_type, $other_type, $codebase, false, false);

            if ($intersection_type !== null) {
                $if_types = [];

                $left_id = self::getOperandId($conditional->left, $this_class_name, $source);
                $right_id = self::getOperandId($conditional->right, $this_class_name, $source);

                foreach ([
                    [$conditional->left, $var_type, $right_id],
                    [$conditional->right, $other_type, $left_id],
                ] as [$operand, $type, $comparand]) {
                    $operand_name = ExpressionIdentifier::getExtendedVarId(
                        $operand,
                        $this_class_name,
                        $source,
                    );

                    if ($operand_name === null || $type->getId() === $intersection_type->getId()) {
                        continue;
                    }

                    $anded_types = [];

                    foreach ($intersection_type->getAtomicTypes() as $atomic_type) {
                        $anded_types[] = [new IsNotIdentical($atomic_type, $comparand, true)];
                    }

                    $if_types[$operand_name] = $anded_types;
                }

                return $if_types ? [$if_types] : [];
            }
        }

        return [];
    }

    /**
     * @psalm-pure
     */
    private static function isStrictComparison(PhpParser\Node\Expr\BinaryOp $conditional): bool
    {
        return $conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical;
    }

    /**
     * @psalm-pure
     */
    private static function withPolarity(Assertion $assertion, bool $negated): Assertion
    {
        return $negated ? $assertion->getNegation() : $assertion;
    }

    /**
     * @psalm-pure
     */
    private static function isNonNumericStringLiteral(Union $type): bool
    {
        return $type->isSingleStringLiteral() && !is_numeric($type->getSingleStringLiteral()->value);
    }

    /**
     * @param self::ASSIGNMENT_TO_RIGHT|self::ASSIGNMENT_TO_LEFT|int $position
     * @return array{PhpParser\Node\Expr, PhpParser\Node\Expr} the operand on the other side of the one found at
     *     the position, then that one
     * @psalm-capabilities read-props
     */
    private static function getOperands(PhpParser\Node\Expr\BinaryOp $conditional, int $position): array
    {
        if ($position === self::ASSIGNMENT_TO_RIGHT) {
            return [$conditional->left, $conditional->right];
        }

        if ($position === self::ASSIGNMENT_TO_LEFT) {
            return [$conditional->right, $conditional->left];
        }

        throw new UnexpectedValueException('Bad operand position');
    }

    /**
     * Reports the comparison of an operand with a value it can never be: impossible for `===`, redundant for `!==`
     */
    private static function reportNeverIdenticalValue(
        StatementsAnalyzer $source,
        Codebase $codebase,
        PhpParser\Node\Expr\BinaryOp $conditional,
        Union $var_type,
        Union $value_type,
        string $value_name,
        bool $negated,
    ): void {
        if (UnionTypeComparator::canExpressionTypesBeIdentical($codebase, $value_type, $var_type)) {
            return;
        }

        $code_location = new CodeLocation($source, $conditional);
        // the key of the issue the reconciler reports for the same comparison, so that only one is shown
        $dupe_key = !$negated && !$var_type->from_docblock && $value_type->isNull()
            ? $var_type->getId()
            : $var_type->getId() . ' ' . $value_type->getId();

        if ($negated) {
            $issue = $var_type->from_docblock
                ? new RedundantConditionGivenDocblockType(
                    'Docblock-defined type ' . $var_type->getId() . ' can never be ' . $value_name,
                    $code_location,
                    $dupe_key,
                )
                : new RedundantCondition(
                    $var_type->getId() . ' can never be ' . $value_name,
                    $code_location,
                    $dupe_key,
                );
        } elseif ($var_type->from_docblock) {
            $issue = new DocblockTypeContradiction(
                $var_type->getId() . ' does not contain ' . $value_name,
                $code_location,
                $dupe_key,
            );
        } elseif ($value_type->isNull()) {
            $issue = new TypeDoesNotContainNull(
                $var_type->getId() . ' does not contain ' . $value_name,
                $code_location,
                $dupe_key,
            );
        } else {
            $issue = new TypeDoesNotContainType(
                $var_type->getId() . ' does not contain ' . $value_name,
                $code_location,
                $dupe_key,
            );
        }

        IssueBuffer::maybeAdd($issue, $source->getSuppressedIssues());
    }

    /**
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    public static function processFunctionCall(
        PhpParser\Node\Expr\FuncCall $expr,
        ?string $this_class_name,
        FileSource $source,
        ?Codebase $codebase = null,
        bool $negate = false,
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

        $function_id = self::getCalledFunctionId($expr, $source);

        if ($tmp_if_types = self::handleIsTypeCheck(
            $codebase,
            $source,
            $function_id,
            $first_var_name,
            $first_var_type,
            $expr,
            $negate,
        )) {
            $if_types = $tmp_if_types;
        } elseif ($source instanceof StatementsAnalyzer && self::hasIsACheck($expr, $function_id, $source)) {
            return self::getIsaAssertions($expr, $function_id, $source, $this_class_name, $first_var_name);
        } elseif ($function_id === 'is_callable') {
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
        } elseif ($function_id === 'class_exists') {
            if ($first_var_name) {
                $class_string_type = new TClassString('object', null, self::isLoadedExistsCheck($expr));
                $if_types[$first_var_name] = [[new IsType($class_string_type)]];
            }
        } elseif ($function_id === 'trait_exists') {
            if ($first_var_name) {
                $trait_string_type = new TTraitString(is_loaded: self::isLoadedExistsCheck($expr));
                $if_types[$first_var_name] = [[new IsType($trait_string_type)]];
            }
        } elseif ($function_id === 'enum_exists') {
            if ($first_var_name) {
                $class_string = new TClassString('object', null, false, false, true);
                $if_types[$first_var_name] = [[new IsType($class_string)]];
            }
        } elseif ($function_id === 'interface_exists') {
            if ($first_var_name) {
                $class_string = new TClassString('object', null, false, true, false);
                $if_types[$first_var_name] = [[new IsType($class_string)]];
            }
        } elseif ($function_id === 'function_exists') {
            if ($first_var_name) {
                $if_types[$first_var_name] = [[new IsType(new TCallableString())]];
            }
        } elseif ($function_id === 'method_exists'
            && isset($expr->getArgs()[1])
            && $expr->getArgs()[1]->value instanceof PhpParser\Node\Scalar\String_
        ) {
            if ($first_var_name) {
                $if_types[$first_var_name] = [[new HasMethod($expr->getArgs()[1]->value->value)]];
            }
        } elseif ($function_id === 'in_array'
            && self::hasTrueThirdArg($expr)
            && $source instanceof StatementsAnalyzer
            && isset($expr->getArgs()[1])
        ) {
            return self::getInarrayAssertions(
                $expr,
                $source,
                $first_var_name ?? self::getStringCastNeedleVarId($expr, $source, $this_class_name),
                self::getOperandId($expr->getArgs()[1]->value, $this_class_name, $source),
            );
        } elseif ($function_id === 'array_key_exists' || $function_id === 'key_exists') {
            return self::getArrayKeyExistsAssertions(
                $expr,
                $first_var_type,
                $first_var_name,
                $source,
                $this_class_name,
                $codebase && $codebase->literal_array_key_check,
            );
        } elseif ($function_id === 'count' || $function_id === 'sizeof') {
            if ($first_var_name) {
                $if_types[$first_var_name] = [[new NonEmptyCountable(true)]];
            }
        } elseif ($function_id === 'defined'
            && isset($expr->getArgs()[0])
            && $expr->getArgs()[0]->value instanceof PhpParser\Node\Scalar\String_
            && ConstFetchAnalyzer::isConstVarId($expr->getArgs()[0]->value->value)
        ) {
            // says nothing about the type of the constant, but puts it into the clause, so that
            // `!defined('X') || !is_string(X)` narrows X where it is false
            $if_types[$expr->getArgs()[0]->value->value] = [[new Any()]];
        } elseif ($source instanceof StatementsAnalyzer
            && $function_id === 'preg_match'
            && isset($expr->getArgs()[1])
        ) {
            return self::getPatternMatchAssertions($expr, $source, $this_class_name);
        } elseif ($source instanceof StatementsAnalyzer
            && $function_id === 'str_starts_with'
            && isset($expr->getArgs()[1])
        ) {
            return [
                ...self::processCustomAssertion($expr, $this_class_name, $source),
                ...self::getUrlStartAssertions($expr, $source, $this_class_name),
            ];
        } else {
            return self::processCustomAssertion($expr, $this_class_name, $source);
        }

        $native_symbol_guard = self::getNativeSymbolGuardAssertion(
            $expr,
            $function_id,
            $source,
            $codebase,
            $first_var_type,
        );
        if ($native_symbol_guard !== null) {
            $if_types[Context::PHP_SYMBOL_GUARD_VAR_ID] = [[$native_symbol_guard]];
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * A `function_exists()`, `class_exists()`, `interface_exists()`, `enum_exists()` or
     * `method_exists()` check of a native symbol newer than the analysed PHP version: where it
     * holds, PHP_VERSION_ID is at least the version that introduced the symbol, so the symbol
     * can be used there (see Codebase::getGuardedPhpVersionId()).
     */
    private static function getNativeSymbolGuardAssertion(
        PhpParser\Node\Expr\FuncCall $expr,
        ?string $function_id,
        FileSource $source,
        ?Codebase $codebase,
        ?Union $first_var_type,
    ): ?Assertion {
        if (!$codebase
            || !$source instanceof StatementsAnalyzer
            || $expr->isFirstClassCallable()
        ) {
            return null;
        }

        $args = $expr->getArgs();
        $since_php_version_id = null;

        if ($function_id === 'function_exists') {
            $guarded_function_name = isset($args[0]) && $args[0]->value instanceof PhpParser\Node\Scalar\String_
                ? ltrim($args[0]->value->value, '\\')
                : '';

            if ($guarded_function_name !== '') {
                $guarded_function_id = strtolower($guarded_function_name);

                if ($codebase->functions->functionExists($source, $guarded_function_id)) {
                    try {
                        $since_php_version_id = $codebase->functions
                            ->getStorage($source, $guarded_function_id)
                            ->since_php_version_id
                            ?? InternalCallMapHandler::getIntroducingPhpVersionId($guarded_function_id);
                    } catch (UnexpectedValueException) {
                    }
                }
            }
        } elseif (in_array($function_id, ['class_exists', 'interface_exists', 'enum_exists'], true)) {
            $guarded_class = isset($args[0]) ? self::getLiteralClassName($args[0]->value) : null;

            if ($guarded_class !== null && $codebase->classlike_storage_provider->has($guarded_class)) {
                $class_storage = $codebase->classlike_storage_provider->get($guarded_class);

                if (!$class_storage->user_defined && !$codebase->isClassLikePolyfilled($class_storage->name)) {
                    $since_php_version_id = $class_storage->since_php_version_id;
                }
            }
        } elseif ($function_id === 'method_exists'
            && isset($args[0], $args[1])
            && $args[1]->value instanceof PhpParser\Node\Scalar\String_
        ) {
            $guarded_classes = [];
            if (($guarded_class = self::getLiteralClassName($args[0]->value)) !== null) {
                $guarded_classes[] = $guarded_class;
            } elseif ($first_var_type !== null) {
                foreach ($first_var_type->getAtomicTypes() as $atomic) {
                    if ($atomic instanceof TNamedObject) {
                        $guarded_classes[] = $atomic->value;
                    }
                }
            }

            foreach ($guarded_classes as $guarded_class) {
                $method_id = new MethodIdentifier($guarded_class, strtolower($args[1]->value->value));

                if ($codebase->classlike_storage_provider->has($guarded_class)
                    && $codebase->methods->methodExists($codebase, $method_id)
                ) {
                    $since_php_version_id = MethodAnalyzer::getMethodSincePhpVersionId($codebase, $method_id);
                    break;
                }
            }
        }

        return $since_php_version_id !== null && $since_php_version_id > $codebase->analysis_php_version_id
            ? new IsGreaterThanOrEqualTo($since_php_version_id)
            : null;
    }

    /**
     * For a `bool` operand `=== true` and `!== false` are plain truthiness, which is what the clauses
     * of an assignment like `$c = $a && $b` are expressed in.
     *
     * @psalm-capabilities read-props
     */
    private static function isBoolVariable(PhpParser\Node\Expr $expr, FileSource $source): bool
    {
        if (!$source instanceof StatementsAnalyzer || !($type = $source->node_data->getType($expr))) {
            return false;
        }

        return $type->getId() === 'bool' && !$type->possibly_undefined;
    }

    /**
     * The class named by a string literal or a `Foo::class` constant.
     */
    private static function getLiteralClassName(PhpParser\Node\Expr $expr): ?string
    {
        if ($expr instanceof PhpParser\Node\Scalar\String_) {
            $class = ltrim($expr->value, '\\');

            return $class !== '' ? $class : null;
        }

        if ($expr instanceof PhpParser\Node\Expr\ClassConstFetch
            && $expr->class instanceof PhpParser\Node\Name
            && $expr->name instanceof PhpParser\Node\Identifier
            && strtolower($expr->name->name) === 'class'
        ) {
            $class = (string) $expr->class->getAttribute('resolvedName');

            return $class !== '' ? $class : null;
        }

        return null;
    }

    private static function processIrreconcilableFunctionCall(
        Union $first_var_type,
        Union $expected_type,
        PhpParser\Node\Expr $expr,
        StatementsAnalyzer $source,
        Codebase $codebase,
        bool $negate,
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
    private static function processCustomAssertion(
        PhpParser\Node\Expr $expr,
        ?string $this_class_name,
        FileSource $source,
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

                $assertion = self::expandClassConstantsOfRules($assertion, $source->getCodebase());

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
                        $if_types[$var_name] = self::getAlternatives($assertion->rule);
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
                        $if_types[$var_id] = self::getAlternatives($assertion->rule);
                    }
                } elseif ($assertion->var_id === Context::PHP_SAPI_VAR_ID) {
                    $if_types[Context::PHP_SAPI_VAR_ID] = self::getAlternatives($assertion->rule);
                } elseif (is_string($assertion->var_id) && self::isArrayOffsetAssertionId($assertion->var_id)) {
                    $offset_var_id = self::getArrayOffsetVarId(
                        $assertion->var_id,
                        $expr->getArgs(),
                        $this_class_name,
                        $source,
                    );

                    if ($offset_var_id !== null && $assertion->rule !== []) {
                        $if_types[$offset_var_id] = [$assertion->rule];
                    }
                } elseif (is_string($assertion->var_id)) {
                    $is_function = str_ends_with($assertion->var_id, '()');
                    $exploded_id = explode('->', $assertion->var_id);
                    $var_id   = $exploded_id[0] ?? null;
                    $property = $exploded_id[1] ?? null;

                    if (is_numeric($var_id) && null !== $property && !$is_function) {
                        $assertion_var_id = self::getAssertedArgumentPropertyVarId(
                            $expr,
                            $expr->getArgs(),
                            $source,
                            $assertion->var_id,
                        );

                        if ($assertion_var_id === null) {
                            continue;
                        }
                    } elseif (self::splitArgumentOffsetVarId($assertion->var_id) !== null) {
                        $assertion_var_id = self::getArgumentOffsetVarId($assertion->var_id, $expr->getArgs(), $source);

                        if ($assertion_var_id === null) {
                            continue;
                        }
                    } elseif (!$expr instanceof PhpParser\Node\Expr\FuncCall) {
                        $assertion_var_id = $assertion->var_id;
                    } else {
                        IssueBuffer::maybeAdd(
                            new InvalidDocblock(
                                sprintf('Assertion of variable "%s" cannot be recognized', $assertion->var_id),
                                new CodeLocation($source, $expr),
                            ),
                        );
                        continue;
                    }
                    $if_types[$assertion_var_id] = self::getAlternatives($assertion->rule);
                }

                if ($if_types) {
                    $anded_types[] = $if_types;
                }
            }
        }

        if ($if_false_assertions) {
            foreach ($if_false_assertions as $assertion) {
                $if_types = [];

                $assertion = self::expandClassConstantsOfRules($assertion, $source->getCodebase());

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
                        $if_types[$var_name] = self::getNegatedAlternatives($assertion->rule);
                    }
                } elseif ($assertion->var_id === '$this' && $expr instanceof PhpParser\Node\Expr\MethodCall) {
                    $var_id = ExpressionIdentifier::getExtendedVarId(
                        $expr->var,
                        $this_class_name,
                        $source,
                    );

                    if ($var_id) {
                        $if_types[$var_id] = self::getNegatedAlternatives($assertion->rule);
                    }
                } elseif ($assertion->var_id === Context::PHP_SAPI_VAR_ID) {
                    $if_types[Context::PHP_SAPI_VAR_ID] = self::getNegatedAlternatives($assertion->rule);
                } elseif (is_string($assertion->var_id) && self::isArrayOffsetAssertionId($assertion->var_id)) {
                    $offset_var_id = self::getArrayOffsetVarId(
                        $assertion->var_id,
                        $expr->getArgs(),
                        $this_class_name,
                        $source,
                    );

                    if ($offset_var_id !== null && $assertion->rule !== []) {
                        $if_types[$offset_var_id] = [array_map(
                            static fn(Assertion $rule): Assertion => $rule->getNegation(),
                            $assertion->rule,
                        )];
                    }
                } elseif (is_string($assertion->var_id)) {
                    $is_function = str_ends_with($assertion->var_id, '()');
                    $exploded_id = explode('->', $assertion->var_id);
                    $var_id   = $exploded_id[0] ?? null;
                    $property = $exploded_id[1] ?? null;

                    if (is_numeric($var_id) && null !== $property && !$is_function) {
                        $assertion_var_id = self::getAssertedArgumentPropertyVarId(
                            $expr,
                            $expr->getArgs(),
                            $source,
                            $assertion->var_id,
                        );

                        if ($assertion_var_id === null) {
                            continue;
                        }

                        $if_types[$assertion_var_id] = self::getNegatedAlternatives($assertion->rule);
                    } elseif (self::splitArgumentOffsetVarId($assertion->var_id) !== null) {
                        $offset_var_id = self::getArgumentOffsetVarId($assertion->var_id, $expr->getArgs(), $source);

                        if ($offset_var_id === null || $assertion->rule === []) {
                            continue;
                        }

                        $if_types[$offset_var_id] = self::getNegatedAlternatives($assertion->rule);
                    } elseif (!$expr instanceof PhpParser\Node\Expr\FuncCall) {
                        $if_types[$assertion->var_id] = self::getNegatedAlternatives($assertion->rule);
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
     * The id of the property of an argument that an `@psalm-assert-if-*` of `N->property` is about, or null, after
     * reporting why it can't be asserted
     *
     * @param list<PhpParser\Node\Arg> $args
     */
    public static function getAssertedArgumentPropertyVarId(
        PhpParser\NodeAbstract $expr,
        array $args,
        StatementsAnalyzer $source,
        string $assertion_id,
    ): ?string {
        $exploded_id = explode('->', $assertion_id);
        $var_id = $exploded_id[0];
        $property = $exploded_id[1] ?? null;

        if (!is_numeric($var_id) || !isset($args[(int) $var_id]) || $property === null) {
            IssueBuffer::maybeAdd(
                new InvalidDocblock(
                    'Variable '.$var_id.' is not an argument so cannot be asserted',
                    new CodeLocation($source, $expr),
                ),
            );

            return null;
        }

        $arg_value = $args[(int) $var_id]->value;

        $arg_var_id = ExpressionIdentifier::getExtendedVarId($arg_value, null, $source);

        if (null === $arg_var_id) {
            IssueBuffer::maybeAdd(
                new InvalidDocblock(
                    'Variable being asserted as argument ' . ((int) $var_id + 1) . ' cannot be found in local scope',
                    new CodeLocation($source, $expr),
                ),
            );

            return null;
        }

        if (count($exploded_id) === 2) {
            $failedMessage = self::isPropertyImmutableOnArgument(
                $property,
                $source->getNodeTypeProvider()->getType($arg_value),
                $source->getCodebase()->classlike_storage_provider,
                $arg_var_id,
            );

            if (null !== $failedMessage) {
                IssueBuffer::maybeAdd(
                    new InvalidDocblock($failedMessage, new CodeLocation($source, $expr)),
                );

                return null;
            }
        }

        return $arg_var_id . substr($assertion_id, strlen($var_id));
    }

    private static function expandClassConstantsOfRules(Possibilities $assertion, Codebase $codebase): Possibilities
    {
        $rules = [];

        foreach ($assertion->rule as $rule) {
            $rule_type = $rule->getAtomicType();

            $rules[] = $rule_type instanceof TClassConstant
                ? $rule->setAtomicType(TypeExpander::expandAtomic($codebase, $rule_type, null, null, null)[0])
                : $rule;
        }

        return new Possibilities($assertion->var_id, $rules);
    }

    /**
     * `0:context['key']` stands for `$context['key']` of the array passed as the parameter `$context` at position 0
     *
     * @return array{int, string, string}|null the parameter's position and name, and the rest of the id (`['key']`)
     * @psalm-pure
     */
    public static function splitArgumentOffsetVarId(string $var_id): ?array
    {
        if (preg_match('/^(\d+):(\w+)(\[.+)$/', $var_id, $matches) !== 1
            || !isset($matches[1], $matches[2], $matches[3])
        ) {
            return null;
        }

        return [(int) $matches[1], $matches[2], $matches[3]];
    }

    /**
     * @param list<PhpParser\Node\Arg> $args
     */
    public static function getArgumentOffsetVarId(string $var_id, array $args, FileSource $source): ?string
    {
        $parts = self::splitArgumentOffsetVarId($var_id);

        if ($parts === null) {
            return null;
        }

        $arg = self::getArgumentOfParameter($args, $parts[0], $parts[1]);

        if ($arg === null) {
            return null;
        }

        $arg_var_id = ExpressionIdentifier::getExtendedVarId($arg->value, null, $source);

        return $arg_var_id === null ? null : $arg_var_id . $parts[2];
    }

    /**
     * @return list<Assertion>
     */
    private static function getInstanceOfAssertions(
        PhpParser\Node\Expr\Instanceof_ $stmt,
        ?string $this_class_name,
        FileSource $source,
    ): array {
        if ($stmt->class instanceof PhpParser\Node\Name) {
            // no object is an instance of `iterable` (a reserved word reported where it is analysed): the check
            // says nothing about the variable, instead of being resolved to the type iterable
            if (strtolower($stmt->class->toString()) === 'iterable') {
                return [];
            }

            if (!in_array(strtolower($stmt->class->getFirst()), ['self', 'static', 'parent'], true)) {
                $instanceof_class = ClassLikeAnalyzer::getFQCLNFromNameObject(
                    $stmt->class,
                    $source->getAliases(),
                );

                if ($source instanceof StatementsAnalyzer) {
                    $codebase = $source->getCodebase();
                    $instanceof_class = $codebase->classlikes->getUnAliasedName($instanceof_class);
                }

                return [new IsType(TNamedObject::createFromName($instanceof_class))];
            }

            if ($this_class_name !== null
                && (in_array(strtolower($stmt->class->getFirst()), ['self', 'static'], true))) {
                $is_static = $stmt->class->getFirst() === 'static';
                $named_object = new TNamedObject($this_class_name, $is_static);

                if ($is_static) {
                    return [new IsIdentical($named_object)];
                }

                return [new IsType($named_object)];
            }
        } elseif ($stmt->class instanceof PhpParser\Node\Expr\Variable && $stmt->class->name === 'this') {
            if ($this_class_name !== null) {
                $named_object = new TNamedObject($this_class_name, true);

                return [new IsIdentical($named_object)];
            }
        } elseif ($source instanceof StatementsAnalyzer) {
            $stmt_class_type = $source->node_data->getType($stmt->class);

            if ($stmt_class_type) {
                $literal_class_strings = [];

                foreach ($stmt_class_type->getAtomicTypes() as $atomic_type) {
                    // a class-string only bounds the class: the object may be of any subclass of it, so the
                    // check isn't redundant, and its negation tells nothing
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
                        $literal_class_strings[] = new IsIdentical(
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
    private static function hasNullVariable(
        PhpParser\Node\Expr\BinaryOp $conditional,
        FileSource $source,
    ): ?int {
        if ($conditional->right instanceof PhpParser\Node\Expr\ConstFetch
            && strtolower($conditional->right->name->getFirst()) === 'null'
        ) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\ConstFetch
            && strtolower($conditional->left->name->getFirst()) === 'null'
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
        PhpParser\Node\Expr\BinaryOp $conditional,
    ): ?int {
        if ($conditional->right instanceof PhpParser\Node\Expr\ConstFetch
            && strtolower($conditional->right->name->getFirst()) === 'false'
        ) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\ConstFetch
            && strtolower($conditional->left->name->getFirst()) === 'false'
        ) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return null;
    }

    /**
     * @param Identical|Equal|NotIdentical|NotEqual $conditional
     */
    public static function hasTrueVariable(
        PhpParser\Node\Expr\BinaryOp $conditional,
    ): ?int {
        if ($conditional->right instanceof PhpParser\Node\Expr\ConstFetch
            && strtolower($conditional->right->name->getFirst()) === 'true'
        ) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if ($conditional->left instanceof PhpParser\Node\Expr\ConstFetch
            && strtolower($conditional->left->name->getFirst()) === 'true'
        ) {
            return self::ASSIGNMENT_TO_LEFT;
        }

        return null;
    }

    /**
     * @param Identical|Equal|NotIdentical|NotEqual $conditional
     * @psalm-mutation-free
     */
    private static function hasEmptyArrayVariable(
        PhpParser\Node\Expr\BinaryOp $conditional,
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
     * The id of the function called, resolved like FunctionCallAnalyzer does: the function of the namespace, or
     * the imported one, if it exists, else the global function PHP falls back to.
     *
     * @return lowercase-string|null
     */
    private static function getCalledFunctionId(PhpParser\Node\Expr\FuncCall $expr, FileSource $source): ?string
    {
        if (!$expr->name instanceof PhpParser\Node\Name) {
            return null;
        }

        $function_name = $expr->name->toLowerString();

        if ($expr->name instanceof PhpParser\Node\Name\FullyQualified || !$source instanceof StatementsAnalyzer) {
            return $function_name;
        }

        $functions = $source->getCodebase()->functions;
        $function_id = strtolower($functions->getFullyQualifiedFunctionNameFromString(
            $expr->name->toString(),
            $source,
        ));

        return $function_id === $function_name || $functions->functionExists($source, $function_id)
            ? $function_id
            : $function_name;
    }

    /**
     * @param list<string> $lowercase_names
     */
    private static function isFunctionCallToOneOf(
        PhpParser\Node\Expr $expr,
        FileSource $source,
        array $lowercase_names,
        int $min_args = 0,
    ): bool {
        return $expr instanceof PhpParser\Node\Expr\FuncCall
            && !$expr->isPartialFunctionApplication()
            && in_array(self::getCalledFunctionId($expr, $source), $lowercase_names, true)
            && count($expr->getArgs()) >= $min_args;
    }

    /**
     * @param Identical|Equal|NotIdentical|NotEqual $conditional
     * @return false|int
     */
    private static function hasGetTypeCheck(
        PhpParser\Node\Expr\BinaryOp $conditional,
        FileSource $source,
    ): bool|int {
        if (self::isFunctionCallToOneOf($conditional->right, $source, ['gettype'], 1)
            && $conditional->left instanceof PhpParser\Node\Scalar\String_
        ) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if (self::isFunctionCallToOneOf($conditional->left, $source, ['gettype'], 1)
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
    private static function hasGetDebugTypeCheck(
        PhpParser\Node\Expr\BinaryOp $conditional,
        FileSource $source,
    ): bool|int {
        if (self::isFunctionCallToOneOf($conditional->right, $source, ['get_debug_type'], 1)
            && ($conditional->left instanceof PhpParser\Node\Scalar\String_
                || $conditional->left instanceof PhpParser\Node\Expr\ClassConstFetch)
        ) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        if (self::isFunctionCallToOneOf($conditional->left, $source, ['get_debug_type'], 1)
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
    private static function hasGetClassCheck(
        PhpParser\Node\Expr\BinaryOp $conditional,
        FileSource $source,
    ): bool|int {
        if (!$source instanceof StatementsAnalyzer) {
            return false;
        }

        $right_get_class = self::isFunctionCallToOneOf($conditional->right, $source, ['get_class']);

        $right_static_class = $conditional->right instanceof PhpParser\Node\Expr\ClassConstFetch
            && $conditional->right->class instanceof PhpParser\Node\Name
            && $conditional->right->class->getParts() === ['static']
            && $conditional->right->name instanceof PhpParser\Node\Identifier
            && strtolower($conditional->right->name->name) === 'class';

        $right_variable_class_const = $conditional->right instanceof PhpParser\Node\Expr\ClassConstFetch
            && !$conditional->right->class instanceof PhpParser\Node\Name
            && $conditional->right->name instanceof PhpParser\Node\Identifier
            && strtolower($conditional->right->name->name) === 'class';

        $left_class_string = $conditional->left instanceof PhpParser\Node\Expr\ClassConstFetch
            && $conditional->left->class instanceof PhpParser\Node\Name
            && $conditional->left->name instanceof PhpParser\Node\Identifier
            && strtolower($conditional->left->name->name) === 'class';

        $left_variable_class_const = $conditional->left instanceof PhpParser\Node\Expr\ClassConstFetch
            && !$conditional->left->class instanceof PhpParser\Node\Name
            && $conditional->left->name instanceof PhpParser\Node\Identifier
            && strtolower($conditional->left->name->name) === 'class';

        $left_class_string_t = false;

        if (!$left_variable_class_const) {
            $left_type = $source->node_data->getType($conditional->left);

            if ($left_type && $left_type->isSingle()) {
                foreach ($left_type->getAtomicTypes() as $type_part) {
                    if ($type_part instanceof TClassString || $type_part instanceof TLiteralClassString) {
                        $left_class_string_t = true;
                        break;
                    }
                }
            }
        }

        if (($right_get_class || $right_static_class || $right_variable_class_const)
            && ($left_class_string || $left_class_string_t)
        ) {
            return self::ASSIGNMENT_TO_RIGHT;
        }

        $left_get_class = self::isFunctionCallToOneOf($conditional->left, $source, ['get_class']);

        $left_static_class = $conditional->left instanceof PhpParser\Node\Expr\ClassConstFetch
            && $conditional->left->class instanceof PhpParser\Node\Name
            && $conditional->left->class->getParts() === ['static']
            && $conditional->left->name instanceof PhpParser\Node\Identifier
            && strtolower($conditional->left->name->name) === 'class';

        $right_class_string = $conditional->right instanceof PhpParser\Node\Expr\ClassConstFetch
            && $conditional->right->class instanceof PhpParser\Node\Name
            && $conditional->right->name instanceof PhpParser\Node\Identifier
            && strtolower($conditional->right->name->name) === 'class';

        $right_class_string_t = false;

        if (!$right_variable_class_const) {
            $right_type = $source->node_data->getType($conditional->right);

            if ($right_type && $right_type->isSingle()) {
                foreach ($right_type->getAtomicTypes() as $type_part) {
                    if ($type_part instanceof TClassString || $type_part instanceof TLiteralClassString) {
                        $right_class_string_t = true;
                        break;
                    }
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
    private static function hasNonEmptyCountEqualityCheck(
        PhpParser\Node\Expr\BinaryOp $conditional,
        FileSource $source,
        ?int &$min_count,
    ): bool|int {
        if (self::isFunctionCallToOneOf($conditional->left, $source, ['count', 'sizeof'], 1)
            && ($conditional instanceof BinaryOp\Greater || $conditional instanceof BinaryOp\GreaterOrEqual)
        ) {
            $assignment_to = self::ASSIGNMENT_TO_RIGHT;
            $compare_to = $conditional->right;
            $comparison_adjustment = $conditional instanceof BinaryOp\Greater ? 1 : 0;
        } elseif (self::isFunctionCallToOneOf($conditional->right, $source, ['count', 'sizeof'], 1)
            && ($conditional instanceof BinaryOp\Smaller || $conditional instanceof BinaryOp\SmallerOrEqual)
        ) {
            $assignment_to = self::ASSIGNMENT_TO_LEFT;
            $compare_to = $conditional->left;
            $comparison_adjustment = $conditional instanceof BinaryOp\Smaller ? 1 : 0;
        } else {
            return false;
        }

        // TODO get node type provider here somehow and check literal ints and int ranges
        if ($compare_to instanceof PhpParser\Node\Scalar\Int_
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
    private static function hasLessThanCountEqualityCheck(
        PhpParser\Node\Expr\BinaryOp $conditional,
        FileSource $source,
        ?int &$max_count,
    ): bool|int {
        $left_count = self::isFunctionCallToOneOf($conditional->left, $source, ['count', 'sizeof'], 1);

        $operator_less_than_or_equal =
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\SmallerOrEqual
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Smaller;

        if ($left_count
            && $operator_less_than_or_equal
            && $conditional->right instanceof PhpParser\Node\Scalar\Int_
        ) {
            $max_count = $conditional->right->value -
                ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Smaller ? 1 : 0);

            return self::ASSIGNMENT_TO_RIGHT;
        }

        $right_count = self::isFunctionCallToOneOf($conditional->right, $source, ['count', 'sizeof'], 1);

        $operator_greater_than_or_equal =
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\GreaterOrEqual
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Greater;

        if ($right_count
            && $operator_greater_than_or_equal
            && $conditional->left instanceof PhpParser\Node\Scalar\Int_
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
    private static function hasCountEqualityCheck(
        PhpParser\Node\Expr\BinaryOp $conditional,
        FileSource $source,
        ?int &$count,
    ): bool|int {
        $left_count = self::isFunctionCallToOneOf($conditional->left, $source, ['count', 'sizeof'], 1);

        if ($left_count && $conditional->right instanceof PhpParser\Node\Scalar\Int_) {
            $count = $conditional->right->value;

            return self::ASSIGNMENT_TO_RIGHT;
        }

        $right_count = self::isFunctionCallToOneOf($conditional->right, $source, ['count', 'sizeof'], 1);

        if ($right_count && $conditional->left instanceof PhpParser\Node\Scalar\Int_) {
            $count = $conditional->left->value;

            return self::ASSIGNMENT_TO_LEFT;
        }

        return false;
    }

    /**
     * @param Greater|GreaterOrEqual|Smaller|SmallerOrEqual $conditional
     * @return false|int
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private static function hasIntLiteralComparison(
        FileSource $source,
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?int &$literal_value_comparison,
    ): bool|int {
        $value_right = self::getIntLiteralValue($source, $conditional->right);

        if ($value_right !== null) {
            $literal_value_comparison = $value_right;

            return self::ASSIGNMENT_TO_RIGHT;
        }

        $value_left = self::getIntLiteralValue($source, $conditional->left);

        if ($value_left !== null) {
            $literal_value_comparison = $value_left;

            return self::ASSIGNMENT_TO_LEFT;
        }

        return false;
    }

    /**
     * @psalm-capabilities read-props
     */
    private static function getIntLiteralValue(FileSource $source, PhpParser\Node\Expr $expr): ?int
    {
        if ($source instanceof StatementsAnalyzer
            && ($type = $source->node_data->getType($expr))
            && $type->isSingleIntLiteral()
        ) {
            return $type->getSingleIntLiteral()->value;
        }

        if ($expr instanceof Int_) {
            return $expr->value;
        }

        if ($expr instanceof UnaryMinus && $expr->expr instanceof Int_) {
            return -$expr->expr->value;
        }

        if ($expr instanceof UnaryPlus && $expr->expr instanceof Int_) {
            return $expr->expr->value;
        }

        return null;
    }

    /**
     * @param Greater|GreaterOrEqual|Smaller|SmallerOrEqual $conditional
     */
    private static function hasReconcilableNonEmptyCountEqualityCheck(
        PhpParser\Node\Expr\BinaryOp $conditional,
        FileSource $source,
    ): bool {
        return self::isFunctionCallToOneOf($conditional->left, $source, ['count', 'sizeof'])
            && $conditional->right instanceof PhpParser\Node\Scalar\Int_
            && $conditional->right->value === ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Greater ? 0 : 1);
    }

    /**
     * @param Identical|Equal|NotIdentical|NotEqual $conditional
     * @return false|int
     * @psalm-capabilities read-props
     */
    private static function hasTypedValueComparison(
        PhpParser\Node\Expr\BinaryOp $conditional,
        FileSource $source,
    ): bool|int {
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

    /**
     * @psalm-capabilities read-props
     */
    private static function hasIsACheck(
        PhpParser\Node\Expr\FuncCall $stmt,
        ?string $function_id,
        StatementsAnalyzer $source,
    ): bool {
        if (($function_id === 'is_a' || $function_id === 'is_subclass_of')
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

    /**
     * @psalm-pure
     */
    private static function getIsAssertion(string $function_name): ?Assertion
    {
        return match ($function_name) {
            'is_string' => new IsType(new Atomic\TString()),
            'is_int', 'is_integer', 'is_long' => new IsType(new Atomic\TInt()),
            'is_float', 'is_double', 'is_real' => new IsType(new Atomic\TFloat()),
            'is_scalar' => new IsType(new Atomic\TScalar()),
            'is_bool' => new IsType(new Atomic\TBool()),
            'is_resource' => new IsType(new Atomic\TResource()),
            'is_object' => new IsType(new Atomic\TObject()),
            'array_is_list' => new IsType(Type::getListAtomic(Type::getMixed())),
            'is_array' => new IsType(new Atomic\TArray([Type::getArrayKey(), Type::getMixed()])),
            'is_numeric' => new IsType(new Atomic\TNumeric()),
            'is_null' => new IsType(new Atomic\TNull()),
            'is_iterable' => new IsType(new Atomic\TIterable()),
            'is_countable' => new IsCountable(),
            'ctype_digit' => new IsType(new Atomic\TNumericString),
            'ctype_lower' => new IsType(new Atomic\TNonEmptyLowercaseString),
            default => null,
        };
    }

    /**
     * @psalm-pure
     */
    private static function getGettypeAtomic(string $gettype): ?Atomic
    {
        return match ($gettype) {
            'boolean' => new Atomic\TBool(),
            'integer' => new Atomic\TInt(),
            'double' => new Atomic\TFloat(),
            'string' => new Atomic\TString(),
            'array' => new Atomic\TArray([Type::getArrayKey(), Type::getMixed()]),
            'object' => new TObject(),
            'resource' => new TResource(),
            'resource (closed)' => new TClosedResource(),
            'NULL' => new Atomic\TNull(),
            // 'unknown type' is not returned by any supported PHP version
            default => null,
        };
    }

    /**
     * @return array<string, non-empty-list<non-empty-list<Assertion>>>
     */
    private static function handleIsTypeCheck(
        ?Codebase $codebase,
        FileSource $source,
        ?string $function_id,
        ?string $first_var_name,
        ?Union $first_var_type,
        PhpParser\Node\Expr\FuncCall $expr,
        bool $negate,
    ): array {
        $if_types = [];
        if ($function_id !== null
            && ($assertion_type = self::getIsAssertion($function_id))
            && $source instanceof StatementsAnalyzer
        ) {
            if ($first_var_name) {
                $if_types[$first_var_name] = [[$assertion_type]];
            } elseif ($first_var_type
                && $codebase
                && $assertion_type instanceof IsType
                // a string passing a ctype check is of the type, but a string of the type may not pass it ("-5")
                && !str_starts_with($function_id, 'ctype_')
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

    /**
     * A `class_exists()` or `trait_exists()` that does not autoload: a class it accepts is already loaded.
     */
    private static function isLoadedExistsCheck(PhpParser\Node\Expr\FuncCall $stmt): bool
    {
        if (!isset($stmt->getArgs()[1])) {
            return false;
        }

        $second_arg = $stmt->getArgs()[1]->value;

        return !$second_arg instanceof PhpParser\Node\Expr\ConstFetch
            || strtolower($second_arg->name->getFirst()) !== 'true';
    }

    private static function hasTrueThirdArg(PhpParser\Node\Expr\FuncCall $stmt): bool
    {
        if (!isset($stmt->getArgs()[2])) {
            return false;
        }

        $third_arg = $stmt->getArgs()[2]->value;

        return $third_arg instanceof PhpParser\Node\Expr\ConstFetch
            && strtolower($third_arg->name->getFirst()) === 'true';
    }

    /**
     * A nullsafe chain that is null only by short-circuiting is null exactly when the receiver of its nullsafe link
     * is, which holds for a single link: with several, a null intermediate value says nothing. A nullsafe property
     * fetch compared to null is expanded by the FormulaGenerator.
     */
    private static function getReceiverOfAChainNullOnlyByShortCircuit(
        PhpParser\Node\Expr $chain,
        ?string $this_class_name,
        FileSource $source,
    ): ?string {
        if (NullsafeChainState::of($chain) !== NullsafeChainState::ShortCircuit
            || $chain instanceof PhpParser\Node\Expr\NullsafePropertyFetch
        ) {
            return null;
        }

        $nullsafe_receivers = [];
        $chain_link = $chain;

        while ($chain_link instanceof PhpParser\Node\Expr\PropertyFetch
            || $chain_link instanceof PhpParser\Node\Expr\NullsafePropertyFetch
            || $chain_link instanceof PhpParser\Node\Expr\MethodCall
            || $chain_link instanceof PhpParser\Node\Expr\NullsafeMethodCall
        ) {
            if ($chain_link instanceof PhpParser\Node\Expr\NullsafePropertyFetch
                || $chain_link instanceof PhpParser\Node\Expr\NullsafeMethodCall
            ) {
                $nullsafe_receivers[] = $chain_link->var;
            }

            $chain_link = $chain_link->var;
        }

        return count($nullsafe_receivers) === 1
            ? ExpressionIdentifier::getExtendedVarId($nullsafe_receivers[0], $this_class_name, $source)
            : null;
    }

    /**
     * What a non-null (or truthy) `$a?->b?->value` says: the fetch is that way, and so is every receiver of the chain
     * (`$a?->b` and `$a`). A null one says nothing about the receivers, so no assertion is made for it.
     *
     * @return array<string, non-empty-list<non-empty-list<Assertion>>>
     */
    private static function getNullsafeAssertions(
        PhpParser\Node\Expr\NullsafePropertyFetch $expr,
        Assertion $assertion,
        ?string $this_class_name,
        FileSource $source,
    ): array {
        $if_types = [];

        $property_id = self::getNullsafeChainVarId($expr, $this_class_name, $source);

        // a property that can't be null (or falsy) says nothing: it is the receiver that was checked
        $property_type = self::getNullsafePropertyType($expr, $source);

        if ($property_id !== null
            && ($property_type === null
                || ($assertion instanceof Truthy ? !$property_type->isAlwaysTruthy() : $property_type->isNullable()))
        ) {
            $if_types[$property_id] = [[$assertion]];
        }

        $receiver = $expr;

        while ($receiver instanceof PhpParser\Node\Expr\NullsafePropertyFetch) {
            $receiver = $receiver->var;
            $receiver_id = self::getNullsafeChainVarId($receiver, $this_class_name, $source);

            if ($receiver_id !== null) {
                $if_types[$receiver_id] = [[new IsNotType(new TNull())]];
            }
        }

        return $if_types;
    }

    /**
     * The declared type of the property a nullsafe fetch reads, or null when it isn't known for every class the
     * receiver may be
     */
    public static function getNullsafePropertyType(
        PhpParser\Node\Expr\NullsafePropertyFetch $expr,
        FileSource $source,
    ): ?Union {
        if (!$source instanceof StatementsAnalyzer || !$expr->name instanceof PhpParser\Node\Identifier) {
            return null;
        }

        $receiver_type = $source->getNodeTypeProvider()->getType($expr->var);

        if ($receiver_type === null) {
            return null;
        }

        $codebase = $source->getCodebase();
        $property_types = [];

        $receiver_atomic_types = [];

        foreach ($receiver_type->getAtomicTypes() as $atomic_type) {
            // a template stands for the types it is bounded by
            $receiver_atomic_types = [
                ...$receiver_atomic_types,
                ...($atomic_type instanceof TTemplateParam ? $atomic_type->as->getAtomicTypes() : [$atomic_type]),
            ];
        }

        foreach ($receiver_atomic_types as $atomic_type) {
            if ($atomic_type instanceof TNull) {
                continue;
            }

            if (!$atomic_type instanceof TNamedObject || !$codebase->classExists($atomic_type->value)) {
                return null;
            }

            $storage = $codebase->classlike_storage_provider->get($atomic_type->value);
            $declaring_class = $storage->declaring_property_ids[$expr->name->name] ?? null;
            $property_type = $declaring_class === null
                ? null
                : $codebase->classlike_storage_provider->get($declaring_class)->properties[$expr->name->name]->type
                    ?? null;

            if ($property_type === null || $property_type->hasMixed() || $property_type->hasTemplate()) {
                return null;
            }

            $property_types[] = $property_type;
        }

        return $property_types === [] ? null : Type::combineUnionTypeArray($property_types, $codebase);
    }

    /**
     * The id of a chain of properties, `$a?->b` counting as `$a->b`
     */
    private static function getNullsafeChainVarId(
        PhpParser\Node\Expr $expr,
        ?string $this_class_name,
        FileSource $source,
    ): ?string {
        if (!$expr instanceof PhpParser\Node\Expr\NullsafePropertyFetch) {
            return ExpressionIdentifier::getExtendedVarId($expr, $this_class_name, $source);
        }

        if (!$expr->name instanceof PhpParser\Node\Identifier) {
            return null;
        }

        $object_id = self::getNullsafeChainVarId($expr->var, $this_class_name, $source);

        return $object_id === null ? null : $object_id . '->' . $expr->name->name;
    }

    /**
     * @param Identical|Equal|NotIdentical|NotEqual $conditional
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function getNullComparisonAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        FileSource $source,
        ?Codebase $codebase,
        int $null_position,
        bool $negated,
    ): array {
        $if_types = [];
        $strict = self::isStrictComparison($conditional);

        [$base_conditional] = self::getOperands($conditional, $null_position);

        $var_name = ExpressionIdentifier::getExtendedVarId(
            $base_conditional,
            $this_class_name,
            $source,
        );

        if ($var_name) {
            if ($base_conditional instanceof PhpParser\Node\Expr\Assign
                || $base_conditional instanceof PhpParser\Node\Expr\AssignRef
                || $base_conditional instanceof PhpParser\Node\Expr\AssignOp\Coalesce
            ) {
                $var_name = '=' . $var_name;
            }

            $if_types[$var_name] = [[self::withPolarity(
                $strict ? new IsType(new TNull()) : new IsLooselyEqual(new TNull()),
                $negated,
            )]];
        } elseif ($negated && $base_conditional instanceof PhpParser\Node\Expr\NullsafePropertyFetch) {
            // a null `$a?->b` does not tell whether `$a` is null
            $if_types = self::getNullsafeAssertions(
                $base_conditional,
                $strict ? new IsNotType(new TNull()) : new Truthy(),
                $this_class_name,
                $source,
            );
        }

        // a loose comparison also matches a falsy receiver that is not null (`false == null`)
        $nullsafe_var_name = $strict
            ? self::getReceiverOfAChainNullOnlyByShortCircuit($base_conditional, $this_class_name, $source)
            : null;

        if ($nullsafe_var_name !== null) {
            $if_types[$nullsafe_var_name] = [[self::withPolarity(new IsType(new TNull()), $negated)]];
        }

        if ($strict
            && $codebase
            && $source instanceof StatementsAnalyzer
            && ($var_type = $source->node_data->getType($base_conditional))
        ) {
            self::reportNeverIdenticalValue(
                $source,
                $codebase,
                $conditional,
                $var_type,
                Type::getNull(),
                'null',
                $negated,
            );
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @psalm-suppress MoreSpecificReturnType
     * @param Identical|Equal|NotIdentical|NotEqual $conditional
     * @param bool $value whether the operand is compared with true or with false
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function getBoolComparisonAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        FileSource $source,
        ?Codebase $codebase,
        int $bool_position,
        bool $value,
        bool $negated,
        bool $cache,
        bool $inside_conditional,
    ): array {
        $strict = self::isStrictComparison($conditional);
        // `=== true` and `!== false` hold when the operand holds, `=== false` and `!== true` when it doesn't
        $operand_holds = $value !== $negated;

        [$base_conditional] = self::getOperands($conditional, $bool_position);

        $var_name = ExpressionIdentifier::getExtendedVarId(
            $base_conditional,
            $this_class_name,
            $source,
        );

        if ($var_name) {
            if ($strict && !self::isBoolVariable($base_conditional, $source)) {
                $assertion = new IsType($value ? new TTrue() : new TFalse());
            } else {
                $assertion = $value ? new Truthy() : new Falsy();
            }

            $if_types = [[$var_name => [[self::withPolarity($assertion, $negated)]]]];
        } elseif (!$value && $strict && self::canBeFalsyButNotFalse($base_conditional, $source)) {
            // `preg_match() !== false` is also true when it found no match, and `=== false` false, so only the
            // assertions a function declares for when it doesn't return false (`strpos() !== false`) hold
            $base_assertions = ($base_conditional instanceof PhpParser\Node\Expr\FuncCall
                    || $base_conditional instanceof PhpParser\Node\Expr\MethodCall
                    || $base_conditional instanceof PhpParser\Node\Expr\StaticCall)
                && !$base_conditional->isFirstClassCallable()
                ? self::processCustomAssertion($base_conditional, $this_class_name, $source)
                : [];

            $if_types = $operand_holds ? $base_assertions : self::getNegatedAssertions($base_assertions);
        } else {
            if ($base_conditional instanceof PhpParser\Node\Expr\FuncCall
                && !$base_conditional->isFirstClassCallable()
            ) {
                $base_assertions = self::processFunctionCall(
                    $base_conditional,
                    $this_class_name,
                    $source,
                    $codebase,
                    !$operand_holds,
                );
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
            }

            $if_types = $operand_holds ? $base_assertions : self::getNegatedAssertions($base_assertions);
        }

        if ($strict
            && $codebase
            && $source instanceof StatementsAnalyzer
            && ($var_type = $source->node_data->getType($base_conditional))
        ) {
            if ($operand_holds
                && !$codebase->config->allow_bool_to_literal_bool_comparison
                && $var_type->isSingle()
                && $var_type->hasBool()
                && !$var_type->from_docblock
                && !$conditional instanceof VirtualIdentical
                && !$conditional instanceof VirtualNotIdentical
            ) {
                IssueBuffer::maybeAdd(
                    new RedundantIdentityWithTrue(
                        'The "' . ($negated ? '!== false' : '=== true') . '" part of this comparison is redundant',
                        new CodeLocation($source, $conditional),
                    ),
                    $source->getSuppressedIssues(),
                );
            }

            self::reportNeverIdenticalValue(
                $source,
                $codebase,
                $conditional,
                $var_type,
                $value ? Type::getTrue() : Type::getFalse(),
                $value ? 'true' : 'false',
                $negated,
            );
        }

        /** @psalm-suppress LessSpecificReturnStatement */
        return $if_types;
    }

    /**
     * The assertions of an expression being false, from those of it being true
     *
     * @param list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>> $notif_types
     * @return list<array<string, non-empty-list<non-empty-list<Assertion>>>>
     * @psalm-pure
     */
    private static function getNegatedAssertions(array $notif_types): array
    {
        if (count($notif_types) === 1) {
            $if_types = count($notif_types[0]) === 1 ? Algebra::negateTypes($notif_types[0]) : [];

            return $if_types ? [$if_types] : [];
        }

        if (count($notif_types) !== 2) {
            return [];
        }

        // @psalm-assert-if-true and @psalm-assert-if-false for same variable in same function, e.g. array/list cases
        // @todo optionally extend this to arbitrary number of assert-if cases of multiple variables in the function
        $if_types = [];
        $check_var_assertion = null;
        $check_var = null;

        foreach ($notif_types as $notif_type) {
            foreach ($notif_type as $var => $assertions) {
                if (count($assertions) !== 1 || count($assertions[0]) !== 1) {
                    return [];
                }

                $is_not_assertion = $assertions[0][0] instanceof IsNotType;

                if ($check_var === null) {
                    $check_var_assertion = $is_not_assertion;
                    $check_var = $var;
                    continue;
                }

                // only if we have 1 IsType and 1 IsNotType assertion for same variable
                if ($check_var !== $var || $check_var_assertion === $is_not_assertion) {
                    return [];
                }
            }

            $if_types[] = Algebra::negateTypes($notif_type);
        }

        return $if_types;
    }

    /**
     * @param Identical|Equal|NotIdentical|NotEqual $conditional
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function getEmptyArrayComparisonAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        FileSource $source,
        ?Codebase $codebase,
        int $empty_array_position,
        bool $negated,
    ): array {
        $if_types = [];
        $strict = self::isStrictComparison($conditional);

        [$base_conditional] = self::getOperands($conditional, $empty_array_position);

        $var_name = ExpressionIdentifier::getExtendedVarId(
            $base_conditional,
            $this_class_name,
            $source,
        );

        if ($var_name) {
            $if_types[$var_name] = [[self::withPolarity(
                $strict ? new NotNonEmptyCountable() : new Falsy(),
                $negated,
            )]];
        }

        if ($strict
            && $codebase
            && $source instanceof StatementsAnalyzer
            && ($var_type = $source->node_data->getType($base_conditional))
        ) {
            self::reportNeverIdenticalValue(
                $source,
                $codebase,
                $conditional,
                $var_type,
                Type::getEmptyArray(),
                'an empty array',
                $negated,
            );
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param Identical|Equal|NotIdentical|NotEqual $conditional
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function getGettypeComparisonAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        FileSource $source,
        int $gettype_position,
        bool $negated,
    ): array {
        [$string_expr, $gettype_expr] = self::getOperands($conditional, $gettype_position);

        /** @var PhpParser\Node\Expr\FuncCall $gettype_expr */
        $var_name = ExpressionIdentifier::getExtendedVarId(
            $gettype_expr->getArgs()[0]->value,
            $this_class_name,
            $source,
        );

        /** @var PhpParser\Node\Scalar\String_ $string_expr */
        $type_name = $string_expr->value;

        if (!isset(ClassLikeAnalyzer::GETTYPE_TYPES[$type_name])) {
            IssueBuffer::maybeAdd(
                new UnevaluatedCode(
                    'gettype cannot return this value',
                    new CodeLocation($source, $string_expr),
                ),
            );

            return [];
        }

        $atomic = self::getGettypeAtomic($type_name);

        if ($var_name === null || $atomic === null) {
            return [];
        }

        return [[$var_name => [[self::withPolarity(new IsType($atomic), $negated)]]]];
    }

    /**
     * @param Identical|Equal|NotIdentical|NotEqual $conditional
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function getGetdebugtypeComparisonAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        FileSource $source,
        int $get_debug_type_position,
        bool $negated,
    ): array {
        [$whichclass_expr, $get_debug_type_expr] = self::getOperands($conditional, $get_debug_type_position);

        /** @var PhpParser\Node\Expr\FuncCall $get_debug_type_expr */
        $var_name = ExpressionIdentifier::getExtendedVarId(
            $get_debug_type_expr->getArgs()[0]->value,
            $this_class_name,
            $source,
        );

        if ($whichclass_expr instanceof PhpParser\Node\Scalar\String_) {
            $type_name = $whichclass_expr->value;
        } elseif ($whichclass_expr instanceof PhpParser\Node\Expr\ClassConstFetch
            && $whichclass_expr->class instanceof PhpParser\Node\Name
            && $whichclass_expr->name instanceof PhpParser\Node\Identifier
            && strtolower($whichclass_expr->name->name) === 'class'
        ) {
            $type_name = ClassLikeAnalyzer::getFQCLNFromNameObject(
                $whichclass_expr->class,
                $source->getAliases(),
            );
        } else {
            // a constant other than ::class says nothing about the class
            return [];
        }

        // get_debug_type() never returns ""
        if ($var_name === null || $type_name === '') {
            return [];
        }

        if ($type_name === 'class@anonymous') {
            $assertion = new IsIdentical(new TObject());
        } elseif ($type_name === 'resource (closed)') {
            $assertion = new IsType(new TClosedResource());
        } elseif (str_starts_with($type_name, 'resource (')) {
            $assertion = new IsIdentical(new TResource());
        } else {
            try {
                $assertion = new IsType(Atomic::create($type_name));
            } catch (TypeParseTreeException) {
                // not a type name, like "1": get_debug_type() never returns it either
                return [];
            }
        }

        return [[$var_name => [[self::withPolarity($assertion, $negated)]]]];
    }

    /**
     * @param Identical|Equal|NotIdentical|NotEqual $conditional
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function getGetclassComparisonAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        StatementsAnalyzer $source,
        int $getclass_position,
        bool $negated,
    ): array {
        $if_types = [];

        [$whichclass_expr, $getclass_expr] = self::getOperands($conditional, $getclass_position);

        if ($getclass_expr instanceof PhpParser\Node\Expr\FuncCall && isset($getclass_expr->getArgs()[0])) {
            $var_name = ExpressionIdentifier::getExtendedVarId(
                $getclass_expr->getArgs()[0]->value,
                $this_class_name,
                $source,
            );
        } elseif ($getclass_expr instanceof PhpParser\Node\Expr\ClassConstFetch
            && $getclass_expr->class instanceof PhpParser\Node\Expr
            && $getclass_expr->name instanceof PhpParser\Node\Identifier
            && strtolower($getclass_expr->name->name) === 'class'
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
            && $whichclass_expr->name instanceof PhpParser\Node\Identifier
            && strtolower($whichclass_expr->name->name) === 'class'
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

            if ($var_type
                && ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                    $source,
                    $var_type,
                    new CodeLocation($source, $whichclass_expr),
                    null,
                    $source->getSuppressedIssues(),
                    new ClassLikeNameOptions(true),
                ) !== false
                && $var_name
            ) {
                $if_types[$var_name] = [[self::withPolarity(new IsClassEqual($var_type), $negated)]];
            }
        } else {
            $type = $source->node_data->getType($whichclass_expr);

            if ($type && $var_name) {
                foreach ($type->getAtomicTypes() as $type_part) {
                    if ($type_part instanceof TTemplateParamClass) {
                        $if_types[$var_name] = [[self::withPolarity(
                            new IsIdentical(new TTemplateParam(
                                $type_part->param_name,
                                new Union([$type_part->as_type ?: new TObject()]),
                                $type_part->defining_class,
                            )),
                            $negated,
                        )]];
                    } elseif ($type->isSingle() && $type_part instanceof TLiteralClassString) {
                        $if_types[$var_name] = [[self::withPolarity(new IsClassEqual($type_part->value), $negated)]];
                    } elseif (!$negated
                        && $type->isSingle()
                        && $type_part instanceof TClassString
                        && $type_part->as_type
                    ) {
                        // the class of the object is the class-string, so the object is one of its bound; as for
                        // `instanceof $cs`, the check isn't redundant and its negation tells nothing: the object
                        // may be of another subclass of the bound
                        $if_types[$var_name] = [[new IsIdentical($type_part->as_type)]];
                    }
                }
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param Identical|Equal|NotIdentical|NotEqual $conditional
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function getTypedValueComparisonAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        ?string $this_class_name,
        StatementsAnalyzer $source,
        ?Codebase $codebase,
        int $typed_value_position,
        bool $negated,
    ): array {
        $if_types = [];

        [$other_type_expr, $var_type_expr] = self::getOperands($conditional, $typed_value_position);

        $var_name = ExpressionIdentifier::getExtendedVarId(
            $other_type_expr,
            $this_class_name,
            $source,
        );

        $other_var_name = ExpressionIdentifier::getExtendedVarId(
            $var_type_expr,
            $this_class_name,
            $source,
        );

        $var_type = $source->node_data->getType($var_type_expr);
        $other_type = $source->node_data->getType($other_type_expr);

        // a loose comparison of two values of the same type compares them as `===` does, but for two strings that
        // may both be numeric (`"1" == "01"`)
        $identical = self::isStrictComparison($conditional)
            || ($other_type && $var_type
                && (($var_type->isString(true)
                        && $other_type->isString(true)
                        && (self::isNonNumericStringLiteral($var_type)
                            || self::isNonNumericStringLiteral($other_type)))
                    || ($var_type->isInt(true) && $other_type->isInt(true))
                    || ($var_type->isFloat() && $other_type->isFloat())
                )
            );

        // hasTypedValueComparison() only finds a single atomic type, not mixed, on the side of the value
        if ($var_name && $var_type) {
            $orred_types = [];
            $var_type_operand_id = self::getOperandId($var_type_expr, $this_class_name, $source);

            foreach ($var_type->getAtomicTypes() as $atomic_var_type) {
                $comparand = self::getComparand($atomic_var_type, $var_type_operand_id);

                $orred_types[] = self::withPolarity(
                    $identical
                        ? new IsIdentical($atomic_var_type, $comparand)
                        : new IsLooselyEqual($atomic_var_type, $comparand),
                    $negated,
                );
            }

            $if_types[$var_name] = [$orred_types];
        }

        if (!$negated
            && $other_var_name
            && $other_type
            && !$other_type->isMixed()
            && count($other_type->getAtomicTypes()) === 1
            && $other_var_name[0] === '$' //don't try to assert on constant or other, it's too perf consuming
        ) {
            $orred_types = [];
            $other_type_operand_id = self::getOperandId($other_type_expr, $this_class_name, $source);

            foreach ($other_type->getAtomicTypes() as $atomic_other_type) {
                $comparand = self::getComparand($atomic_other_type, $other_type_operand_id);

                if ($identical) {
                    $orred_types[] = new IsIdentical($atomic_other_type, $comparand);
                } else {
                    $orred_types[] = new IsLooselyEqual($atomic_other_type, $comparand);
                }
            }

            $if_types[$other_var_name] = [$orred_types];
        }

        if ($codebase && $other_type && $var_type && $identical) {
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
        ?string $function_id,
        StatementsAnalyzer $source,
        ?string $this_class_name,
        ?string $first_var_name,
    ): array {
        $if_types = [];

        if ($expr->getArgs()[0]->value instanceof PhpParser\Node\Expr\ClassConstFetch
            && $expr->getArgs()[0]->value->name instanceof PhpParser\Node\Identifier
            && strtolower($expr->getArgs()[0]->value->name->name) === 'class'
            && $expr->getArgs()[0]->value->class instanceof PhpParser\Node\Name
            && count($expr->getArgs()[0]->value->class->getParts()) === 1
            && strtolower($expr->getArgs()[0]->value->class->getFirst()) === 'static'
        ) {
            $first_var_name = '$this';
        }

        if ($first_var_name) {
            $first_arg = $expr->getArgs()[0]->value;
            $second_arg = $expr->getArgs()[1]->value;
            $third_arg = $expr->getArgs()[2]->value ?? null;

            if ($third_arg instanceof PhpParser\Node\Expr\ConstFetch) {
                if (!in_array(strtolower($third_arg->name->getFirst()), ['true', 'false'])) {
                    return [];
                }

                $third_arg_value = strtolower($third_arg->name->getFirst());
            } else {
                $third_arg_value = $function_id === 'is_subclass_of' ? 'true' : 'false';
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

                    if ($class_node->getParts() === ['static']) {
                        if ($this_class_name !== null) {
                            $object = new TNamedObject($this_class_name, true);

                            $if_types[$first_var_name] = [[new IsAClass($object, $third_arg_value === 'true')]];
                        }
                    } elseif ($class_node->getParts() === ['self']) {
                        if ($this_class_name !== null) {
                            $object = new TNamedObject($this_class_name);
                            $if_types[$first_var_name] = [[new IsAClass($object, $third_arg_value === 'true')]];
                        }
                    } elseif ($class_node->getParts() === ['parent']) {
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
     * What identifies the value of an operand: its id, or else where it is
     */
    private static function getOperandId(
        PhpParser\Node\Expr $expr,
        ?string $this_class_name,
        StatementsAnalyzer $source,
    ): string {
        return ExpressionIdentifier::getExtendedVarId($expr, $this_class_name, $source)
            ?? '*' . (int) $expr->getAttribute('startFilePos') . ':' . (int) $expr->getAttribute('endFilePos');
    }

    /**
     * The variable an operand compared with an integer is: `$a++` and `$a--` compare the value `$a` had before, so
     * when it is an integer, what `$a` holds afterwards is compared with the integer shifted by one. A variable the
     * operand evaluated after it writes (`$a < ($a = 10)`) no longer holds the compared value.
     */
    private static function getComparedVarId(
        PhpParser\Node\Expr $operand,
        ?PhpParser\Node\Expr $later_operand,
        ?string $this_class_name,
        FileSource $source,
        int &$compared_value,
    ): ?string {
        $shift = 0;

        if ($operand instanceof PhpParser\Node\Expr\PostInc || $operand instanceof PhpParser\Node\Expr\PostDec) {
            $shift = $operand instanceof PhpParser\Node\Expr\PostInc ? 1 : -1;

            if (!$source instanceof StatementsAnalyzer
                || !($old_type = $source->node_data->getType($operand))
                || !$old_type->isInt()
                || $compared_value === ($shift === 1 ? PHP_INT_MAX : PHP_INT_MIN)
            ) {
                return null;
            }

            $operand = $operand->var;
        }

        $var_id = ExpressionIdentifier::getExtendedVarId($operand, $this_class_name, $source);

        if ($var_id === null) {
            return null;
        }

        if ($later_operand !== null) {
            $written_var_ids = FormulaGenerator::getAssignedVarIds(
                $later_operand,
                $this_class_name,
                $source instanceof StatementsAnalyzer ? $source->node_data : null,
            );

            while ($operand instanceof PhpParser\Node\Expr\Assign
                || $operand instanceof PhpParser\Node\Expr\AssignRef
                || $operand instanceof PhpParser\Node\Expr\AssignOp\Coalesce
                || $operand instanceof PhpParser\Node\Expr\PreInc
                || $operand instanceof PhpParser\Node\Expr\PreDec
            ) {
                $operand = $operand->var;
            }

            $root_var_id = ExpressionIdentifier::getRootVarId($operand, $this_class_name, $source);

            if ($root_var_id === null ? $written_var_ids !== [] : isset($written_var_ids[$root_var_id])) {
                return null;
            }
        }

        $compared_value += $shift;

        return $var_id;
    }

    /**
     * The operand a value compared with an operand of this type is identical to, unless the type has a single value
     * and says it all: two comparisons with different values of the same type are not each other's negation
     *
     * @psalm-pure
     */
    public static function getComparand(Atomic $type, string $operand_id): ?string
    {
        return $type instanceof TLiteralInt
            || $type instanceof TLiteralFloat
            || $type instanceof TLiteralString
            || $type instanceof TEnumCase
            || $type instanceof TNull
            || $type instanceof TTrue
            || $type instanceof TFalse
            ? null
            : $operand_id;
    }

    /**
     * @return non-empty-list<IsIdentical>
     * @psalm-pure
     */
    private static function getIdenticalAssertions(Union $type, string $operand_id): array
    {
        $assertions = [];

        foreach ($type->getAtomicTypes() as $atomic_type) {
            $assertions[] = new IsIdentical($atomic_type, self::getComparand($atomic_type, $operand_id));
        }

        return $assertions;
    }

    /**
     * A string matching a pattern anchored at both ends holds only the characters it allows, so it can't carry some
     * taints (see PatternCharacters)
     *
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function getPatternMatchAssertions(
        PhpParser\Node\Expr\FuncCall $expr,
        StatementsAnalyzer $source,
        ?string $this_class_name,
        bool $negated = false,
    ): array {
        $args = $expr->getArgs();
        $pattern_arg = self::getPregMatchArg($args, 0, 'pattern');
        $subject_arg = self::getPregMatchArg($args, 1, 'subject');

        // from an offset, the start of the subject isn't matched
        if ($pattern_arg === null || $subject_arg === null || self::hasNonZeroOffsetArg($expr, $source)) {
            return [];
        }

        $pattern_type = $source->node_data->getType($pattern_arg->value);

        if ($pattern_type === null || !$pattern_type->isSingleStringLiteral()) {
            return [];
        }

        $pattern = $pattern_type->getSingleStringLiteral()->value;
        $removed_taints = PatternCharacters::getTaintsRemoved($pattern)
            | PatternCharacters::getTaintsRemovedByStart($pattern);
        $subject_var_id = self::getStringFormVarId($subject_arg->value, $this_class_name, $source);

        $assertions = [];
        if ($removed_taints !== 0 && $subject_var_id !== null) {
            $assertions[$subject_var_id] = [[new MatchesPattern($removed_taints, $negated)]];
        }

        // the host of a URL matching a list of hosts
        $url_assertion = UrlHosts::getAssertion($source, $pattern, $subject_arg->value, $negated);
        if ($url_assertion !== null) {
            $assertions[$url_assertion[0]] = [[$url_assertion[1]]];
        }

        return $assertions === [] ? [] : [$assertions];
    }

    /**
     * A URL starting with a literal such as `https://` or `/` has a scheme the rest of it can't choose
     *
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function getUrlStartAssertions(
        PhpParser\Node\Expr\FuncCall $expr,
        StatementsAnalyzer $source,
        ?string $this_class_name,
    ): array {
        $args = $expr->getArgs();
        $haystack_arg = $args[0] ?? null;
        $needle_arg = $args[1] ?? null;
        $needle_type = $needle_arg ? $source->node_data->getType($needle_arg->value) : null;

        if ($haystack_arg === null
            || $needle_arg === null
            || $haystack_arg->name !== null
            || $needle_arg->name !== null
            || $needle_type === null
            || !$needle_type->allStringLiterals()
        ) {
            return [];
        }

        foreach ($needle_type->getLiteralStrings() as $needle) {
            if (!StringContext::fixesUrlScheme($needle->value)) {
                return [];
            }
        }

        $haystack_var_id = self::getStringFormVarId($haystack_arg->value, $this_class_name, $source);

        return $haystack_var_id === null
            ? []
            : [[$haystack_var_id => [[new MatchesPattern(TaintKind::INPUT_URL_SCHEME)]]]];
    }

    /**
     * Whether $expr can be falsy other than by being false, so that comparing it to false strictly doesn't tell
     * whether it is truthy
     *
     * @psalm-capabilities read-props
     */
    private static function canBeFalsyButNotFalse(PhpParser\Node\Expr $expr, FileSource $source): bool
    {
        $type = $source instanceof StatementsAnalyzer ? $source->node_data->getType($expr) : null;

        foreach ($type?->getAtomicTypes() ?? [] as $atomic) {
            if (!($atomic instanceof TBool && !$atomic instanceof TTrue) && !$atomic->isTruthy()) {
                return true;
            }
        }

        return false;
    }

    /**
     * The argument of preg_match() given to the parameter at $position, named $name
     *
     * @param list<PhpParser\Node\Arg> $args
     * @psalm-mutation-free
     */
    private static function getPregMatchArg(array $args, int $position, string $name): ?PhpParser\Node\Arg
    {
        foreach ($args as $offset => $arg) {
            if ($arg->name !== null ? $arg->name->name === $name : $offset === $position) {
                return $arg;
            }
        }

        return null;
    }

    /**
     * The preg_match() call of `preg_match(...) === 1` or `1 !== preg_match(...)`, which tell whether it matched
     *
     * @psalm-mutation-free
     */
    private static function getPatternMatchComparedToOne(
        PhpParser\Node\Expr\BinaryOp $conditional,
    ): ?PhpParser\Node\Expr\FuncCall {
        $sides = [[$conditional->left, $conditional->right], [$conditional->right, $conditional->left]];

        foreach ($sides as [$call, $one]) {
            if ($call instanceof PhpParser\Node\Expr\FuncCall
                && $call->name instanceof PhpParser\Node\Name
                && strtolower($call->name->name) === 'preg_match'
                && isset($call->getArgs()[1])
                && $one instanceof PhpParser\Node\Scalar\Int_
                && $one->value === 1
            ) {
                return $call;
            }
        }

        return null;
    }

    /**
     * Whether preg_match() is given an offset the analysis can't tell is 0
     *
     * @psalm-capabilities read-props
     */
    private static function hasNonZeroOffsetArg(PhpParser\Node\Expr\FuncCall $expr, StatementsAnalyzer $source): bool
    {
        foreach ($expr->getArgs() as $offset => $arg) {
            if ($arg->unpack) {
                return true;
            }

            if ($arg->name !== null ? $arg->name->name === 'offset' : $offset === 4) {
                $type = $source->node_data->getType($arg->value);

                return $type === null || !$type->isSingleIntLiteral() || $type->getSingleIntLiteral()->value !== 0;
            }
        }

        return false;
    }

    /**
     * The id of the variable $expr is, or casts to a string: a scalar or null, whose string form is its value
     * where it can carry taints
     */
    private static function getStringFormVarId(
        PhpParser\Node\Expr $expr,
        ?string $this_class_name,
        StatementsAnalyzer $source,
    ): ?string {
        if ($expr instanceof PhpParser\Node\Expr\Cast\String_) {
            $type = $source->node_data->getType($expr->expr);

            if ($type === null) {
                return null;
            }

            foreach ($type->getAtomicTypes() as $atomic) {
                if (!$atomic instanceof Scalar && !$atomic instanceof TNull) {
                    return null;
                }
            }

            $expr = $expr->expr;
        }

        return ExpressionIdentifier::getExtendedVarId($expr, $this_class_name, $source);
    }

    /**
     * The variable cast to a string an in_array() check is given as its needle (`in_array((string) $x, ["ls"])`),
     * if only a string has the string form of each value it can find. Its value is then the one found.
     */
    private static function getStringCastNeedleVarId(
        PhpParser\Node\Expr\FuncCall $expr,
        StatementsAnalyzer $source,
        ?string $this_class_name,
    ): ?string {
        $args = $expr->getArgs();

        if (!isset($args[0], $args[1]) || !$args[0]->value instanceof PhpParser\Node\Expr\Cast\String_) {
            return null;
        }

        $needle = $args[0]->value->expr;
        $needle_type = $source->node_data->getType($needle);
        $haystack_type = $source->node_data->getType($args[1]->value);

        if ($needle_type === null || $haystack_type === null) {
            return null;
        }

        foreach ($haystack_type->getAtomicTypes() as $haystack) {
            if (!$haystack instanceof TKeyedArray || $haystack->fallback_params !== null) {
                return null;
            }

            foreach ($haystack->getGenericValueType()->getAtomicTypes() as $value) {
                if (!$value instanceof TLiteralString) {
                    return null;
                }

                foreach ($needle_type->getAtomicTypes() as $atomic) {
                    if (self::mayBeNonStringWithStringForm($atomic, $value->value) !== false) {
                        return null;
                    }
                }
            }
        }

        return ExpressionIdentifier::getExtendedVarId($needle, $this_class_name, $source);
    }

    /**
     * Whether a value of $atomic that isn't a string may have $string as its string form, null if its string form
     * isn't known
     *
     * @psalm-pure
     */
    private static function mayBeNonStringWithStringForm(Atomic $atomic, string $string): ?bool
    {
        $numeric = is_numeric($string) || in_array($string, ['INF', '-INF', 'NAN'], true);

        return match (true) {
            $atomic instanceof TString => false,
            $atomic instanceof TInt,
            $atomic instanceof TFloat,
            $atomic instanceof TNumeric,
            $atomic instanceof TArrayKey => $numeric,
            $atomic instanceof TBool => $string === '1' || $string === '',
            $atomic instanceof TNull => $string === '',
            $atomic instanceof TScalar => $numeric || $string === '1' || $string === '',
            $atomic instanceof TArray,
            $atomic instanceof TKeyedArray,
            $atomic instanceof TClassStringMap => $string === 'Array',
            default => null,
        };
    }

    /**
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function getInarrayAssertions(
        PhpParser\Node\Expr\FuncCall $expr,
        StatementsAnalyzer $source,
        ?string $first_var_name,
        string $haystack,
    ): array {
        $if_types = [];

        if ($first_var_name
            && ($second_arg_type = $source->node_data->getType($expr->getArgs()[1]->value))
            && ($first_arg = $expr->getArgs()[0]->value ?? null)
            && !$first_arg instanceof PhpParser\Node\Expr\ClassConstFetch
        ) {
            foreach ($second_arg_type->getAtomicTypes() as $atomic_type) {
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
                            $assertions[] = new InArray($value_type, $haystack);
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
                                $assertions[] = new InArray($value_type, $haystack);
                            }
                        }
                    }

                    if ($assertions !== []) {
                        $if_types[$first_var_name] = [$assertions];
                    }
                } elseif ($second_arg_type->isSingle() && self::isArrayTemplate($atomic_type)) {
                    $value_of_type = new Union([new TTemplateValueOf(
                        $atomic_type->param_name,
                        $atomic_type->defining_class,
                        new Union([$atomic_type]),
                    )]);

                    // the values of two templates can't be intersected
                    if (($first_var_type = $source->node_data->getType($first_arg))
                        && Type::intersectUnionTypes($value_of_type, $first_var_type, $source->getCodebase()) !== null
                    ) {
                        $if_types[$first_var_name] = [[new InArray($value_of_type, $haystack)]];
                    }
                }
            }
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @psalm-assert-if-true TTemplateParam $atomic_type
     * @psalm-pure
     */
    private static function isArrayTemplate(?Atomic $atomic_type): bool
    {
        return $atomic_type instanceof TTemplateParam
            && array_all(
                $atomic_type->as->getAtomicTypes(),
                static fn(Atomic $as): bool => $as instanceof TArray || $as instanceof TKeyedArray,
            );
    }

    /**
     * What a key that exists in an array as an int is: the int, or the string that PHP casts to it, whichever the type
     * of the value looked up can be. A value that is not an int or a string (`true`, `1.0`) matches it too, so any
     * other type keeps the loose comparison
     *
     * @return list<Assertion>
     * @psalm-pure
     */
    private static function getIntKeyAssertions(int $key, Union $looked_up_type): array
    {
        foreach ($looked_up_type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TTemplateParam) {
                $atomic_type = $atomic_type->as->isSingle() ? $atomic_type->as->getSingleAtomic() : $atomic_type;
            }

            if (!$atomic_type instanceof TInt && !$atomic_type instanceof TString) {
                return [new IsLooselyEqual(new TLiteralInt($key))];
            }
        }

        $assertions = [];

        if (!$looked_up_type->isString(true)) {
            $assertions[] = new IsIdentical(new TLiteralInt($key));
        }

        if (!$looked_up_type->isInt(true)) {
            $assertions[] = new IsIdentical(TLiteralString::make((string) $key));
        }

        return $assertions;
    }

    /**
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function getArrayKeyExistsAssertions(
        PhpParser\Node\Expr\FuncCall $expr,
        ?Union $first_var_type,
        ?string $first_var_name,
        FileSource $source,
        ?string $this_class_name,
        bool $check_literal_keys,
    ): array {
        if ($check_literal_keys
            && $first_var_type
            && $source instanceof StatementsAnalyzer
            && ($second_var_type = $source->node_data->getType($expr->getArgs()[1]->value))
        ) {
            ArrayFetchAnalyzer::validateArrayOffset(
                $source,
                $expr,
                $second_var_type,
                $first_var_type,
            );
        }
        $if_types = [];

        $literal_assertions = [];

        $safe_to_track_literals = true;

        if (isset($expr->getArgs()[0])
            && isset($expr->getArgs()[1])
            && $first_var_type
            && $first_var_name !== null
            && !$expr->getArgs()[0]->value instanceof PhpParser\Node\Expr\ClassConstFetch
            && $source instanceof StatementsAnalyzer
            && ($second_var_type = $source->node_data->getType($expr->getArgs()[1]->value))
        ) {
            foreach ($second_var_type->getAtomicTypes() as $atomic_type) {
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
                            $string_to_int = ArrayAnalyzer::getLiteralArrayKeyInt($array_literal_type->value);
                            if ($string_to_int === false) {
                                $literal_assertions[] = new IsIdentical($array_literal_type);
                            } else {
                                array_push(
                                    $literal_assertions,
                                    ...self::getIntKeyAssertions($string_to_int, $first_var_type),
                                );
                            }
                        }
                    } elseif ($key_type->allIntLiterals() && !$key_type->possibly_undefined) {
                        foreach ($key_type->getLiteralInts() as $array_literal_type) {
                            array_push(
                                $literal_assertions,
                                ...self::getIntKeyAssertions($array_literal_type->value, $first_var_type),
                            );
                        }
                    } else {
                        $safe_to_track_literals = false;
                    }
                }
            }

            $array_atomic = $second_var_type->isSingle() ? $second_var_type->getSingleAtomic() : null;

            if (self::isArrayTemplate($array_atomic)) {
                $key_of_type = new Union([new TTemplateKeyOf(
                    $array_atomic->param_name,
                    $array_atomic->defining_class,
                    new Union([$array_atomic]),
                )]);

                // only an int finds no other key than itself: '0' finds the key 0, null the key ''. A string that
                // keys of T can be is still narrowed, though '0' may have found the int key 0
                if (Type::intersectUnionTypes($key_of_type, $first_var_type, $source->getCodebase()) !== null
                    && array_all(
                        $first_var_type->getAtomicTypes(),
                        static fn(Atomic $key_atomic): bool => $key_atomic instanceof TInt
                            || Type::intersectUnionTypes(
                                $key_of_type,
                                new Union([$key_atomic]),
                                $source->getCodebase(),
                            ) !== null,
                    )
                ) {
                    $template_key_var_name = $first_var_name;
                    $template_key_assertion = new InArray(
                        $key_of_type,
                        self::getOperandId($expr->getArgs()[1]->value, $this_class_name, $source),
                    );
                }
            }
        }

        if ($literal_assertions && $first_var_name !== null && $safe_to_track_literals) {
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
                        $string_to_int = ArrayAnalyzer::getLiteralArrayKeyInt($first_arg->value->value);
                        $first_var_name = $string_to_int === false
                            ? ExpressionIdentifier::quoteOffset($first_arg->value->value)
                            : (string) $string_to_int;
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
                            $string_to_int = ArrayAnalyzer::getLiteralArrayKeyInt(
                                $const_type->getSingleStringLiteral()->value,
                            );
                            $first_var_name = $string_to_int === false
                                ? ExpressionIdentifier::quoteOffset($const_type->getSingleStringLiteral()->value)
                                : (string) $string_to_int;
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
                    // only one key is known to exist: it is not any of the keys of a union
                    if ($first_var_type->isSingleStringLiteral()) {
                        $array_literal_type = $first_var_type->getSingleStringLiteral();
                        $string_to_int = ArrayAnalyzer::getLiteralArrayKeyInt($array_literal_type->value);
                        $literal_key = $string_to_int === false
                            ? ExpressionIdentifier::quoteOffset($array_literal_type->value)
                            : $string_to_int;
                        $if_types[$array_root . "[" . $literal_key . "]"] = [[new ArrayKeyExists()]];
                    } elseif ($first_var_type->isSingleIntLiteral()) {
                        $array_literal_type = $first_var_type->getSingleIntLiteral();
                        $if_types[$array_root . "[" . $array_literal_type->value . "]"] = [[new ArrayKeyExists()]];
                    }
                }

                if ($first_var_name !== null
                    && (!strpos($first_var_name, '->')
                        || $expr->getArgs()[0]->value instanceof PhpParser\Node\Expr\PropertyFetch)
                    && !strpos($first_var_name, '[')
                ) {
                    $if_types[$array_root . '[' . $first_var_name . ']'] = [[new ArrayKeyExists()]];
                }
            }
        }

        if (isset($template_key_var_name, $template_key_assertion)) {
            $if_types[$template_key_var_name] = [[$template_key_assertion]];
        }

        return $if_types ? [$if_types] : [];
    }

    /**
     * @param Greater|GreaterOrEqual|Smaller|SmallerOrEqual $conditional
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function getOrderComparisonAssertions(
        PhpParser\Node\Expr\BinaryOp $conditional,
        FileSource $source,
        ?string $this_class_name,
    ): array {
        $if_types = [];

        $min_count = null;
        $count_equality_position = self::hasNonEmptyCountEqualityCheck($conditional, $source, $min_count);
        $max_count = null;
        $count_inequality_position = self::hasLessThanCountEqualityCheck($conditional, $source, $max_count);
        $literal_value_comparison = null;
        $literal_value_position = self::hasIntLiteralComparison(
            $source,
            $conditional,
            $literal_value_comparison,
        );

        if ($count_equality_position !== false) {
            /** @var PhpParser\Node\Expr\FuncCall $counted_expr */
            $counted_expr = $count_equality_position === self::ASSIGNMENT_TO_RIGHT
                ? $conditional->left
                : $conditional->right;

            $var_name = ExpressionIdentifier::getExtendedVarId(
                $counted_expr->getArgs()[0]->value,
                $this_class_name,
                $source,
            );

            if ($var_name) {
                if (self::hasReconcilableNonEmptyCountEqualityCheck($conditional, $source)) {
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

        if ($count_inequality_position !== false) {
            /** @var PhpParser\Node\Expr\FuncCall $count_expr */
            $count_expr = $count_inequality_position === self::ASSIGNMENT_TO_RIGHT
                ? $conditional->left
                : $conditional->right;

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

        if ($literal_value_position !== false && $literal_value_comparison !== null) {
            $var_name = self::getComparedVarId(
                $literal_value_position === self::ASSIGNMENT_TO_RIGHT ? $conditional->left : $conditional->right,
                $literal_value_position === self::ASSIGNMENT_TO_RIGHT ? $conditional->right : null,
                $this_class_name,
                $source,
                $literal_value_comparison,
            );

            if ($var_name !== null) {
                $or_equal = $conditional instanceof GreaterOrEqual || $conditional instanceof SmallerOrEqual;

                // the variable is on the greater side of the comparison
                if (($literal_value_position === self::ASSIGNMENT_TO_RIGHT)
                    === ($conditional instanceof Greater || $conditional instanceof GreaterOrEqual)
                ) {
                    $if_types[$var_name] = [[$or_equal
                        ? new IsGreaterThanOrEqualTo($literal_value_comparison)
                        : new IsGreaterThan($literal_value_comparison)]];
                } else {
                    $if_types[$var_name] = [[$or_equal
                        ? new IsLessThanOrEqualTo($literal_value_comparison)
                        : new IsLessThan($literal_value_comparison)]];
                }
            }

            return $if_types ? [$if_types] : [];
        }

        return [];
    }

    /**
     * A comparison of strlen() with 0 or 1 that tells whether the string is empty
     *
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function getStringLengthAssertions(
        PhpParser\Node\Expr $conditional,
        ?string $this_class_name,
        FileSource $source,
    ): array {
        $comparison = self::getStringLengthComparison($conditional, $this_class_name, $source);

        if ($comparison === null) {
            return [];
        }

        [$var_name, $operator, $compared_length] = $comparison;

        // only a condition that holds exactly when the string is (non-)empty, as its negation holds otherwise
        $is_non_empty = match ($operator) {
            '>', '!=' => $compared_length === 0,
            '>=' => $compared_length === 1,
            default => false,
        };
        $is_empty = match ($operator) {
            '<' => $compared_length === 1,
            '<=', '==' => $compared_length === 0,
            default => false,
        };

        if (!$is_non_empty && !$is_empty) {
            return [];
        }

        $empty_string = TLiteralString::make('');

        return [[$var_name => [[$is_non_empty ? new IsNotIdentical($empty_string) : new IsIdentical($empty_string)]]]];
    }

    /**
     * A comparison of strlen() that implies the string isn't empty in only one of its branches, like
     * `strlen($s) === 3` when true or `strlen($s) < 3` when false
     *
     * @return array{string, bool}|null the string, and whether it's non-empty when the comparison is true
     */
    public static function getOneWayNonEmptyStringLengthComparison(
        PhpParser\Node\Expr $conditional,
        ?string $this_class_name,
        FileSource $source,
    ): ?array {
        $comparison = self::getStringLengthComparison($conditional, $this_class_name, $source);

        if ($comparison === null) {
            return null;
        }

        [$var_name, $operator, $compared_length, $argument_type] = $comparison;

        // the comparison would only be reported as redundant for a string that can't be empty anyway
        if ($argument_type->isNonEmptyString(true)) {
            return null;
        }

        return match (true) {
            $operator === '==' && $compared_length > 0,
            $operator === '>' && $compared_length > 0,
            $operator === '>=' && $compared_length > 1 => [$var_name, true],
            $operator === '!=' && $compared_length > 0,
            $operator === '<' && $compared_length > 1,
            $operator === '<=' && $compared_length > 0 => [$var_name, false],
            default => null,
        };
    }

    /**
     * @return array{string, '=='|'!='|'>'|'>='|'<'|'<=', int, Union}|null
     */
    private static function getStringLengthComparison(
        PhpParser\Node\Expr $conditional,
        ?string $this_class_name,
        FileSource $source,
    ): ?array {
        if (!$conditional instanceof BinaryOp || !$source instanceof StatementsAnalyzer) {
            return null;
        }

        $operator = match (true) {
            $conditional instanceof BinaryOp\Identical, $conditional instanceof BinaryOp\Equal => '==',
            $conditional instanceof BinaryOp\NotIdentical, $conditional instanceof BinaryOp\NotEqual => '!=',
            $conditional instanceof BinaryOp\Greater => '>',
            $conditional instanceof BinaryOp\GreaterOrEqual => '>=',
            $conditional instanceof BinaryOp\Smaller => '<',
            $conditional instanceof BinaryOp\SmallerOrEqual => '<=',
            default => null,
        };

        if ($operator === null) {
            return null;
        }

        $left_argument = self::getStringLengthArgument($conditional->left, $source);
        $right_argument = self::getStringLengthArgument($conditional->right, $source);
        if ($left_argument && $conditional->right instanceof PhpParser\Node\Scalar\Int_) {
            $length_argument = $left_argument;
            $compared_length = $conditional->right->value;
        } elseif ($right_argument && $conditional->left instanceof PhpParser\Node\Scalar\Int_) {
            $length_argument = $right_argument;
            $compared_length = $conditional->left->value;
            $operator = match ($operator) {
                '>' => '<',
                '>=' => '<=',
                '<' => '>',
                '<=' => '>=',
                default => $operator,
            };
        } else {
            return null;
        }

        $var_name = ExpressionIdentifier::getExtendedVarId(
            $length_argument,
            $this_class_name,
            $source,
        );

        if ($var_name === null) {
            return null;
        }

        // in the non-strict mode the length of null and false is 0 too: only a string is empty exactly when it is
        $argument_type = $source->node_data->getType($length_argument);

        if ($argument_type === null || !$argument_type->isString(true)) {
            return null;
        }

        return [$var_name, $operator, $compared_length, $argument_type];
    }

    private static function getStringLengthArgument(
        PhpParser\Node\Expr $expr,
        StatementsAnalyzer $source,
    ): ?PhpParser\Node\Expr {
        if (!$expr instanceof PhpParser\Node\Expr\FuncCall
            || $expr->isFirstClassCallable()
            || !in_array(self::getCalledFunctionId($expr, $source), ['strlen', 'mb_strlen'], true)
        ) {
            return null;
        }

        $first_argument = $expr->getArgs()[0] ?? null;

        return $first_argument && !$first_argument->unpack && !$first_argument->name
            ? $first_argument->value
            : null;
    }

    /**
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>>
     */
    private static function getAndCheckInstanceofAssertions(
        PhpParser\Node\Expr\Instanceof_ $conditional,
        ?Codebase $codebase,
        FileSource $source,
        ?string $this_class_name,
        bool $inside_negation,
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

                        // instanceof only checks the class: compared with the type arguments, the bare
                        // class would not fit an `Iterator<K, V>` whose K is a template
                        if (!UnionTypeComparator::canExpressionTypesBeIdentical(
                            $codebase,
                            $instanceof_type,
                            self::withoutTypeArguments($var_type),
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
     * @psalm-pure
     */
    private static function withoutTypeArguments(Union $type): Union
    {
        $atomic_types = [];

        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TGenericObject) {
                $atomic_type = new TNamedObject(
                    $atomic_type->value,
                    $atomic_type->is_static,
                    $atomic_type->definite_class,
                    $atomic_type->extra_types,
                    $atomic_type->from_docblock,
                );
            }

            $atomic_types[] = $atomic_type;
        }

        return $type->setTypes($atomic_types);
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
        PhpParser\Node\Expr\BinaryOp $conditional,
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
                    $conditional instanceof NotEqual || $conditional instanceof NotIdentical
                        ? new RedundantConditionGivenDocblockType(
                            'Docblock-defined type ' . $var_type->getId()
                                . ' can never contain ' . $other_type->getId(),
                            new CodeLocation($source, $conditional),
                            $var_type->getId() . ' ' . $other_type->getId(),
                        )
                        : new DocblockTypeContradiction(
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

    /**
     * The rules of a docblock assertion are alternatives: the variable satisfies at least one of them
     *
     * @param list<Assertion> $rules
     * @return non-empty-list<non-empty-list<Assertion>>
     * @psalm-pure
     */
    private static function getAlternatives(array $rules): array
    {
        assert($rules !== []);

        return [$rules];
    }

    /**
     * The negation of getAlternatives(): the variable satisfies none of the rules
     *
     * @param list<Assertion> $rules
     * @return non-empty-list<non-empty-list<Assertion>>
     * @psalm-pure
     */
    private static function getNegatedAlternatives(array $rules): array
    {
        assert($rules !== []);

        return array_map(static fn(Assertion $rule): array => [$rule->getNegation()], $rules);
    }

    /**
     * @psalm-pure
     */
    public static function isArrayOffsetAssertionId(string $var_id): bool
    {
        return preg_match('/^\d+:\w+\[\d+:\w+]$/', $var_id) === 1;
    }

    /**
     * The id of the offset of the argument that an assertion on `<array index>:<array name>[<key index>:<key name>]`
     * is about, such as `$c[0]` or `$c[$key]`: null if the arguments don't name one
     *
     * @param list<PhpParser\Node\Arg> $args
     */
    public static function getArrayOffsetVarId(
        string $var_id,
        array $args,
        ?string $this_class_name,
        FileSource $source,
    ): ?string {
        if (preg_match('/^(\d+):(\w+)\[(\d+):(\w+)]$/', $var_id, $matches) !== 1
            || !isset($matches[1], $matches[2], $matches[3], $matches[4])
        ) {
            return null;
        }

        $array_arg = self::getArgumentOfParameter($args, (int) $matches[1], $matches[2]);
        $key_arg = self::getArgumentOfParameter($args, (int) $matches[3], $matches[4]);

        if ($array_arg === null || $key_arg === null) {
            return null;
        }

        return ExpressionIdentifier::getExtendedVarId(
            new VirtualArrayDimFetch($array_arg->value, $key_arg->value),
            $this_class_name,
            $source,
        );
    }

    /**
     * The argument passed for the parameter at $index called $name: by its name, or by its position if it isn't named
     *
     * @param list<PhpParser\Node\Arg> $args
     * @psalm-capabilities read-props
     */
    private static function getArgumentOfParameter(array $args, int $index, string $name): ?PhpParser\Node\Arg
    {
        foreach ($args as $position => $arg) {
            if ($arg->unpack) {
                return null;
            }

            if ($arg->name !== null ? $arg->name->name === $name : $position === $index) {
                return $arg;
            }
        }

        return null;
    }

    /**
     * @psalm-capabilities read-props
     */
    public static function isPropertyImmutableOnArgument(
        string                       $property,
        ?Union                       $type,
        ClassLikeStorageProvider     $class_provider,
        string                       $arg_var_id,
    ): ?string {
        $name = ltrim($arg_var_id, '$');

        if (null === $type) {
            return 'Cannot resolve a type of variable ' . $name;
        }

        foreach ($type->getAtomicTypes() as $type) {
            if ($type instanceof TObjectWithProperties) {
                if (!isset($type->properties[$property])) {
                    return sprintf(
                        'Property %s is not defined on variable %s so the assertion cannot be applied',
                        $property,
                        $name,
                    );
                }

                continue;
            }

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
                if ($magic_getter === null || !$magic_getter->isMutationFree()) {
                    return "{$class_definition->name}::__get needs more than read-props,"
                        . " so the assertion cannot be applied";
                }
            }
        }

        return null;
    }
}
