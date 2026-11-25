<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Storage\Assertion\MatchesPattern;
use Psalm\Type\TaintKind;
use Psalm\Type\Union;

use function strlen;
use function strtolower;

/**
 * A path that starts with a directory (`str_starts_with($path, '/var/app/data/')`, `strpos($path, '/var/app/data/')
 * === 0`) is local, not the URL of a server. The path realpath() returns is canonical: absolute, without `.` or `..`
 * and without symbolic links, so one that starts with a directory is in it, and can't be a file the user chooses
 * elsewhere.
 *
 * @internal
 */
final class CanonicalPaths
{
    /**
     * What realpath() returns, recorded as a canonical path
     */
    public static function recordRealpath(
        StatementsAnalyzer $statements_analyzer,
        FuncCall $stmt,
        string $function_id,
        Union $stmt_type,
    ): Union {
        $graph = $statements_analyzer->getTaintFlowGraphWithSuppressed();

        if ($graph === null || strtolower($function_id) !== 'realpath' || $stmt_type->parent_nodes === []) {
            return $stmt_type;
        }

        $path_node = DataFlowNode::getForAssignment('canonical path', new CodeLocation($statements_analyzer, $stmt));
        $graph->addNode($path_node);
        foreach ($stmt_type->parent_nodes as $parent_node) {
            $graph->addPath($parent_node, $path_node, '=');
        }

        $graph->addCanonicalPath($path_node);

        return $stmt_type->setParentNodes([$path_node->id => $path_node]);
    }

    /**
     * Records that the parent nodes hold a canonical path for the node they flow into, such as that of the variable
     * they are assigned to or of a cast
     *
     * @param array<string, DataFlowNode> $parent_nodes
     * @psalm-capabilities read-props|write-props
     */
    public static function recordFlow(TaintFlowGraph $graph, array $parent_nodes, DataFlowNode $node): void
    {
        if ($parent_nodes !== [] && $graph->areCanonicalPaths($parent_nodes)) {
            $graph->addCanonicalPath($node);
        }
    }

    /**
     * The assertion that the haystack given to str_starts_with() or strpos() is a path starting with the directory
     * the needle is
     *
     * @param array<array-key, Arg> $args
     * @return list<non-empty-array<string, non-empty-list<non-empty-list<MatchesPattern>>>>
     */
    public static function getAssertions(StatementsAnalyzer $statements_analyzer, array $args, bool $negated): array
    {
        $subject = null;
        $prefix = null;
        foreach ($args as $offset => $arg) {
            $name = $arg->name?->name;
            if ($arg->unpack) {
                return [];
            } elseif ($name === 'haystack' || ($name === null && $offset === 0)) {
                $subject = $arg->value;
            } elseif ($name === 'needle' || ($name === null && $offset === 1)) {
                $prefix = $arg->value;
            } else {
                // strpos() from an offset
                return [];
            }
        }

        $graph = $statements_analyzer->getTaintFlowGraphWithSuppressed();
        $subject_type = $subject === null ? null : $statements_analyzer->node_data->getType($subject);
        $prefix_type = $prefix === null ? null : $statements_analyzer->node_data->getType($prefix);

        if ($subject === null
            || $graph === null
            || $subject_type === null
            || $subject_type->parent_nodes === []
            || $prefix_type === null
            || !$prefix_type->isSingleStringLiteral()
            || !self::isDirectory($prefix_type->getSingleStringLiteral()->value)
        ) {
            return [];
        }

        $var_id = ExpressionIdentifier::getExtendedVarId(
            $subject,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer,
        );

        $removed_taints = $graph->areCanonicalPaths($subject_type->parent_nodes)
            ? TaintKind::INPUT_SSRF | TaintKind::INPUT_FILE
            : TaintKind::INPUT_SSRF;

        return $var_id === null ? [] : [[$var_id => [[new MatchesPattern($removed_taints, $negated)]]]];
    }

    /**
     * An absolute path ending with a separator: what starts with it is in the directory, not one whose name starts
     * the same way (`/var/app/data2`), and isn't a URL (`//server/`)
     *
     * @psalm-pure
     */
    private static function isDirectory(string $prefix): bool
    {
        return strlen($prefix) > 2 && $prefix[0] === '/' && $prefix[1] !== '/' && $prefix[strlen($prefix) - 1] === '/';
    }
}
