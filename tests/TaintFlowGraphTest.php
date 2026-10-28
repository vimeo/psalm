<?php

declare(strict_types=1);

namespace Psalm\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\CodeLocation\Raw;
use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Codebase\DataFlowGraph;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\DataFlow\Path;
use Psalm\IssueBuffer;
use Psalm\Type\TaintKind;
use ReflectionProperty;

use function array_reverse;
use function extension_loaded;
use function igbinary_serialize;
use function igbinary_unserialize;
use function json_encode;
use function serialize;
use function sort;
use function str_starts_with;
use function unserialize;

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

    /**
     * Two files can add the same path between two nodes, changing taints differently: a flow takes either, so
     * the taint one of them keeps reaches the sink, whichever graph is merged first.
     */
    public function testPathAddedTwiceKeepsTheTaintsEitherKeeps(): void
    {
        foreach ([false, true] as $reverse_merge_order) {
            $this->assertSame(
                ['TaintedHtml'],
                $this->resolveMergedPaths(
                    [['=', TaintKind::INPUT_HTML], ['=', 0]],
                    'arg',
                    $reverse_merge_order,
                ),
            );
        }
    }

    /**
     * Two files can add paths of different types between the same nodes: a flow takes either. The source is
     * assigned to another key than the one the sink fetches: the fetch ignores it past a plain path, but not
     * past an assignment to any key, whichever graph is merged first.
     */
    public function testPathsOfDifferentTypesBetweenTheSameNodesAreBothTaken(): void
    {
        foreach ([false, true] as $reverse_merge_order) {
            $this->assertSame(
                ['TaintedHtml'],
                $this->resolveMergedPaths(
                    [['=', 0], ['arrayvalue-assignment', 0]],
                    'arrayvalue-fetch-\'key\'',
                    $reverse_merge_order,
                ),
            );
        }
    }

    /**
     * @return iterable<string, array{bool}>
     * @psalm-pure
     */
    public static function provideSerializers(): iterable
    {
        yield 'native' => [false];
        yield 'igbinary' => [true];
    }

    /**
     * Forked workers send their graph to the parent, which merges and resolves what it decodes.
     */
    #[DataProvider('provideSerializers')]
    public function testGraphResolvesTheSameAfterEncodingAndDecoding(bool $igbinary): void
    {
        if ($igbinary && !extension_loaded('igbinary')) {
            $this->markTestSkipped('igbinary is not loaded');
        }

        IssueBuffer::clear();

        $this->project_analyzer->trackTaintedInputs();
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->config->throw_exception = false;

        $file_path = self::$src_dir_path . 'somefile.php';
        $this->addFile($file_path, '<?php echo $x;');
        $codebase->addFilesToAnalyze([$file_path => $file_path]);
        $codebase->scanFiles();
        $location = new Raw('<?php echo $x;', $file_path, 'somefile.php', 6, 14);

        $graph = new TaintFlowGraph();
        $source = DataFlowNode::getForTaint('source', $location, TaintKind::INPUT_HTML);
        $variable = DataFlowNode::getForAssignment('$x', $location);
        $other = DataFlowNode::getForAssignment('$y', $location);
        $dead_end = DataFlowNode::getForAssignment('$z', $location);
        $later_line = DataFlowNode::getForAssignment(
            '$w',
            new Raw("<?php\n\necho \$x;", $file_path, 'somefile.php', 8, 16),
        );
        $sink = DataFlowNode::getForTaint('sink', $location, TaintKind::INPUT_HTML);

        $graph->addSource($source);
        $graph->addNode($variable);
        $graph->addNode($other);
        $graph->addNode($dead_end);
        $graph->addNode($later_line);
        $graph->addSink($sink);
        $graph->addPath($source, $variable, '=');
        $graph->addPath($source, $other, '=');
        $graph->addPath($variable, $sink, 'arrayvalue-fetch', TaintKind::INPUT_SQL);
        $graph->addPath($other, $dead_end, 'arrayvalue-fetch', TaintKind::INPUT_SQL);
        // Paths that differ from the shared ones in one thing each: the taints it removes, the taints it adds,
        // and its length (the lines between the nodes)
        $graph->addPath($dead_end, $later_line, 'arrayvalue-fetch', TaintKind::INPUT_SQL, TaintKind::INPUT_HTML);
        $graph->addPath($later_line, $other, 'arrayvalue-fetch', TaintKind::INPUT_HTML);
        $graph->addPath($source, $later_line, '=');

        $serialized = $igbinary ? igbinary_serialize($graph) : serialize($graph);
        $this->assertIsString($serialized);
        $copy = $igbinary ? igbinary_unserialize($serialized) : unserialize($serialized);

        $this->assertInstanceOf(TaintFlowGraph::class, $copy);
        $this->assertEquals($graph, $copy);

        /** @var array<string, array<string, Path>> $edges */
        $edges = (new ReflectionProperty(DataFlowGraph::class, 'forward_edges'))->getValue($copy);
        $this->assertSame($edges[$source->id][$variable->id], $edges[$source->id][$other->id]);
        $this->assertSame($edges[$variable->id][$sink->id], $edges[$other->id][$dead_end->id]);
        $this->assertNotSame($edges[$source->id][$variable->id], $edges[$variable->id][$sink->id]);
        $this->assertNotSame($edges[$variable->id][$sink->id], $edges[$dead_end->id][$later_line->id]);
        $this->assertNotSame($edges[$variable->id][$sink->id], $edges[$later_line->id][$other->id]);
        $this->assertNotSame($edges[$source->id][$variable->id], $edges[$source->id][$later_line->id]);

        $merged = new TaintFlowGraph();
        $merged->addGraph($copy);
        $codebase->taint_flow_graph = $merged;
        $merged->connectSinksAndSources($codebase->progress);

        $issue_types = [];

        foreach (IssueBuffer::getIssuesData() as $file_issues) {
            foreach ($file_issues as $issue) {
                $issue_types[] = $issue->type;
            }
        }

        $this->assertSame(['TaintedHtml'], $issue_types);
    }

    /**
     * Resolves the merge of partial graphs, each with the source assigned to the array key 'other' of an
     * array, a path of the given type and removed taints from that array to a variable, and a path of type
     * $sink_path_type from the variable to a sink, and returns the types of the issues reported.
     *
     * @param non-empty-list<array{string, int}> $paths
     * @return list<string>
     */
    private function resolveMergedPaths(array $paths, string $sink_path_type, bool $reverse_merge_order): array
    {
        IssueBuffer::clear();

        $this->project_analyzer->trackTaintedInputs();
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->config->throw_exception = false;

        $file_path = self::$src_dir_path . 'somefile.php';
        $this->addFile($file_path, '<?php echo $x;');
        $codebase->addFilesToAnalyze([$file_path => $file_path]);
        $codebase->scanFiles();
        $location = new Raw('<?php echo $x;', $file_path, 'somefile.php', 6, 14);

        $partials = [];

        foreach ($paths as [$path_type, $removed_taints]) {
            $partial = new TaintFlowGraph();
            $source = DataFlowNode::getForTaint('source', $location, TaintKind::INPUT_HTML);
            $array = DataFlowNode::getForAssignment('array', $location);
            $variable = DataFlowNode::getForAssignment('$x', $location);
            $sink = DataFlowNode::getForTaint('sink', $location, TaintKind::INPUT_HTML);

            $partial->addSource($source);
            $partial->addNode($array);
            $partial->addNode($variable);
            $partial->addSink($sink);
            $partial->addPath($source, $array, 'arrayvalue-assignment-\'other\'');
            $partial->addPath($array, $variable, $path_type, 0, $removed_taints);
            $partial->addPath($variable, $sink, $sink_path_type);

            $partials[] = $partial;
        }

        if ($reverse_merge_order) {
            $partials = array_reverse($partials);
        }

        $merged = new TaintFlowGraph();

        foreach ($partials as $partial) {
            $merged->addGraph($partial);
        }

        $codebase->taint_flow_graph = $merged;
        $merged->connectSinksAndSources($codebase->progress);

        $issue_types = [];

        foreach (IssueBuffer::getIssuesData() as $file_issues) {
            foreach ($file_issues as $issue) {
                $issue_types[] = $issue->type;
            }
        }

        return $issue_types;
    }
}
