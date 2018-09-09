<?php
namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;

class MagicMethodAnnotationTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return void
     */
    public function testPhpDocMethodWhenUndefined()
    {
        Config::getInstance()->use_phpdoc_methods_without_call = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @method string getString()
                 * @method  void setInteger(int $integer)
                 * @method setString(int $integer)
                 * @method  getBool(string $foo) : bool
                 * @method (string|int)[] getArray() : array
                 * @method (callable() : string) getCallable() : callable
                 */
                class Child {}

                $child = new Child();

                $a = $child->getString();
                $child->setInteger(4);
                /** @psalm-suppress MixedAssignment */
                $b = $child->setString(5);
                $c = $child->getBool("hello");
                $d = $child->getArray();
                $e = $child->getCallable();'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testCannotOverrideParentClassRetunTypeWhenIgnoringPhpDocMethod()
    {
        Config::getInstance()->use_phpdoc_methods_without_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class ParentClass {
                    public static function getMe() : self {
                        return new self();
                    }
                }

                /**
                 * @method getMe() : Child
                 */
                class Child extends ParentClass {}

                $child = Child::getMe();'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);

        $this->assertSame('ParentClass', (string) $context->vars_in_scope['$child']);
    }

    /**
     * @return void
     */
    public function testOverrideParentClassRetunType()
    {
        Config::getInstance()->use_phpdoc_methods_without_call = true;

        $this->addFile(
            'somefile.php',
            '<?php
                class ParentClass {
                    public static function getMe() : self {
                        return new self();
                    }
                }

                /**
                 * @method getMe() : Child
                 */
                class Child extends ParentClass {}

                $child = Child::getMe();'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);

        $this->assertSame('Child', (string) $context->vars_in_scope['$child']);
    }

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'validSimpleAnnotations' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method string getString() dsa sada
                     * @method  void setInteger(int $integer) dsa sada
                     * @method setString(int $integer) dsa sada
                     * @method setMixed(mixed $foo) dsa sada
                     * @method setImplicitMixed($foo) dsa sada
                     * @method setAnotherImplicitMixed( $foo, $bar,$baz) dsa sada
                     * @method setYetAnotherImplicitMixed( $foo  ,$bar,  $baz    ) dsa sada
                     * @method  getBool(string $foo)  :   bool dsa sada
                     * @method (string|int)[] getArray() : array with some text dsa sada
                     * @method (callable() : string) getCallable() : callable dsa sada
                     */
                    class Child extends ParentClass {}

                    $child = new Child();

                    $a = $child->getString();
                    $child->setInteger(4);
                    /** @psalm-suppress MixedAssignment */
                    $b = $child->setString(5);
                    $c = $child->getBool("hello");
                    $d = $child->getArray();
                    $child->setArray(["boo"]);
                    $e = $child->getCallable();
                    $child->setMixed("hello");
                    $child->setMixed(4);
                    $child->setImplicitMixed("hello");
                    $child->setImplicitMixed(4);',
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'mixed',
                    '$c' => 'bool',
                    '$d' => 'array<mixed, string|int>',
                    '$e' => 'callable():string',
                ],
            ],
            'validAnnotationWithDefault' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method void setArray(array $arr = array(), int $foo = 5) with some more text
                     */
                    class Child extends ParentClass {}

                    $child = new Child();

                    $child->setArray(["boo"]);
                    $child->setArray(["boo"], 8);',
            ],
            'validAnnotationWithVariadic' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method void setInts(int ...$foo) with some more text
                     */
                    class Child extends ParentClass {}

                    $child = new Child();

                    $child->setInts(1, 2, 3, 4);',
            ],
            'validUnionAnnotations' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method setBool(string $foo, string|bool $bar)  :   bool dsa sada
                     * @method void setAnotherArray(int[]|string[] $arr = [], int $foo = 5) with some more text
                     */
                    class Child extends ParentClass {}

                    $child = new Child();

                    $b = $child->setBool("hello", true);
                    $c = $child->setBool("hello", "true");
                    $child->setAnotherArray(["boo"]);',
                'assertions' => [
                    '$b' => 'bool',
                    '$c' => 'bool',
                ],
            ],
            'namespacedValidAnnotations' => [
                '<?php
                    namespace Foo;

                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method setBool(string $foo, string|bool $bar)  :   bool
                     */
                    class Child extends ParentClass {}

                    $child = new Child();

                    $c = $child->setBool("hello", true);
                    $c = $child->setBool("hello", "true");',
            ],
            'globalMethod' => [
                '<?php
                    /** @method void global() */
                    class A {
                        public function __call(string $s) {}
                    }',
            ],
            'magicMethodInternalCall' => [
                '<?php
                    /**
                     * @method I[] work()
                     */
                    class I {
                        function __call(string $method, array $args) { return [new I, new I]; }

                        function zugzug(): void {
                            echo count($this->work());
                        }
                    }',
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'annotationWithoutCall' => [
                '<?php
                    /**
                     * @method string getString()
                     */
                    class Child {}

                    $child = new Child();

                    $a = $child->getString();',
                'error_message' => 'UndefinedMethod',
            ],
            'annotationWithBadDocblock' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method string getString(\)
                     */
                    class Child extends ParentClass {}',
                'error_message' => 'InvalidDocblock',
            ],
            'annotationWithByRefParam' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method string getString(&$a)
                     */
                    class Child extends ParentClass {}',
                'error_message' => 'InvalidDocblock',
            ],
            'annotationWithSealed' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method string getString()
                     * @psalm-seal-methods
                     */
                    class Child extends ParentClass {}

                    $child = new Child();
                    $child->getString();
                    $child->foo();',
                'error_message' => 'UndefinedMethod - src/somefile.php:14 - Method Child::foo does not exist',
            ],
            'annotationInvalidArg' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method setString(int $integer)
                     */
                    class Child extends ParentClass {}

                    $child = new Child();

                    $child->setString("five");',
                'error_message' => 'InvalidScalarArgument',
            ],
            'unionAnnotationInvalidArg' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method setBool(string $foo, string|bool $bar)  :   bool dsa sada
                     */
                    class Child extends ParentClass {}

                    $child = new Child();

                    $b = $child->setBool("hello", 5);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'validAnnotationWithInvalidVariadicCall' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method void setInts(int ...$foo) with some more text
                     */
                    class Child extends ParentClass {}

                    $child = new Child();

                    $child->setInts([1, 2, 3]);',
                'error_message' => 'InvalidArgument',
            ],
        ];
    }
}
