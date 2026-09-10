<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeFinder;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\FileManipulation\FunctionDocblockManipulator;
use Psalm\Issue\MissingPureAnnotation;
use Psalm\IssueBuffer;
use Psalm\Storage\Mutations;
use Throwable;

use function array_keys;
use function array_pop;
use function max;

/**
 * Infers the level of mutations (purity) of every analysed function-like once
 * the whole codebase has been analysed.
 *
 * During analysis, each function-like records the mutations it performs itself
 * and the unannotated project function-likes it calls (see
 * {@see \Psalm\Internal\Analyzer\SourceAnalyzer::signalMutationOnlyInferred()}).
 * The final level of a function-like is the maximum of its own level and of the
 * final levels of its callees, which is computed here as a fixpoint over the
 * call graph, so that call chains in any order and recursive cycles (direct,
 * mutual, or through closures) all converge.
 *
 * @psalm-type MutationInfo = array{
 *     intrinsic: Mutations::LEVEL_*,
 *     allowed: Mutations::LEVEL_*,
 *     callees: array<string, bool>,
 *     location: CodeLocation,
 *     cased_name: string,
 *     suppressed_issues: array<int, string>,
 *     class: ?string,
 *     file_path: string,
 *     start: int,
 *     fresh: bool,
 *     report: bool
 * }
 * @internal
 */
final class MutationLevelResolver
{
    /**
     * Computes the final mutation level of every function-like with recorded
     * mutation info: node id => level.
     *
     * @param array<string, MutationInfo> $infos
     * @return array<string, Mutations::LEVEL_*>
     * @psalm-pure
     */
    public static function resolveLevels(array $infos): array
    {
        $levels = [];

        /** @var array<string, array<string, true>> callee => callers */
        $callers = [];

        foreach ($infos as $node_id => $info) {
            $levels[$node_id] = $info['intrinsic'];

            foreach ($info['callees'] as $callee_id => $_) {
                $callers[$callee_id][$node_id] = true;
            }
        }

        $queue = array_keys($infos);

        while ($queue) {
            $node_id = array_pop($queue);
            $level = $levels[$node_id];

            foreach ($infos[$node_id]['callees'] as $callee_id => $internal_mutations_ok) {
                // a callee that was never analysed (e.g. skipped) could do anything
                $callee_level = $levels[$callee_id] ?? Mutations::LEVEL_ALL;

                if ($internal_mutations_ok && $callee_level <= Mutations::LEVEL_INTERNAL_READ_WRITE) {
                    // mutations of the callee's own instance don't leak to the caller
                    $callee_level = Mutations::LEVEL_NONE;
                }

                $level = max($level, $callee_level);
            }

            if ($level !== $levels[$node_id]) {
                // levels only ever increase and are bounded, so this terminates even with cycles
                $levels[$node_id] = $level;

                foreach ($callers[$node_id] ?? [] as $caller_id => $_) {
                    $queue[] = $caller_id;
                }
            }
        }

        return $levels;
    }

    /**
     * Resolves the mutation levels of the code analysed in this run, reports
     * function-likes that could be marked with a stricter purity annotation and
     * queues the annotation fixes when running with `--alter`.
     */
    public static function resolve(ProjectAnalyzer $project_analyzer): void
    {
        $codebase = $project_analyzer->getCodebase();
        $graph = $codebase->code_use_graph;

        $infos = $graph->getMutationInfo();

        if (!$infos) {
            return;
        }

        $levels = self::resolveLevels($infos);

        $fix = $codebase->alter_code && isset($project_analyzer->getIssuesToFix()['MissingPureAnnotation']);

        foreach ($infos as $node_id => $info) {
            $level = $levels[$node_id];

            if ($info['class'] !== null) {
                $codebase->analyzer->addMutableClass($info['class'], $level);
            }

            if (!$info['fresh']) {
                // analysed in a previous run: its issues come from the cache
                continue;
            }

            $graph->markMutationInfoStale($node_id);

            if (!$info['report'] || $level >= $info['allowed']) {
                continue;
            }

            IssueBuffer::maybeAdd(
                new MissingPureAnnotation(
                    $info['cased_name'] . ' must be marked @' . Mutations::TO_ATTRIBUTE_FUNCTIONLIKE[$level]
                    . ' to aid security analysis'
                    . ', run with --alter --issues=MissingPureAnnotation to fix this',
                    $info['location'],
                ),
                $info['suppressed_issues'],
            );

            if ($fix) {
                [$stmt, $docblock_anchor] = self::findFunctionLike($codebase, $info['file_path'], $info['start']);

                if ($stmt !== null) {
                    FunctionDocblockManipulator::getForFunction(
                        $project_analyzer,
                        $info['file_path'],
                        $stmt,
                        $docblock_anchor,
                    )->setAllowedMutations($level);
                }
            }
        }
    }

    /**
     * Finds the function-like starting at the given offset, along with the
     * statement its docblock belongs to for closures (`$f = function () {}`).
     *
     * @return array{Closure|Function_|ClassMethod|ArrowFunction|null, ?Stmt}
     */
    private static function findFunctionLike(
        Codebase $codebase,
        string $file_path,
        int $start_pos,
    ): array {
        try {
            $stmts = $codebase->getStatementsForFile($file_path);
        } catch (Throwable) {
            return [null, null];
        }

        $finder = new NodeFinder();

        $node = $finder->findFirst(
            $stmts,
            static fn(Node $node): bool => $node instanceof FunctionLike
                && (int) $node->getAttribute('startFilePos') === $start_pos,
        );

        if ($node instanceof Function_ || $node instanceof ClassMethod) {
            return [$node, null];
        }

        if (!$node instanceof Closure && !$node instanceof ArrowFunction) {
            return [null, null];
        }

        // the innermost statement containing the closure
        $anchor = null;

        foreach ($finder->find(
            $stmts,
            static fn(Node $candidate): bool => $candidate instanceof Stmt
                && (int) $candidate->getAttribute('startFilePos') <= $start_pos
                && (int) $candidate->getAttribute('endFilePos') >= $start_pos,
        ) as $candidate) {
            if ($anchor === null
                || (int) $candidate->getAttribute('startFilePos') >= (int) $anchor->getAttribute('startFilePos')
            ) {
                $anchor = $candidate;
            }
        }

        return [$node, $anchor instanceof Stmt ? $anchor : null];
    }
}
