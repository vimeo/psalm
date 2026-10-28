<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use Closure;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PhpParser\Node\Scalar\String_;
use Psalm\Aliases;
use Psalm\CodeLocation;
use Psalm\FileSource;
use Psalm\Internal\Codebase\CodeUseGraph;
use Psalm\Internal\Codebase\MutationLevelResolver;
use Psalm\Storage\Capabilities;
use Psalm\Tests\TestCase;

use function array_keys;
use function array_slice;
use function count;

/**
 * @psalm-import-type MutationInfo from MutationLevelResolver
 * @psalm-import-type CapabilitySet from Capabilities
 */
final class CodeUseGraphTest extends TestCase
{
    /**
     * Nothing is treated as external code in these unit tests.
     *
     * @return Closure(string): bool
     * @psalm-pure
     */
    private static function notExternal(): Closure
    {
        return static fn(string $_node_id): bool => false;
    }

    public function testResolvesCyclesRecursively(): void
    {
        $graph = new CodeUseGraph();
        // method ids (with ::) so they are not free-function entry points
        $used_a = CodeUseGraph::functionLikeNode('used\\c::a');
        $used_b = CodeUseGraph::functionLikeNode('used\\c::b');
        $unused_a = CodeUseGraph::functionLikeNode('unused\\c::a');
        $unused_b = CodeUseGraph::functionLikeNode('unused\\c::b');

        $graph->addEdge($used_a, $used_b);
        $graph->addEdge($used_b, $used_a);
        $graph->addEdge($unused_a, $unused_b);
        $graph->addEdge($unused_b, $unused_a);
        $graph->markAsPublicApi($used_a);

        $graph->resolve(self::notExternal());

        self::assertTrue($graph->isUsed($used_a));
        self::assertTrue($graph->isUsed($used_b));
        self::assertFalse($graph->isUsed($unused_a));
        self::assertFalse($graph->isUsed($unused_b));
    }

    public function testUsedReturnUsesFunction(): void
    {
        $graph = new CodeUseGraph();
        $function = CodeUseGraph::functionLikeNode('a\\c::m');
        $return = CodeUseGraph::functionLikeReturnNode('a\\c::m');
        $graph->addEdge($return, $function, CodeUseGraph::EDGE_RETURN);
        $graph->markAsPublicApi($return);

        $graph->resolve(self::notExternal());

        self::assertTrue($graph->isUsed($return));
        self::assertTrue($graph->isUsed($function));
        self::assertSame([$return => true], $graph->getReferencingNodes($function));
    }

    public function testCanResolveAgainAfterGraphChanges(): void
    {
        $graph = new CodeUseGraph();
        $a = CodeUseGraph::functionLikeNode('a\\c::m');
        $b = CodeUseGraph::functionLikeNode('b\\c::m');
        $graph->markAsPublicApi($a);
        $graph->resolve(self::notExternal());
        self::assertFalse($graph->isUsed($b));

        $graph->addEdge($a, $b);
        $graph->resolve(self::notExternal());

        self::assertTrue($graph->isUsed($b));
    }

    public function testWriteEdgeDoesNotMarkPropertyUsed(): void
    {
        $graph = new CodeUseGraph();
        $property = CodeUseGraph::propertyNode('a\\c', 'value');

        // a read from top-level code of /read.php, a write from /write.php
        $graph->addReference($property, null, null, CodeUseGraph::EDGE_USE, '/read.php');
        $graph->addReference($property, null, null, CodeUseGraph::EDGE_WRITE, '/write.php');

        $graph->resolve(self::notExternal());
        self::assertTrue($graph->isUsed($property), 'a read keeps the property used');

        // dropping the only read leaves just the write, which must not keep it used
        $graph->removeReferencesFromFile('/read.php');
        $graph->resolve(self::notExternal());

        self::assertFalse($graph->isUsed($property), 'a write alone does not use the property');
        self::assertSame(
            [CodeUseGraph::fileNode('/write.php') => true],
            $graph->getReferencingNodes($property),
        );
    }

    public function testExternalCallerMarksTargetUsed(): void
    {
        $graph = new CodeUseGraph();
        $method = CodeUseGraph::functionLikeNode('a\\c::m');
        $external = CodeUseGraph::functionLikeNode('vendor\\c::caller');
        $graph->addEdge($external, $method);

        // the caller belongs to code outside the project, so what it calls is used
        $graph->resolve(static fn(string $node_id): bool => $node_id === $external);

        self::assertTrue($graph->isUsed($method));
    }

    /**
     * @param array<string, bool> $callees
     * @param CapabilitySet $intrinsic
     * @return MutationInfo
     */
    private static function mutationInfo(
        string $name,
        string $root,
        bool $inherited = false,
        int $intrinsic = Capabilities::NONE,
        array $callees = [],
        bool $fresh = true,
    ): array {
        $declaring_file = new class ('/src/Declaring.php', new Aliases()) implements FileSource {
            public function __construct(private string $path, private Aliases $aliases)
            {
            }

            #[Override]
            public function getFileName(): string
            {
                return $this->path;
            }

            #[Override]
            public function getFilePath(): string
            {
                return $this->path;
            }

            #[Override]
            public function getRootFileName(): string
            {
                return $this->path;
            }

            #[Override]
            public function getRootFilePath(): string
            {
                return $this->path;
            }

            #[Override]
            public function getAliases(): Aliases
            {
                return $this->aliases;
            }
        };

        return [
            'intrinsic' => $intrinsic,
            'allowed' => Capabilities::NONE,
            'callees' => $callees,
            'default_intrinsic' => Capabilities::NONE,
            'default_callees' => [],
            'location' => new CodeLocation($declaring_file, new String_('x')),
            'cased_name' => $name,
            'suppressed_issues' => [],
            'class' => null,
            'start' => 0,
            'fresh' => $fresh,
            'root' => $root,
            'inherited' => $inherited,
            'report' => true,
        ];
    }

    /**
     * Candidates analysed for the same function-like declared in /src/Declaring.php, with the name of the one that
     * has to win. The merge has to give the same result for every order the workers' graphs are merged in.
     *
     * @return iterable<string, array{list<MutationInfo>, string}>
     */
    public static function providerMutationInfoPrecedence(): iterable
    {
        $declaring = '/src/Declaring.php';

        yield 'analysis in the declaring file over the analysis when included' => [
            [
                self::mutationInfo('own', $declaring),
                self::mutationInfo('included', '/zzz/Includer.php', false, Capabilities::ALL),
            ],
            'own',
        ];

        yield 'analysis when included over the analysis of an inherited body' => [
            [
                self::mutationInfo('included', '/aaa/Includer.php'),
                self::mutationInfo('inherited', '/zzz/Includer.php', true, Capabilities::ALL),
            ],
            'included',
        ];

        yield 'analysis in the declaring file over the analysis of an inherited body' => [
            [
                self::mutationInfo('own', $declaring),
                self::mutationInfo('inherited', $declaring, true, Capabilities::ALL),
            ],
            'own',
        ];

        yield 'greater root file among the analyses when included' => [
            [
                self::mutationInfo('b', '/src/B.php', false, Capabilities::NONE),
                self::mutationInfo('a', '/src/A.php', false, Capabilities::ALL),
            ],
            'b',
        ];

        yield 'greater intrinsic level among analyses of one root' => [
            [
                self::mutationInfo('low', '/src/Root.php', true, Capabilities::READ_PROPS, ['z' => false]),
                self::mutationInfo('high', '/src/Root.php', true, Capabilities::IO, ['a' => false]),
            ],
            'high',
        ];

        yield 'greater callees among analyses of one root and level' => [
            [
                self::mutationInfo('x', '/src/Root.php', true, Capabilities::IO, ['x' => false]),
                self::mutationInfo('y', '/src/Root.php', true, Capabilities::IO, ['y' => false]),
            ],
            'y',
        ];

        yield 'callees are compared as a list' => [
            [
                self::mutationInfo('short', '/src/Root.php', true, Capabilities::IO, ['a' => false]),
                self::mutationInfo('long', '/src/Root.php', true, Capabilities::IO, ['a' => false, 'b' => true]),
            ],
            'long',
        ];

        yield 'trait method analysed in three classes using it' => [
            [
                self::mutationInfo('first', '/src/Declaring.php', true, Capabilities::READ_PROPS, ['m' => false]),
                self::mutationInfo('second', '/src/Declaring.php', true, Capabilities::WRITE_PROPS, ['k' => false]),
                self::mutationInfo('third', '/src/Declaring.php', true, Capabilities::WRITE_PROPS, ['n' => false]),
            ],
            'third',
        ];

        yield 'fresh analysis over one of a previous run' => [
            [
                self::mutationInfo('stale', $declaring, false, Capabilities::NONE, [], false),
                self::mutationInfo('fresh', '/src/Includer.php', true, Capabilities::ALL),
            ],
            'fresh',
        ];

        yield 'fresh analysis over one of a previous run of a greater root' => [
            [
                self::mutationInfo('stale', '/zzz/Root.php', false, Capabilities::ALL, [], false),
                self::mutationInfo('fresh', '/aaa/Root.php', false, Capabilities::NONE),
            ],
            'fresh',
        ];
    }

    /**
     * @param list<MutationInfo> $infos
     * @return list<list<MutationInfo>>
     * @psalm-pure
     */
    private static function permutations(array $infos): array
    {
        if (count($infos) <= 1) {
            return [$infos];
        }

        $permutations = [];

        foreach ($infos as $i => $info) {
            $rest = [...array_slice($infos, 0, $i), ...array_slice($infos, $i + 1)];

            foreach (self::permutations($rest) as $permutation) {
                $permutations[] = [$info, ...$permutation];
            }
        }

        return $permutations;
    }

    /**
     * @param list<MutationInfo> $infos
     */
    #[DataProvider('providerMutationInfoPrecedence')]
    public function testMergingWorkerGraphsPicksTheSameMutationInfoInAnyOrder(array $infos, string $winner): void
    {
        $node_id = CodeUseGraph::functionLikeNode('a\\trait_::m');

        $expected_levels = null;

        foreach (self::permutations($infos) as $permutation) {
            $merged = new CodeUseGraph();

            foreach ($permutation as $info) {
                $worker_graph = new CodeUseGraph();
                $worker_graph->addMutationInfo($node_id, $info);
                $merged->addGraph($worker_graph);
            }

            $order = '';

            foreach ($permutation as $info) {
                $order .= $info['cased_name'] . ' ';
            }

            self::assertSame([$node_id], array_keys($merged->getMutationInfo()), $order);
            self::assertSame($winner, $merged->getMutationInfo()[$node_id]['cased_name'], 'merged in order: ' . $order);

            $expected_levels ??= $merged->getMutationLevels();
            self::assertSame($expected_levels, $merged->getMutationLevels(), 'merged in order: ' . $order);
        }
    }

    public function testAnalysisWithEqualPrecedenceReplacesTheRecordedOne(): void
    {
        $graph = new CodeUseGraph();
        $node_id = CodeUseGraph::functionLikeNode('a\\c::m');

        $graph->addMutationInfo($node_id, self::mutationInfo('before', '/src/Root.php', true, Capabilities::IO));
        $graph->addMutationInfo($node_id, self::mutationInfo('after', '/src/Root.php', true, Capabilities::IO));

        self::assertSame('after', $graph->getMutationInfo()[$node_id]['cased_name']);
    }

    public function testStaleMutationInfoNeverReplacesFreshOne(): void
    {
        $graph = new CodeUseGraph();
        $node_id = CodeUseGraph::functionLikeNode('a\\c::m');

        $graph->addMutationInfo($node_id, self::mutationInfo('fresh', '/src/Root.php', true));
        $graph->addMutationInfo(
            $node_id,
            self::mutationInfo('stale', '/src/Declaring.php', false, Capabilities::ALL, [], false),
        );

        self::assertSame('fresh', $graph->getMutationInfo()[$node_id]['cased_name']);
    }

    public function testMutationLevelsFollowTheMutationInfoThatWon(): void
    {
        $graph = new CodeUseGraph();
        $node_id = CodeUseGraph::functionLikeNode('a\\c::m');

        $graph->addMutationInfo($node_id, self::mutationInfo('included', '/src/Includer.php', false, Capabilities::IO));
        self::assertSame([$node_id => Capabilities::IO], $graph->getMutationLevels());

        // the analysis in the declaring file replaces it
        $graph->addMutationInfo(
            $node_id,
            self::mutationInfo('own', '/src/Declaring.php', false, Capabilities::READ_PROPS),
        );
        self::assertSame([$node_id => Capabilities::READ_PROPS], $graph->getMutationLevels());

        // one that does not replace it leaves them as they are
        $graph->addMutationInfo($node_id, self::mutationInfo('inherited', '/src/Includer.php', true, Capabilities::ALL));
        self::assertSame([$node_id => Capabilities::READ_PROPS], $graph->getMutationLevels());
    }
}
