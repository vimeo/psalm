<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\BinaryOp;

use PhpParser\Node\Arg;
use PhpParser\Node\ArgPlaceholder;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Pipe;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\VariadicPlaceholder;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\Call\PartialApplicationAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Node\Expr\PartialApplication;
use Psalm\Node\Expr\VirtualFuncCall;
use Psalm\Node\Expr\VirtualMethodCall;
use Psalm\Node\Expr\VirtualPipeValue;
use Psalm\Node\Expr\VirtualStaticCall;
use Psalm\Node\VirtualArg;
use Psalm\Type;

/**
 * `$value |> $callable` calls `$callable($value)`. A first-class callable or a partial application with a single
 * placeholder is called directly, with `$value` in the place of the placeholder, as PHP does; any other partial
 * application creates its closure after `$value` is evaluated, and the closure is called with it.
 *
 * @internal
 */
final class PipeAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        Pipe $stmt,
        Context $context,
    ): bool {
        $left = $stmt->left;
        $right = $stmt->right;
        $direct_call = self::getDirectCall($right);

        // the closure of a direct call is never created, so only the syntax is checked
        if ($direct_call !== null && $right instanceof PartialApplication) {
            PartialApplicationAnalyzer::checkPhpVersion($statements_analyzer, $right);
        }

        // PHP evaluates the left-hand side before any part of the right-hand side, which a call analyses first
        $was_inside_call = $context->inside_call;
        $was_inside_isset = $context->inside_isset;
        $context->inside_call = true;
        $context->inside_isset = false;
        $left_result = ExpressionAnalyzer::analyze($statements_analyzer, $left, $context);
        $context->inside_call = $was_inside_call;
        $context->inside_isset = $was_inside_isset;

        if ($left_result === false) {
            return false;
        }

        $value = new VirtualPipeValue(
            $statements_analyzer->node_data->getType($left) ?? Type::getMixed(),
            $left->getAttributes(),
        );

        $attributes = self::getCallAttributes($stmt, $direct_call[0] ?? null);
        $call = $direct_call !== null
            ? self::createDirectCall($direct_call[0], $direct_call[1], $value, $attributes)
            : new VirtualFuncCall(
                $right,
                [new VirtualArg($value, false, false, $value->getAttributes())],
                $attributes,
            );

        if (ExpressionAnalyzer::analyze($statements_analyzer, $call, $context) === false) {
            return false;
        }

        $statements_analyzer->node_data->setType(
            $stmt,
            $statements_analyzer->node_data->getType($call) ?? Type::getMixed(),
        );

        if ($call->getAttribute('pure', false)) {
            $stmt->setAttribute('pure', true);
        }

        return true;
    }

    /**
     * The callee and arguments when PHP calls the callee directly with the piped value as its placeholder: a single
     * `?` or `...`, no named or unpacked argument before a `...`. A positional argument after a named one is not direct
     * because it is a compile error, which the partial application reports.
     *
     * @return array{FuncCall|MethodCall|StaticCall, list<Arg|ArgPlaceholder|VariadicPlaceholder>}|null
     */
    private static function getDirectCall(Expr $right): ?array
    {
        if ($right instanceof PartialApplication) {
            $callee = $right->call;
            $raw_args = $right->args;
        } elseif ($right instanceof CallLike && $right->isFirstClassCallable()) {
            $callee = $right;
            $raw_args = $right->getRawArgs();
        } else {
            return null;
        }

        if (!$callee instanceof FuncCall && !$callee instanceof MethodCall && !$callee instanceof StaticCall) {
            return null;
        }

        $placeholders = 0;
        $has_named = false;
        $has_named_or_unpacked = false;

        foreach ($raw_args as $arg) {
            if ($arg instanceof VariadicPlaceholder) {
                if ($has_named_or_unpacked) {
                    return null;
                }

                $placeholders++;
            } elseif ($arg->name !== null) {
                $has_named = $has_named_or_unpacked = true;

                if ($arg instanceof ArgPlaceholder) {
                    $placeholders++;
                }
            } elseif ($arg instanceof Arg && $arg->unpack) {
                $has_named_or_unpacked = true;
            } elseif ($has_named) {
                return null;
            } elseif ($arg instanceof ArgPlaceholder) {
                $placeholders++;
            }
        }

        return $placeholders === 1 ? [$callee, $raw_args] : null;
    }

    /**
     * The call spans the pipe but starts where no other analysed node does: calls are told apart by their start (issue
     * dedupe, data-flow specialization), and the pipe, like every pipe of a chain, starts with its left-hand side.
     *
     * @return array<string, mixed>
     */
    private static function getCallAttributes(Pipe $stmt, FuncCall|MethodCall|StaticCall|null $callee): array
    {
        $right = $stmt->right;
        $start = null;

        if (($callee instanceof MethodCall || $callee instanceof StaticCall) && $callee->name instanceof Identifier) {
            $start = $callee->name;
        } elseif ($callee instanceof FuncCall && $callee->name instanceof Name) {
            $start = $callee;
        }

        $attributes = $stmt->getAttributes();

        if ($start !== null) {
            $attributes['startLine'] = $start->getStartLine();
            $attributes['startFilePos'] = $start->getStartFilePos();
            $attributes['startTokenPos'] = $start->getStartTokenPos();
        } else {
            // between the operands, at the end of the `|>` or of a parenthesis around the right-hand side
            $attributes['startLine'] = $right->getStartLine();
            $attributes['startFilePos'] = $right->getStartFilePos() - 1;
            $attributes['startTokenPos'] = $right->getStartTokenPos() - 1;
        }

        return $attributes;
    }

    /**
     * @param list<Arg|ArgPlaceholder|VariadicPlaceholder> $raw_args
     * @param array<string, mixed> $attributes
     */
    private static function createDirectCall(
        FuncCall|MethodCall|StaticCall $callee,
        array $raw_args,
        Expr $value,
        array $attributes,
    ): Expr {
        $has_placeholder = false;

        foreach ($raw_args as $raw_arg) {
            $has_placeholder = $has_placeholder || $raw_arg instanceof ArgPlaceholder;
        }

        $args = [];

        foreach ($raw_args as $raw_arg) {
            if ($raw_arg instanceof ArgPlaceholder) {
                $args[] = new VirtualArg($value, false, false, $raw_arg->getAttributes(), $raw_arg->name);
            } elseif ($raw_arg instanceof VariadicPlaceholder) {
                if (!$has_placeholder) {
                    $args[] = new VirtualArg($value, false, false, $raw_arg->getAttributes());
                }
            } else {
                $args[] = $raw_arg;
            }
        }

        if ($callee instanceof MethodCall) {
            return new VirtualMethodCall($callee->var, $callee->name, $args, $attributes);
        }

        if ($callee instanceof StaticCall) {
            return new VirtualStaticCall($callee->class, $callee->name, $args, $attributes);
        }

        return new VirtualFuncCall($callee->name, $args, $attributes);
    }
}
