<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Storage\Assertion\MatchesPattern;
use Psalm\Type\TaintKind;
use Psalm\Type\Union;

use function array_keys;
use function preg_match;
use function sort;
use function strtolower;

use const PHP_URL_HOST;

/**
 * A URL whose host matches a list of hosts (`preg_match('/^(a\.example|b\.example)$/', parse_url($url,
 * PHP_URL_HOST))`) can only make PHP's stream wrappers, which parse URLs as parse_url() does, reach those servers
 * (SSRF). A browser parses some URLs differently (`http://evil.example\@a.example/`), so it can still be redirected
 * elsewhere. The host keeps track of the local variable holding the URL it was taken from, and of the nodes that
 * variable held, so that the URL is only trusted while it holds the same value.
 *
 * @internal
 */
final class UrlHosts
{
    /** What a URL whose server is one of a list can't carry */
    public const REMOVED_TAINTS = TaintKind::INPUT_SSRF;

    /**
     * What `parse_url($url, PHP_URL_HOST)` returns, recorded as the host of `$url`
     *
     * @param list<Arg> $args
     */
    public static function recordParseUrl(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        FuncCall $stmt,
        string $function_id,
        array $args,
        Union $stmt_type,
    ): Union {
        $graph = $statements_analyzer->getTaintFlowGraphWithSuppressed();

        if ($graph === null || $function_id !== 'parse_url' || $stmt_type->parent_nodes === []) {
            return $stmt_type;
        }

        $url_arg = null;
        $component_arg = null;
        foreach ($args as $offset => $arg) {
            if ($arg->unpack) {
                return $stmt_type;
            }

            $name = $arg->name?->name;
            if ($name === 'url' || ($name === null && $offset === 0)) {
                $url_arg = $arg;
            } elseif ($name === 'component' || ($name === null && $offset === 1)) {
                $component_arg = $arg;
            }
        }

        $component_type = $component_arg === null
            ? null
            : $statements_analyzer->node_data->getType($component_arg->value);

        if ($url_arg === null
            || $component_type === null
            || !$component_type->isSingleIntLiteral()
            || $component_type->getSingleIntLiteral()->value !== PHP_URL_HOST
        ) {
            return $stmt_type;
        }

        $url_var_id = ExpressionIdentifier::getExtendedVarId(
            $url_arg->value,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer,
        );
        $url_type = $statements_analyzer->node_data->getType($url_arg->value);

        // a property, an element or a reference can be changed by a call without the variable being assigned
        if ($url_var_id === null
            || !preg_match('/^\$[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $url_var_id)
            || $url_type === null
            || $url_type->by_ref
            || isset($context->references_in_scope[$url_var_id])
            || isset($context->references_to_external_scope[$url_var_id])
            || ($context->referenced_counts[$url_var_id] ?? 0) > 0
            || $url_type->parent_nodes === []
        ) {
            return $stmt_type;
        }

        $host_node = DataFlowNode::getForAssignment('url host', new CodeLocation($statements_analyzer, $stmt));
        $graph->addNode($host_node);
        foreach ($stmt_type->parent_nodes as $parent_node) {
            $graph->addPath($parent_node, $host_node, '=');
        }

        $url_node_ids = array_keys($url_type->parent_nodes);
        sort($url_node_ids);
        $graph->addUrlHost($host_node, [$url_var_id, $url_node_ids]);

        return $stmt_type->setParentNodes([$host_node->id => $host_node]);
    }

    /**
     * Records the host the value assigned holds for the node of the variable it is assigned to, if it can only be
     * that host: `parse_url()` itself, cast to a string or with an empty string for a missing host (`?: ''`, `?? ''`).
     * What else a ternary can give has no node to tell it apart.
     *
     * @psalm-capabilities read-props|write-props
     */
    public static function recordAssignment(
        StatementsAnalyzer $statements_analyzer,
        TaintFlowGraph $graph,
        Expr $assigned,
        DataFlowNode $var_node,
    ): void {
        $type = $statements_analyzer->node_data->getType($assigned);
        $url = $type === null || $type->parent_nodes === [] ? null : $graph->getUrlOfHost($type->parent_nodes);

        if ($url !== null && self::isOnlyAHost($assigned)) {
            $graph->addUrlHost($var_node, $url);
        }
    }

    /**
     * Records the host a cast to a string of it holds
     *
     * @param array<string, DataFlowNode> $parent_nodes
     * @psalm-capabilities read-props|write-props
     */
    public static function recordCast(TaintFlowGraph $graph, array $parent_nodes, DataFlowNode $cast_node): void
    {
        $url = $parent_nodes === [] ? null : $graph->getUrlOfHost($parent_nodes);

        if ($url !== null) {
            $graph->addUrlHost($cast_node, $url);
        }
    }

    /**
     * Whether the expression can only be a host parse_url() returns, or empty
     *
     * @psalm-capabilities read-props
     */
    private static function isOnlyAHost(Expr $expr): bool
    {
        while (true) {
            if ($expr instanceof Expr\Cast\String_) {
                $expr = $expr->expr;
            } elseif (($expr instanceof Expr\Ternary && $expr->if === null && self::isEmptyString($expr->else))
                || ($expr instanceof Expr\BinaryOp\Coalesce && self::isEmptyString($expr->right))
            ) {
                $expr = $expr instanceof Expr\Ternary ? $expr->cond : $expr->left;
            } else {
                break;
            }
        }

        return $expr instanceof FuncCall
            && $expr->name instanceof Name
            && strtolower($expr->name->toString()) === 'parse_url';
    }

    /**
     * @psalm-capabilities read-props
     */
    private static function isEmptyString(Expr $expr): bool
    {
        return $expr instanceof String_ && $expr->value === '';
    }

    /**
     * The assertion that the URL the subject is the host of has a server of the list of hosts the pattern matches
     *
     * @return ?array{string, MatchesPattern} the id of the variable holding the URL and the assertion
     * @psalm-capabilities read-props
     */
    public static function getAssertion(
        StatementsAnalyzer $statements_analyzer,
        string $pattern,
        Expr $subject,
        bool $negated,
    ): ?array {
        $graph = $statements_analyzer->getTaintFlowGraphWithSuppressed();
        $subject_type = $statements_analyzer->node_data->getType($subject);

        // a variable only holds a host once assigned one (see recordAssignment())
        if ($graph === null || $subject_type === null || $subject_type->parent_nodes === []
            || !self::matchesAListOfHosts($pattern)
            || !($subject instanceof Expr\Variable || self::isOnlyAHost($subject))
        ) {
            return null;
        }

        $url = $graph->getUrlOfHost($subject_type->parent_nodes);

        return $url === null ? null : [$url[0], new MatchesPattern(self::REMOVED_TAINTS, $negated, $url[1])];
    }

    /**
     * Whether the pattern matches only some exact names (`/^(a\.example|b\.example)$/D`): letters, digits, dashes and
     * escaped dots, anchored at both ends, with no other syntax
     *
     * @psalm-pure
     */
    public static function matchesAListOfHosts(string $pattern): bool
    {
        if (preg_match('/^(.)(.*)\1([a-zA-Z]*)$/s', $pattern, $matches) !== 1) {
            return false;
        }

        [, , $body, $modifiers] = $matches;

        // `$` also matches before a final newline, which no host has; `m` makes the anchors match at each line
        if (preg_match('/[^iDuS]/', $modifiers) === 1) {
            return false;
        }

        $name = '(?:[a-zA-Z0-9-]|\\\\\.)+';

        return preg_match('/^\^(?:' . $name . '|\((?:\?:)?' . $name . '(?:\|' . $name . ')*\))\$$/', $body) === 1;
    }
}
