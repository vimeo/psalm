<?php

declare(strict_types=1);

namespace Psalm\Tests\FileManipulation;

use Psalm\Context;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;

use function strpos;

class ClassMoveTest extends TestCase
{
    protected ProjectAnalyzer $project_analyzer;

    public function setUp(): void
    {
        RuntimeCaches::clearAll();

        $this->file_provider = new FakeFileProvider();
    }

    /**
     * @dataProvider providerValidCodeParse
     * @param array<string, string> $constants_to_move
     */
    public function testValidCode(
        string $input_code,
        string $output_code,
        array $constants_to_move,
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
                new FakeParserCacheProvider(),
            ),
        );

        $context = new Context();

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            $input_code,
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
     * @return array<string,array{input:string,output:string,migrations:array<string, string>}>
     */
    public function providerValidCodeParse(): array
    {
        return [
            'renameEmptyClass' => [
                'input' => '<?php
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
                     * @param A::class|C::class $b
                     * @return A
                     */
                    function foo(A $a, string $b) : A {
                        return $a;
                    }

                    /** @var A */
                    $i = new A();

                    if ($i instanceof A) {}',
                'output' => '<?php
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
                     * @param B::class|C::class $b
                     * @return B
                     */
                    function foo(B $a, string $b) : B {
                        return $a;
                    }

                    /** @var B */
                    $i = new B();

                    if ($i instanceof B) {}',
                'migrations' => [
                    'Ns\A' => 'Ns\B',
                ],
            ],
            'renameEmptyClassWithSpacesInDocblock' => [
                'input' => '<?php
                    namespace Ns;

                    class A {}

                    /**
                     * @param ?A $a
                     * @param string | null $b
                     * @param callable(): A $c
                     * @return A | null
                     */
                    function foo(?A $a, $b, $c) : ?A {
                        return $a;
                    }',
                'output' => '<?php
                    namespace Ns;

                    class B {}

                    /**
                     * @param B|null $a
                     * @param string | null $b
                     * @param callable():B $c
                     * @return B|null
                     */
                    function foo(?B $a, $b, $c) : ?B {
                        return $a;
                    }',
                'migrations' => [
                    'Ns\A' => 'Ns\B',
                ],
            ],
            'renameClassWithInstanceMethod' => [
                'input' => '<?php
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
                'output' => '<?php
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
                'migrations' => [
                    'Ns\A' => 'Ns\B',
                ],
            ],
            'renameClassWithStaticMethod' => [
                'input' => '<?php
                    namespace Ns;

                    class AParent {
                        public static function foo(A $one, A $two) {

                        }
                    }

                    class A extends AParent {
                        /**
                         * @param self $one
                         * @param A $two
                         * @return static
                         */
                        public static function foo(self $one, A $two) : void {
                            A::foo($one, $two);
                            self::foo($one, $two);
                            parent::foo($one, $two);
                            static::foo($one, $two);
                            return new static();
                        }
                    }

                    $a = A::class;

                    $a::foo(new A(), new A());

                    function foo() {
                        A::foo(new A(), A::foo());
                    }',
                'output' => '<?php
                    namespace Ns;

                    class AParent {
                        public static function foo(B $one, B $two) {

                        }
                    }

                    class B extends AParent {
                        /**
                         * @param self $one
                         * @param self $two
                         * @return static
                         */
                        public static function foo(self $one, self $two) : void {
                            B::foo($one, $two);
                            self::foo($one, $two);
                            parent::foo($one, $two);
                            static::foo($one, $two);
                            return new static();
                        }
                    }

                    $a = B::class;

                    $a::foo(new B(), new B());

                    function foo() {
                        B::foo(new B(), B::foo());
                    }',
                'migrations' => [
                    'Ns\A' => 'Ns\B',
                ],
            ],
            'renameClassWithInstanceProperty' => [
                'input' => '<?php
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
                'output' => '<?php
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
                'migrations' => [
                    'Ns\A' => 'Ns\B',
                ],
            ],
            'renameClassWithStaticProperty' => [
                'input' => '<?php
                    namespace Ns;

                    class A {
                        /**
                         * @var string
                         */
                        public static $one = "one";

                        /**
                         * @var array
                         */
                        public static $vars = ["one"];
                    }

                    echo A::$one;
                    A::$one = "two";

                    foreach (A::$vars as $var) {}',
                'output' => '<?php
                    namespace Ns;

                    class B {
                        /**
                         * @var string
                         */
                        public static $one = "one";

                        /**
                         * @var array
                         */
                        public static $vars = ["one"];
                    }

                    echo B::$one;
                    B::$one = "two";

                    foreach (B::$vars as $var) {}',
                'migrations' => [
                    'Ns\A' => 'Ns\B',
                ],
            ],
            'moveClassIntoNamespace' => [
                'input' => '<?php
                    use Exception;

                    class A {
                        /** @var ?Exception */
                        public $x;

                        /**
                         * @param ArrayObject<int, A> $a
                         * @throws RunTimeException
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
                            echo __CLASS__;
                            echo self::class;

                            ArrayObject::foo();

                            return new Exception("bad");
                        }

                        public function bar() : void {}
                    }',
                'output' => '<?php
                    namespace Foo\Bar\Baz;

                    use Exception;

                    class B {
                        /** @var Exception|null */
                        public $x;

                        /**
                         * @param \ArrayObject<int, self> $a
                         * @throws \RunTimeException
                         */
                        public function foo(\ArrayObject $a) : Exception {
                            foreach ($a as $b) {
                                $b->bar();
                            }

                            try {
                                // something
                            } catch (\InvalidArgumentException $e) {

                            }

                            echo B::class;
                            echo B::class;
                            echo self::class;

                            \ArrayObject::foo();

                            return new Exception("bad");
                        }

                        public function bar() : void {}
                    }',
                'migrations' => [
                    'A' => 'Foo\Bar\Baz\B',
                ],
            ],
            'moveClassDeeperIntoNamespaceAdjustUseWithoutAlias' => [
                'input' => '<?php
                    namespace Foo {
                        use Bar\Bat;

                        echo Bat::FOO;
                        echo Bat::FAR;

                        /**
                         * @param  Bat $b
                         * @param  Bat::FOO|Bat::FAR $c
                         */
                        function doSomething(Bat $b, int $c) : void {}

                        class A {
                            /** @var ?Bat */
                            public $x = null;
                        }
                    }
                    namespace Bar {
                        class Bat {
                            const FOO = 5;
                            const FAR = 7;
                        }
                    }',
                'output' => '<?php
                    namespace Foo {
                        use Bar\Baz\Bahh;

                        echo Bahh::FOO;
                        echo Bahh::FAR;

                        /**
                         * @param  Bahh $b
                         * @param  Bahh::FAR|Bahh::FOO $c
                         */
                        function doSomething(Bahh $b, int $c) : void {}

                        class A {
                            /** @var Bahh|null */
                            public $x = null;
                        }
                    }
                    namespace Bar\Baz {
                        class Bahh {
                            const FOO = 5;
                            const FAR = 7;
                        }
                    }',
                'migrations' => [
                    'Bar\Bat' => 'Bar\Baz\Bahh',
                ],
            ],
            'moveClassesIntoNamespace' => [
                'input' => '<?php
                    namespace Foo {
                        class A {
                            /** @var ?B */
                            public $x = null;
                            /** @var ?A */
                            public $y = null;
                            /** @var A|B|C|null */
                            public $z = null;
                        }
                    }

                    namespace Foo {
                        class B {
                            /** @var ?A */
                            public $x = null;
                            /** @var ?B */
                            public $y = null;
                            /** @var A|B|C|null */
                            public $z = null;
                        }
                    }

                    namespace Bar {
                        use Foo\A;
                        use Foo\B;

                        class C {
                            /** @var ?A */
                            public $x = null;
                            /** @var ?B */
                            public $y = null;
                            /** @var A|B|null */
                            public $z = null;
                        }
                    }',
                'output' => '<?php
                    namespace Bar\Baz {
                        class A {
                            /** @var B|null */
                            public $x = null;
                            /** @var null|self */
                            public $y = null;
                            /** @var B|\Foo\C|null|self */
                            public $z = null;
                        }
                    }

                    namespace Bar\Baz {
                        class B {
                            /** @var A|null */
                            public $x = null;
                            /** @var null|self */
                            public $y = null;
                            /** @var A|\Foo\C|null|self */
                            public $z = null;
                        }
                    }

                    namespace Bar {
                        use Bar\Baz\A;
                        use Bar\Baz\B;

                        class C {
                            /** @var A|null */
                            public $x = null;
                            /** @var B|null */
                            public $y = null;
                            /** @var A|B|null */
                            public $z = null;
                        }
                    }',
                'migrations' => [
                    'Foo\A' => 'Bar\Baz\A',
                    'Foo\B' => 'Bar\Baz\B',
                ],
            ],
            'moveClassesIntoNamespaceWithoutAlias' => [
                'input' => '<?php
                    namespace Foo {
                        class A {
                            /** @var ?B */
                            public $x = null;
                            /** @var ?A */
                            public $y = null;
                            /** @var A|B|C|null */
                            public $z = null;

                            public static $vars = [1, 2, 3];
                        }
                    }

                    namespace Foo {
                        class B {
                            /** @var ?A */
                            public $x = null;
                            /** @var ?B */
                            public $y = null;
                            /** @var A|B|C|null */
                            public $z = null;
                        }

                        foreach (A::$vars[$foo] as $var) {}
                    }

                    namespace Bar {
                        class C {
                            /** @var ?\Foo\A */
                            public $x = null;
                            /** @var ?\Foo\B */
                            public $y = null;
                            /** @var \Foo\A|\Foo\B|null */
                            public $z = null;
                        }

                        foreach (\Foo\A::$vars as $var) {}
                    }',
                'output' => '<?php
                    namespace Bar\Baz {
                        class A {
                            /** @var B|null */
                            public $x = null;
                            /** @var null|self */
                            public $y = null;
                            /** @var B|\Foo\C|null|self */
                            public $z = null;

                            public static $vars = [1, 2, 3];
                        }
                    }

                    namespace Bar\Baz {
                        class B {
                            /** @var A|null */
                            public $x = null;
                            /** @var null|self */
                            public $y = null;
                            /** @var A|\Foo\C|null|self */
                            public $z = null;
                        }

                        foreach (\Bar\Baz\A::$vars[$foo] as $var) {}
                    }

                    namespace Bar {
                        class C {
                            /** @var Baz\A|null */
                            public $x = null;
                            /** @var Baz\B|null */
                            public $y = null;
                            /** @var Baz\A|Baz\B|null */
                            public $z = null;
                        }

                        foreach (Baz\A::$vars as $var) {}
                    }',
                'migrations' => [
                    'Foo\A' => 'Bar\Baz\A',
                    'Foo\B' => 'Bar\Baz\B',
                ],
            ],
            'moveClassDeeperIntoNamespaceAdjustUseWithAlias' => [
                'input' => '<?php
                    namespace Foo {
                        use Bar\Bat as Kappa;

                        echo Kappa::FOO;
                        echo Kappa::FAR;

                        /**
                         * @param  Kappa $b
                         * @param  Kappa::FOO|Kappa::FAR $c
                         */
                        function doSomething(Kappa $b, int $c) : void {}

                        class A {
                            /** @var ?Kappa */
                            public $x = null;
                        }
                    }
                    namespace Bar {
                        class Bat {
                            const FOO = 5;
                            const FAR = 7;
                        }
                    }',
                'output' => '<?php
                    namespace Foo {
                        use Bar\Baz\Bahh as Kappa;

                        echo Kappa::FOO;
                        echo Kappa::FAR;

                        /**
                         * @param  Kappa $b
                         * @param  Kappa::FAR|Kappa::FOO $c
                         */
                        function doSomething(Kappa $b, int $c) : void {}

                        class A {
                            /** @var Kappa|null */
                            public $x = null;
                        }
                    }
                    namespace Bar\Baz {
                        class Bahh {
                            const FOO = 5;
                            const FAR = 7;
                        }
                    }',
                'migrations' => [
                    'Bar\Bat' => 'Bar\Baz\Bahh',
                ],
            ],
            'moveClassDeeperIntoNamespaceDontAdjustGroupUse' => [
                'input' => '<?php
                    namespace Foo {
                        use Bar\{Bat};

                        /**
                         * @param  Bat $b
                         */
                        function doSomething(Bat $b) : void {}
                    }
                    namespace Bar {
                        class Bat {}
                    }',
                'output' => '<?php
                    namespace Foo {
                        use Bar\{Bat};

                        /**
                         * @param  \Bar\Baz\Bahh $b
                         */
                        function doSomething(\Bar\Baz\Bahh $b) : void {}
                    }
                    namespace Bar\Baz {
                        class Bahh {}
                    }',
                'migrations' => [
                    'Bar\Bat' => 'Bar\Baz\Bahh',
                ],
            ],
            'moveClassBewareOfPropertyNotSetInConstructorCheck' => [
                'input' => '<?php
                    namespace Foo {
                        class Base
                        {
                            protected $property1;

                            public function __construct()
                            {
                                $this->property1 = "";
                            }
                        }
                    }
                    namespace Foo {
                        class Hello extends Base {}
                    }',
                'output' => '<?php
                    namespace Foo {
                        class Base
                        {
                            protected $property1;

                            public function __construct()
                            {
                                $this->property1 = "";
                            }
                        }
                    }
                    namespace Foo\Bar {
                        class Hello extends \Foo\Base {}
                    }',
                'migrations' => [
                    'Foo\Hello' => 'Foo\Bar\Hello',
                ],
            ],
            'renamesAllStaticPropReferences' => [
                'input' => '<?php
                    namespace Foo;

                    class Bar {
                        public static $props = [1, 2, 3];
                    }
                    echo Bar::$props[1];
                    echo Bar::$props[2];
                ',
                'output' => '<?php
                    namespace Zoo;

                    class Baz {
                        public static $props = [1, 2, 3];
                    }
                    echo \Zoo\Baz::$props[1];
                    echo \Zoo\Baz::$props[2];
                ',
                'migrations' => [
                    'Foo\Bar' => 'Zoo\Baz',
                ],
            ],
        ];
    }
}
