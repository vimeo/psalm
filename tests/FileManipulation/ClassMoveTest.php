<?php
namespace Psalm\Tests\FileManipulation;

use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Tests\Internal\Provider;
use Psalm\Tests\TestConfig;

class ClassMoveTest extends \Psalm\Tests\TestCase
{
    /** @var \Psalm\Internal\Analyzer\ProjectAnalyzer */
    protected $project_analyzer;

    public function setUp() : void
    {
        FileAnalyzer::clearCache();
        \Psalm\Internal\FileManipulation\FunctionDocblockManipulator::clearCache();

        $this->file_provider = new Provider\FakeFileProvider();
    }

    /**
     * @dataProvider providerValidCodeParse
     *
     * @param string $input_code
     * @param string $output_code
     * @param array<string, string> $constants_to_move
     * @param array<string, string> $call_transforms
     *
     * @return void
     */
    public function testValidCode(
        string $input_code,
        string $output_code,
        array $constants_to_move
    ) {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        $config = new TestConfig();

        $this->project_analyzer = new \Psalm\Internal\Analyzer\ProjectAnalyzer(
            $config,
            new \Psalm\Internal\Provider\Providers(
                $this->file_provider,
                new Provider\FakeParserCacheProvider()
            )
        );

        $context = new Context();

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            $input_code
        );

        $codebase = $this->project_analyzer->getCodebase();

        $this->project_analyzer->refactorCodeAfterCompletion($constants_to_move);

        $this->analyzeFile($file_path, $context);

        $this->project_analyzer->prepareMigration();

        $codebase->analyzer->updateFile($file_path, false);

        $this->project_analyzer->migrateCode();

        $this->assertSame($output_code, $codebase->getFileContents($file_path));
    }

    /**
     * @return array<string,array{string,string,array<string, string>}>
     */
    public function providerValidCodeParse()
    {
        return [
            'renameEmptyClass' => [
                '<?php
                    namespace Ns;

                    class A {}

                    class C extends A {
                        /**
                         * @var A
                         */
                        public $one;
                    }

                    /**
                     * @param A $a
                     * @return A
                     */
                    function foo(A $a) : A {
                        return $a;
                    }

                    /** @var A */
                    $i = new A();

                    if ($i instanceof A) {}',
                '<?php
                    namespace Ns;

                    class B {}

                    class C extends B {
                        /**
                         * @var B
                         */
                        public $one;
                    }

                    /**
                     * @param B $a
                     * @return B
                     */
                    function foo(B $a) : B {
                        return $a;
                    }

                    /** @var B */
                    $i = new B();

                    if ($i instanceof B) {}',
                [
                    'Ns\A' => 'Ns\B',
                ]
            ],
            'renameClassWithInstanceMethod' => [
                '<?php
                    namespace Ns;

                    class A {
                        /**
                         * @param self $one
                         * @param A $two
                         */
                        public function foo(self $one, A $two) : void {}
                    }

                    function foo(A $a) : A {
                        return $a->foo($a, $a);
                    }',
                '<?php
                    namespace Ns;

                    class B {
                        /**
                         * @param self $one
                         * @param self $two
                         */
                        public function foo(self $one, self $two) : void {}
                    }

                    function foo(B $a) : B {
                        return $a->foo($a, $a);
                    }',
                [
                    'Ns\A' => 'Ns\B',
                ]
            ],
            'renameClassWithStaticMethod' => [
                '<?php
                    namespace Ns;

                    class A {
                        /**
                         * @param self $one
                         * @param A $two
                         */
                        public static function foo(self $one, A $two) : void {
                            A::foo($one, $two);
                        }
                    }

                    function foo() {
                        A::foo(new A(), A::foo());
                    }',
                '<?php
                    namespace Ns;

                    class B {
                        /**
                         * @param self $one
                         * @param self $two
                         */
                        public static function foo(self $one, self $two) : void {
                            self::foo($one, $two);
                        }
                    }

                    function foo() {
                        B::foo(new B(), B::foo());
                    }',
                [
                    'Ns\A' => 'Ns\B',
                ]
            ],
            'renameClassWithInstanceProperty' => [
                '<?php
                    namespace Ns;

                    class A {
                        /**
                         * @var A
                         */
                        public $one;

                        /**
                         * @var self
                         */
                        public $two;
                    }',
                '<?php
                    namespace Ns;

                    class B {
                        /**
                         * @var self
                         */
                        public $one;

                        /**
                         * @var self
                         */
                        public $two;
                    }',
                [
                    'Ns\A' => 'Ns\B',
                ]
            ],
            'renameClassWithStaticProperty' => [
                '<?php
                    namespace Ns;

                    class A {
                        /**
                         * @var string
                         */
                        public static $one = "one";
                    }

                    echo A::$one;
                    A::$one = "two";',
                '<?php
                    namespace Ns;

                    class B {
                        /**
                         * @var string
                         */
                        public static $one = "one";
                    }

                    echo B::$one;
                    B::$one = "two";',
                [
                    'Ns\A' => 'Ns\B',
                ]
            ],
            'moveClassIntoNamespace' => [
                '<?php
                    use Exception;

                    class A {
                        /** @var ?Exception */
                        public $x;

                        /**
                         * @param ArrayObject<int, A> $a
                         */
                        public function foo(ArrayObject $a) : Exception {
                            foreach ($a as $b) {
                                $b->bar();
                            }

                            try {
                                // something
                            } catch (InvalidArgumentException $e) {

                            }

                            echo \A::class;

                            ArrayObject::foo();

                            return new Exception("bad");
                        }

                        public function bar() : void {}
                    }',
                '<?php
                    namespace Foo\Bar\Baz;

                    use Exception;

                    class B {
                        /** @var null|Exception */
                        public $x;

                        /**
                         * @param \ArrayObject<int, self> $a
                         */
                        public function foo(\ArrayObject $a) : Exception {
                            foreach ($a as $b) {
                                $b->bar();
                            }

                            try {
                                // something
                            } catch (\InvalidArgumentException $e) {

                            }

                            echo self::class;

                            \ArrayObject::foo();

                            return new Exception("bad");
                        }

                        public function bar() : void {}
                    }',
                [
                    'A' => 'Foo\Bar\Baz\B',
                ]
            ],
            'moveClassDeeperIntoNamespace' => [
                '<?php
                    namespace Foo;

                    use Exception;
                    use ArrayObject;

                    class A {
                        /** @var ?Exception */
                        public $x;

                        /**
                         * @param ArrayObject<int, A> $a
                         */
                        public function foo(ArrayObject $a) : Exception {
                            foreach ($a as $b) {
                                $b->bar();
                            }

                            return new Exception("bad");
                        }

                        public function bar() : void {}
                    }',
                '<?php
                    namespace Foo\Bar\Baz;

                    use Exception;
                    use ArrayObject;

                    class B {
                        /** @var null|Exception */
                        public $x;

                        /**
                         * @param ArrayObject<int, self> $a
                         */
                        public function foo(ArrayObject $a) : Exception {
                            foreach ($a as $b) {
                                $b->bar();
                            }

                            return new Exception("bad");
                        }

                        public function bar() : void {}
                    }',
                [
                    'Foo\A' => 'Foo\Bar\Baz\B',
                ]
            ],
        ];
    }
}
