<?php

declare(strict_types=1);

namespace Psalm\Internal\Algebra;

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
use Psalm\Node\Expr\VirtualBooleanNot;
use Psalm\Storage\Assertion;
use Psalm\Storage\Assertion\Truthy;

use function array_filter;
use function array_map;
use function array_values;
use function count;
use function reset;
use function spl_object_id;
use function strcspn;
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
                            $clause->implied,
                        )
                        : $clause,
                    $right_clauses,
                );

                // nor can what a call on the left side asserts of the previous value hold where the right one runs
                $left_clauses = array_values(array_filter(
                    $left_clauses,
                    static function (Clause $clause) use ($right_assigned_var_ids): bool {
                        if ($clause->premise_var_id === null) {
                            return true;
                        }

                        foreach ($clause->possibilities as $var_id => $_) {
                            if (isset($right_assigned_var_ids[$var_id])
                                || isset($right_assigned_var_ids[substr($var_id, 0, strcspn($var_id, '[-'))])
                            ) {
                                return false;
                            }
                        }

                        return true;
                    },
                ));
            }

            return Algebra::combineOredClauses($left_clauses, $right_clauses, $conditional_object_id);
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

            if ($conditional->expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd) {
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

        if (($conditional instanceof PhpParser\Node\Expr\FuncCall
                || $conditional instanceof PhpParser\Node\Expr\MethodCall
                || $conditional instanceof PhpParser\Node\Expr\StaticCall)
            && !$conditional->isFirstClassCallable()
            && ($assertions_by_result = AssertionFinder::getCustomAssertionsByResult(
                $conditional,
                $this_class_name,
                $source,
            ))
        ) {
            return self::getCallResultFormula(
                $conditional_object_id,
                $creating_object_id,
                $conditional,
                $this_class_name,
                $source,
                ...$assertions_by_result,
            );
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

        /** @psalm-suppress MixedOperand */
        $conditional_ref = '*' . $conditional->getAttribute('startFilePos')
            . ':' . $conditional->getAttribute('endFilePos');

        return [
            new Clause(
                [$conditional_ref => ['truthy' => new Truthy()]],
                $conditional_object_id,
                $creating_object_id,
            ),
        ];
    }

    /**
     * The formula of a call's result: the call returns true, so what it asserts where it does holds, and, as for a
     * call that asserts one thing, what it asserts where it returns false doesn't. Its implications from the result
     * to each if-false assertion, ($call || if-false assertion), are kept as they are by negating the formula: what
     * the call asserts where it returns false then holds there.
     *
     * @param list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>> $if_true_assertions
     * @param non-empty-list<non-empty-array<string, non-empty-list<non-empty-list<Assertion>>>> $if_false_assertions
     * @return non-empty-list<Clause>
     */
    private static function getCallResultFormula(
        int $conditional_object_id,
        int $creating_object_id,
        PhpParser\Node\Expr\FuncCall|PhpParser\Node\Expr\MethodCall|PhpParser\Node\Expr\StaticCall $conditional,
        ?string $this_class_name,
        FileSource $source,
        array $if_true_assertions,
        array $if_false_assertions,
    ): array {
        /** @psalm-suppress MixedOperand */
        $call_id = ExpressionIdentifier::getExtendedVarId($conditional, $this_class_name, $source)
            ?? '*' . $conditional->getAttribute('startFilePos') . ':' . $conditional->getAttribute('endFilePos');

        $clauses = [new Clause([$call_id => ['truthy' => new Truthy()]], $conditional_object_id, $creating_object_id)];

        foreach ([[$if_true_assertions, true], [$if_false_assertions, false]] as [$anded_assertions, $if_true]) {
            foreach ($anded_assertions as $assertions) {
                foreach ($assertions as $var_id => $anded_types) {
                    foreach ($anded_types as $orred_types) {
                        $mapped_orred_types = [];
                        foreach ($orred_types as $orred_type) {
                            $mapped_orred_types[(string) $orred_type] = $orred_type;
                        }

                        if (!$if_true) {
                            $clauses[] = new Clause(
                                [$call_id => ['truthy' => new Truthy()], $var_id => $mapped_orred_types],
                                $conditional_object_id,
                                $creating_object_id,
                                generated: $orred_types[0]->hasEquality(),
                                premise_var_id: $call_id,
                            );

                            if (count($orred_types) !== 1) {
                                continue;
                            }

                            $negated_type = $orred_types[0]->getNegation();
                            $mapped_orred_types = [(string) $negated_type => $negated_type];
                        }

                        $clauses[] = new Clause(
                            [$var_id => $mapped_orred_types],
                            $conditional_object_id,
                            $creating_object_id,
                            generated: reset($mapped_orred_types)->hasEquality(),
                            implied: true,
                        );
                    }
                }
            }
        }

        return $clauses;
    }

    /**
     * The variables an expression assigns, or assigns an item or property of
     *
     * @return array<string, true>
     */
    private static function getAssignedVarIds(PhpParser\Node\Expr $expr, ?string $this_class_name): array
    {
        $targets = [];

        (new PhpParser\NodeFinder())->find(
            $expr,
            static function (PhpParser\Node $node) use (&$targets): bool {
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
                                $targets[] = $item->value;
                            }
                        }
                    } else {
                        $targets[] = $node->var;
                    }
                }

                return false;
            },
        );

        $var_ids = [];

        foreach ($targets as $target) {
            $var_id = ExpressionIdentifier::getRootVarId($target, $this_class_name);

            if ($var_id !== null) {
                $var_ids[$var_id] = true;
            }
        }

        return $var_ids;
    }
}
