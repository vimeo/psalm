<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Context;

use function getcwd;
use function sort;
use function str_contains;

/**
 * The array_map()/array_filter() return-type providers create synthetic "offset" variables while
 * analysing the mapping callback, and the names of those variables end up as taint-graph node ids. They
 * used to be named with mt_rand(), which made the taint graph — and therefore the taint-graph dump and
 * any downstream diffing of it — non-deterministic from one run to the next. The discriminator is now
 * derived from the call position, so analysing the same code twice must produce the same taint graph.
 */
final class TaintDeterminismTest extends TestCase
{
    public function testArrayMapTaintGraphIsDeterministic(): void
    {
        $code = <<<'PHP'
            <?php
            function process(array $arr): void {
                $mapped = array_map('strval', $arr);
                echo $mapped[0];
            }
            PHP;

        $edges = $this->analyzeTaintEdges($code);
        // Guard: the code really does create the array_map synthetic-variable nodes we are testing.
        self::assertNotSame([], $this->fakeVarEdges($edges));

        $this->assertSame($edges, $this->analyzeTaintEdges($code));
    }

    public function testArrayFilterTaintGraphIsDeterministic(): void
    {
        $code = <<<'PHP'
            <?php
            function process(array $arr): void {
                $filtered = array_filter($arr, 'strlen');
                echo $filtered[0];
            }
            PHP;

        $edges = $this->analyzeTaintEdges($code);
        self::assertNotSame([], $this->fakeVarEdges($edges));

        $this->assertSame($edges, $this->analyzeTaintEdges($code));
    }

    /**
     * The array_map()/array_filter() synthetic offset variable is what used to be named with mt_rand();
     * isolate those edges so the "does the graph even contain them" guard can't silently pass.
     *
     * @param list<list<string>> $edges
     * @return list<list<string>>
     * @psalm-pure
     */
    private function fakeVarEdges(array $edges): array
    {
        $out = [];
        foreach ($edges as $edge) {
            foreach ($edge as $node) {
                if (str_contains($node, '__fake_')) {
                    $out[] = $edge;
                    break;
                }
            }
        }

        return $out;
    }

    /**
     * Analyse $code in a freshly initialised project and return the (order-normalised) taint-graph edges.
     *
     * @return list<list<string>>
     */
    private function analyzeTaintEdges(string $code): array
    {
        // Re-initialise so the second analysis starts from clean state (and, before the fix, would draw a
        // fresh mt_rand() discriminator).
        $this->setUp();

        // We only want to inspect the taint graph, not assert on the issues, so don't throw on them.
        $this->testConfig->throw_exception = false;

        $file_path = (string) getcwd() . '/src/taint_determinism.php';
        $this->addFile($file_path, $code);

        $this->analyzeFile($file_path, new Context(), false, true);

        $taint_flow_graph = $this->project_analyzer->getCodebase()->taint_flow_graph;
        self::assertNotNull($taint_flow_graph);

        $edges = $taint_flow_graph->summarizeEdges();

        // We only care that the set/naming of edges is stable, not the iteration order.
        sort($edges);

        return $edges;
    }
}
