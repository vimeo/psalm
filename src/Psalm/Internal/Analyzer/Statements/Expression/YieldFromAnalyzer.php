<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\ForeachAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\InvalidIterator;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

use function in_array;
use function strtolower;

/**
 * @internal
 */
final class YieldFromAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\YieldFrom $stmt,
        Context $context,
    ): bool {
        YieldAnalyzer::checkGeneratorConstructorOrDestructor($statements_analyzer, $stmt);

        $was_inside_call = $context->inside_call;

        $context->inside_call = true;

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            $context->inside_call = $was_inside_call;

            return false;
        }

        if ($stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr)) {
            // what it delegates to yields for the generator
            YieldAnalyzer::taintGenerator($statements_analyzer, $context, $stmt_expr_type, 'yield-from');
            YieldAnalyzer::taintDelegatedSends($statements_analyzer, $context, $stmt_expr_type);

            $key_type = null;
            $value_type = null;
            $always_non_empty_array = true;
            if (ForeachAnalyzer::checkIteratorType(
                $statements_analyzer,
                $stmt->expr,
                $stmt_expr_type,
                $statements_analyzer->getCodebase(),
                $context,
                $key_type,
                $value_type,
                $always_non_empty_array,
                forwards_send: true,
            ) === false
            ) {
                $context->inside_call = $was_inside_call;

                return false;
            }

            self::checkForwardedSendType($statements_analyzer, $stmt, $stmt_expr_type);

            $yield_from_type = null;

            foreach ($stmt_expr_type->getAtomicTypes() as $atomic_type) {
                if ($yield_from_type === null) {
                    if ($atomic_type instanceof TGenericObject
                        && strtolower($atomic_type->value) === 'generator'
                        && isset($atomic_type->type_params[3])
                    ) {
                        $yield_from_type = $atomic_type->type_params[3];
                    } elseif ($atomic_type instanceof TArray || $atomic_type instanceof TKeyedArray) {
                        $yield_from_type = Type::getNull();
                    }
                } else {
                    $yield_from_type = Type::getMixed();
                }
            }

            // this should be whatever the generator above returns, but *not* the return type
            $statements_analyzer->node_data->setType($stmt, $yield_from_type ?: Type::getMixed());
        }

        $context->inside_call = $was_inside_call;

        return true;
    }

    /**
     * What a generator delegated to receives is what the generator the `yield from` is in is sent: the send type of
     * its declared return type, or null when it is only iterated (iterable, Traversable, Iterator, ...)
     */
    private static function checkForwardedSendType(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\YieldFrom $stmt,
        Union $delegated_type,
    ): void {
        $source = $statements_analyzer->getSource();

        if (!$source instanceof FunctionLikeAnalyzer) {
            return;
        }

        $declared_return_type = $source->getFunctionLikeStorage($statements_analyzer)->return_type;

        if ($declared_return_type === null || !$declared_return_type->isSingle()) {
            return;
        }

        $declared_atomic_type = $declared_return_type->getSingleAtomic();

        if ($declared_atomic_type instanceof TGenericObject
            && strtolower($declared_atomic_type->value) === 'generator'
        ) {
            $outer_send_type = $declared_atomic_type->type_params[2] ?? null;
        } elseif ($declared_atomic_type instanceof TIterable
            || ($declared_atomic_type instanceof TNamedObject
                && in_array(
                    strtolower($declared_atomic_type->value),
                    ['traversable', 'iterator', 'iteratoraggregate'],
                    true,
                ))
        ) {
            $outer_send_type = Type::getNull();
        } else {
            return;
        }

        if ($outer_send_type === null || $outer_send_type->hasTemplate()) {
            return;
        }

        // `mixed` says nothing of what is sent, except that iterating the generator sends null
        if ($outer_send_type->isMixed()) {
            $outer_send_type = Type::getNull();
        }

        $codebase = $statements_analyzer->getCodebase();

        foreach ($delegated_type->getAtomicTypes() as $delegated_atomic_type) {
            if (!$delegated_atomic_type instanceof TGenericObject
                || strtolower($delegated_atomic_type->value) !== 'generator'
                || !isset($delegated_atomic_type->type_params[2])
            ) {
                continue;
            }

            $inner_send_type = $delegated_atomic_type->type_params[2];

            if ($inner_send_type->isVoid()
                || $inner_send_type->isMixed()
                || $inner_send_type->hasTemplate()
                || UnionTypeComparator::isContainedBy($codebase, $outer_send_type, $inner_send_type)
            ) {
                continue;
            }

            IssueBuffer::maybeAdd(
                new InvalidIterator(
                    'yield from forwards send() values of type ' . $outer_send_type->getId() . ' to '
                        . $delegated_atomic_type->getId(),
                    new CodeLocation($statements_analyzer->getSource(), $stmt->expr),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );

            return;
        }
    }
}
