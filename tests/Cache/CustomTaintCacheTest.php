<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use Amp\Future;
use Amp\Sync\Channel;
use Amp\Sync\ChannelException;
use Override;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\Provider\ClassLikeStorageCacheProvider;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\FileReferenceCacheProvider;
use Psalm\Internal\Provider\FileStorageCacheProvider;
use Psalm\Internal\Provider\ParserCacheProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Tests\Internal\Provider\ProjectCacheProvider;
use Psalm\Tests\TestCase;
use Psalm\Type\TaintKind;

use function Amp\Sync\createChannelPair;
use function Amp\async;

/**
 * Custom taint types (registered lazily via {@see \Psalm\Codebase::getOrRegisterTaint()}, e.g. from
 * `@psalm-taint-sink`/`@psalm-taint-source` docblocks or plugins) are assigned a bit in registration
 * order, and that bit is baked into the cached file/classlike storage. On a cache hit the defining
 * docblocks are not re-parsed, so unless the name->bit mapping is restored the same taint name gets a
 * different bit (or none), and cached taint sinks/sources silently stop matching. These tests cover the
 * persistence/restore mechanism that keeps the bits stable across runs.
 *
 * `graphql`/`graphql-json` below are arbitrary *custom* taint names (they are deliberately not among the
 * builtin {@see TaintKind::TAINT_NAMES}), standing in for taints a plugin or docblock might introduce.
 */
final class CustomTaintCacheTest extends TestCase
{
    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        RuntimeCaches::clearAll();
    }

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    public function tearDown(): void
    {
        RuntimeCaches::clearAll();

        parent::tearDown();
    }

    public function testExportImportRoundTripPreservesBitsAndAppends(): void
    {
        $codebase = $this->project_analyzer->getCodebase();

        $graphql = $codebase->getOrRegisterTaint('graphql');
        $graphql_json = $codebase->getOrRegisterTaint('graphql-json');

        // Custom taints get the first bits after the builtin ones, in registration order.
        $this->assertSame(1 << TaintKind::BUILTIN_TAINT_COUNT, $graphql);
        $this->assertSame(1 << (TaintKind::BUILTIN_TAINT_COUNT + 1), $graphql_json);

        $exported = $codebase->exportCustomTaints();

        // A fresh codebase, as if this were a later run that reuses the cache.
        $fresh = (new ProjectAnalyzer(
            $this->testConfig,
            new Providers($this->file_provider, new FakeParserCacheProvider()),
        ))->getCodebase();

        $fresh->importCustomTaints($exported);

        // The restored names resolve to exactly the same bits...
        $this->assertSame($graphql, $fresh->getOrRegisterTaint('graphql'));
        $this->assertSame($graphql_json, $fresh->getOrRegisterTaint('graphql-json'));

        // ...and a brand new taint appends after the restored ones rather than colliding with them.
        $this->assertSame(
            1 << (TaintKind::BUILTIN_TAINT_COUNT + 2),
            $fresh->getOrRegisterTaint('brand-new'),
        );
    }

    public function testImportIsNoOpOnceCustomTaintsAreRegistered(): void
    {
        $codebase = $this->project_analyzer->getCodebase();

        $graphql = $codebase->getOrRegisterTaint('graphql');

        // Restoring must not clobber taints already registered in this run.
        $codebase->importCustomTaints([
            'count' => TaintKind::BUILTIN_TAINT_COUNT + 1,
            'custom' => [1 << TaintKind::BUILTIN_TAINT_COUNT => 'something-else'],
            'map' => ['something-else' => 1 << TaintKind::BUILTIN_TAINT_COUNT],
        ]);

        $this->assertSame($graphql, $codebase->getOrRegisterTaint('graphql'));
    }

    public function testForkedWorkerRegistersTaintsThroughParentRegistry(): void
    {
        $parent = $this->project_analyzer->getCodebase();

        [$worker_channel, $parent_channel] = $this->taintChannelPair();
        $worker = $this->freshCodebase();
        $worker->setTaintRegistrationChannel($worker_channel);

        $pump = $this->pumpTaintRequests($parent_channel, $parent);

        // The worker never assigns bits itself; each one comes from the parent's single registry.
        $graphql = $worker->getOrRegisterTaint('graphql');
        $graphql_json = $worker->getOrRegisterTaint('graphql-json');

        $this->assertSame(1 << TaintKind::BUILTIN_TAINT_COUNT, $graphql);
        $this->assertSame(1 << (TaintKind::BUILTIN_TAINT_COUNT + 1), $graphql_json);

        // The parent ends up knowing them too, so exportCustomTaints() persists a complete map.
        $this->assertSame($graphql, $parent->getOrRegisterTaint('graphql'));
        $this->assertSame($graphql_json, $parent->getOrRegisterTaint('graphql-json'));

        $worker_channel->close();
        $pump->await();
    }

    public function testConcurrentWorkersAgreeOnTaintBits(): void
    {
        // This is the case a naive per-worker counter gets wrong: two workers each discover a *different*
        // brand-new taint first, so with independent counters they would assign both the same bit (or the
        // same name two different bits). Routing every registration through the parent keeps them in sync.
        $parent = $this->project_analyzer->getCodebase();

        [$worker_channel_1, $parent_channel_1] = $this->taintChannelPair();
        [$worker_channel_2, $parent_channel_2] = $this->taintChannelPair();

        $worker_1 = $this->freshCodebase();
        $worker_1->setTaintRegistrationChannel($worker_channel_1);
        $worker_2 = $this->freshCodebase();
        $worker_2->setTaintRegistrationChannel($worker_channel_2);

        $pump_1 = $this->pumpTaintRequests($parent_channel_1, $parent);
        $pump_2 = $this->pumpTaintRequests($parent_channel_2, $parent);

        // Deliberately opposite discovery order in the two workers.
        $worker_1_bar = $worker_1->getOrRegisterTaint('bar');
        $worker_2_foo = $worker_2->getOrRegisterTaint('foo');
        $worker_1_foo = $worker_1->getOrRegisterTaint('foo');
        $worker_2_bar = $worker_2->getOrRegisterTaint('bar');

        // Every worker resolves a given name to the same bit...
        $this->assertSame($worker_1_foo, $worker_2_foo);
        $this->assertSame($worker_1_bar, $worker_2_bar);
        // ...and distinct names never share a bit.
        $this->assertNotSame($worker_1_foo, $worker_1_bar);

        $worker_channel_1->close();
        $worker_channel_2->close();
        $pump_1->await();
        $pump_2->await();
    }

    /**
     * A connected pair of channels typed like the ones psalm uses: the worker end receives responses and
     * sends taint names, the parent end receives names and sends responses.
     *
     * @return array{
     *     Channel<array{id: int|null, count: int}, string>,
     *     Channel<string, array{id: int|null, count: int}>
     * }
     */
    private function taintChannelPair(): array
    {
        /**
         * @var array{
         *     Channel<array{id: int|null, count: int}, string>,
         *     Channel<string, array{id: int|null, count: int}>
         * }
         */
        return createChannelPair();
    }

    /**
     * Answer taint-registration requests on $channel through $registry's single registry, like Pool does.
     *
     * @param Channel<string, array{id: int|null, count: int}> $channel
     */
    private function pumpTaintRequests(Channel $channel, Codebase $registry): Future
    {
        return async(static function () use ($channel, $registry): void {
            try {
                while (true) {
                    $channel->send($registry->registerTaintFromWorker($channel->receive()));
                }
            } catch (ChannelException) {
                // Channel closed once the "worker" is done.
            }
        });
    }

    private function freshCodebase(): Codebase
    {
        return (new ProjectAnalyzer(
            $this->testConfig,
            new Providers($this->file_provider, new FakeParserCacheProvider()),
        ))->getCodebase();
    }

    public function testCustomTaintBitsSurviveCacheReuseAcrossRuns(): void
    {
        $config = $this->makeConfig();
        $config->setIncludeCollector(new IncludeCollector());

        // The ProjectCacheProvider is the one piece of state that persists between the two runs, exactly
        // like the on-disk cache in a real invocation.
        $providers = new Providers(
            new FakeFileProvider(),
            new ParserCacheProvider($config, '', false),
            new FileStorageCacheProvider($config, '', false),
            new ClassLikeStorageCacheProvider($config, '', false),
            new FileReferenceCacheProvider($config, '', false),
            new ProjectCacheProvider(),
        );

        // First run registers two custom taints (order fixes their bits) and persists the mapping.
        $first = new ProjectAnalyzer($config, $providers);
        $graphql = $first->getCodebase()->getOrRegisterTaint('graphql');
        $graphql_json = $first->getCodebase()->getOrRegisterTaint('graphql-json');
        $first->persistCustomTaints();

        // Second run reuses the same cache. Suppose only the file defining `graphql-json` gets re-scanned
        // (the `graphql` sink is served from cache and never re-registered). Without restoring the mapping,
        // `graphql-json` would now be the *first* custom taint and collide with `graphql`'s cached bit.
        $second = new ProjectAnalyzer($config, $providers);
        $this->assertSame($graphql_json, $second->getCodebase()->getOrRegisterTaint('graphql-json'));
        $this->assertSame($graphql, $second->getCodebase()->getOrRegisterTaint('graphql'));

        // Sanity check the failure mode: with a fresh (empty) cache, `graphql-json` really would be assigned
        // `graphql`'s bit, which is exactly the mismatch the persistence prevents.
        $withoutCache = new ProjectAnalyzer(
            $config,
            new Providers(
                new FakeFileProvider(),
                new ParserCacheProvider($config, '', false),
                new FileStorageCacheProvider($config, '', false),
                new ClassLikeStorageCacheProvider($config, '', false),
                new FileReferenceCacheProvider($config, '', false),
                new ProjectCacheProvider(),
            ),
        );
        $this->assertSame($graphql, $withoutCache->getCodebase()->getOrRegisterTaint('graphql-json'));
    }
}
