<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\IssueBuffer;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Type\Union;

use function count;

/**
 * @internal
 */
final class OverloadSelector
{
    /**
     * The offset of the first of the overloads (`@method` of one name, `@psalm-overload`) whose parameters accept the
     * arguments of the call, null when none does
     *
     * @param list<PhpParser\Node\Arg> $args
     * @param list<FunctionLikeStorage> $overloads
     * @return int<0, max>|null
     */
    public static function findOffset(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        array $args,
        Context $context,
        array $overloads,
    ): ?int {
        $arg_types = self::getArgTypes($statements_analyzer, $args, $context);

        foreach ($overloads as $offset => $overload) {
            if (self::overloadAcceptsArgs($codebase, $overload, $args, $arg_types)) {
                return $offset;
            }
        }

        return null;
    }

    /**
     * The types of the arguments, from a trial analysis that leaves the context, the node types and the issues alone
     *
     * @param list<PhpParser\Node\Arg> $args
     * @return list<?Union>
     */
    private static function getArgTypes(
        StatementsAnalyzer $statements_analyzer,
        array $args,
        Context $context,
    ): array {
        $trial_context = clone $context;
        $trial_context->inside_call = true;

        $old_node_data = $statements_analyzer->node_data;
        $statements_analyzer->node_data = clone $old_node_data;

        IssueBuffer::startRecording();

        $types = [];

        foreach ($args as $arg) {
            ExpressionAnalyzer::analyze($statements_analyzer, $arg->value, $trial_context);
            $types[] = $statements_analyzer->node_data->getType($arg->value);
        }

        IssueBuffer::clearRecordingLevel();
        IssueBuffer::stopRecording();

        $statements_analyzer->node_data = $old_node_data;

        return $types;
    }

    /**
     * @param list<PhpParser\Node\Arg> $args
     * @param list<?Union> $arg_types
     */
    private static function overloadAcceptsArgs(
        Codebase $codebase,
        FunctionLikeStorage $overload,
        array $args,
        array $arg_types,
    ): bool {
        $params = $overload->params;
        $last_param = $params ? $params[count($params) - 1] : null;

        if (count($args) < $overload->required_param_count
            || (count($args) > count($params) && !($last_param && $last_param->is_variadic))
        ) {
            return false;
        }

        foreach ($args as $offset => $arg) {
            if ($arg->unpack || $arg->name) {
                return true;
            }

            $param = $params[$offset] ?? $last_param;

            if (!$param || !$param->type) {
                continue;
            }

            $arg_type = $arg_types[$offset];

            if ($arg_type && !UnionTypeComparator::isContainedBy($codebase, $arg_type, $param->type)) {
                return false;
            }
        }

        return true;
    }
}
