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
     * The first of the overloads (`@method` of one name, `@psalm-overload`) whose parameters accept the arguments of
     * the call; when none does, the last declared, which reports the issues
     *
     * @template T of FunctionLikeStorage
     * @param list<PhpParser\Node\Arg> $args
     * @param non-empty-list<T> $overloads
     * @return T
     */
    public static function select(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        array $args,
        Context $context,
        array $overloads,
    ): FunctionLikeStorage {
        $arg_types = self::getArgTypes($statements_analyzer, $args, $context);

        foreach ($overloads as $overload) {
            if (self::overloadAcceptsArgs($codebase, $overload, $args, $arg_types)) {
                return $overload;
            }
        }

        return $overloads[count($overloads) - 1];
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
     * Whether the arguments, placed on the parameters by position and by name, fit the overload: each parameter
     * gets at most one argument of an accepted type, and every required one gets one. The arguments from an
     * unpacked one on can't be placed, so they are left out.
     *
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
        $last_offset = count($params) - 1;
        $variadic = $last_offset >= 0 && $params[$last_offset]->is_variadic;

        $bound = [];
        $has_unpack = false;

        foreach ($args as $offset => $arg) {
            if ($arg->unpack) {
                $has_unpack = true;
                break;
            }

            if ($arg->name) {
                $param_offset = null;

                foreach ($params as $candidate_offset => $candidate) {
                    if ($candidate->name === $arg->name->name && !$candidate->is_variadic) {
                        $param_offset = $candidate_offset;
                        break;
                    }
                }

                if ($param_offset === null) {
                    if ($variadic) {
                        continue;
                    }

                    return false;
                }

                if (isset($bound[$param_offset])) {
                    return false;
                }
            } elseif (isset($params[$offset])) {
                $param_offset = $offset;
            } elseif ($variadic) {
                $param_offset = $last_offset;
            } else {
                return false;
            }

            $bound[$param_offset] = true;
            $param_type = $params[$param_offset]->type;

            if ($param_type && $arg_types[$offset]
                && !UnionTypeComparator::isContainedBy($codebase, $arg_types[$offset], $param_type)
            ) {
                return false;
            }
        }

        if (!$has_unpack) {
            for ($required = 0; $required < $overload->required_param_count; $required++) {
                if (!isset($bound[$required])) {
                    return false;
                }
            }
        }

        return true;
    }
}
