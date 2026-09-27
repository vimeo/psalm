<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Context;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Codebase\CodeUseGraph;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\Interner;
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

        self::assertNotSame([], $codebase->findReferencesToClassLike(Interner::intern('A')));
        $codebase->code_use_graph->removeReferencesFrom(CodeUseGraph::functionLikeNode(new MethodIdentifier(Interner::intern('B'), Interner::intern('useA'))));
        self::assertSame([], $codebase->findReferencesToClassLike(Interner::intern('A')));
    }

    public function testRemovedSourceNodeCanBeReassignedToAnotherFile(): void
    {
        $graph = new CodeUseGraph();
        $source_node = CodeUseGraph::functionLikeNode(new MethodIdentifier(Interner::intern('a'), Interner::intern('foo')));
        $target_node = CodeUseGraph::classNode(Interner::intern('b'));
        $context = new Context();
        $context->calling_method_id = new MethodIdentifier(Interner::intern('a'), Interner::intern('foo'));

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
        $used_source = CodeUseGraph::functionLikeNode(new MethodIdentifier(Interner::intern('a'), Interner::intern('used')));
        $dead_source = CodeUseGraph::functionLikeNode(new MethodIdentifier(Interner::intern('a'), Interner::intern('dead')));
        $target = CodeUseGraph::functionLikeNode(new MethodIdentifier(Interner::intern('a'), Interner::intern('target')));

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
                    'class Foo\\A' => [
                        'file /var/www/somefile.php' => true,
                        'func Foo\\B::__construct' => true,
                        'func Foo\\C::foo' => true,
                    ],
                    'class Foo\\C' => [
                        'func Foo\\B::bar' => true,
                    ],
                    'class Foo\\D' => [
                        'file /var/www/somefile.php' => true,
                    ],
                    'func Foo\\A::bat' => [
                        'func Foo\\B::__construct' => true,
                        'return Foo\\A::bat' => true,
                    ],
                    'func Foo\\C::foo' => [
                        'func Foo\\B::bar' => true,
                    ],
                    'func Foo\\D::__construct' => [
                        'return Foo\\D::__construct' => true,
                    ],
                    'missing-method Foo\\A::__construct' => [
                        'file /var/www/somefile.php' => true,
                        'func Foo\\B::__construct' => true,
                        'func Foo\\C::foo' => true,
                    ],
                    'missing-method Foo\\C::__construct' => [
                        'func Foo\\B::bar' => true,
                    ],
                    'property Foo\\D::$foo' => [
                        'file /var/www/somefile.php' => true,
                    ],
                    'return Foo\\A::bat' => [
                        'func Foo\\B::__construct' => true,
                    ],
                    'return Foo\\D::__construct' => [
                        'file /var/www/somefile.php' => true,
                    ],
                    'use-alias use:A:d7863b8594fe57f85cb8183fe55a6c15' => [
                        'func Foo\\B::__construct' => true,
                        'func Foo\\C::foo' => true,
                    ],
                    'use-alias use:C:d7863b8594fe57f85cb8183fe55a6c15' => [
                        'func Foo\\B::bar' => true,
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
                    'class Foo\\A' => [
                        'class Foo\\B' => true,
                        'func Foo\\D::bat' => true,
                    ],
                    'class Foo\\B' => [
                        'class Foo\\C' => true,
                    ],
                    'class Foo\\C' => [
                        'func Foo\\D::bat' => true,
                    ],
                    'func Foo\\A::__construct' => [
                        'return Foo\\A::__construct' => true,
                    ],
                    'func Foo\\A::bar' => [
                        'func Foo\\D::bat' => true,
                    ],
                    'func Foo\\B::__construct' => [
                        'return Foo\\B::__construct' => true,
                    ],
                    'func Foo\\B::bar' => [
                        'func Foo\\D::bat' => true,
                    ],
                    'func Foo\\C::__construct' => [
                        'return Foo\\C::__construct' => true,
                    ],
                    'func Foo\\C::bar' => [
                        'func Foo\\D::bat' => true,
                    ],
                    'return Foo\\A::__construct' => [
                        'func Foo\\D::bat' => true,
                    ],
                    'return Foo\\B::__construct' => [
                        'func Foo\\D::bat' => true,
                    ],
                    'return Foo\\C::__construct' => [
                        'func Foo\\D::bat' => true,
                    ],
                    'use-alias use:C:d7863b8594fe57f85cb8183fe55a6c15' => [
                        'func Foo\\D::bat' => true,
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
                    'class Foo\\A' => [
                        'func Foo\\B::__construct' => true,
                        'func Foo\\C::foo' => true,
                    ],
                    'const Foo\\A::C' => [
                        'func Foo\\B::__construct' => true,
                        'func Foo\\C::foo' => true,
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
                    'class Foo\\A' => [
                        'func Foo\\B::__construct' => true,
                        'func Foo\\C::foo' => true,
                    ],
                    'property Foo\\A::$fooBar' => [
                        'func Foo\\B::__construct' => true,
                        'func Foo\\C::foo' => true,
                    ],
                    'use-alias use:A:d7863b8594fe57f85cb8183fe55a6c15' => [
                        'func Foo\\B::__construct' => true,
                        'func Foo\\C::foo' => true,
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
                    'class Foo\\A' => [
                        'func Foo\\B::__construct' => true,
                        'func Foo\\C::foo' => true,
                    ],
                    'missing-method Foo\\A::__construct' => [
                        'func Foo\\B::__construct' => true,
                        'func Foo\\C::foo' => true,
                    ],
                    'property Foo\\A::$fooBar' => [
                        'func Foo\\B::__construct' => true,
                        'func Foo\\C::foo' => true,
                    ],
                    'use-alias use:A:d7863b8594fe57f85cb8183fe55a6c15' => [
                        'func Foo\\B::__construct' => true,
                        'func Foo\\C::foo' => true,
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
                    'class Ns\\A' => [
                        'func Ns\\C::bar' => true,
                    ],
                    'class Ns\\T' => [
                        'class Ns\\C' => true,
                    ],
                    'func Ns\\A::foo' => [
                        'func Ns\\C::bar' => true,
                    ],
                    'use-alias use:A:d7863b8594fe57f85cb8183fe55a6c15' => [
                        'func Ns\\C::bar' => true,
                    ],
                ],
            ],
        ];
    }
}
