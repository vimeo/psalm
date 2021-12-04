<?php
namespace Psalm\Tests\FileManipulation;

use Psalm\Context;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\Tests\Internal\Provider;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;

use function strpos;

class MethodMoveTest extends TestCase
{
    /** @var ProjectAnalyzer */
    protected $project_analyzer;

    public function setUp() : void
    {
        RuntimeCaches::clearAll();

        $this->file_provider = new FakeFileProvider();
    }

    /**
     * @dataProvider providerValidCodeParse
     *
     * @param array<string, string> $methods_to_move
     */
    public function testValidCode(
        string $input_code,
        string $output_code,
        array $methods_to_move
    ): void {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        $config = new TestConfig();

        $this->project_analyzer = new ProjectAnalyzer(
            $config,
            new Providers(
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

        $this->project_analyzer->refactorCodeAfterCompletion($methods_to_move);

        $this->analyzeFile($file_path, $context);

        $this->project_analyzer->prepareMigration();

        $codebase->analyzer->updateFile($file_path, false);

        $this->project_analyzer->migrateCode();

        $this->assertSame($output_code, $codebase->getFileContents($file_path));
    }

    /**
     * @return array<string,array{string,string,array<string, string>}>
     */
    public function providerValidCodeParse(): array
    {
        return [
            'moveSimpleStaticMethodWithForeachIterator' => [
                '<?php
                    namespace Ns;

                    use ArrayObject;

                    A::Foo();

                    class A {
                        /**
                         * @return ArrayObject<int, int>
                         */
                        public static function Foo() {
                            return new ArrayObject([1, 2, 3]);
                        }
                    }

                    class B {
                        public static function bar() : void {
                            A::Foo();
                            foreach (A::Foo() as $f) {}
                        }
                    }',
                '<?php
                    namespace Ns;

                    use ArrayObject;

                    B::Fe();

                    class A {

                    }

                    class B {
                        public static function bar() : void {
                            B::Fe();
                            foreach (B::Fe() as $f) {}
                        }

                        /**
                         * @return ArrayObject<int, int>
                         */
                        public static function Fe() {
                            return new ArrayObject([1, 2, 3]);
                        }
                    }',
                [
                    'Ns\A::Foo' => 'Ns\B::Fe',
                ],
            ],
            'moveSimpleStaticMethodWithConstant' => [
                '<?php
                    namespace Ns;

                    class A {
                        const C = 5;

                        public static function Foo() : void {
                            echo self::C;
                        }
                    }

                    class B {

                    }',
                '<?php
                    namespace Ns;

                    class A {
                        const C = 5;


                    }

                    class B {


                        public static function Fe() : void {
                            echo A::C;
                        }
                    }',
                [
                    'Ns\A::Foo' => 'Ns\B::Fe',
                ],
            ],
            'moveSimpleStaticMethodWithProperty' => [
                '<?php
                    namespace Ns;

                    class A {
                        /** @var int */
                        public static $baz;

                        public static function Foo() : void {
                            echo self::$baz;
                            echo A::$baz . " ";
                            self::$baz = 12;
                            A::$baz = 14;
                        }
                    }

                    class B {

                    }',
                '<?php
                    namespace Ns;

                    class A {
                        /** @var int */
                        public static $baz;


                    }

                    class B {


                        public static function Fe() : void {
                            echo A::$baz;
                            echo A::$baz . " ";
                            A::$baz = 12;
                            A::$baz = 14;
                        }
                    }',
                [
                    'Ns\A::Foo' => 'Ns\B::Fe',
                ],
            ],
            'moveStaticMethodIntoNamespaceWithExistingUse' => [
                '<?php
                    namespace {
                        class A {
                            public static function Foo() : void {}
                        }
                    }

                    namespace Ns {
                        use A;

                        class C {
                            public static function Bar() : void {
                                A::Foo();
                            }
                        }
                    }

                    namespace Ns\A {
                        class B {

                        }
                    }',
                '<?php
                    namespace {
                        class A {

                        }
                    }

                    namespace Ns {
                        use A;

                        class C {
                            public static function Bar() : void {
                                \Ns\A\B::Fedcba();
                            }
                        }
                    }

                    namespace Ns\A {
                        class B {


                            public static function Fedcba() : void {}
                        }
                    }',
                [
                    'A::Foo' => 'Ns\A\B::Fedcba',
                ],
            ],
            'moveEmptyStaticMethodOnly' => [
                '<?php
                    namespace Ns;

                    class A {
                        /**
                         * @return void
                         */
                        public static function Foo() : void {}
                    }

                    class B {
                    }',
                '<?php
                    namespace Ns;

                    class A {

                    }

                    class B {

                        /**
                         * @return void
                         */
                        public static function Fedcba() : void {}
                    }',
                [
                    'Ns\A::Foo' => 'Ns\B::Fedcba',
                ],
            ],
            'moveStaticMethodOnly' => [
                '<?php
                    namespace Ns;

                    class A {
                        const C = 5;

                        /**
                         * @param self $a1
                         * Some description
                         * @param ?self
                         *        $a2
                         * @param array<
                         *     int,
                         *     self
                         * > $a3
                         * @return self
                         */
                        public static function Foo(self $a1, ?self $a2, array $a3) : self {
                            echo self::C;
                            echo A::C;
                            self::Bar();
                            A::Bar();
                            echo \Ns\B::D;
                            new A();
                            /** @var self */
                            $a = new self();
                            new B();

                            return $a;
                        }

                        public static function Bar() : void {}
                    }

                    class B {
                        const D = 5;
                    }',
                '<?php
                    namespace Ns;

                    class A {
                        const C = 5;



                        public static function Bar() : void {}
                    }

                    class B {
                        const D = 5;

                        /**
                         * @param A $a1
                         * Some description
                         * @param A|null
                         *        $a2
                         * @param array<int, A> $a3
                         * @return A
                         */
                        public static function Fedbca(A $a1, ?A $a2, array $a3) : A {
                            echo A::C;
                            echo A::C;
                            A::Bar();
                            A::Bar();
                            echo B::D;
                            new A();
                            /** @var A */
                            $a = new A();
                            new B();

                            return $a;
                        }
                    }',
                [
                    'Ns\A::Foo' => 'Ns\B::Fedbca',
                ],
            ],
            'moveTwoStaticMethods' => [
                '<?php
                    namespace Ns;

                    class A {
                        const C = 5;

                        /**
                         * @param self $a1
                         * Some description
                         * @param ?self
                         *        $a2
                         * @param array<
                         *     int,
                         *     self
                         * > $a3
                         * @return self
                         */
                        public static function Foo(self $a1, ?self $a2, array $a3) : self {
                            echo self::C;
                            echo A::C;
                            self::Bar();
                            A::Bar();
                            echo \Ns\B::D;
                            new A();
                            /** @var self */
                            $a = new self();
                            new B();

                            return $a;
                        }

                        public static function Bar() : void {}
                    }

                    class B {
                        const D = 5;
                    }',
                '<?php
                    namespace Ns;

                    class A {
                        const C = 5;




                    }

                    class B {
                        const D = 5;

                        /**
                         * @param A $a1
                         * Some description
                         * @param A|null
                         *        $a2
                         * @param array<int, A> $a3
                         * @return A
                         */
                        public static function Fedbca(A $a1, ?A $a2, array $a3) : A {
                            echo A::C;
                            echo A::C;
                            self::Blacksheep();
                            B::Blacksheep();
                            echo B::D;
                            new A();
                            /** @var A */
                            $a = new A();
                            new B();

                            return $a;
                        }

                        public static function Blacksheep() : void {}
                    }',
                [
                    'Ns\A::Foo' => 'Ns\B::Fedbca',
                    'Ns\A::Bar' => 'Ns\B::Blacksheep',
                ],
            ],
            'moveInstanceMethodIntoSubclassOnly' => [
                '<?php
                    namespace Ns;

                    class A {
                        const C = 5;

                        /**
                         * @param self $a1
                         * Some description
                         * @param ?self
                         *        $a2
                         * @param array<
                         *     int,
                         *     self
                         * > $a3
                         * @return self
                         */
                        public function Foo(self $a1, ?self $a2, array $a3) : self {
                            echo self::C;
                            echo A::C;
                            $this->Bar();
                            A::Bar();
                            echo \Ns\AChild::D;
                            new A();
                            /** @var self */
                            $a = new self();
                            new AChild();

                            return $a;
                        }

                        public function Bar() : void {}
                    }

                    class AChild extends A {
                        const D = 5;
                    }',
                '<?php
                    namespace Ns;

                    class A {
                        const C = 5;



                        public function Bar() : void {}
                    }

                    class AChild extends A {
                        const D = 5;

                        /**
                         * @param A $a1
                         * Some description
                         * @param A|null
                         *        $a2
                         * @param array<int, A> $a3
                         * @return A
                         */
                        public function Fedbca(A $a1, ?A $a2, array $a3) : A {
                            echo A::C;
                            echo A::C;
                            $this->Bar();
                            A::Bar();
                            echo AChild::D;
                            new A();
                            /** @var A */
                            $a = new A();
                            new AChild();

                            return $a;
                        }
                    }',
                [
                    'Ns\A::Foo' => 'Ns\AChild::Fedbca',
                ],
            ],
            'moveStaticMethodAndReferencesFromAbove' => [
                '<?php
                    namespace Ns;

                    class A {
                        const C = 5;

                        /**
                         * @return void
                         */
                        public static function Foo() : void {
                            echo self::C;
                        }
                    }

                    class B {
                        public static function bar() : void {
                            A::Foo();
                        }
                    }',
                '<?php
                    namespace Ns;

                    class A {
                        const C = 5;


                    }

                    class B {
                        public static function bar() : void {
                            B::Fe();
                        }

                        /**
                         * @return void
                         */
                        public static function Fe() : void {
                            echo A::C;
                        }
                    }',
                [
                    'Ns\A::Foo' => 'Ns\B::Fe',
                ],
            ],
            'moveStaticMethodAndReferencesFromBelow' => [
                '<?php
                    namespace Ns;

                    class B {
                        public static function bar() : void {
                            A::Foo();
                        }
                    }

                    class A {
                        const C = 5;

                        /**
                         * @return void
                         */
                        public static function Foo() : void {
                            echo self::C;
                        }
                    }',
                '<?php
                    namespace Ns;

                    class B {
                        public static function bar() : void {
                            B::Fe();
                        }

                        /**
                         * @return void
                         */
                        public static function Fe() : void {
                            echo A::C;
                        }
                    }

                    class A {
                        const C = 5;


                    }',
                [
                    'Ns\A::Foo' => 'Ns\B::Fe',
                ],
            ],
            'moveStaticMethodAndReferencesAcrossNamespaces' => [
                '<?php
                    namespace Ns1 {
                        class A {
                            const C = 5;

                            /**
                             * @return void
                             */
                            public static function Foo() : void {
                                echo self::C;
                            }
                        }
                    }

                    namespace Ns2\Ns3 {
                        class B {
                            public static function bar() : void {
                                \Ns1\A::Foo();
                            }
                        }
                    }',
                '<?php
                    namespace Ns1 {
                        class A {
                            const C = 5;


                        }
                    }

                    namespace Ns2\Ns3 {
                        class B {
                            public static function bar() : void {
                                B::Fe();
                            }

                            /**
                             * @return void
                             */
                            public static function Fe() : void {
                                echo \Ns1\A::C;
                            }
                        }
                    }',
                [
                    'Ns1\A::Foo' => 'Ns2\Ns3\B::Fe',
                ],
            ],
            'moveStaticMethodAndReferencesAcrossNamespacesWithExistingUse' => [
                '<?php
                    namespace Ns1 {
                        class A {
                            const C = 5;

                            /**
                             * @return void
                             */
                            public static function Foo() : void {
                                echo self::C;
                            }
                        }
                    }

                    namespace Ns2\Ns3 {
                        use Ns1\A;

                        class B {
                            public static function bar() : void {
                                \Ns1\A::Foo();
                            }
                        }
                    }',
                '<?php
                    namespace Ns1 {
                        class A {
                            const C = 5;


                        }
                    }

                    namespace Ns2\Ns3 {
                        use Ns1\A;

                        class B {
                            public static function bar() : void {
                                B::Fedcba();
                            }

                            /**
                             * @return void
                             */
                            public static function Fedcba() : void {
                                echo A::C;
                            }
                        }
                    }',
                [
                    'Ns1\A::Foo' => 'Ns2\Ns3\B::Fedcba',
                ],
            ],
            'renameInstanceMethod' => [
                '<?php
                    namespace Ns;

                    use ArrayObject;

                    class A {
                        /**
                         * @return ArrayObject<int, int>
                         */
                        public function Foo() {
                            return new ArrayObject([self::C]);
                        }

                        public function bat() {
                            $this->foo();
                        }
                    }

                    class B extends A {
                        public static function bar(A $a) : void {
                            $a->Foo();

                            $this->foo();
                            parent::foo();

                            foreach ($a->Foo() as $f) {}
                        }
                    }',
                '<?php
                    namespace Ns;

                    use ArrayObject;

                    class A {
                        /**
                         * @return ArrayObject<int, int>
                         */
                        public function Fedcba() {
                            return new ArrayObject([self::C]);
                        }

                        public function bat() {
                            $this->Fedcba();
                        }
                    }

                    class B extends A {
                        public static function bar(A $a) : void {
                            $a->Fedcba();

                            $this->Fedcba();
                            parent::Fedcba();

                            foreach ($a->Fedcba() as $f) {}
                        }
                    }',
                [
                    'Ns\A::foo' => 'Ns\A::Fedcba',
                ],
            ],
        ];
    }
}
