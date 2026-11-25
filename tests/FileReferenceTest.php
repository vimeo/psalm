<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Context;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\CacheMode;
use Psalm\Internal\Codebase\CodeUseGraph;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\FileReferenceCacheProvider;
use Psalm\Internal\Provider\FileReferenceProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;

use function array_keys;
use function array_values;
use function count;
use function is_array;
use function ksort;
use function strpos;

final class FileReferenceTest extends TestCase
{
    protected ProjectAnalyzer $project_analyzer;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->project_analyzer->getCodebase()->collectLocations();
        $this->project_analyzer->setPhpVersion('7.3', 'tests');
    }

    /**
     * @param array<int, string> $expected_locations
     */
    #[DataProvider('providerReferenceLocations')]
    public function testReferenceLocations(string $input_code, string $symbol, array $expected_locations): void
    {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        $context = new Context();

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile($file_path, $input_code);

        $this->analyzeFile($file_path, $context);

        $found_references = $this->project_analyzer->getCodebase()->findReferencesToSymbol($symbol);
        $found_references = array_values($found_references);

        $this->assertCount(count($found_references), $expected_locations);

        foreach ($found_references as &$loc) {
            $loc = $loc->getLineNumber() . ':' . $loc->getColumn()
                    . ':' . $loc->getSelectedText();
        } unset($loc);

        $this->assertEquals($expected_locations, $found_references);
    }

    public function testLeadingBackslashFindsTheSameReferencesForEverySymbolKind(): void
    {
        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                namespace App;

                function helper(): int {
                    return 1;
                }

                final class H {
                    public const C = 1;
                    public int $p = 1;

                    public function a(): int {
                        return $this->p + self::C + helper();
                    }
                }

                echo (new H())->a();',
        );

        $this->analyzeFile($file_path, new Context());

        $codebase = $this->project_analyzer->getCodebase();

        foreach (['App\\H', 'App\\H::a', 'App\\H::$p', 'App\\H::C', 'App\\helper'] as $symbol) {
            $references = $codebase->findReferencesToSymbol($symbol);

            $this->assertNotEmpty($references, $symbol);
            $this->assertSame(
                array_keys($references),
                array_keys($codebase->findReferencesToSymbol('\\' . $symbol)),
                $symbol,
            );
        }
    }

    public function testReferenceLocationsAreRemovedWithTheirSourceNode(): void
    {
        $file_path = self::$src_dir_path . 'somefile.php';
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->diff_methods = true;

        $this->file_provider->registerFile(
            $file_path,
            '<?php
                class A {}
                final class B {
                    public function useA(): void {
                        new A();
                    }
                }
                (new B())->useA();',
        );
        $codebase->reloadFiles($this->project_analyzer, [$file_path]);
        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

        self::assertNotSame([], $codebase->findReferencesToClassLike('A'));
        $codebase->code_use_graph->removeReferencesFrom(CodeUseGraph::functionLikeNode('b::usea'));
        self::assertSame([], $codebase->findReferencesToClassLike('A'));
    }

    public function testRemovedSourceNodeCanBeReassignedToAnotherFile(): void
    {
        $graph = new CodeUseGraph();
        $source_node = CodeUseGraph::functionLikeNode('a::foo');
        $target_node = CodeUseGraph::classNode('b');
        $context = new Context();
        $context->calling_method_id = 'a::foo';

        $graph->addReference($target_node, $context, null, CodeUseGraph::EDGE_USE, '/old.php');
        self::assertSame('/old.php', $graph->getNodeFile($source_node));

        $graph->removeReferencesFrom($source_node);
        self::assertNull($graph->getNodeFile($source_node));

        $graph->addReference($target_node, $context, null, CodeUseGraph::EDGE_USE, '/new.php');
        self::assertSame('/new.php', $graph->getNodeFile($source_node));
    }

    public function testUsedReferencesExcludeDeadSources(): void
    {
        $graph = new CodeUseGraph();
        $used_source = CodeUseGraph::functionLikeNode('a::used');
        $dead_source = CodeUseGraph::functionLikeNode('a::dead');
        $target = CodeUseGraph::functionLikeNode('a::target');

        $graph->markAsPublicApi($used_source);
        $graph->addEdge($used_source, $target);
        $graph->addEdge($dead_source, $target);
        $graph->resolve(static fn(string $_): bool => false);

        self::assertSame([$used_source => true], $graph->getUsedReferencingNodes($target));
    }

    /**
     * @see https://github.com/vimeo/psalm/issues/12586
     */
    public function testLineOneLocationHasOneBasedColumnAndFullSnippet(): void
    {
        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile($file_path, "<?php class A {} new A();\n");

        $this->analyzeFile($file_path, new Context());

        $found_references = $this->project_analyzer->getCodebase()->findReferencesToSymbol('A');

        // `new A()` is recorded both as the whole expression and as the class name
        $actual = [];
        foreach ($found_references as $location) {
            $actual[$location->getColumn()] = $location->getLineNumber()
                . ':' . $location->getColumn()
                . ':' . $location->getSelectedText()
                . ':' . $location->getSnippet();
        }
        ksort($actual);

        $this->assertSame(
            [
                18 => '1:18:new A():<?php class A {} new A();',
                22 => '1:22:A:<?php class A {} new A();',
            ],
            $actual,
        );
    }

    /**
     * @param array<string,array<string,bool>> $expected_references
     */
    #[DataProvider('providerReferencedMethods')]
    public function testReferencedMethods(
        string $input_code,
        array $expected_references,
    ): void {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        $context = new Context();

        $file_path = '/var/www/somefile.php';

        $this->addFile($file_path, $input_code);

        $this->analyzeFile($file_path, $context);

        $graph = $this->project_analyzer->getCodebase()->code_use_graph;

        /**
         * @psalm-suppress MixedAssignment
         * @psalm-capabilities write-refs
         */
        $ksort_recursive = function (array &$arr) use (&$ksort_recursive): void {
            ksort($arr);
            foreach ($arr as &$value) {
                if (is_array($value)) {
                    $ksort_recursive($value);
                }
            }
        };

        $all = $graph->getAllReferences();
        $ksort_recursive($all);
        $this->assertSame($expected_references, $all);
    }

    /**
     * @param array<string, list<string>> $expected_locations
     */
    #[DataProvider('providerReferenceLocationsThroughInheritingClasses')]
    public function testReferenceLocationsThroughInheritingClasses(string $input_code, array $expected_locations): void
    {
        $this->addFile(self::$src_dir_path . 'somefile.php', $input_code);
        $this->analyzeFile(self::$src_dir_path . 'somefile.php', new Context());

        $found = [];

        foreach (array_keys($expected_locations) as $symbol) {
            $found[$symbol] = [];

            foreach ($this->project_analyzer->getCodebase()->findReferencesToSymbol($symbol) as $location) {
                $found[$symbol][] = $location->getLineNumber() . ':' . $location->getColumn()
                    . ':' . $location->getSelectedText();
            }
        }

        $this->assertSame($expected_locations, $found);
    }

    public function testReferencedMethodsStopAtAClassWhoseStorageIsGone(): void
    {
        $file_path = self::$src_dir_path . 'somefile.php';
        $this->addFile($file_path, '<?php
            class A {
                public function m(): int {
                    return 1;
                }
            }

            class B extends A {}

            class C extends B {}');
        $this->analyzeFile($file_path, new Context());

        $codebase = $this->project_analyzer->getCodebase();
        $codebase->classlike_storage_provider->remove('B', $file_path);

        $this->assertTrue($codebase->methods->methodExists($codebase, new MethodIdentifier('C', 'm'), 'caller::f'));

        $references = $codebase->code_use_graph->getAllReferences();

        $this->assertSame(['func caller::f' => true], $references['func c::m'] ?? null);
        $this->assertSame(['func caller::f' => true], $references['func b::m'] ?? null);
    }

    public function testReferencedMethodsStopAtAClassAlreadyVisited(): void
    {
        $file_path = self::$src_dir_path . 'somefile.php';
        $this->addFile($file_path, '<?php
            class A {
                public function m(): int {
                    return 1;
                }
            }

            class B extends A {}

            class C extends B {}');
        $this->analyzeFile($file_path, new Context());

        $codebase = $this->project_analyzer->getCodebase();
        $codebase->classlike_storage_provider->get('B')->inherited_method_sources['m'] = 'c';

        $this->assertTrue($codebase->methods->methodExists($codebase, new MethodIdentifier('C', 'm'), 'caller::f'));

        $references = $codebase->code_use_graph->getAllReferences();

        $this->assertSame(['func caller::f' => true], $references['func c::m'] ?? null);
        $this->assertSame(['func caller::f' => true], $references['func b::m'] ?? null);
    }

    public function testAnalysisPersistsEveryResultToTheReferenceCache(): void
    {
        $config = new TestConfig();
        $cache = new FileReferenceCacheProvider($config, '', CacheMode::Memory);
        $file_path = $this->scanFileWithReferenceCache($cache);
        $this->project_analyzer->getCodebase()->analyzer->analyzeFiles($this->project_analyzer, 1, false);

        self::assertArrayHasKey('b::foo', $cache->getAnalyzedMethodCache()[$file_path] ?? []);
        self::assertNotSame([], $cache->getFileMapCache()[$file_path] ?? []);
        self::assertNotSame([], $cache->getTypeCoverage()[$file_path] ?? []);
        $issue = $cache->getCachedIssues()[$file_path][0] ?? null;
        self::assertInstanceOf(IssueData::class, $issue);
        self::assertSame('InvalidReturnStatement', $issue->type);
        self::assertSame([$file_path], $cache->getCachedFileReferences()[$file_path]['i'] ?? null);
    }

    public function testCacheSavedWithoutEdgesIsOnlyLoadedByRunsThatDoNotNeedThem(): void
    {
        $cache = new FileReferenceCacheProvider(new TestConfig(), '', CacheMode::Memory);
        $file_path = $this->scanFileWithReferenceCache($cache);
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->code_use_graph->record_edges = false;
        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

        self::assertFalse($cache->getCachedCodeUseGraph()['edges_recorded'] ?? null);

        // a run needing the edges loads nothing of the cache, `--diff` would trust the analysed methods it has
        FileReferenceProvider::clearCache();
        $needs_edges = new FileReferenceProvider($this->file_provider, $cache);
        self::assertFalse($needs_edges->loadReferenceCache());
        self::assertSame([], $needs_edges->getAnalyzedMethods());

        FileReferenceProvider::clearCache();
        $without_edges = new FileReferenceProvider($this->file_provider, $cache);
        $without_edges->code_use_graph->record_edges = false;
        self::assertTrue($without_edges->loadReferenceCache());
        self::assertArrayHasKey($file_path, $without_edges->getAnalyzedMethods());
    }

    public function testCacheSavedWithEdgesIsLoadedByAnyRun(): void
    {
        $cache = new FileReferenceCacheProvider(new TestConfig(), '', CacheMode::Memory);
        $this->scanFileWithReferenceCache($cache);
        $this->project_analyzer->getCodebase()->analyzer->analyzeFiles($this->project_analyzer, 1, false);

        self::assertTrue($cache->getCachedCodeUseGraph()['edges_recorded'] ?? null);

        $needs_edges = new FileReferenceProvider($this->file_provider, $cache);
        self::assertTrue($needs_edges->loadReferenceCache());
        self::assertNotSame([], $needs_edges->code_use_graph->getAllReferences());

        // the edges stay in the cache, but nothing reads them
        $without_edges = new FileReferenceProvider($this->file_provider, $cache);
        $without_edges->code_use_graph->record_edges = false;
        self::assertTrue($without_edges->loadReferenceCache());
        self::assertSame([], $without_edges->code_use_graph->getAllReferences());
    }

    public function testCacheSavedBeforeEdgesWereOptionalIsLoadedByRunsNeedingThem(): void
    {
        $cache = new FileReferenceCacheProvider(new TestConfig(), '', CacheMode::Memory);
        $this->scanFileWithReferenceCache($cache);
        $this->project_analyzer->getCodebase()->analyzer->analyzeFiles($this->project_analyzer, 1, false);

        $cached = $cache->getCachedCodeUseGraph();
        self::assertNotNull($cached);
        self::assertArrayHasKey('edges_recorded', $cached);
        unset($cached['edges_recorded']);
        $cache->setCachedCodeUseGraph($cached);

        $needs_edges = new FileReferenceProvider($this->file_provider, $cache);
        self::assertTrue($needs_edges->loadReferenceCache());
        self::assertNotSame([], $needs_edges->code_use_graph->getAllReferences());
    }

    public function testUpdatingReferenceCacheWithoutEdgesLeavesTheFileReferences(): void
    {
        $codebase = $this->project_analyzer->getCodebase();
        $file_path = self::$src_dir_path . 'a.php';
        $this->file_provider->registerFile($file_path, '<?php class A {}');
        $codebase->addFilesToAnalyze([$file_path => $file_path]);
        $codebase->scanFiles();

        $provider = $codebase->file_reference_provider;
        $provider->code_use_graph->record_edges = false;
        $provider->addFileInheritanceToClass('/b.php', 'a');
        $provider->code_use_graph->addReference(
            CodeUseGraph::classNode('a'),
            null,
            null,
            CodeUseGraph::EDGE_USE,
            '/b.php',
        );
        $provider->updateReferenceCache($codebase, [$file_path => true]);

        self::assertSame([], $provider->getFilesInheritingFromFile($file_path));
        self::assertSame([], $provider->getFilesReferencingFile($file_path));
    }

    public function testAnalysisWritesTheReferenceCacheOnce(): void
    {
        $cache = $this->getMockBuilder(FileReferenceCacheProvider::class)
            ->setConstructorArgs([new TestConfig(), '', CacheMode::Memory])
            ->onlyMethods(['setCachedFileReferences'])
            ->getMock();

        $this->scanFileWithReferenceCache($cache);
        $cache->expects(self::once())->method('setCachedFileReferences');
        $this->project_analyzer->getCodebase()->analyzer->analyzeFiles($this->project_analyzer, 1, false);
    }

    public function testUpdatingReferenceCacheKeepsReferencesAsLists(): void
    {
        $codebase = $this->project_analyzer->getCodebase();
        $file_path = self::$src_dir_path . 'a.php';
        $this->file_provider->registerFile($file_path, '<?php class A {}');
        $codebase->addFilesToAnalyze([$file_path => $file_path]);
        $codebase->scanFiles();

        $provider = $codebase->file_reference_provider;
        $graph = $provider->code_use_graph;
        $class_node = CodeUseGraph::classNode('a');

        $provider->addFileInheritanceToClass('/b.php', 'a');
        $graph->addReference($class_node, null, null, CodeUseGraph::EDGE_USE, '/b.php');
        $provider->updateReferenceCache($codebase, [$file_path => true]);

        $provider->addFileInheritanceToClass('/c.php', 'a');
        $graph->addReference($class_node, null, null, CodeUseGraph::EDGE_USE, '/c.php');
        $provider->updateReferenceCache($codebase, [$file_path => true]);

        self::assertSame(['/b.php', '/c.php'], $provider->getFilesInheritingFromFile($file_path));
        self::assertSame(['/b.php', '/c.php'], $provider->getFilesReferencingFile($file_path));
    }

    public function testFileOfAClassInheritsFromTheClassesItsMixinLookupReaches(): void
    {
        $codebase = $this->project_analyzer->getCodebase();
        $deep_path = self::$src_dir_path . 'deep.php';
        $mid_path = self::$src_dir_path . 'mid.php';
        $host_path = self::$src_dir_path . 'host.php';
        $this->file_provider->registerFile($deep_path, '<?php final class Deep {}');
        $this->file_provider->registerFile($mid_path, '<?php /** @mixin Deep */ final class Mid {}');
        $this->file_provider->registerFile(
            $host_path,
            '<?php /** @mixin Mid */ trait ForwardsCalls {} abstract class Base { use ForwardsCalls; } final class Host extends Base {}',
        );
        $codebase->addFilesToAnalyze([$deep_path => $deep_path, $mid_path => $mid_path, $host_path => $host_path]);
        $codebase->scanFiles();

        $provider = $codebase->file_reference_provider;
        $provider->updateReferenceCache($codebase, [$deep_path => true, $mid_path => true]);

        // a change to the mixin of the mixin of a trait of the parent class reaches the file of the class
        self::assertEqualsCanonicalizing([$mid_path, $host_path], $provider->getFilesInheritingFromFile($deep_path));
        self::assertSame([$host_path], $provider->getFilesInheritingFromFile($mid_path));
    }

    private function scanFileWithReferenceCache(FileReferenceCacheProvider $cache): string
    {
        $this->file_provider = new FakeFileProvider();
        $this->project_analyzer = new ProjectAnalyzer(
            new TestConfig(),
            new Providers($this->file_provider, new FakeParserCacheProvider(), null, null, $cache),
        );
        $this->project_analyzer->setPhpVersion('7.3', 'tests');

        $file_path = self::$src_dir_path . 'somefile.php';
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->diff_methods = true;
        $codebase->config->throw_exception = false;

        $this->file_provider->registerFile(
            $file_path,
            '<?php
                class A {}
                class B extends A {
                    public function foo(): int {
                        return "a";
                    }
                }',
        );
        $codebase->reloadFiles($this->project_analyzer, [$file_path]);

        return $file_path;
    }

    /**
     * @return array<string,array{string,string,array<int,string>}>
     * @psalm-pure
     */
    public static function providerReferenceLocations(): array
    {
        return [
            'getClassLocation' => [
                '<?php
                    class A {}

                    new A();',
                'A',
                [
                    '4:25:A',
                    '4:21:new A()',
                ],
            ],
            'getMethodLocation' => [
                '<?php
                    class A {
                        /** @psalm-mutation-free */
                        public function foo(): void {}
                    }

                    (new A())->foo();',
                'A::foo',
                ['7:32:foo'],
            ],
            'getFunctionLocation' => [
                '<?php
                    namespace App;

                    function helper(): int {
                        return 1;
                    }

                    final class H {
                        public function a(): int {
                            return helper();
                        }
                    }

                    helper();
                    $f = \\App\\helper(...);',
                'App\\helper',
                [
                    '10:36:helper',
                    '14:21:helper',
                    '15:26:\\App\\helper',
                ],
            ],
            'getFunctionLocationByItsFullyQualifiedName' => [
                '<?php
                    namespace App;

                    function helper(): int {
                        return 1;
                    }

                    helper();',
                '\\App\\helper',
                ['8:21:helper'],
            ],
            'getMethodLocationWithUsedReturnValue' => [
                '<?php
                    final class A {
                        public function foo(): ?string {
                            return null;
                        }
                    }

                    function f(A $a): string {
                        return $a->foo() ?? "";
                    }

                    function g(A $a): void {
                        $a->foo();
                    }',
                'A::foo',
                ['9:36:foo', '13:29:foo'],
            ],
            'getInterfaceMethodLocationWithUsedReturnValue' => [
                '<?php
                    interface I {
                        public function foo(): string;
                    }

                    function f(I $i): string {
                        return $i->foo();
                    }',
                'I::foo',
                ['7:36:foo'],
            ],
            'getPropertyLocation' => [
                '<?php
                    class A {
                        /** @var int */
                        public $foo = 1;
                    }

                    echo (new A())->foo;',
                'A::$foo',
                ['7:26:(new A())->foo'],
            ],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: array<string, list<string>>}>
     * @psalm-pure
     */
    public static function providerReferenceLocationsThroughInheritingClasses(): array
    {
        return [
            'throughClassChain' => [
                '<?php
                    class A {
                        public function m(): int {
                            return 1;
                        }
                    }

                    class B extends A {}

                    class C extends B {}

                    echo (new C())->m();',
                [
                    'A::m' => ['12:37:m'],
                    'B::m' => ['12:37:m'],
                    'C::m' => ['12:37:m'],
                ],
            ],
            'throughNestedTraits' => [
                '<?php
                    trait T1 {
                        public function m(): int {
                            return 1;
                        }
                    }

                    trait T2 {
                        use T1;
                    }

                    class A {
                        use T2;
                    }

                    class B extends A {}

                    echo (new B())->m();',
                [
                    'T1::m' => ['18:37:m'],
                    'T2::m' => ['18:37:m'],
                    'A::m' => ['18:37:m'],
                    'B::m' => ['18:37:m'],
                ],
            ],
            'throughInterfaceChain' => [
                '<?php
                    interface I {
                        public function m(): int;
                    }

                    interface J extends I {}

                    interface K extends J {}

                    function f(K $k): int {
                        return $k->m();
                    }',
                [
                    'I::m' => ['11:36:m'],
                    'J::m' => ['11:36:m'],
                    'K::m' => ['11:36:m'],
                ],
            ],
            'throughReflectedParent' => [
                '<?php
                    class C extends Exception {}

                    class D extends C {}

                    echo (new D("a"))->getMessage();',
                [
                    'Exception::getMessage' => ['6:40:getMessage'],
                    'C::getMessage' => ['6:40:getMessage'],
                    'D::getMessage' => ['6:40:getMessage'],
                    'Exception::__construct' => ['6:27:new D("a")'],
                    'C::__construct' => ['6:27:new D("a")'],
                    'D::__construct' => ['6:27:new D("a")'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{
     *              0: string,
     *              1: array<string,array<string,bool>>
     * }>
     * @psalm-pure
     */
    public static function providerReferencedMethods(): array
    {
        return [
            'getClassReferences' => [
                '<?php
                    namespace Foo;

                    class A {
                        /** @psalm-mutation-free */
                        public static function bat() : void {
                        }
                    }

                    class B {
                        /** @psalm-mutation-free */
                        public function __construct() {
                            new A();
                            A::bat();
                        }

                        /** @psalm-mutation-free */
                        public function bar() : void {
                            (new C)->foo();
                        }
                    }

                    class C {
                        /** @psalm-mutation-free */
                        public function foo() : void {
                            new A();
                        }
                    }

                    class D {
                        /** @var ?string */
                        public $foo;
                        /** @psalm-pure */
                        public function __construct() {}
                    }

                    $d = new D();
                    $d->foo = "bar";

                    $a = new A();',
                [
                    'class foo\\a' => [
                        'file /var/www/somefile.php' => true,
                        'func foo\\b::__construct' => true,
                        'func foo\\c::foo' => true,
                    ],
                    'class foo\\c' => [
                        'func foo\\b::bar' => true,
                    ],
                    'class foo\\d' => [
                        'file /var/www/somefile.php' => true,
                    ],
                    'func foo\\a::bat' => [
                        'func foo\\b::__construct' => true,
                        'return foo\\a::bat' => true,
                    ],
                    'func foo\\c::foo' => [
                        'func foo\\b::bar' => true,
                    ],
                    'func foo\\d::__construct' => [
                        'return foo\\d::__construct' => true,
                    ],
                    'missing-method foo\\a::__construct' => [
                        'file /var/www/somefile.php' => true,
                        'func foo\\b::__construct' => true,
                        'func foo\\c::foo' => true,
                    ],
                    'missing-method foo\\c::__construct' => [
                        'func foo\\b::bar' => true,
                    ],
                    'property foo\\d::$foo' => [
                        'file /var/www/somefile.php' => true,
                    ],
                    'return foo\\a::bat' => [
                        'func foo\\b::__construct' => true,
                    ],
                    'return foo\\d::__construct' => [
                        'file /var/www/somefile.php' => true,
                    ],
                    'use-alias use:A:bcf863c38be34a3d' => [
                        'func foo\\b::__construct' => true,
                        'func foo\\c::foo' => true,
                    ],
                    'use-alias use:C:bcf863c38be34a3d' => [
                        'func foo\\b::bar' => true,
                    ],
                ],
            ],
            'interpolateClassCalls' => [
                '<?php
                    namespace Foo;

                    class A {
                        /** @psalm-mutation-free */
                        public function __construct() {}
                        /** @psalm-mutation-free */
                        public static function bar() : void {}
                    }

                    class B extends A { }

                    class C extends B { }

                    class D {
                        /** @psalm-mutation-free */
                        public function bat() : void {
                            $c = new C();
                            $c->bar();
                        }
                    }',
                [
                    'class foo\\a' => [
                        'class foo\\b' => true,
                        'func foo\\d::bat' => true,
                    ],
                    'class foo\\b' => [
                        'class foo\\c' => true,
                    ],
                    'class foo\\c' => [
                        'func foo\\d::bat' => true,
                    ],
                    'func foo\\a::__construct' => [
                        'return foo\\a::__construct' => true,
                    ],
                    'func foo\\a::bar' => [
                        'func foo\\d::bat' => true,
                    ],
                    'func foo\\b::__construct' => [
                        'return foo\\b::__construct' => true,
                    ],
                    'func foo\\b::bar' => [
                        'func foo\\d::bat' => true,
                    ],
                    'func foo\\c::__construct' => [
                        'return foo\\c::__construct' => true,
                    ],
                    'func foo\\c::bar' => [
                        'func foo\\d::bat' => true,
                    ],
                    'return foo\\a::__construct' => [
                        'func foo\\d::bat' => true,
                    ],
                    'return foo\\b::__construct' => [
                        'func foo\\d::bat' => true,
                    ],
                    'return foo\\c::__construct' => [
                        'func foo\\d::bat' => true,
                    ],
                    'use-alias use:C:bcf863c38be34a3d' => [
                        'func foo\\d::bat' => true,
                    ],
                ],
            ],
            'inheritedThroughTraitAliasAndInsteadof' => [
                '<?php
                    namespace Foo;

                    trait TA {
                        public function shared(): int {
                            return 1;
                        }
                    }

                    trait TB {
                        public function shared(): int {
                            return $this->onlyFromTB();
                        }
                    }

                    class Al {
                        use TA, TB {
                            TB::shared insteadof TA;
                            TA::shared as sharedA;
                        }

                        private function onlyFromTB(): int {
                            return 2;
                        }
                    }

                    class AlChild extends Al {}

                    final class Caller {
                        public function viaClass(Al $a): int {
                            return $a->sharedA();
                        }

                        public function viaChild(AlChild $a): int {
                            return $a->sharedA();
                        }
                    }',
                [
                    'class foo\\al' => [
                        'class foo\\alchild' => true,
                        'func foo\\al::shared' => true,
                        'func foo\\caller::viaclass' => true,
                    ],
                    'class foo\\alchild' => [
                        'func foo\\caller::viachild' => true,
                    ],
                    'class foo\\ta' => [
                        'class foo\\al' => true,
                        'func foo\\caller::viachild' => true,
                        'func foo\\caller::viaclass' => true,
                    ],
                    'class foo\\tb' => [
                        'class foo\\al' => true,
                    ],
                    'func foo\\al::onlyfromtb' => [
                        'return foo\\al::onlyfromtb' => true,
                    ],
                    'func foo\\al::shared' => [
                        'return foo\\al::shared' => true,
                    ],
                    'func foo\\al::shareda' => [
                        'return foo\\al::shareda' => true,
                    ],
                    'func foo\\alchild::shareda' => [
                        'return foo\\alchild::shareda' => true,
                    ],
                    'func foo\\ta::shared' => [
                        'return foo\\ta::shared' => true,
                    ],
                    'return foo\\al::onlyfromtb' => [
                        'func foo\\al::shared' => true,
                    ],
                    'return foo\\al::shared' => [
                        'func foo\\caller::viachild' => true,
                        'func foo\\caller::viaclass' => true,
                    ],
                    'return foo\\al::shareda' => [
                        'func foo\\caller::viachild' => true,
                    ],
                    'return foo\\alchild::shareda' => [
                        'func foo\\caller::viachild' => true,
                    ],
                    'return foo\\ta::shared' => [
                        'func foo\\caller::viachild' => true,
                        'func foo\\caller::viaclass' => true,
                    ],
                    'use-alias use:Al:bcf863c38be34a3d' => [
                        'func foo\\caller::viaclass' => true,
                    ],
                    'use-alias use:AlChild:bcf863c38be34a3d' => [
                        'func foo\\caller::viachild' => true,
                    ],
                ],
            ],
            'inheritedThroughNestedTraits' => [
                '<?php
                    namespace Foo;

                    trait T1 {
                        public function m(): int {
                            return 1;
                        }
                    }

                    trait T2 {
                        use T1;
                    }

                    class A {
                        use T2;
                    }

                    class B extends A {}

                    final class Caller {
                        public function f(B $b): int {
                            return $b->m();
                        }
                    }',
                [
                    'class foo\\a' => [
                        'class foo\\b' => true,
                    ],
                    'class foo\\b' => [
                        'func foo\\caller::f' => true,
                    ],
                    'class foo\\t1' => [
                        'class foo\\a' => true,
                        'func foo\\caller::f' => true,
                    ],
                    'class foo\\t2' => [
                        'class foo\\a' => true,
                    ],
                    'func foo\\a::m' => [
                        'return foo\\a::m' => true,
                    ],
                    'func foo\\b::m' => [
                        'return foo\\b::m' => true,
                    ],
                    'func foo\\t1::m' => [
                        'return foo\\t1::m' => true,
                    ],
                    'func foo\\t2::m' => [
                        'return foo\\t2::m' => true,
                    ],
                    'return foo\\a::m' => [
                        'func foo\\caller::f' => true,
                    ],
                    'return foo\\b::m' => [
                        'func foo\\caller::f' => true,
                    ],
                    'return foo\\t1::m' => [
                        'func foo\\caller::f' => true,
                    ],
                    'return foo\\t2::m' => [
                        'func foo\\caller::f' => true,
                    ],
                    'use-alias use:B:bcf863c38be34a3d' => [
                        'func foo\\caller::f' => true,
                    ],
                ],
            ],
            'inheritedThroughInterfaceChain' => [
                '<?php
                    namespace Foo;

                    interface I {
                        public function m(): int;
                    }

                    interface J extends I {}

                    interface K extends J {}

                    final class Caller {
                        public function f(K $k): int {
                            return $k->m();
                        }
                    }',
                [
                    'class foo\\i' => [
                        'class foo\\j' => true,
                        'func foo\\caller::f' => true,
                    ],
                    'class foo\\j' => [
                        'class foo\\k' => true,
                    ],
                    'class foo\\k' => [
                        'func foo\\caller::f' => true,
                    ],
                    'func foo\\i::m' => [
                        'return foo\\i::m' => true,
                    ],
                    'func foo\\j::m' => [
                        'return foo\\j::m' => true,
                    ],
                    'func foo\\k::m' => [
                        'return foo\\k::m' => true,
                    ],
                    'return foo\\i::m' => [
                        'func foo\\caller::f' => true,
                    ],
                    'return foo\\j::m' => [
                        'func foo\\caller::f' => true,
                    ],
                    'return foo\\k::m' => [
                        'func foo\\caller::f' => true,
                    ],
                    'use-alias use:K:bcf863c38be34a3d' => [
                        'func foo\\caller::f' => true,
                    ],
                ],
            ],
            'inheritedFromReflectedParent' => [
                '<?php
                    namespace Foo;

                    class C extends \\Exception {}

                    class D extends C {}

                    final class Caller {
                        public function f(): string {
                            $d = new D("a");
                            return $d->getMessage();
                        }
                    }',
                [
                    'class exception' => [
                        'class foo\\c' => true,
                        'func foo\\caller::f' => true,
                    ],
                    'class foo\\c' => [
                        'class foo\\d' => true,
                    ],
                    'class foo\\d' => [
                        'func foo\\caller::f' => true,
                    ],
                    'func exception::__construct' => [
                        'return exception::__construct' => true,
                    ],
                    'func exception::getmessage' => [
                        'return exception::getmessage' => true,
                    ],
                    'func foo\\c::__construct' => [
                        'return foo\\c::__construct' => true,
                    ],
                    'func foo\\c::getmessage' => [
                        'return foo\\c::getmessage' => true,
                    ],
                    'func foo\\d::__construct' => [
                        'return foo\\d::__construct' => true,
                    ],
                    'func foo\\d::getmessage' => [
                        'return foo\\d::getmessage' => true,
                    ],
                    'func stringable::__construct' => [
                        'return stringable::__construct' => true,
                    ],
                    'func stringable::getmessage' => [
                        'return stringable::getmessage' => true,
                    ],
                    'func throwable::__construct' => [
                        'return throwable::__construct' => true,
                    ],
                    'func throwable::getmessage' => [
                        'return throwable::getmessage' => true,
                    ],
                    'return exception::__construct' => [
                        'func foo\\caller::f' => true,
                    ],
                    'return exception::getmessage' => [
                        'func foo\\caller::f' => true,
                    ],
                    'return foo\\c::__construct' => [
                        'func foo\\caller::f' => true,
                    ],
                    'return foo\\c::getmessage' => [
                        'func foo\\caller::f' => true,
                    ],
                    'return foo\\d::__construct' => [
                        'func foo\\caller::f' => true,
                    ],
                    'return foo\\d::getmessage' => [
                        'func foo\\caller::f' => true,
                    ],
                    'return stringable::__construct' => [
                        'func foo\\caller::f' => true,
                    ],
                    'return stringable::getmessage' => [
                        'func foo\\caller::f' => true,
                    ],
                    'return throwable::__construct' => [
                        'func foo\\caller::f' => true,
                    ],
                    'return throwable::getmessage' => [
                        'func foo\\caller::f' => true,
                    ],
                    'use-alias use:D:bcf863c38be34a3d' => [
                        'func foo\\caller::f' => true,
                    ],
                ],
            ],
            'inheritedThroughAnAliasOfANestedTraitMethod' => [
                '<?php
                    namespace Foo;

                    trait TX {
                        public function shared(): int {
                            return 1;
                        }
                    }

                    trait TA {
                        use TX;
                    }

                    class A {
                        use TA {
                            shared as sharedA;
                        }
                    }

                    class B extends A {}

                    final class Caller {
                        public function f(B $b): int {
                            return $b->sharedA();
                        }
                    }',
                [
                    'class foo\\a' => [
                        'class foo\\b' => true,
                    ],
                    'class foo\\b' => [
                        'func foo\\caller::f' => true,
                    ],
                    'class foo\\ta' => [
                        'class foo\\a' => true,
                    ],
                    'class foo\\tx' => [
                        'class foo\\a' => true,
                        'func foo\\caller::f' => true,
                    ],
                    'func foo\\a::shared' => [
                        'return foo\\a::shared' => true,
                    ],
                    'func foo\\a::shareda' => [
                        'return foo\\a::shareda' => true,
                    ],
                    'func foo\\b::shareda' => [
                        'return foo\\b::shareda' => true,
                    ],
                    'func foo\\ta::shared' => [
                        'return foo\\ta::shared' => true,
                    ],
                    'return foo\\a::shared' => [
                        'func foo\\caller::f' => true,
                    ],
                    'return foo\\a::shareda' => [
                        'func foo\\caller::f' => true,
                    ],
                    'return foo\\b::shareda' => [
                        'func foo\\caller::f' => true,
                    ],
                    'return foo\\ta::shared' => [
                        'func foo\\caller::f' => true,
                    ],
                    'use-alias use:B:bcf863c38be34a3d' => [
                        'func foo\\caller::f' => true,
                    ],
                ],
            ],
            'constantRefs' => [
                '<?php
                    namespace Foo;

                    class A {
                        const C = "bar";
                    }

                    class B {
                        public function __construct() {
                            echo A::C;
                        }
                    }

                    class C {
                        public function foo() : void {
                            echo A::C;
                        }
                    }',
                [
                    'class foo\\a' => [
                        'func foo\\b::__construct' => true,
                        'func foo\\c::foo' => true,
                    ],
                    'const foo\\a::C' => [
                        'func foo\\b::__construct' => true,
                        'func foo\\c::foo' => true,
                    ],
                ],
            ],
            'staticPropertyRefs' => [
                '<?php
                    namespace Foo;

                    class A {
                        /** @var int */
                        public static $fooBar = 5;
                    }

                    class B {
                        public function __construct() {
                            echo A::$fooBar;
                        }
                    }

                    class C {
                        public function foo() : void {
                            echo A::$fooBar;
                        }
                    }',
                [
                    'class foo\\a' => [
                        'func foo\\b::__construct' => true,
                        'func foo\\c::foo' => true,
                    ],
                    'property foo\\a::$fooBar' => [
                        'func foo\\b::__construct' => true,
                        'func foo\\c::foo' => true,
                    ],
                    'use-alias use:A:bcf863c38be34a3d' => [
                        'func foo\\b::__construct' => true,
                        'func foo\\c::foo' => true,
                    ],
                ],
            ],
            'instancePropertyRefs' => [
                '<?php
                    namespace Foo;

                    class A {
                        /** @var int */
                        public $fooBar = 5;
                    }

                    class B {
                        public function __construct() {
                            echo (new A)->fooBar;
                        }
                    }

                    class C {
                        public function foo() : void {
                            echo (new A)->fooBar;
                        }
                    }',
                [
                    'class foo\\a' => [
                        'func foo\\b::__construct' => true,
                        'func foo\\c::foo' => true,
                    ],
                    'missing-method foo\\a::__construct' => [
                        'func foo\\b::__construct' => true,
                        'func foo\\c::foo' => true,
                    ],
                    'property foo\\a::$fooBar' => [
                        'func foo\\b::__construct' => true,
                        'func foo\\c::foo' => true,
                    ],
                    'use-alias use:A:bcf863c38be34a3d' => [
                        'func foo\\b::__construct' => true,
                        'func foo\\c::foo' => true,
                    ],
                ],
            ],
            'traitAbstractRefs' => [
                '<?php
                    namespace Ns;

                    abstract class A {
                        /** @psalm-mutation-free */
                        public function foo() : void {}
                    }

                    trait T {
                        /** @psalm-mutation-free */
                        public function bar(A $a) : void {
                            $a->foo();
                        }
                    }

                    class C {
                        use T;
                    }',
                [
                    'class ns\\a' => [
                        'func ns\\c::bar' => true,
                    ],
                    'class ns\\t' => [
                        'class ns\\c' => true,
                    ],
                    'func ns\\a::foo' => [
                        'func ns\\c::bar' => true,
                    ],
                    'use-alias use:A:bcf863c38be34a3d' => [
                        'func ns\\c::bar' => true,
                    ],
                ],
            ],
        ];
    }
}
