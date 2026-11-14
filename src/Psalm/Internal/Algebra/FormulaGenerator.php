<?php

declare(strict_types=1);

namespace Psalm\Internal\Algebra;

use Override;
use PhpParser;
use Psalm\Codebase;
use Psalm\FileSource;
use Psalm\Internal\Algebra;
use Psalm\Internal\Analyzer\Statements\Expression\AssertionFinder;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Clause;
use Psalm\Node\Expr\BinaryOp\VirtualBooleanAnd;
use Psalm\Node\Expr\BinaryOp\VirtualBooleanOr;
use Psalm\Node\Expr\BinaryOp\VirtualEqual;
use Psalm\Node\Expr\BinaryOp\VirtualIdentical;
use Psalm\Node\Expr\BinaryOp\VirtualNotEqual;
use Psalm\Node\Expr\BinaryOp\VirtualNotIdentical;
use Psalm\Node\Expr\VirtualBooleanNot;
use Psalm\Node\Expr\VirtualPropertyFetch;
use Psalm\Storage\Assertion\Falsy;
use Psalm\Storage\Assertion\IsNotType;
use Psalm\Storage\Assertion\Truthy;
use Psalm\Type\Atomic\TNull;

use function array_diff_key;
use function array_filter;
use function array_map;
use function array_values;
use function count;
use function spl_object_id;
use function str_starts_with;
use function strcspn;
use function strtolower;
use function substr;

/**
 * @internal
 */
final class FormulaGenerator
{
     /**
     * @return list<Clause>
     */
    public static function getFormula(
        int $conditional_object_id,
        int $creating_object_id,
        PhpParser\Node\Expr $conditional,
        ?string $this_class_name,
        FileSource $source,
        ?Codebase $codebase = null,
        bool $inside_negation = false,
        bool $cache = true,
    ): array {
        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd ||
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
        ) {
            $left_assertions = self::getFormula(
                $conditional_object_id,
                spl_object_id($conditional->left),
                $conditional->left,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation,
                $cache,
            );

            $right_assertions = self::getFormula(
                $conditional_object_id,
                spl_object_id($conditional->right),
                $conditional->right,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation,
                $cache,
            );

            // what the left side tested of a variable that the right side assigns is about its previous value
            $right_assigned_var_ids = self::getAssignedVarIds($conditional->right, $this_class_name);

            if ($right_assigned_var_ids) {
                $left_assertions = array_values(array_filter(
                    $left_assertions,
                    static function (Clause $clause) use ($right_assigned_var_ids): bool {
                        foreach ($clause->possibilities as $var_id => $_) {
                            foreach ($right_assigned_var_ids as $assigned_var_id => $__) {
                                if ($var_id === $assigned_var_id
                                    || str_starts_with($var_id, $assigned_var_id . '[')
                                    || str_starts_with($var_id, $assigned_var_id . '->')
                                ) {
                                    return false;
                                }
                            }
                        }

                        return true;
                    },
                ));
            }

            return [...$left_assertions, ...$right_assertions];
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr ||
            $conditional instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
        ) {
            $left_clauses = self::getFormula(
                $conditional_object_id,
                spl_object_id($conditional->left),
                $conditional->left,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation,
                $cache,
            );

            $right_clauses = self::getFormula(
                $conditional_object_id,
                spl_object_id($conditional->right),
                $conditional->right,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation,
                $cache,
            );

            // the right side is only evaluated where the left one is false: a clause that says nothing of what it
            // assigns (see Algebra::combineOredClauses()) can't keep what the left one tested of the previous value
            $right_assigned_var_ids = self::getAssignedVarIds($conditional->right, $this_class_name);

            if ($right_assigned_var_ids) {
                $right_clauses = array_map(
                    static fn(Clause $clause): Clause => $clause->wedge || !$clause->reconcilable
                        ? new Clause(
                            $clause->possibilities,
                            $clause->creating_conditional_id,
                            $clause->creating_object_id,
                            $clause->wedge,
                            $clause->reconcilable,
                            $clause->generated,
                            [...$clause->redefined_vars, ...$right_assigned_var_ids],
                        )
                        : $clause,
                    $right_clauses,
                );
            }

            return Algebra::combineOredClauses($left_clauses, $right_clauses, $conditional_object_id);
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
        ) {
            $nullsafe_expansion = self::expandNullsafePropertyNullComparison($conditional, $source);

            if ($nullsafe_expansion !== null) {
                return self::getFormula(
                    $conditional_object_id,
                    $conditional_object_id,
                    $nullsafe_expansion,
                    $this_class_name,
                    $source,
                    $codebase,
                    $inside_negation,
                    false,
                );
            }
        }

        if ($conditional instanceof PhpParser\Node\Expr\BooleanNot) {
            if ($conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr) {
                $and_expr = new VirtualBooleanAnd(
                    new VirtualBooleanNot(
                        $conditional->expr->left,
                        $conditional->getAttributes(),
                    ),
                    new VirtualBooleanNot(
                        $conditional->expr->right,
                        $conditional->getAttributes(),
                    ),
                    $conditional->expr->getAttributes(),
                );

                return self::getFormula(
                    $conditional_object_id,
                    $conditional_object_id,
                    $and_expr,
                    $this_class_name,
                    $source,
                    $codebase,
                    $inside_negation,
                    false,
                );
            }

            if ($conditional->expr instanceof PhpParser\Node\Expr\Isset_
                && count($conditional->expr->vars) > 1
            ) {
                $anded_assertions = null;

                if ($cache && $source instanceof StatementsAnalyzer) {
                    $anded_assertions = $source->node_data->getAssertions($conditional->expr);
                }

                if ($anded_assertions === null) {
                    $anded_assertions = AssertionFinder::scrapeAssertions(
                        $conditional->expr,
                        $this_class_name,
                        $source,
                        $codebase,
                        $inside_negation,
                        $cache,
                    );

                    if ($cache && $source instanceof StatementsAnalyzer) {
                        $source->node_data->setAssertions($conditional->expr, $anded_assertions);
                    }
                }

                $clauses = [];

                foreach ($anded_assertions as $assertions) {
                    foreach ($assertions as $var => $anded_types) {
                        $redefined = false;

                        if ($var[0] === '=') {
                            $var = substr($var, 1);
                            $redefined = true;
                        }

                        foreach ($anded_types as $orred_types) {
                            $mapped_orred_types = [];
                            foreach ($orred_types as $orred_type) {
                                $mapped_orred_types[(string)$orred_type] = $orred_type;
                            }
                            $clauses[] = new Clause(
                                [$var => $mapped_orred_types],
                                $conditional_object_id,
                                spl_object_id($conditional->expr),
                                false,
                                true,
                                $orred_types[0]->hasEquality(),
                                $redefined ? [$var => true] : [],
                            );
                        }
                    }
                }

                return Algebra::negateFormula($clauses);
            }

            if ($conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                || $conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
            ) {
                $and_expr = new VirtualBooleanOr(
                    new VirtualBooleanNot(
                        $conditional->expr->left,
                        $conditional->getAttributes(),
                    ),
                    new VirtualBooleanNot(
                        $conditional->expr->right,
                        $conditional->getAttributes(),
                    ),
                    $conditional->expr->getAttributes(),
                );

                return self::getFormula(
                    $conditional_object_id,
                    spl_object_id($conditional->expr),
                    $and_expr,
                    $this_class_name,
                    $source,
                    $codebase,
                    $inside_negation,
                    false,
                );
            }

            return Algebra::negateFormula(
                self::getFormula(
                    $conditional_object_id,
                    spl_object_id($conditional->expr),
                    $conditional->expr,
                    $this_class_name,
                    $source,
                    $codebase,
                    !$inside_negation,
                ),
            );
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal
        ) {
            $false_pos = AssertionFinder::hasFalseVariable($conditional);
            $true_pos = AssertionFinder::hasTrueVariable($conditional);

            if ($false_pos === AssertionFinder::ASSIGNMENT_TO_RIGHT
                && ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal
                    || $conditional->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                    || $conditional->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
                    || $conditional->left instanceof PhpParser\Node\Expr\BooleanNot)
            ) {
                return Algebra::negateFormula(
                    self::getFormula(
                        $conditional_object_id,
                        spl_object_id($conditional->left),
                        $conditional->left,
                        $this_class_name,
                        $source,
                        $codebase,
                        !$inside_negation,
                        $cache,
                    ),
                );
            }

            if ($false_pos === AssertionFinder::ASSIGNMENT_TO_LEFT
                && ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal
                    || $conditional->right instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                    || $conditional->right instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
                    || $conditional->right instanceof PhpParser\Node\Expr\BooleanNot)
            ) {
                return Algebra::negateFormula(
                    self::getFormula(
                        $conditional_object_id,
                        spl_object_id($conditional->right),
                        $conditional->right,
                        $this_class_name,
                        $source,
                        $codebase,
                        !$inside_negation,
                        $cache,
                    ),
                );
            }

            if ($true_pos === AssertionFinder::ASSIGNMENT_TO_RIGHT
                && ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal
                    || $conditional->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                    || $conditional->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
                    || $conditional->left instanceof PhpParser\Node\Expr\BooleanNot)
            ) {
                return self::getFormula(
                    $conditional_object_id,
                    spl_object_id($conditional->left),
                    $conditional->left,
                    $this_class_name,
                    $source,
                    $codebase,
                    $inside_negation,
                    $cache,
                );
            }

            if ($true_pos === AssertionFinder::ASSIGNMENT_TO_LEFT
                && ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal
                    || $conditional->right instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                    || $conditional->right instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
                    || $conditional->right instanceof PhpParser\Node\Expr\BooleanNot)
            ) {
                return self::getFormula(
                    $conditional_object_id,
                    spl_object_id($conditional->right),
                    $conditional->right,
                    $this_class_name,
                    $source,
                    $codebase,
                    $inside_negation,
                    $cache,
                );
            }
        }

        if ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
        ) {
            $false_pos = AssertionFinder::hasFalseVariable($conditional);
            $true_pos = AssertionFinder::hasTrueVariable($conditional);

            if ($true_pos === AssertionFinder::ASSIGNMENT_TO_RIGHT
                && ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
                    || $conditional->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                    || $conditional->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
                    || $conditional->left instanceof PhpParser\Node\Expr\BooleanNot)
            ) {
                return Algebra::negateFormula(
                    self::getFormula(
                        $conditional_object_id,
                        spl_object_id($conditional->left),
                        $conditional->left,
                        $this_class_name,
                        $source,
                        $codebase,
                        !$inside_negation,
                        $cache,
                    ),
                );
            }

            if ($true_pos === AssertionFinder::ASSIGNMENT_TO_LEFT
                && ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
                    || $conditional->right instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                    || $conditional->right instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
                    || $conditional->right instanceof PhpParser\Node\Expr\BooleanNot)
            ) {
                return Algebra::negateFormula(
                    self::getFormula(
                        $conditional_object_id,
                        spl_object_id($conditional->right),
                        $conditional->right,
                        $this_class_name,
                        $source,
                        $codebase,
                        !$inside_negation,
                        $cache,
                    ),
                );
            }

            if ($false_pos === AssertionFinder::ASSIGNMENT_TO_RIGHT
                && ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
                    || $conditional->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                    || $conditional->left instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
                    || $conditional->left instanceof PhpParser\Node\Expr\BooleanNot)
            ) {
                return self::getFormula(
                    $conditional_object_id,
                    spl_object_id($conditional->left),
                    $conditional->left,
                    $this_class_name,
                    $source,
                    $codebase,
                    $inside_negation,
                    $cache,
                );
            }

            if ($false_pos === AssertionFinder::ASSIGNMENT_TO_LEFT
                && ($conditional instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
                    || $conditional->right instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                    || $conditional->right instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
                    || $conditional->right instanceof PhpParser\Node\Expr\BooleanNot)
            ) {
                return self::getFormula(
                    $conditional_object_id,
                    spl_object_id($conditional->right),
                    $conditional->right,
                    $this_class_name,
                    $source,
                    $codebase,
                    $inside_negation,
                    $cache,
                );
            }
        }

        if ($conditional instanceof PhpParser\Node\Expr\Cast\Bool_) {
            return self::getFormula(
                $conditional_object_id,
                spl_object_id($conditional->expr),
                $conditional->expr,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation,
                $cache,
            );
        }

        $conditional_ref = '*' . (int) $conditional->getAttribute('startFilePos')
            . ':' . (int) $conditional->getAttribute('endFilePos');

        $nullsafe_call = $conditional instanceof PhpParser\Node\Expr\Assign ? $conditional->expr : $conditional;

        if ($nullsafe_call instanceof PhpParser\Node\Expr\NullsafeMethodCall) {
            $receiver_id = ExpressionIdentifier::getExtendedVarId($nullsafe_call->var, $this_class_name, $source);

            $result_id = $conditional instanceof PhpParser\Node\Expr\Assign
                ? ExpressionIdentifier::getExtendedVarId($conditional->var, $this_class_name, $source)
                : $conditional_ref;

            if ($receiver_id !== null && $result_id !== null) {
                // a truthy result means the receiver wasn't null, but a falsy one says nothing about it
                $not_null = new IsNotType(new TNull());

                return [
                    new Clause(
                        [$result_id => ['truthy' => new Truthy()]],
                        $conditional_object_id,
                        $creating_object_id,
                    ),
                    new Clause(
                        [$result_id => ['falsy' => new Falsy()], $receiver_id => [(string) $not_null => $not_null]],
                        $conditional_object_id,
                        $creating_object_id,
                    ),
                ];
            }
        }

        $anded_assertions = null;

        if ($cache && $source instanceof StatementsAnalyzer) {
            $anded_assertions = $source->node_data->getAssertions($conditional);
        }

        if ($anded_assertions === null) {
            $anded_assertions = AssertionFinder::scrapeAssertions(
                $conditional,
                $this_class_name,
                $source,
                $codebase,
                $inside_negation,
                $cache,
            );

            if ($cache && $source instanceof StatementsAnalyzer) {
                $source->node_data->setAssertions($conditional, $anded_assertions);
            }
        }

        $clauses = [];

        foreach ($anded_assertions as $assertions) {
            foreach ($assertions as $var => $anded_types) {
                $redefined = false;

                if ($var[0] === '=') {
                    $var = substr($var, 1);
                    $redefined = true;
                }

                foreach ($anded_types as $orred_types) {
                    $mapped_orred_types = [];
                    foreach ($orred_types as $orred_type) {
                        $mapped_orred_types[(string)$orred_type] = $orred_type;
                    }
                    $clauses[] = new Clause(
                        [$var => $mapped_orred_types],
                        $conditional_object_id,
                        $creating_object_id,
                        false,
                        true,
                        $orred_types[0]->hasEquality(),
                        $redefined ? [$var => true] : [],
                    );
                }
            }
        }

        if ($clauses) {
            return $clauses;
        }

        $truthy = new Truthy();

        return [
            new Clause(
                [$conditional_ref => [(string) $truthy => $truthy]],
                $conditional_object_id,
                $creating_object_id,
            ),
        ];
    }

    /**
     * The variables that the right side of an `||` of the expression assigns, and its left side doesn't: where the
     * whole expression is false the right side has run, so what the negated left side says of them is about the
     * value they had before
     *
     * @return array<string, true>
     */
    public static function getVarsAssignedByOredRightSides(PhpParser\Node\Expr $expr, ?string $this_class_name): array
    {
        if (!$expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
            && !$expr instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
        ) {
            return [];
        }

        return [
            ...self::getVarsAssignedByOredRightSides($expr->left, $this_class_name),
            ...array_diff_key(
                self::getAssignedVarIds($expr->right, $this_class_name),
                self::getAssignedVarIds($expr->left, $this_class_name),
            ),
        ];
    }

    /**
     * The negation of a condition without what it says of a variable that the right side of one of its `||` assigns.
     * Where the condition is false that right side has run, so what the negated left side says of the variable is
     * about the value it had before; leaving out conjuncts of a negation keeps it true.
     *
     * @param list<Clause> $negated_clauses
     * @return list<Clause>
     */
    public static function withoutClausesOfReassignedVars(
        array $negated_clauses,
        PhpParser\Node\Expr $condition,
        ?string $this_class_name,
    ): array {
        $reassigned_var_ids = self::getVarsAssignedByOredRightSides($condition, $this_class_name);

        if (!$reassigned_var_ids) {
            return $negated_clauses;
        }

        return array_values(array_filter(
            $negated_clauses,
            static fn(Clause $clause): bool => !self::testsAnyVar($clause, $reassigned_var_ids),
        ));
    }

    /**
     * Whether the clause tests one of the variables, or an item or property of one
     *
     * @param array<string, mixed> $var_ids
     * @psalm-pure
     */
    private static function testsAnyVar(Clause $clause, array $var_ids): bool
    {
        foreach ($clause->possibilities as $var => $_) {
            if (isset($var_ids[$var]) || isset($var_ids[substr($var, 0, strcspn($var, '[-'))])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the formula of the condition leaves out what the left side of an `&&` tested of a variable that the right
     * side assigns: it holds where the whole condition does, so its negation has to be made from the negated condition
     * (`!A || !B`), where the right side is only evaluated if the left one holds
     */
    public static function leavesOutClausesOfAssignedVars(
        PhpParser\Node\Expr $conditional,
        ?string $this_class_name,
    ): bool {
        return (new PhpParser\NodeFinder())->findFirst(
            $conditional,
            static fn(PhpParser\Node $node): bool => ($node instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                    || $node instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd)
                && self::getAssignedVarIds($node->right, $this_class_name) !== [],
        ) !== null;
    }

    /**
     * The variables an expression assigns, or assigns an item or property of
     *
     * @return array<string, true>
     */
    public static function getAssignedVarIds(PhpParser\Node\Expr $expr, ?string $this_class_name): array
    {
        $visitor = new class extends PhpParser\NodeVisitorAbstract {
            /** @var list<PhpParser\Node\Expr> */
            public array $targets = [];

            /** @psalm-capabilities read-props|write-this-props|write-refs */
            #[Override]
            public function enterNode(PhpParser\Node $node): ?int
            {
                // the body of a closure assigns its own variables; only what it takes by reference is the outer's
                if ($node instanceof PhpParser\Node\Expr\Closure) {
                    foreach ($node->uses as $use) {
                        if ($use->byRef) {
                            $this->targets[] = $use->var;
                        }
                    }

                    return PhpParser\NodeVisitor::DONT_TRAVERSE_CHILDREN;
                }

                if ($node instanceof PhpParser\Node\Expr\ArrowFunction
                    || $node instanceof PhpParser\Node\Stmt\ClassLike
                ) {
                    return PhpParser\NodeVisitor::DONT_TRAVERSE_CHILDREN;
                }

                if ($node instanceof PhpParser\Node\Expr\Assign
                    || $node instanceof PhpParser\Node\Expr\AssignRef
                    || $node instanceof PhpParser\Node\Expr\AssignOp
                    || $node instanceof PhpParser\Node\Expr\PreInc
                    || $node instanceof PhpParser\Node\Expr\PreDec
                    || $node instanceof PhpParser\Node\Expr\PostInc
                    || $node instanceof PhpParser\Node\Expr\PostDec
                ) {
                    if ($node->var instanceof PhpParser\Node\Expr\List_
                        || $node->var instanceof PhpParser\Node\Expr\Array_
                    ) {
                        foreach ($node->var->items as $item) {
                            if ($item !== null) {
                                $this->targets[] = $item->value;
                            }
                        }
                    } else {
                        $this->targets[] = $node->var;
                    }
                }

                return null;
            }
        };

        $traverser = new PhpParser\NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse([$expr]);

        $targets = $visitor->targets;

        $var_ids = [];

        foreach ($targets as $target) {
            $var_id = ExpressionIdentifier::getRootVarId($target, $this_class_name);

            if ($var_id !== null) {
                $var_ids[$var_id] = true;
            }
        }

        return $var_ids;
    }

    /**
     * `$a?->b === null` is `$a === null || $a->b === null` and `$a?->b !== null` is `$a !== null && $a->b !== null`.
     * The copies of the receiver have no types, as the receiver of a nullsafe fetch is typed without the null that
     * the comparison tests it for.
     */
    private static function expandNullsafePropertyNullComparison(
        PhpParser\Node\Expr\BinaryOp $conditional,
        FileSource $source,
    ): ?PhpParser\Node\Expr {
        if ($conditional->left instanceof PhpParser\Node\Expr\NullsafePropertyFetch
            && self::isNullLiteral($conditional->right)
        ) {
            $nullsafe_fetch = $conditional->left;
            $null_operand = $conditional->right;
            $nullsafe_is_left = true;
        } elseif ($conditional->right instanceof PhpParser\Node\Expr\NullsafePropertyFetch
            && self::isNullLiteral($conditional->left)
        ) {
            $nullsafe_fetch = $conditional->right;
            $null_operand = $conditional->left;
            $nullsafe_is_left = false;
        } else {
            return null;
        }

        $traverser = new PhpParser\NodeTraverser(new PhpParser\NodeVisitor\CloningVisitor());
        $receiver = $traverser->traverse([$nullsafe_fetch->var])[0] ?? null;

        if (!$receiver instanceof PhpParser\Node\Expr) {
            return null;
        }

        $attributes = $conditional->getAttributes();
        $receiver_comparison = self::createComparison($conditional, $receiver, $null_operand, $nullsafe_is_left);

        // a property that can't be null (or loosely equal to null) says nothing: it is the receiver that was checked
        $property_type = AssertionFinder::getNullsafePropertyType($nullsafe_fetch, $source);

        if ($property_type !== null
            && ($conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
                || $conditional instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical
                    ? !$property_type->isNullable()
                    : $property_type->isAlwaysTruthy())
        ) {
            return $receiver_comparison;
        }

        // the property is only compared where the receiver isn't null, so `$a?->b?->c` is `$a->b->c` there
        $property_fetch = new VirtualPropertyFetch(
            self::withoutNullsafeFetches($receiver),
            $nullsafe_fetch->name,
            $nullsafe_fetch->getAttributes(),
        );

        $property_comparison = self::createComparison($conditional, $property_fetch, $null_operand, $nullsafe_is_left);

        return $conditional instanceof PhpParser\Node\Expr\BinaryOp\Identical
            || $conditional instanceof PhpParser\Node\Expr\BinaryOp\Equal
            ? new VirtualBooleanOr($receiver_comparison, $property_comparison, $attributes)
            : new VirtualBooleanAnd($receiver_comparison, $property_comparison, $attributes);
    }

    private static function createComparison(
        PhpParser\Node\Expr\BinaryOp $like,
        PhpParser\Node\Expr $operand,
        PhpParser\Node\Expr $null_operand,
        bool $operand_is_left,
    ): PhpParser\Node\Expr\BinaryOp {
        $left = $operand_is_left ? $operand : $null_operand;
        $right = $operand_is_left ? $null_operand : $operand;
        $attributes = $like->getAttributes();

        return match (true) {
            $like instanceof PhpParser\Node\Expr\BinaryOp\Identical => new VirtualIdentical($left, $right, $attributes),
            $like instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical
                => new VirtualNotIdentical($left, $right, $attributes),
            $like instanceof PhpParser\Node\Expr\BinaryOp\Equal => new VirtualEqual($left, $right, $attributes),
            default => new VirtualNotEqual($left, $right, $attributes),
        };
    }

    /**
     * A chain of properties with every `?->` replaced by `->`
     */
    private static function withoutNullsafeFetches(PhpParser\Node\Expr $expr): PhpParser\Node\Expr
    {
        if ($expr instanceof PhpParser\Node\Expr\NullsafePropertyFetch
            || $expr instanceof PhpParser\Node\Expr\PropertyFetch
        ) {
            $var = self::withoutNullsafeFetches($expr->var);

            if ($var !== $expr->var || $expr instanceof PhpParser\Node\Expr\NullsafePropertyFetch) {
                return new VirtualPropertyFetch($var, $expr->name, $expr->getAttributes());
            }
        }

        return $expr;
    }

    private static function isNullLiteral(PhpParser\Node\Expr $expr): bool
    {
        return $expr instanceof PhpParser\Node\Expr\ConstFetch && strtolower($expr->name->getFirst()) === 'null';
    }
}
