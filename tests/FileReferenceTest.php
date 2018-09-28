<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;
use Psalm\Context;

class FileReferenceTest extends TestCase
{
    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;

    /**
     * @return void
     */
    public function setUp()
    {
        FileChecker::clearCache();
        \Psalm\FileManipulation\FunctionDocblockManipulator::clearCache();

        $this->file_provider = new Provider\FakeFileProvider();

        $this->project_checker = new \Psalm\Checker\ProjectChecker(
            new TestConfig(),
            new \Psalm\Provider\Providers(
                $this->file_provider,
                new Provider\FakeParserCacheProvider()
            )
        );

        $this->project_checker->getCodebase()->collectReferences();
    }

    /**
     * @dataProvider providerReferenceLocations
     *
     * @param string $input_code
     * @param string $symbol
     * @param array<int, string> $expected_locations
     *
     * @return void
     */
    public function testReferenceLocations($input_code, $symbol, $expected_locations)
    {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'PHP7-') !== false) {
            if (version_compare(PHP_VERSION, '7.0.0dev', '<')) {
                $this->markTestSkipped('Test case requires PHP 7.');

                return;
            }
        } elseif (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        $context = new Context();

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile($file_path, $input_code);

        $this->analyzeFile($file_path, $context);

        $found_references = $this->project_checker->getCodebase()->findReferencesToSymbol($symbol);

        if (!isset($found_references[$file_path])) {
            throw new \UnexpectedValueException('No file references found in this file');
        }

        $file_references = $found_references[$file_path];

        $this->assertSame(count($file_references), count($expected_locations));

        foreach ($expected_locations as $i => $expected_location) {
            $actual_location = $file_references[$i];

            $this->assertSame(
                $expected_location,
                $actual_location->getLineNumber() . ':' . $actual_location->getColumn()
                    . ':' . $actual_location->getSelectedText()
            );
        }
    }

    /**
     * @dataProvider providerReferencedMethods
     *
     * @param string $input_code
     * @param string $symbol
     * @param array<int, string> $expected_locations
     *
     * @return void
     */
    public function testReferencedMethods($input_code, array $expected_referenced_methods)
    {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'PHP7-') !== false) {
            if (version_compare(PHP_VERSION, '7.0.0dev', '<')) {
                $this->markTestSkipped('Test case requires PHP 7.');

                return;
            }
        } elseif (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        $context = new Context();

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile($file_path, $input_code);

        $this->analyzeFile($file_path, $context);

        $referenced_methods = $this->project_checker->file_reference_provider->getClassMethodReferences();

        $this->assertSame($expected_referenced_methods, $referenced_methods);
    }

    /**
     * @return array
     */
    public function providerReferenceLocations()
    {
        return [
            'getClassLocation' => [
                '<?php
                    class A {}

                    new A();',
                'A',
                ['4:25:A']
            ],
            'getMethodLocation' => [
                '<?php
                    class A {
                        public function foo(): void {}
                    }

                    (new A())->foo();',
                'A::foo',
                ['6:21:(new A())->foo()'],
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerReferencedMethods()
    {
        return [
            'getClassLocation' => [
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
                    }',
                [
                    'foo\a::__construct' => [
                        'foo\b::__construct' => true,
                        'foo\c::foo' => true,
                    ],
                    'foo\a::bat' => [
                        'foo\b::__construct' => true,
                    ],
                    'foo\c::__construct' => [
                        'foo\b::bar' => true,
                    ],
                    'foo\c::foo' => [
                        'foo\b::bar' => true,
                    ]
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
                    'foo\a::$fooBar' => [
                        'foo\b::__construct' => true,
                        'foo\c::foo' => true,
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
                    'foo\a::__construct' => [
                        'foo\b::__construct' => true,
                        'foo\c::foo' => true,
                    ],
                    'foo\a::$fooBar' => [
                        'foo\b::__construct' => true,
                        'foo\c::foo' => true,
                    ],
                ],
            ],
        ];
    }
}
