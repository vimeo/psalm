<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Context;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use UnexpectedValueException;

use function count;
use function strpos;

class FileReferenceTest extends TestCase
{
    protected ProjectAnalyzer $project_analyzer;

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

        if (!$found_references) {
            throw new UnexpectedValueException('No file references found in this file');
        }

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
     * @param array<string,array<string,bool>> $expected_method_references_to_members
     * @param array<string,array<string,bool>> $expected_file_references_to_members
     * @param array<string,array<string,bool>> $expected_method_references_to_missing_members
     * @param array<string,array<string,bool>> $expected_file_references_to_missing_members
     */
    public function testReferencedMethods(
        string $input_code,
        array $expected_method_references_to_members,
        array $expected_method_references_to_missing_members,
        array $expected_file_references_to_members,
        array $expected_file_references_to_missing_members,
    ): void {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        $context = new Context();

        $file_path = '/var/www/somefile.php';

        $this->addFile($file_path, $input_code);

        $this->analyzeFile($file_path, $context);

        $referenced_members = $this->project_analyzer->getCodebase()->file_reference_provider->getAllMethodReferencesToClassMembers();

        $this->assertSame($expected_method_references_to_members, $referenced_members);

        $referenced_missing_members = $this->project_analyzer->getCodebase()->file_reference_provider->getAllMethodReferencesToMissingClassMembers();

        $this->assertSame($expected_method_references_to_missing_members, $referenced_missing_members);

        $referenced_files = $this->project_analyzer->getCodebase()->file_reference_provider->getAllFileReferencesToClassMembers();

        $this->assertSame($expected_file_references_to_members, $referenced_files);

        $referenced_missing_files = $this->project_analyzer->getCodebase()->file_reference_provider->getAllFileReferencesToMissingClassMembers();

        $this->assertSame($expected_file_references_to_missing_members, $referenced_missing_files);
    }

    /**
     * @return array<string,array{string,string,array<int,string>}>
     */
    public function providerReferenceLocations(): array
    {
        return [
            'getClassLocation' => [
                '<?php
                    class A {}

                    new A();',
                'A',
                ['4:25:A'],
            ],
            'getMethodLocation' => [
                '<?php
                    class A {
                        public function foo(): void {}
                    }

                    (new A())->foo();',
                'A::foo',
                ['6:32:foo'],
            ],
        ];
    }

    /**
     * @return array<string, array{
     *              0: string,
     *              1: array<string,array<string,bool>>,
     *              2: array<string,array<string,bool>>,
     *              3: array<string,array<string,bool>>,
     *              4: array<string,array<string,bool>>
     * }>
     */
    public function providerReferencedMethods(): array
    {
        return [
            'getClassReferences' => [
                '<?php
                    namespace Foo;

                    class A {
                        public static function bat() : void {
                        }
                    }

                    class B {
                        public function __construct() {
                            new A();
                            A::bat();
                        }

                        public function bar() : void {
                            (new C)->foo();
                        }
                    }

                    class C {
                        public function foo() : void {
                            new A();
                        }
                    }

                    class D {
                        /** @var ?string */
                        public $foo;
                        public function __construct() {}
                    }

                    $d = new D();
                    $d->foo = "bar";

                    $a = new A();',
                [
                    'use:A:d7863b8594fe57f85cb8183fe55a6c15' => [
                        'foo\b::__construct' => true,
                        'foo\c::foo' => true,
                    ],
                    'foo\a::bat' => [
                        'foo\b::__construct' => true,
                    ],
                    'use:C:d7863b8594fe57f85cb8183fe55a6c15' => [
                        'foo\b::bar' => true,
                    ],
                    'foo\c::foo' => [
                        'foo\b::bar' => true,
                    ],
                ],
                [
                    'foo\a::__construct' => [
                        'foo\b::__construct' => true,
                        'foo\c::foo' => true,
                    ],
                    'foo\c::__construct' => [
                        'foo\b::bar' => true,
                    ],
                ],
                [
                    'foo\d::__construct' => [
                        '/var/www/somefile.php' => true,
                    ],
                    'foo\d::$foo' => [
                        '/var/www/somefile.php' => true,
                    ],
                ],
                [
                    'foo\a::__construct' => [
                        '/var/www/somefile.php' => true,
                    ],
                ],
            ],
            'interpolateClassCalls' => [
                '<?php
                    namespace Foo;

                    class A {
                        public function __construct() {}
                        public static function bar() : void {}
                    }

                    class B extends A { }

                    class C extends B { }

                    class D {
                        public function bat() : void {
                            $c = new C();
                            $c->bar();
                        }
                    }',
                [
                    'use:C:d7863b8594fe57f85cb8183fe55a6c15' => [
                        'foo\d::bat' => true,
                    ],
                    'foo\b::__construct' => [
                        'foo\d::bat' => true,
                    ],
                    'foo\a::__construct' => [
                        'foo\d::bat' => true,
                    ],
                    'foo\c::__construct' => [
                        'foo\d::bat' => true,
                    ],
                    'foo\b::bar' => [
                        'foo\d::bat' => true,
                    ],
                    'foo\a::bar' => [
                        'foo\d::bat' => true,
                    ],
                    'foo\c::bar' => [
                        'foo\d::bat' => true,
                    ],
                ],
                [],
                [],
                [],
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
                    'foo\a::C' => [
                        'foo\b::__construct' => true,
                        'foo\c::foo' => true,
                    ],
                ],
                [],
                [],
                [],
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
                    'use:A:d7863b8594fe57f85cb8183fe55a6c15' => [
                        'foo\b::__construct' => true,
                        'foo\c::foo' => true,
                    ],
                    'foo\a::$fooBar' => [
                        'foo\b::__construct' => true,
                        'foo\c::foo' => true,
                    ],
                ],
                [],
                [],
                [],
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
                    'use:A:d7863b8594fe57f85cb8183fe55a6c15' => [
                        'foo\b::__construct' => true,
                        'foo\c::foo' => true,
                    ],
                    'foo\a::$fooBar' => [
                        'foo\b::__construct' => true,
                        'foo\c::foo' => true,
                    ],

                ],
                [
                    'foo\a::__construct' => [
                        'foo\b::__construct' => true,
                        'foo\c::foo' => true,
                    ],
                ],
                [],
                [],
            ],
            'traitAbstractRefs' => [
                '<?php
                    namespace Ns;

                    abstract class A {
                        public function foo() : void {}
                    }

                    trait T {
                        public function bar(A $a) : void {
                            $a->foo();
                        }
                    }

                    class C {
                        use T;
                    }',
                [
                    'use:A:d7863b8594fe57f85cb8183fe55a6c15' => [
                        'ns\c::bar' => true,
                    ],
                    'ns\a::foo' => [
                        'ns\c::bar' => true,
                    ],
                ],
                [],
                [],
                [],
            ],
        ];
    }
}
