<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\IssueBuffer;

use function array_reverse;
use function json_encode;
use function sort;
use function str_starts_with;

use const JSON_THROW_ON_ERROR;

/**
 * Regression test for non-deterministic taint issue generation in
 * multi-threaded mode.
 *
 * When analysis is forked, each worker builds its own partial taint flow graph
 * and the main process merges them (TaintFlowGraph::addGraph) in fork
 * completion order, which is non-deterministic. The same data-flow node can be
 * produced by more than one worker carrying a different CodeLocation, so a
 * "keep whatever arrived first" merge made the reported taint issues depend on
 * which worker happened to finish first - issues (and their traces) would shift
 * between otherwise identical runs and break the baseline.
 *
 * Rather than relying on real thread races (which can't be reproduced reliably
 * in a unit test), this test runs the real analyzer to build a separate partial
 * graph per file - exactly what each worker produces - then merges those
 * partials in different orders and asserts the resolved taint issues are
 * identical regardless of merge order.
 */
final class TaintFlowGraphTest extends TestCase
{
    /**
     * Analyzes each file into its own partial taint graph (as a worker would),
     * merges them in the given order, resolves and returns a stable, fully
     * descriptive fingerprint of every taint issue produced.
     *
     * @param array<string, string> $files
     * @return list<string>
     */
    private function analyzeWithMergeOrder(array $files, bool $reverse_merge_order): array
    {
        IssueBuffer::clear();

        $this->project_analyzer->trackTaintedInputs();

        $codebase = $this->project_analyzer->getCodebase();

        // Collect every taint issue instead of throwing on the first one.
        $codebase->config->throw_exception = false;

        $paths = [];
        foreach ($files as $name => $contents) {
            $path = self::$src_dir_path . $name;
            $this->file_provider->registerFile($path, $contents);
            $codebase->scanner->addFileToShallowScan($path);
            $paths[$path] = $path;
        }

        $codebase->addFilesToAnalyze($paths);
        $codebase->scanFiles();
        $this->project_analyzer->getConfig()->visitStubFiles($codebase);

        // Build one partial graph per file, mirroring per-worker analysis.
        $partials = [];
        foreach ($paths as $path) {
            $partial = new TaintFlowGraph();
            $codebase->taint_flow_graph = $partial;

            $file_analyzer = new FileAnalyzer(
                $this->project_analyzer,
                $path,
                $codebase->config->shortenFileName($path),
            );
            $file_analyzer->analyze(new Context());

            $partials[] = $partial;
        }

        if ($reverse_merge_order) {
            $partials = array_reverse($partials);
        }

        // Merge the partials and resolve, exactly as the main process does.
        $merged = new TaintFlowGraph();
        foreach ($partials as $partial) {
            $merged->addGraph($partial);
        }
        $codebase->taint_flow_graph = $merged;
        $merged->connectSinksAndSources($codebase->progress);

        $issues = [];
        foreach (IssueBuffer::getIssuesData() as $file_issues) {
            foreach ($file_issues as $issue) {
                if (!str_starts_with($issue->type, 'Tainted')) {
                    continue;
                }
                // Everything a baseline / report cares about, plus the trace.
                $issues[] = json_encode([
                    $issue->type,
                    $issue->file_name,
                    $issue->line_from,
                    $issue->column_from,
                    $issue->selected_text,
                    $issue->taint_trace,
                ], JSON_THROW_ON_ERROR);
            }
        }
        sort($issues);

        return $issues;
    }

    public function testTaintIssuesAreDeterministicAcrossMergeOrder(): void
    {
        // A taint-source helper that is *shared* between two files. The source
        // node for H::src() is created independently while analyzing each
        // caller, and the two CodeLocations it receives differ - this is what
        // used to make the merge order observable.
        $files = [
            'Source.php' => '<?php
                namespace App;
                class Tainter {
                    /** @psalm-taint-source html */
                    public function src(): string { return ""; }
                }',
            'Sink.php' => '<?php
                namespace App;
                class Sink {
                    /** @psalm-taint-sink html $s */
                    public function consume(string $s): void {}
                }',
            'Consumer.php' => '<?php
                namespace App;
                function consumer(): void {
                    (new Sink())->consume((new Tainter())->src());
                }',
            'Consumer2.php' => '<?php
                namespace App;
                function consumer2(): void {
                    echo (new Tainter())->src();
                }',
        ];

        $forward = $this->analyzeWithMergeOrder($files, false);
        $reverse = $this->analyzeWithMergeOrder($files, true);

        $this->assertNotEmpty($forward, 'Expected the scenario to produce taint issues');
        $this->assertSame(
            $forward,
            $reverse,
            'Taint issues must not depend on the order partial graphs are merged in',
        );
    }
}
