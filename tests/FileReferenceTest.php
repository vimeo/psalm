<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Context;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Codebase\CodeUseGraph;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;

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
        RuntimeCaches::clearAll();

        $this->file_provider = new FakeFileProvider();

        $this->project_analyzer = new ProjectAnalyzer(
            new TestConfig(),
            new Providers(
                $this->file_provider,
                new FakeParserCacheProvider(),
            ),
        );

        $this->project_analyzer->getCodebase()->collectLocations();
        $this->project_analyzer->setPhpVersion('7.3', 'tests');
    }

    /**
     * @dataProvider providerReferenceLocations
     * @param array<int, string> $expected_locations
     */
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

        $this->assertSame(count($found_references), count($expected_locations));

        foreach ($found_references as &$loc) {
            $loc = $loc->getLineNumber() . ':' . $loc->getColumn()
                    . ':' . $loc->getSelectedText();
        } unset($loc);

        $this->assertEquals($expected_locations, $found_references);
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
     * @dataProvider providerReferencedMethods
     * @param array<string,array<string,bool>> $expected_references
     */
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
         * @psalm-pure
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
     * @return array<string,array{string,string,array<int,string>}>
     * @psalm-pure
     */
    public function providerReferenceLocations(): array
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
     * @return array<string, array{
     *              0: string,
     *              1: array<string,array<string,bool>>
     * }>
     * @psalm-pure
     */
    public function providerReferencedMethods(): array
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
                    'use-alias use:A:d7863b8594fe57f85cb8183fe55a6c15' => [
                        'func foo\\b::__construct' => true,
                        'func foo\\c::foo' => true,
                    ],
                    'use-alias use:C:d7863b8594fe57f85cb8183fe55a6c15' => [
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
                    'use-alias use:C:d7863b8594fe57f85cb8183fe55a6c15' => [
                        'func foo\\d::bat' => true,
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
                    'use-alias use:A:d7863b8594fe57f85cb8183fe55a6c15' => [
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
                    'use-alias use:A:d7863b8594fe57f85cb8183fe55a6c15' => [
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
                    'use-alias use:A:d7863b8594fe57f85cb8183fe55a6c15' => [
                        'func ns\\c::bar' => true,
                    ],
                ],
            ],
        ];
    }
}
