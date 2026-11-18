<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Storage\Assertion\MatchesPattern;
use Psalm\Type\TaintKind;
use Psalm\Type\Union;

use function array_keys;
use function preg_match;
use function sort;

use const PHP_URL_HOST;

/**
 * A URL whose host matches a list of hosts (`preg_match('/^(a\.example|b\.example)$/', parse_url($url,
 * PHP_URL_HOST))`) can only reach those servers: it can't make a request to a server it chooses (SSRF) nor redirect
 * to one. The host keeps track of the variable holding the URL it was taken from, and of the nodes that variable
 * held, so that the URL is only trusted while it holds the same value.
 *
 * @internal
 */
final class UrlHosts
{
    /** What a URL whose server is one of a list can't carry */
    public const REMOVED_TAINTS = TaintKind::INPUT_SSRF | TaintKind::INPUT_REDIRECT;

    /**
     * What `parse_url($url, PHP_URL_HOST)` returns, recorded as the host of `$url`
     *
     * @param list<Arg> $args
     */
    public static function recordParseUrl(
        StatementsAnalyzer $statements_analyzer,
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

        if ($url_var_id === null || $url_type === null || $url_type->parent_nodes === []) {
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
     * Records the host the parent nodes hold for the node they flow into, such as that of the variable they are
     * assigned to or of a cast
     *
     * @param array<string, DataFlowNode> $parent_nodes
     * @psalm-capabilities read-props|write-props
     */
    public static function recordFlow(TaintFlowGraph $graph, array $parent_nodes, DataFlowNode $node): void
    {
        $url = $parent_nodes === [] ? null : $graph->getUrlOfHost($parent_nodes);

        if ($url !== null) {
            $graph->addUrlHost($node, $url);
        }
    }

    /**
     * The assertion that the URL the subject is the host of has a server of the list of hosts the pattern matches
     *
     * @return ?array{string, MatchesPattern} the id of the variable holding the URL and the assertion
     *
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

        if ($graph === null || $subject_type === null || $subject_type->parent_nodes === []
            || !self::matchesAListOfHosts($pattern)
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
