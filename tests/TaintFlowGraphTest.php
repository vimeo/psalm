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
use Psalm\Storage\FunctionStorage;
use Psalm\Type\TaintKind;
use ReflectionProperty;

use function array_diff;
use function array_keys;
use function array_reverse;
use function extension_loaded;
use function igbinary_serialize;
use function igbinary_unserialize;
use function json_encode;
use function ksort;
use function range;
use function serialize;
use function sort;
use function str_repeat;
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
     * Which generators a send is linked to must not depend on the order the workers' graphs are merged in, which
     * sets the order of a node's edges. linkGeneratorSends() reaches a node here with 17 call stacks, one over
     * TaintFlowGraph::GENERATOR_SEND_STACKS, and which of them it replaces with "any call matches" decides whether
     * `other`, the generator that isn't sent to, is linked too.
     */
    public function testGeneratorSendLinksAreDeterministicAcrossMergeOrder(): void
    {
        $lines = $this->getGeneratorSendLinkLines(false);

        // the 17 generators sent to are on the lines 2 to 18, the one that isn't on the line 19
        $this->assertSame([], array_diff(range(2, 18), $lines));
        $this->assertSame($lines, $this->getGeneratorSendLinkLines(true));
    }

    /**
     * Resolves a tainted send that reaches 17 generators, 16 of them through calls of a relay function, with a sink
     * on what is sent to each. One more generator, `other`, is reachable only where linkGeneratorSends() matches any
     * call. Merges the workers' graphs in reverse order when $reverse_merge_order is true.
     *
     * @return list<int> the lines of the sinks reached, one per linked generator
     */
    private function getGeneratorSendLinkLines(bool $reverse_merge_order): array
    {
        IssueBuffer::clear();

        $this->project_analyzer->trackTaintedInputs();
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->config->throw_exception = false;

        $file_path = self::$src_dir_path . 'somefile.php';
        $file_contents = "<?php\n" . str_repeat("echo \$x;\n", 20);
        $this->addFile($file_path, $file_contents);
        $codebase->addFilesToAnalyze([$file_path => $file_path]);
        $codebase->scanFiles();

        $storage = new FunctionStorage();
        $node = static fn(string $id, ?string $key = null): DataFlowNode
            => DataFlowNode::getForMethodReturn($id, $storage, specialization_key: $key);

        $shared = DataFlowNode::getForGlobalVariable('shared');
        $relays = 16;
        $order = range(0, $relays);

        if ($reverse_merge_order) {
            $order = array_reverse($order);
        }

        // the node every call of the shared function is reached from: its edges are added by each worker
        $graph = new TaintFlowGraph();

        foreach ($order as $i) {
            $worker = new TaintFlowGraph();
            $call = $node('root#1', "R$i");
            $worker->addNode($shared);
            $worker->addNode($call);
            $worker->addPath($shared, $call, '=');
            $graph->addGraph($worker);
        }

        $edge = static function (DataFlowNode $from, DataFlowNode $to) use ($graph): void {
            $graph->addNode($from);
            $graph->addNode($to);
            $graph->addPath($from, $to, '=');
        };

        // the sink of the generator with the index $index is on the line $index + 2 of the file
        $sink_for = static function (
            int $index,
            DataFlowNode $sent,
        ) use (
            $graph,
            $edge,
            $file_path,
            $file_contents,
        ): void {
            $start = 6 + $index * 9;
            $sink = DataFlowNode::getForTaint(
                "sink $index",
                new Raw($file_contents, $file_path, 'somefile.php', $start, $start + 4),
                TaintKind::INPUT_HTML,
            );
            $graph->addSink($sink);
            $edge($sent, $sink);
        };

        // 16 relays and 1 direct call of the shared function, each with the generator it is given
        for ($i = 0; $i <= $relays; $i++) {
            $direct = $i === $relays;
            $edge(
                $direct ? $node('pass1', 'H') : $node('relay', "A$i"),
                $node('root#1', "R$i"),
            );
            $generator = $node("gen$i");
            $sent = $node("gen$i sent");
            $graph->addNode($sent);
            $graph->addGenerator($generator, $sent);
            $sink_for($i, $sent);
            $edge($generator, $direct ? $node('pass1#1', 'H') : $node('relay#1', "A$i"));
        }

        // the relays are called through another shared function, called with the generator that isn't sent to
        $edge($node('pass1', 'I'), $node('relay'));
        $edge($node('pass1#1'), $node('pass1'));
        $edge($node('pass2', 'K'), $node('pass1#1', 'I'));
        $edge($node('pass2#1'), $node('pass2'));
        $edge($node('relay#1'), $node('pass2#1', 'K'));

        $other = $node('other');
        $other_sent = $node('other sent');
        $graph->addNode($other_sent);
        $graph->addGenerator($other, $other_sent);
        $sink_for($relays + 1, $other_sent);
        $edge($other, $node('pass2#1', 'L'));

        $send = DataFlowNode::getForTaint(
            'send',
            new Raw($file_contents, $file_path, 'somefile.php', 0, 4),
            TaintKind::INPUT_HTML,
        );
        $graph->addSource($send);
        $root = $node('root#1');
        $graph->addGeneratorSend($send, [$root->id => $root]);

        $codebase->taint_flow_graph = $graph;
        $graph->connectSinksAndSources($codebase->progress);

        $lines = [];

        foreach (IssueBuffer::getIssuesData() as $file_issues) {
            foreach ($file_issues as $issue) {
                if ($issue->type === 'TaintedHtml') {
                    $lines[$issue->line_from] = true;
                }
            }
        }

        ksort($lines);

        return array_keys($lines);
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
