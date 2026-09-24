<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\VariableFetchAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Issue\CodeIssue;
use Psalm\Storage\Capabilities;

use function is_string;

/**
 * Tracks the values reached from global state (static properties, superglobals, `global`
 * variables, and what callees that read globals return), the way Hack's `readonly` does for
 * values read under `read_globals`: mutating such a value mutates global state, which needs
 * the write-globals capability, not just write-props.
 *
 * The property {@see \Psalm\Type\Union::$from_global_state} carries this through assignments and
 * along property, array and method chains.
 *
 * @internal
 */
final class GlobalStateAnalyzer
{
    /** The attribute a call analyzer sets on its node when the callee may read globals. */
    public const ATTRIBUTE = 'from_global_state';

    /**
     * Marks the value of an expression that has just been analysed when it is reached from
     * global state.
     */
    public static function propagate(
        StatementsAnalyzer $statements_analyzer,
        Expr $stmt,
        Context $context,
    ): void {
        if ($stmt instanceof Expr\StaticPropertyFetch) {
            self::mark($statements_analyzer, $stmt, $context);

            return;
        }

        if ($stmt instanceof Expr\Variable) {
            if (is_string($stmt->name)) {
                $var_id = '$' . $stmt->name;

                if (VariableFetchAnalyzer::isSuperGlobal($var_id) || isset($context->referenced_globals[$var_id])) {
                    self::mark($statements_analyzer, $stmt, $context);
                }
            }

            return;
        }

        if ($stmt instanceof Expr\PropertyFetch
            || $stmt instanceof Expr\NullsafePropertyFetch
            || $stmt instanceof Expr\ArrayDimFetch
            || $stmt instanceof Expr\MethodCall
            || $stmt instanceof Expr\NullsafeMethodCall
        ) {
            $source_type = $statements_analyzer->node_data->getType($stmt->var);

            if ($source_type !== null && $source_type->from_global_state) {
                self::mark($statements_analyzer, $stmt, $context);

                return;
            }
        }

        if ($stmt->getAttribute(self::ATTRIBUTE) === true) {
            self::mark($statements_analyzer, $stmt, $context);
        }
    }

    private static function mark(StatementsAnalyzer $statements_analyzer, Expr $stmt, Context $context): void
    {
        $type = $statements_analyzer->node_data->getType($stmt);

        if ($type !== null && !$type->from_global_state) {
            $statements_analyzer->node_data->setType($stmt, $type->setProperties(['from_global_state' => true]));
        }

        // the variable the expression stands for (`Foo::$bar`, `$_GET`), for its later reads
        $var_id = ExpressionIdentifier::getExtendedVarId(
            $stmt,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer,
        );

        if ($var_id !== null
            && isset($context->vars_in_scope[$var_id])
            && !$context->vars_in_scope[$var_id]->from_global_state
        ) {
            $context->vars_in_scope[$var_id] = $context->vars_in_scope[$var_id]
                ->setProperties(['from_global_state' => true]);
        }
    }

    /**
     * A callee that may write properties may mutate the objects it is given: passing it one
     * reached from global state lets it mutate global state.
     *
     * @param array<Arg|PhpParser\Node\VariadicPlaceholder> $args
     * @param class-string<CodeIssue> $issue_class
     */
    public static function checkArguments(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        array $args,
        int $callee_capabilities,
        string $issue_class,
        string $callee,
    ): void {
        if (($callee_capabilities & Capabilities::WRITE_PROPS) === 0
            // the call itself is reported when it is not allowed
            || !Capabilities::allows($context->capabilities, $callee_capabilities)
        ) {
            return;
        }

        foreach ($args as $arg) {
            if (!$arg instanceof Arg) {
                continue;
            }

            $arg_type = $statements_analyzer->node_data->getType($arg->value);

            if ($arg_type !== null && $arg_type->from_global_state) {
                $statements_analyzer->signalMutation(
                    Capabilities::WRITE_GLOBALS,
                    $context,
                    'passing a value reached from global state to ' . $callee . ', which may mutate it,',
                    $issue_class,
                    $arg->value,
                );
            }
        }
    }
}
