<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\AssignmentAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Storage\Capabilities;
use Psalm\Storage\FunctionLikeParameter;

use function array_key_last;
use function is_string;

/**
 * A callee with the write-refs capability may write through its by-reference parameters:
 * what that costs the caller depends on what the caller passes, not on the callee. Writing a
 * local of the caller is nothing, a by-reference parameter of the caller is write-refs, a
 * property is write-props (write-this-props on `$this`), global state is write-globals.
 *
 * @internal
 */
final class ByRefArgumentAnalyzer
{
    /**
     * The capabilities a call needs, with the callee's write-refs replaced by the cost of the
     * arguments actually passed by reference.
     *
     * @param list<FunctionLikeParameter>|null $params the callee's parameters, null when unknown
     * @param list<Arg|PhpParser\Node\VariadicPlaceholder> $args
     */
    public static function adjustCapabilities(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        int $capabilities,
        ?array $params,
        array $args,
    ): int {
        if (($capabilities & Capabilities::WRITE_REFS) === 0) {
            return $capabilities;
        }

        if ($params === null) {
            return $capabilities;
        }

        $capabilities &= ~Capabilities::WRITE_REFS;

        foreach ($args as $index => $arg) {
            if (!$arg instanceof Arg) {
                continue;
            }

            $param = self::getParam($params, $arg, $index);

            if ($param === null || !$param->by_ref) {
                continue;
            }

            $capabilities |= self::getWriteCapabilities($statements_analyzer, $context, $arg->value);
        }

        return $capabilities;
    }

    /**
     * @param list<FunctionLikeParameter> $params
     * @psalm-mutation-free
     */
    private static function getParam(array $params, Arg $arg, int $index): ?FunctionLikeParameter
    {
        if ($arg->name !== null) {
            $name = $arg->name->toString();

            foreach ($params as $param) {
                if ($param->name === $name) {
                    return $param;
                }
            }

            return null;
        }

        if (isset($params[$index])) {
            return $params[$index];
        }

        if ($params === []) {
            return null;
        }

        $last = $params[array_key_last($params)];

        return $last->is_variadic ? $last : null;
    }

    /**
     * What writing the given expression through a reference costs the current scope.
     */
    private static function getWriteCapabilities(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        Expr $expr,
    ): int {
        $root = $expr;

        while ($root instanceof Expr\ArrayDimFetch) {
            $root = $root->var;
        }

        if ($root instanceof Expr\Variable && is_string($root->name)) {
            return AssignmentAnalyzer::getExternalWriteCapabilities($context, '$' . $root->name);
        }

        if ($root instanceof Expr\StaticPropertyFetch) {
            return Capabilities::READ_GLOBALS | Capabilities::WRITE_GLOBALS;
        }

        if ($root instanceof Expr\PropertyFetch) {
            $object_type = $statements_analyzer->node_data->getType($root->var);
            $on_global_state = $object_type !== null && $object_type->from_global_state;

            $capabilities = $root->var instanceof Expr\Variable && $root->var->name === 'this'
                ? Capabilities::NAMES['write-this-props']
                : Capabilities::NAMES['write-props'];

            return $on_global_state ? $capabilities | Capabilities::WRITE_GLOBALS : $capabilities;
        }

        // a temporary, or something Psalm cannot follow
        return Capabilities::WRITE_REFS;
    }
}
