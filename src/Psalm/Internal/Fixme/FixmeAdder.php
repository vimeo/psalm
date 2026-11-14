<?php

declare(strict_types=1);

namespace Psalm\Internal\Fixme;

use PhpParser\Comment\Doc;
use PhpParser\Node\Stmt;
use PhpParser\NodeFinder;
use Psalm\FileManipulation;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\IssueBuffer;
use Throwable;

use function array_keys;
use function count;
use function implode;
use function preg_match_all;
use function preg_split;
use function sort;
use function spl_object_id;
use function str_contains;
use function strlen;
use function strrpos;
use function substr;
use function trim;
use function usort;

use const PREG_SET_ORDER;

/**
 * Turns the issues Psalm currently reports into inline `@psalm-fixme` annotations.
 *
 * For each issue it finds the innermost statement enclosing the issue and adds a
 * `@psalm-fixme <IssueType>` to that statement's docblock (creating one if needed).
 * When the innermost statement already carries that annotation — meaning a previous
 * run added it there but it did not suppress the issue — the annotation is escalated
 * to the next enclosing statement, so repeated runs converge on a working location.
 *
 * The adder is purely additive: it never removes annotations. A `@psalm-fixme` that
 * ends up not suppressing anything is reported by `--find-unused-psalm-suppress`, which
 * is the supported way to surface and prune stale annotations.
 *
 * @internal
 */
final class FixmeAdder
{
    /**
     * @return int number of `@psalm-fixme` annotations added
     */
    public static function add(ProjectAnalyzer $project_analyzer): int
    {
        $codebase = $project_analyzer->getCodebase();
        $file_provider = $codebase->file_provider;

        $added = 0;

        foreach (IssueBuffer::getIssuesData() as $file_path => $file_issues) {
            if (!$file_provider->fileExists($file_path)) {
                continue;
            }

            try {
                $stmts = $codebase->getStatementsForFile($file_path);
            } catch (Throwable) {
                continue;
            }

            $contents = $file_provider->getContents($file_path);

            $stmt_nodes = (new NodeFinder())->findInstanceOf($stmts, Stmt::class);

            // target statement (by object id) => [stmt, types-to-add]
            $targets = [];
            $manipulations = [];

            foreach ($file_issues as $issue) {
                $target = self::findTargetStmt($stmt_nodes, $issue);

                if ($target === null) {
                    continue;
                }

                $id = spl_object_id($target);

                if (!isset($targets[$id])) {
                    $targets[$id] = ['stmt' => $target, 'types' => []];
                }

                $targets[$id]['types'][$issue->type] = true;
            }

            foreach ($targets as ['stmt' => $stmt, 'types' => $types]) {
                $manipulations[] = self::buildManipulation($stmt, array_keys($types), $contents);
                $added += count($types);
            }

            if (!$manipulations) {
                continue;
            }

            // apply from the end of the file backwards so offsets stay valid, skipping
            // any manipulation that overlaps one already applied (handled on a later run)
            usort($manipulations, static fn(FileManipulation $a, FileManipulation $b): int => $b->start <=> $a->start);

            $min_start = strlen($contents);

            foreach ($manipulations as $manipulation) {
                if ($manipulation->end > $min_start) {
                    continue;
                }

                $contents = $manipulation->transform($contents);
                $min_start = $manipulation->start;
            }

            $file_provider->setContents($file_path, $contents);
        }

        return $added;
    }

    /**
     * @param array<Stmt> $stmt_nodes
     */
    private static function findTargetStmt(array $stmt_nodes, IssueData $issue): ?Stmt
    {
        $offset = $issue->from;

        $covering = [];

        foreach ($stmt_nodes as $stmt) {
            $start = $stmt->getStartFilePos();
            $end = $stmt->getEndFilePos();

            if ($start < 0 || $end < 0) {
                continue;
            }

            if ($start <= $offset && $offset <= $end) {
                $covering[] = $stmt;
            }
        }

        if (!$covering) {
            return null;
        }

        // innermost first: the statement with the largest start position
        usort($covering, static fn(Stmt $a, Stmt $b): int => $b->getStartFilePos() <=> $a->getStartFilePos());

        foreach ($covering as $stmt) {
            $suppressed = self::getSuppressedTypes($stmt->getDocComment());

            if (isset($suppressed['all']) || isset($suppressed[$issue->type])) {
                // already annotated here but the issue persists — escalate outwards
                continue;
            }

            return $stmt;
        }

        return null;
    }

    /**
     * @return array<string, true>
     */
    private static function getSuppressedTypes(?Doc $docblock): array
    {
        if ($docblock === null) {
            return [];
        }

        $match_count = preg_match_all(
            '/@psalm-(?:suppress|fixme)\s+([^\n*]+)/',
            $docblock->getText(),
            $matches,
            PREG_SET_ORDER,
        );

        if ($match_count === false || $match_count === 0) {
            return [];
        }

        $types = [];

        foreach ($matches as $match) {
            $split = preg_split('/[\s,]+/', trim($match[1] ?? ''));

            if ($split === false) {
                continue;
            }

            foreach ($split as $type) {
                if ($type !== '') {
                    $types[$type] = true;
                }
            }
        }

        return $types;
    }

    /**
     * @param list<string> $types
     */
    private static function buildManipulation(Stmt $stmt, array $types, string $contents): FileManipulation
    {
        sort($types);

        $docblock = $stmt->getDocComment();

        if ($docblock === null) {
            $stmt_start = $stmt->getStartFilePos();
            $indent = self::getIndentation($contents, $stmt_start);

            $text = '/** @psalm-fixme ' . implode(', ', $types) . " */\n" . $indent;

            return new FileManipulation($stmt_start, $stmt_start, $text);
        }

        $docblock_start = $docblock->getStartFilePos();
        $docblock_end = $docblock->getEndFilePos();
        $indent = self::getIndentation($contents, $docblock_start);
        $docblock_text = $docblock->getText();

        if (!str_contains($docblock_text, "\n")) {
            // single-line docblock — expand it to multiline so the annotations fit
            $inner = trim(substr($docblock_text, 3, -2));

            $text = "/**\n";
            if ($inner !== '') {
                $text .= $indent . ' * ' . $inner . "\n";
            }
            foreach ($types as $type) {
                $text .= $indent . ' * @psalm-fixme ' . $type . "\n";
            }
            $text .= $indent . ' */';

            return new FileManipulation($docblock_start, $docblock_end + 1, $text);
        }

        // multiline docblock — splice the annotations in before the closing `*/`
        $closing_newline = strrpos($contents, "\n", $docblock_end - strlen($contents));
        $closing_line_start = $closing_newline === false ? 0 : $closing_newline + 1;

        $text = '';
        foreach ($types as $type) {
            $text .= $indent . ' * @psalm-fixme ' . $type . "\n";
        }

        return new FileManipulation($closing_line_start, $closing_line_start, $text);
    }

    private static function getIndentation(string $contents, int $position): string
    {
        $line_start = strrpos($contents, "\n", $position - strlen($contents));
        $line_start = $line_start === false ? 0 : $line_start + 1;

        $prefix = substr($contents, $line_start, $position - $line_start);

        return trim($prefix) === '' ? $prefix : '';
    }
}
