<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Node\Expr\BinaryOp\VirtualIdentical;
use Psalm\Node\Expr\VirtualConstFetch;
use Psalm\Node\Expr\VirtualMethodCall;
use Psalm\Node\Expr\VirtualPropertyFetch;
use Psalm\Node\Expr\VirtualTernary;
use Psalm\Node\Expr\VirtualVariable;
use Psalm\Node\VirtualName;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;

use function array_all;
use function is_string;

/**
 * @internal
 */
final class NullsafeAnalyzer
{
    /**
     * Whether the receiver is a place that keeps its value: a property, a static property or an item of an array.
     * Other receivers (an assignment, a call, an item of an ArrayAccess object) are evaluated once into the
     * temporary variable.
     */
    private static function isStoredInPlace(StatementsAnalyzer $statements_analyzer, PhpParser\Node\Expr $expr): bool
    {
        if ($expr instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            $base_type = $statements_analyzer->node_data->getType($expr->var);

            if ($base_type === null
                || !array_all(
                    $base_type->getAtomicTypes(),
                    static fn(Atomic $atomic): bool => $atomic instanceof TArray || $atomic instanceof TKeyedArray,
                )
            ) {
                return false;
            }
        } elseif (!$expr instanceof PhpParser\Node\Expr\PropertyFetch
            && !$expr instanceof PhpParser\Node\Expr\StaticPropertyFetch
        ) {
            return false;
        }

        return ExpressionIdentifier::getExtendedVarId(
            $expr,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer,
        ) !== null;
    }

    /**
     * Analyzes a call that a nullsafe operator before it in the chain can short-circuit (`$a?->b->c($a->d)`): PHP
     * skips the whole chain, the arguments of the call included, when the receiver of the nullsafe operator is null,
     * so the chain is analyzed as `$a === null ? null : $a->b->c($a->d)`. Only a receiver that is a variable or a
     * property, which reading twice doesn't change, is handled. Returns null when the call is no such chain.
     *
     * @param PhpParser\Node\Expr\MethodCall|PhpParser\Node\Expr\NullsafeMethodCall $stmt
     */
    public static function analyzeShortCircuitedCall(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt,
        Context $context,
    ): ?bool {
        if ($stmt->isFirstClassCallable() || !$stmt->getArgs()) {
            return null;
        }

        // the nullsafe operator closest to the start of the chain
        $nullsafe = null;

        $link = $stmt->var;

        while ($link instanceof PhpParser\Node\Expr\MethodCall
            || $link instanceof PhpParser\Node\Expr\PropertyFetch
            || $link instanceof PhpParser\Node\Expr\NullsafeMethodCall
            || $link instanceof PhpParser\Node\Expr\NullsafePropertyFetch
            || $link instanceof PhpParser\Node\Expr\ArrayDimFetch
        ) {
            if ($link instanceof PhpParser\Node\Expr\NullsafeMethodCall
                || $link instanceof PhpParser\Node\Expr\NullsafePropertyFetch
            ) {
                $nullsafe = $link;
            }

            $link = $link->var;
        }

        if ($nullsafe === null) {
            return null;
        }

        $receiver_id = ExpressionIdentifier::getExtendedVarId(
            $nullsafe->var,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer,
        );

        if ($receiver_id === null) {
            return null;
        }

        $receiver_type = $context->vars_in_scope[$receiver_id] ?? null;

        // a property not read yet is analysed to get its type
        if ($receiver_type === null && !$nullsafe->var instanceof PhpParser\Node\Expr\Variable) {
            $was_inside_general_use = $context->inside_general_use;

            $context->inside_general_use = true;
            ExpressionAnalyzer::analyze($statements_analyzer, $nullsafe->var, $context);
            $context->inside_general_use = $was_inside_general_use;

            $receiver_type = $statements_analyzer->node_data->getType($nullsafe->var);
        }

        if (!$receiver_type?->isNullable()) {
            return null;
        }

        $ternary = new VirtualTernary(
            new VirtualIdentical(
                $nullsafe->var,
                new VirtualConstFetch(new VirtualName('null'), $nullsafe->var->getAttributes()),
                $nullsafe->var->getAttributes(),
            ),
            new VirtualConstFetch(new VirtualName('null'), $nullsafe->var->getAttributes()),
            self::replaceLink($stmt, $nullsafe),
            $stmt->getAttributes(),
        );

        if (!ExpressionAnalyzer::analyze($statements_analyzer, $ternary, $context)) {
            return false;
        }

        $statements_analyzer->node_data->setType(
            $stmt,
            $statements_analyzer->node_data->getType($ternary) ?? Type::getMixed(),
        );

        // the call is null when the receiver is, and otherwise as the chain without the nullsafe operator is, whose
        // own later `?->` short-circuits the whole chain as well
        $chain_state = NullsafeChainState::of($ternary->else);

        if ($chain_state === NullsafeChainState::None) {
            $chain_state = NullsafeChainState::ShortCircuit->afterLink(
                $statements_analyzer->node_data->getType($ternary->else),
            );
        }

        $chain_state->markOn($stmt);

        return true;
    }

    /**
     * The chain with the nullsafe operator replaced by a plain one
     *
     * @param PhpParser\Node\Expr\NullsafeMethodCall|PhpParser\Node\Expr\NullsafePropertyFetch $nullsafe
     */
    private static function replaceLink(PhpParser\Node\Expr $link, PhpParser\Node\Expr $nullsafe): PhpParser\Node\Expr
    {
        if ($link === $nullsafe) {
            return $nullsafe instanceof PhpParser\Node\Expr\NullsafeMethodCall
                ? new VirtualMethodCall($nullsafe->var, $nullsafe->name, $nullsafe->args, $nullsafe->getAttributes())
                : new VirtualPropertyFetch($nullsafe->var, $nullsafe->name, $nullsafe->getAttributes());
        }

        $new_link = clone $link;

        if ($new_link instanceof PhpParser\Node\Expr\MethodCall
            || $new_link instanceof PhpParser\Node\Expr\PropertyFetch
            || $new_link instanceof PhpParser\Node\Expr\NullsafeMethodCall
            || $new_link instanceof PhpParser\Node\Expr\NullsafePropertyFetch
            || $new_link instanceof PhpParser\Node\Expr\ArrayDimFetch
        ) {
            $new_link->var = self::replaceLink($new_link->var, $nullsafe);
        }

        return $new_link;
    }

    /**
     * @param PhpParser\Node\Expr\NullsafePropertyFetch|PhpParser\Node\Expr\NullsafeMethodCall $stmt
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt,
        Context $context,
    ): bool {
        $receiver_id = $stmt->var instanceof PhpParser\Node\Expr\PropertyFetch
            ? ExpressionIdentifier::getExtendedVarId(
                $stmt->var,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer,
            )
            : null;

        $var_type = $receiver_id !== null ? $context->vars_in_scope[$receiver_id] ?? null : null;

        // a receiver the context has no type for (a call, or a property not read yet) is analysed to get its type
        if ($var_type === null && !$stmt->var instanceof PhpParser\Node\Expr\Variable) {
            $was_inside_general_use = $context->inside_general_use;

            $context->inside_general_use = true;
            ExpressionAnalyzer::analyze($statements_analyzer, $stmt->var, $context);
            $context->inside_general_use = $was_inside_general_use;

            $var_type = $statements_analyzer->node_data->getType($stmt->var);
        }

        if ($receiver_id !== null) {
            // a chain of variables and properties is analysed as it stands, so that what is known of it applies
            $tmp_var = $stmt->var;
        } elseif (!$stmt->var instanceof PhpParser\Node\Expr\Variable) {
            $tmp_name = '__tmp_nullsafe__' . (int) $stmt->var->getAttribute('startFilePos');

            // a property, static property or array item is the one the call acts on: what the call does to the
            // object (what it stores in it, for the taint analysis) would be lost on a temporary variable
            if ($var_type
                && !self::isStoredInPlace($statements_analyzer, $stmt->var)
            ) {
                $context->vars_in_scope['$' . $tmp_name] = $var_type;

                $tmp_var = new VirtualVariable($tmp_name, $stmt->var->getAttributes());
            } else {
                $tmp_var = $stmt->var;
            }
        } else {
            $tmp_var = $stmt->var;
            $var_type = is_string($stmt->var->name) ? $context->vars_in_scope['$' . $stmt->var->name] ?? null : null;
        }

        $old_node_data = $statements_analyzer->node_data;
        $statements_analyzer->node_data = clone $statements_analyzer->node_data;

        $null_value1 = new VirtualConstFetch(
            new VirtualName('null'),
            $stmt->var->getAttributes(),
        );

        $null_comparison = new VirtualIdentical(
            $tmp_var,
            $null_value1,
            $stmt->var->getAttributes(),
        );

        $null_value2 = new VirtualConstFetch(
            new VirtualName('null'),
            $stmt->var->getAttributes(),
        );

        if ($stmt instanceof PhpParser\Node\Expr\NullsafePropertyFetch) {
            $ternary = new VirtualTernary(
                $null_comparison,
                $null_value2,
                new VirtualPropertyFetch($tmp_var, $stmt->name, $stmt->getAttributes()),
                $stmt->getAttributes(),
            );
        } else {
            $method_call = new VirtualMethodCall($tmp_var, $stmt->name, $stmt->args, $stmt->getAttributes());

            $ternary = new VirtualTernary(
                $null_comparison,
                $null_value2,
                $method_call,
                $stmt->getAttributes(),
            );
        }

        $receiver_is_known = $stmt instanceof PhpParser\Node\Expr\NullsafePropertyFetch
            && $receiver_id !== null
            && $var_type !== null
            && $var_type->isObjectType()
            && !$var_type->isNullable()
            && !$var_type->possibly_undefined
            && !$var_type->possibly_undefined_from_try;

        // checking a known receiver against null is reported, but must not make the context forget what it knows
        // of the receiver's properties
        ExpressionAnalyzer::analyze($statements_analyzer, $ternary, $receiver_is_known ? clone $context : $context);

        $ternary_type = $statements_analyzer->node_data->getType($ternary);

        $call_type = $statements_analyzer->node_data->getType($ternary->else);

        if ($receiver_is_known) {
            $fetch = new VirtualPropertyFetch($tmp_var, $stmt->name, $stmt->getAttributes());

            ExpressionAnalyzer::analyze($statements_analyzer, $fetch, $context);

            $call_type = $statements_analyzer->node_data->getType($fetch);
        }

        // what the method asserts when it returns true holds where the nullsafe call is truthy (not where it is falsy:
        // the receiver may be null)
        $if_true_assertions = isset($method_call)
            ? $statements_analyzer->node_data->getIfTrueAssertions($method_call)
            : null;

        $statements_analyzer->node_data = $old_node_data;

        if ($stmt instanceof PhpParser\Node\Expr\NullsafeMethodCall
            && $if_true_assertions !== null
            && $if_true_assertions !== []
        ) {
            $statements_analyzer->node_data->setIfTrueAssertions($stmt, $if_true_assertions);
        }

        // a non-null receiver never short-circuits: the null branch is dead
        if ($call_type
            && $var_type
            && $var_type->isObjectType()
            && !$var_type->isNullable()
            && !$var_type->possibly_undefined
            && !$var_type->possibly_undefined_from_try
        ) {
            NullsafeChainState::None->markOn($stmt);
            $statements_analyzer->node_data->setType($stmt, $call_type);

            return true;
        }

        // the null branch is the short-circuit, so any null the call/fetch on the non-null receiver adds is a real one
        $chain_state = NullsafeChainState::None;

        if ($ternary_type && $ternary_type->isNullable()) {
            $chain_state = NullsafeChainState::ShortCircuit->afterLink($call_type);
        }

        $chain_state->markOn($stmt);

        $statements_analyzer->node_data->setType($stmt, $ternary_type ?? Type::getMixed());

        return true;
    }
}
