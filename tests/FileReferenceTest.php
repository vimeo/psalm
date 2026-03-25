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
use UnexpectedValueException;

use function count;
use function str_contains;
use function str_starts_with;
use function strpos;
use function substr;

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
        //$this->project_analyzer->getCodebase()->code_use_graph->collect_locations = false;
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

        $this->assertSame(count($found_references), count($expected_locations));

        foreach ($expected_locations as $i => $expected_location) {
            $actual_location = $found_references[$i];

            $this->assertSame(
                $expected_location,
                $actual_location->getLineNumber() . ':' . $actual_location->getColumn()
                    . ':' . $actual_location->getSelectedText(),
            );
        }
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
        assert($graph !== null);

        $ksort_recursive = function (array &$arr) use (&$ksort_recursive): void {
            ksort($arr);
            foreach ($arr as &$value) {
                if (is_array($value)) {
                    $ksort_recursive($value);
                }
            }
        };

        $all = $graph->getAllIncomingUseSources();
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
                [],
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
                    'class foo\a' => [
                        '/var/www/somefile.php' => true,
                        'func foo\b::__construct' => true,
                        'func foo\c::foo' => true,
                        'generic-use' => true,
                    ],
                    'class foo\c' => [
                        '/var/www/somefile.php' => true,
                        'func foo\b::bar' => true,
                    ],
                    'class foo\d' => [
                        '/var/www/somefile.php' => true,
                        'generic-use' => true,
                    ],
                    'func foo\a::bat' => [
                        '/var/www/somefile.php' => true,
                        'func foo\b::__construct' => true,
                    ],
                    'func foo\c::foo' => [
                        '/var/www/somefile.php' => true,
                        'func foo\b::bar' => true,
                    ],
                    'func foo\d::__construct' => [
                        '/var/www/somefile.php' => true,
                    ],
                    'missing-method foo\a::__callstatic' => [
                        'generic-use' => true,
                    ],
                    'missing-method foo\a::__construct' => [
                        '/var/www/somefile.php' => true,
                        'func foo\b::__construct' => true,
                        'func foo\c::foo' => true,
                    ],
                    'missing-method foo\c::__construct' => [
                        '/var/www/somefile.php' => true,
                        'func foo\b::bar' => true,
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
                    'class foo\a' => [
                        '/var/www/somefile.php' => true,
                        'func foo\d::bat' => true,
                        'generic-use' => true,
                    ],
                    'class foo\b' => [
                        '/var/www/somefile.php' => true,
                        'generic-use' => true,
                    ],
                    'class foo\c' => [
                        '/var/www/somefile.php' => true,
                        'func foo\d::bat' => true,
                    ],
                    'func foo\b::__construct' => [
                        '/var/www/somefile.php' => true,
                        'func foo\d::bat' => true,
                    ],
                    'func foo\a::__construct' => [
                        '/var/www/somefile.php' => true,
                        'func foo\d::bat' => true,
                    ],
                    'func foo\c::__construct' => [
                        '/var/www/somefile.php' => true,
                        'func foo\d::bat' => true,
                    ],
                    'func foo\b::bar' => [
                        '/var/www/somefile.php' => true,
                        'func foo\d::bat' => true,
                    ],
                    'func foo\a::bar' => [
                        '/var/www/somefile.php' => true,
                        'func foo\d::bat' => true,
                    ],
                    'func foo\c::bar' => [
                        '/var/www/somefile.php' => true,
                        'func foo\d::bat' => true,
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
                    'class foo\a' => [
                        'func foo\b::__construct' => true,
                        '/var/www/somefile.php' => true,
                        'func foo\c::foo' => true,
                    ],
                    'const foo\a::c' => [
                        'func foo\b::__construct' => true,
                        '/var/www/somefile.php' => true,
                        'func foo\c::foo' => true,
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
                    'class foo\a' => [
                        'func foo\b::__construct' => true,
                        '/var/www/somefile.php' => true,
                        'func foo\c::foo' => true,
                    ],
                    'property foo\a::fooBar' => [
                        'func foo\b::__construct' => true,
                        '/var/www/somefile.php' => true,
                        'func foo\c::foo' => true,
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
                    'class foo\a' => [
                        'func foo\b::__construct' => true,
                        '/var/www/somefile.php' => true,
                        'func foo\c::foo' => true,
                    ],
                    'missing-method foo\a::__construct' => [
                        'func foo\b::__construct' => true,
                        '/var/www/somefile.php' => true,
                        'func foo\c::foo' => true,
                    ],
                    'property foo\a::fooBar' => [
                        'func foo\b::__construct' => true,
                        '/var/www/somefile.php' => true,
                        'func foo\c::foo' => true,
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
                    'class ns\t' => [
                        'class ns\c' => true,
                        '/var/www/somefile.php' => true,
                    ],
                    'class ns\a' => [
                        '/var/www/somefile.php' => true,
                        'generic-use' => true,
                        'func ns\c::bar' => true,
                    ],
                    'func ns\a::foo' => [
                        '/var/www/somefile.php' => true,
                        'func ns\c::bar' => true,
                    ],
                ],
            ],
        ];
    }
}
