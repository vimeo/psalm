<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class TypeAnnotationTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'typeAliasBeforeClass' => [
                'code' => '<?php
                    namespace Barrr;

                    /**
                     * @psalm-type CoolType = A|B|null
                     */

                    class A {}
                    class B {}

                    /** @return CoolType */
                    function foo() {
                        if (rand(0, 1)) {
                            return new A();
                        }

                        if (rand(0, 1)) {
                            return new B();
                        }

                        return null;
                    }

                    /** @param CoolType $a **/
                    function bar ($a) : void { }

                    bar(foo());',
            ],
            'typeAliasBeforeFunction' => [
                'code' => '<?php
                    namespace Barrr;

                    /**
                     * @psalm-type A_OR_B = A|B
                     * @psalm-type CoolType = A_OR_B|null
                     * @return CoolType
                     */
                    function foo() {
                        if (rand(0, 1)) {
                            return new A();
                        }

                        if (rand(0, 1)) {
                            return new B();
                        }

                        return null;
                    }

                    class A {}
                    class B {}

                    /** @param CoolType $a **/
                    function bar ($a) : void { }

                    bar(foo());',
            ],
            'typeAliasInSeparateBlockBeforeFunction' => [
                'code' => '<?php
                    namespace Barrr;

                    /**
                     * @psalm-type CoolType = A|B|null
                     */
                    /**
                     * @return CoolType
                     */
                    function foo() {
                        if (rand(0, 1)) {
                            return new A();
                        }

                        if (rand(0, 1)) {
                            return new B();
                        }

                        return null;
                    }

                    class A {}
                    class B {}

                    /** @param CoolType $a **/
                    function bar ($a) : void { }

                    bar(foo());',
            ],
            'almostFreeStandingTypeAlias' => [
                'code' => '<?php
                    /**
                     * @psalm-type CoolType = A|B|null
                     */

                    // this breaks up the line

                    class A {}
                    class B {}

                    /** @return CoolType */
                    function foo() {
                        if (rand(0, 1)) {
                            return new A();
                        }

                        if (rand(0, 1)) {
                            return new B();
                        }

                        return null;
                    }

                    /** @param CoolType $a **/
                    function bar ($a) : void { }

                    bar(foo());',
            ],
            'typeAliasUsedTwice' => [
                'code' => '<?php
                    namespace Baz;

                    /** @psalm-type TA = array<int, string> */

                    class Bar {
                        public function foo() : void {
                            $bar =
                                /** @return TA */
                                function() {
                                    return ["hello"];
                            };

                            /** @var array<int, TA> */
                            $bat = [$bar(), $bar()];

                            foreach ($bat as $b) {
                                echo $b[0];
                            }
                        }
                    }

                    /**
                      * @psalm-type _A=array{elt:int}
                      * @param _A $p
                      * @return _A
                      */
                    function f($p) {
                        /** @var _A */
                        $r = $p;
                        return $r;
                    }',
            ],
            'classTypeAliasSimple' => [
                'code' => '<?php
                    namespace Bar;

                    /** @psalm-type PhoneType = array{phone: string} */
                    class Phone {
                        /** @psalm-return PhoneType */
                        public function toArray(): array {
                            return ["phone" => "Nokia"];
                        }
                    }

                    /** @psalm-type NameType = array{name: string} */
                    class Name {
                        /** @psalm-return NameType */
                        function toArray(): array {
                            return ["name" => "Matt"];
                        }
                    }

                    /**
                     * @psalm-import-type PhoneType from Phone as PhoneType2
                     * @psalm-import-type NameType from Name as NameType2
                     *
                     * @psalm-type UserType = PhoneType2&NameType2
                     */
                    class User {
                        /** @psalm-return UserType */
                        function toArray(): array {
                            return array_merge(
                                (new Name)->toArray(),
                                (new Phone)->toArray()
                            );
                        }
                    }',
            ],
            'classTypeAliasImportWithAlias' => [
                'code' => '<?php
                    namespace Bar;

                    /** @psalm-type PhoneType = array{phone: string} */
                    class Phone {
                        /** @psalm-return PhoneType */
                        public function toArray(): array {
                            return ["phone" => "Nokia"];
                        }
                    }

                    /**
                     * @psalm-import-type PhoneType from Phone as TPhone
                     */
                    class User {
                        /** @psalm-return TPhone */
                        function toArray(): array {
                            return array_merge([], (new Phone)->toArray());
                        }
                    }',
            ],
            'classTypeAliasDirectUsage' => [
                'code' => '<?php
                    namespace Bar;

                    /** @psalm-type PhoneType = array{phone: string} */
                    class Phone {
                        /** @psalm-return PhoneType */
                        public function toArray(): array {
                            return ["phone" => "Nokia"];
                        }
                    }

                    /**
                     * @psalm-import-type PhoneType from Phone
                     */
                    class User {
                        /** @psalm-return PhoneType */
                        function toArray(): array {
                            return array_merge([], (new Phone)->toArray());
                        }
                    }',
            ],
            'classTypeAliasFromExternalNamespace' => [
                'code' => '<?php
                namespace Foo {
                    /** @psalm-type PhoneType = array{phone: string} */
                    class Phone {
                        /** @psalm-return PhoneType */
                        public function toArray(): array {
                            return ["phone" => "Nokia"];
                        }
                    }
                }

                namespace Bar {
                    /**
                     * @psalm-import-type PhoneType from \Foo\Phone
                     */
                    class User {
                        /** @psalm-return PhoneType */
                        function toArray(): array {
                            return (new \Foo\Phone)->toArray();
                        }
                    }
                }',
            ],
            'importTypeForParam' => [
                'code' => '<?php
                    namespace Bar;

                    /**
                     * @psalm-type Type = self::NULL|self::BOOL|self::INT|self::STRING
                     */
                    interface I
                    {
                        public const NULL = 0;
                        public const BOOL = 1;
                        public const INT = 2;
                        public const STRING = 3;

                        /**
                         * @psalm-param Type $type
                         */
                        public function a(int $type): void;
                    }

                    /**
                     * @psalm-import-type Type from I as Type2
                     */
                    abstract class C implements I
                    {
                        public function a(int $type): void
                        {
                            $this->b($type);
                        }

                        /**
                         * @psalm-param Type2 $type
                         */
                        private function b(int $type): void
                        {
                        }
                    }',
            ],
            'usedInVarForForeach' => [
                'code' => '<?php
                /** @psalm-type _B=array{p1:string} */
                function e(array $a): void
                {
                    /** @var _B $elt */
                    foreach ($a as $elt) {
                        echo $elt["p1"];
                    }
                }',
            ],
            'objectWithPropertiesAlias' => [
                'code' => '<?php
                    /**
                     * @psalm-type FooStruct=string
                     */
                    class A {}

                    /**
                     * @psalm-import-type FooStruct from A as F2
                     */
                    class B {
                        /**
                         * @param object{foo: F2} $a
                         * @return object{foo: string}
                         */
                        public function bar($a) {
                            return $a;
                        }
                    }',
            ],
            'sameDocBlockTypeAliasAsTypeParameterForInterface' => [
                'code' => '<?php
                    /** @template T */
                    interface A {
                        /** @return T */
                        public function output();
                    }

                    /**
                     * @psalm-type Foo=string
                     * @implements A<Foo>
                     */
                    class C implements A {
                        public function output() {
                            return "hello";
                        }
                    }

                    $instance = new C();
                    $output = $instance->output();',
                'assertions' => [
                    '$output' => 'string',
                ],
            ],
            'sameDocBlockTypeAliasAsTypeParameterForExtendedRegularClass' => [
                'code' => '<?php
                    /** @template T */
                    class A {
                        /** @var T */
                        public $value;

                        /** @param T $value */
                        public function __construct($value) {
                            $this->value = $value;
                        }
                    }

                    /**
                     * @psalm-type Foo=string
                     * @extends A<Foo>
                     */
                    class C extends A {}

                    $instance = new C("hello");
                    $output = $instance->value;',
                'assertions' => [
                    '$output' => 'string',
                ],
            ],
            'sameDocBlockTypeAliasAsTypeParameterForExtendedAbstractClass' => [
                'code' => '<?php
                    /** @template T */
                    abstract class A {
                        /** @var T */
                        public $value;

                        /** @param T $value */
                        public function __construct($value) {
                            $this->value = $value;
                        }
                    }

                    /**
                     * @psalm-type Foo=string
                     * @extends A<Foo>
                     */
                    class C extends A {}

                    $instance = new C("hello");
                    $output = $instance->value;',
                'assertions' => [
                    '$output' => 'string',
                ],
            ],
            'importedTypeAliasAsTypeParameterForImplementation' => [
                'code' => '<?php
                    namespace Bar;

                    /** @template T */
                    interface A {}

                    /** @psalm-type Foo=string */
                    class B {}

                    /**
                     * @psalm-import-type Foo from B
                     * @implements A<Foo>
                     */
                    class C implements A {}',
            ],
            'importedTypeAliasAsConstrainedTypeParameterForImplementation' => [
                'code' => '<?php
                    namespace Bar;

                    /** @template T of string */
                    interface A {}

                    /**
                     * @psalm-type Foo = "foo"
                     */
                    class B {}

                    /**
                     * @psalm-import-type Foo from B
                     * @implements A<Foo>
                     */
                    class C implements A {}
                ',
            ],
            'importedTypeAliasAsTypeParameterForExtendedClass' => [
                'code' => '<?php
                    namespace Bar;

                    /** @template T */
                    class A {}

                    /** @psalm-type Foo=string */
                    class B {}

                    /**
                     * @psalm-import-type Foo from B
                     * @extends A<Foo>
                     */
                    class C extends A {}',
            ],
            'importedTypeAliasAsTypeParameterForExtendedAbstractClass' => [
                'code' => '<?php
                    namespace Bar;

                    /** @template T */
                    abstract class A {}

                    /** @psalm-type Foo=string */
                    class B {}

                    /**
                     * @psalm-import-type Foo from B
                     * @extends A<Foo>
                     */
                    class C extends A {}',
            ],
            'importedTypeAliasRenamedAsTypeParameterForImplementation' => [
                'code' => '<?php
                    namespace Bar;

                    /** @template T */
                    interface A {}

                    /** @psalm-type Foo=string */
                    class B {}

                    /**
                     * @psalm-import-type Foo from B as NewName
                     * @implements A<NewName>
                     */
                    class C implements A {}',
            ],
            'importedTypeAliasRenamedAsTypeParameterForExtendedClass' => [
                'code' => '<?php
                    namespace Bar;

                    /** @template T */
                    class A {}

                    /** @psalm-type Foo=string */
                    class B {}

                    /**
                     * @psalm-import-type Foo from B as NewName
                     * @extends A<NewName>
                     */
                    class C extends A {}',
            ],
            'importedTypeAliasRenamedAsTypeParameterForExtendedAbstractClass' => [
                'code' => '<?php
                    namespace Bar;

                    /** @template T */
                    abstract class A {}

                    /** @psalm-type Foo=string */
                    class B {}

                    /**
                     * @psalm-import-type Foo from B as NewName
                     * @extends A<NewName>
                     */
                    class C extends A {}',
            ],
            'importedTypeInsideLocalTypeAliasUsedAsTypeParameter' => [
                'code' => '<?php
                    /** @template T */
                    abstract class A {
                        /** @var T */
                        public $value;

                        /** @param T $value */
                        public function __construct($value) {
                            $this->value = $value;
                        }
                    }

                    /**
                     * @psalm-type Foo=string
                     */
                    class B {}

                    /**
                     * @psalm-import-type Foo from B
                     * @psalm-type Baz=Foo
                     *
                     * @extends A<Baz>
                     */
                    class C extends A {}

                    $instance = new C("hello");
                    $output = $instance->value;',
                'assertions' => [
                    '$output' => 'string',
                ],
            ],
            'importedTypeWithPhpstanAnnotation' => [
                'code' => '<?php
                    /** @template T */
                    abstract class A {
                        /** @var T */
                        public $value;

                        /** @param T $value */
                        public function __construct($value) {
                            $this->value = $value;
                        }
                    }

                    /**
                     * @phpstan-type Foo=string
                     */
                    class B {}

                    /**
                     * @phpstan-import-type Foo from B
                     * @phpstan-type Baz=Foo
                     *
                     * @extends A<Baz>
                     */
                    class C extends A {}

                    $instance = new C("hello");
                    $output = $instance->value;',
                'assertions' => [
                    '$output' => 'string',
                ],
            ],
            'importedTypeUsedInAssertion' => [
                'code' => '<?php
                    /** @psalm-type Foo = string */
                    class A {}

                    /**
                     * @psalm-immutable
                     * @psalm-import-type Foo from A as FooAlias
                     */
                    class B {
                        /**
                         * @param mixed $input
                         * @psalm-return FooAlias
                         */
                        public function convertToFoo($input) {
                            $this->assertFoo($input);
                            return $input;
                        }

                        /**
                         * @param mixed $value
                         * @psalm-assert FooAlias $value
                         */
                        private function assertFoo($value): void {
                            if(!is_string($value)) {
                                throw new \InvalidArgumentException();
                            }
                        }
                    }

                    $instance = new B();
                    $output = $instance->convertToFoo("hallo");
                ',
                'assertions' => [
                    '$output' => 'string',
                ],
            ],
            'importedTypeUsedInOtherType' => [
                'code' => '<?php
                    /** @psalm-type OpeningTypes=self::TYPE_A|self::TYPE_B */
                    class Foo {
                        public const TYPE_A = 1;
                        public const TYPE_B = 2;
                    }

                    /**
                     * @psalm-import-type OpeningTypes from Foo
                     * @psalm-type OpeningTypeAssignment=list<OpeningTypes>
                     */
                    class Main {
                        /** @return OpeningTypeAssignment */
                        public function doStuff(): array {
                            return [];
                        }
                    }

                    $instance = new Main();
                    $output = $instance->doStuff();
                ',
                'assertions' => [
                    '$output===' => 'list<1|2>',
                ],
            ],
            'callableWithReturnTypeTypeAliasWithinBrackets' => [
                'code' => '<?php
                    /** @psalm-type TCallback (callable():int) */
                    class Foo {
                        /** @psalm-var TCallback */
                        public static callable $callback;
                    }
                    $output = Foo::$callback;
                ',
                'assertions' => [
                    '$output===' => 'callable():int',
                ],
            ],
            'callableWithReturnTypeTypeAlias' => [
                'code' => '<?php
                    /** @psalm-type TCallback callable():int */
                    class Foo {
                        /** @psalm-var TCallback */
                        public static callable $callback;
                    }
                    $output = Foo::$callback;
                ',
                'assertions' => [
                    '$output===' => 'callable():int',
                ],
            ],
            'callableFormats' => [
                'code' => '<?php
                    /**
                     * @psalm-type A callable(int, int): string
                     * @psalm-type B callable(int, int=): string
                     * @psalm-type C callable(int $a, string $b): void
                     * @psalm-type D callable(string $c): mixed
                     * @psalm-type E callable(string $c): mixed
                     * @psalm-type F callable(float...): (int|null)
                     * @psalm-type G callable(float ...$d): (int|null)
                     * @psalm-type H callable(array<int>): array<string>
                     * @psalm-type I callable(array<string, int> $e): array<int, string>
                     * @psalm-type J callable(array<int> ...): string
                     * @psalm-type K callable(array<int> ...$e): string
                     * @psalm-type L \Closure(int, int): string
                     *
                     * @method ma(): A
                     * @method mb(): B
                     * @method mc(): C
                     * @method md(): D
                     * @method me(): E
                     * @method mf(): F
                     * @method mg(): G
                     * @method mh(): H
                     * @method mi(): I
                     * @method mj(): J
                     * @method mk(): K
                     * @method ml(): L
                     */
                    class Foo {
                        public function __call(string $method, array $params) { return 1; }
                    }

                    $foo = new \Foo();
                    $output_ma = $foo->ma();
                    $output_mb = $foo->mb();
                    $output_mc = $foo->mc();
                    $output_md = $foo->md();
                    $output_me = $foo->me();
                    $output_mf = $foo->mf();
                    $output_mg = $foo->mg();
                    $output_mh = $foo->mh();
                    $output_mi = $foo->mi();
                    $output_mj = $foo->mj();
                    $output_mk = $foo->mk();
                    $output_ml = $foo->ml();
                ',
                'assertions' => [
                    '$output_ma===' => 'callable(int, int):string',
                    '$output_mb===' => 'callable(int, int=):string',
                    '$output_mc===' => 'callable(int, string):void',
                    '$output_md===' => 'callable(string):mixed',
                    '$output_me===' => 'callable(string):mixed',
                    '$output_mf===' => 'callable(float...):(int|null)',
                    '$output_mg===' => 'callable(float...):(int|null)',
                    '$output_mh===' => 'callable(array<array-key, int>):array<array-key, string>',
                    '$output_mi===' => 'callable(array<string, int>):array<int, string>',
                    '$output_mj===' => 'callable(array<array-key, int>...):string',
                    '$output_mk===' => 'callable(array<array-key, int>...):string',
                    '$output_ml===' => 'Closure(int, int):string',
                ],
            ],
            'unionOfStringsContainingBraceChar' => [
                'code' => '<?php
                    /** @psalm-type T \'{\'|\'}\' */
                    class Foo {
                        /** @psalm-var T */
                        public static string $t;
                    }
                    $t = Foo::$t;
                ',
                'assertions' => [
                    '$t===' => '\'{\'|\'}\'',
                ],
            ],
            'unionOfStringsContainingGTChar' => [
                'code' => '<?php
                    /** @psalm-type T \'<\'|\'>\' */
                    class Foo {
                        /** @psalm-var T */
                        public static string $t;
                    }
                    $t = Foo::$t;
                ',
                'assertions' => [
                    '$t===' => '\'<\'|\'>\'',
                ],
            ],
            'unionOfStringsContainingBracketChar' => [
                'code' => '<?php
                    /** @psalm-type T \'(\'|\')\' */
                    class Foo {
                        /** @psalm-var T */
                        public static string $t;
                    }
                    $t = Foo::$t;
                ',
                'assertions' => [
                    '$t===' => '\'(\'|\')\'',
                ],
            ],
            'bareWordsCommentAfterType' => [
                'code' => '<?php
                    /**
                     * @psalm-type T string
                     *
                     * Lorem ipsum
                     */
                    class Foo {
                        /** @psalm-var T */
                        public static string $t;
                    }
                    $t = Foo::$t;
                ',
                'assertions' => [
                    '$t===' => 'string',
                ],
            ],
            'handlesTypeWhichEndsWithRoundBracket' => [
                'code' => '<?php
                    /**
                     * @psalm-type Foo=(iterable<mixed>)
                     */
                    class A {}
                    ',
            ],
            'commentAfterType' => [
                'code' => '<?php
                    /**
                     * @psalm-type TTest string
                     *
                     * This is a test class.
                     */
                    class Test {}',
            ],
            'multilineTypeWithComment' => [
                'code' => '<?php
                    /**
                     * @psalm-type PhoneType = array{
                     *    phone: string
                     * }
                     *
                     * Bar
                     */
                    class Foo {
                        /** @var PhoneType */
                        public static $phone;
                    }
                    $output = Foo::$phone;
                    ',
                'assertions' => [
                    '$output===' => 'array{phone: string}',
                ],
            ],
            'combineAliasOfArrayAndArrayCorrectly' => [
                'code' => '<?php

                    /**
                     * @psalm-type C = array{c: string}
                     */
                    class A {}

                    /**
                     * @template F
                     */
                    class D {
                        /**
                         * @param F $data
                         */
                        public function __construct(public $data) {}
                    }


                    /**
                     * @psalm-import-type C from A
                     */
                    class G {

                        /**
                         * @param D<array{b: bool}>|D<C> $_doesNotWork
                         */
                        public function doesNotWork($_doesNotWork): void {
                            /** @psalm-check-type-exact $_doesNotWork = D<array{b?: bool, c?: string}> */;
                        }
                    }',
            ],
            'importFromEnum' => [
                'code' => <<<'PHP'
                <?php
                /** @psalm-type _Foo = array{foo: string} */
                enum E {}
                /**
                 * @psalm-import-type _Foo from E
                 */
                class C {
                    /** @param _Foo $foo */
                    public function f(array $foo): void {
                        echo $foo['foo'];
                    }
                }
                PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'importFromTrait' => [
                'code' => <<<'PHP'
                <?php
                /** @psalm-type _Foo = array{foo: string} */
                trait T {}

                /** @psalm-import-type _Foo from T */
                class C {
                    /** @param _Foo $foo */
                    public function f(array $foo): void {
                        echo $foo['foo'];
                    }
                }
                PHP,
            ],
            'inlineComments' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @psalm-type Foo=array{
                     *   a: string, // comment
                     *   b: string, // comment
                     * }
                     */
                    class A {
                        /**
                         * @psalm-param Foo $foo
                         */
                        public function bar(array $foo): void {}
                    }
                PHP,
            ],
            'typeWithMultipleSpaces' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @psalm-type Foo      =     string
                     * @psalm-type Bar           int
                     */
                    class A {
                        /**
                         * @psalm-param Foo $foo
                         * @psalm-param Bar $bar
                         */
                        public function bar(string $foo, int $bar): void {}
                    }
                PHP,
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'invalidTypeAlias' => [
                'code' => '<?php
                    namespace Barrr;

                    /**
                     * @psalm-type CoolType = A|B>
                     */

                    class A {}',
                'error_message' => 'InvalidDocblock',
            ],
            'typeAliasInTKeyedArray' => [
                'code' => '<?php
                    namespace Barrr;

                    /**
                     * @psalm-type aType null|"a"|"b"|"c"|"d"
                     */

                    /** @psalm-return array{0:bool,1:aType} */
                    function f(): array {
                        return [(bool)rand(0,1), rand(0,1) ? "z" : null];
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'classTypeAliasInvalidReturn' => [
                'code' => '<?php
                    namespace Barrr;

                    /** @psalm-type PhoneType = array{phone: string} */
                    class Phone {
                        /** @psalm-return PhoneType */
                        public function toArray(): array {
                            return ["phone" => "Nokia"];
                        }
                    }

                    /** @psalm-type NameType = array{name: string} */
                    class Name {
                        /** @psalm-return NameType */
                        function toArray(): array {
                            return ["name" => "Matt"];
                        }
                    }

                    /**
                     * @psalm-import-type PhoneType from Phone as PhoneType2
                     * @psalm-import-type NameType from Name as NameType2
                     *
                     * @psalm-type UserType = PhoneType2&NameType2
                     */
                    class User {
                        /** @psalm-return UserType */
                        function toArray(): array {
                            return array_merge(
                                (new Name)->toArray(),
                                ["foo" => "bar"]
                            );
                        }
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'classTypeInvalidAliasImport' => [
                'code' => '<?php
                    namespace Barrr;

                    class Phone {
                        function toArray(): array {
                            return ["name" => "Matt"];
                        }
                    }

                    /**
                     * @psalm-import-type PhoneType from Phone
                     */
                    class User {}',
                'error_message' => 'InvalidTypeImport',
            ],
            'classTypeAliasFromInvalidClass' => [
                'code' => '<?php
                    namespace Barrr;

                    /**
                     * @psalm-import-type PhoneType from Phone
                     */
                    class User {}',
                'error_message' => 'UndefinedDocblockClass',
            ],
            'malformedImportMissingFrom' => [
                'code' => '<?php
                    namespace Barrr;

                    /** @psalm-import-type Thing */
                    class C {}
                ',
                'error_message' => 'InvalidTypeImport',
            ],
            'malformedImportMissingSourceClass' => [
                'code' => '<?php
                    namespace Barrr;

                    /** @psalm-import-type Thing from */
                    class C {}
                ',
                'error_message' => 'InvalidTypeImport',
            ],
            'malformedImportMisspelledFrom' => [
                'code' => '<?php
                    namespace Barrr;

                    /** @psalm-import-type Thing morf */
                    class C {}
                ',
                'error_message' => 'InvalidTypeImport',
            ],
            'malformedImportMissingAlias' => [
                'code' => '<?php
                    namespace Barrr;

                    /** @psalm-import-type Thing from Somewhere as */
                    class C {}
                ',
                'error_message' => 'InvalidTypeImport',
            ],
            'noCrashWithPriorReference' => [
                'code' => '<?php
                    namespace Barrr;

                    /**
                     * @psalm-type _C=array{c:_CC}
                     * @psalm-type _CC=float
                     */
                    class A {
                        /**
                         * @param _C $arr
                         */
                        public function foo(array $arr) : void {}
                    }',
                'error_message' => 'UndefinedDocblockClass',
            ],
            'mergeImportedTypes' => [
                'code' => '<?php
                    namespace A\B;

                    /**
                     * @psalm-type _A=array{
                     *      id:int
                     * }
                     *
                     * @psalm-type _B=array{
                     *      id:int,
                     *      something:int
                     * }
                     */
                    class Types
                    {
                    }

                    namespace A;

                    /**
                     * @psalm-import-type _A from \A\B\Types as _AA
                     * @psalm-import-type _B from \A\B\Types as _BB
                     */
                    class Id
                    {
                        /**
                         * @psalm-param _AA|_BB $_item
                         */
                        public function ff(array $_item): int
                        {
                            return $_item["something"];
                        }
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'noCrashWithSelfReferencingType' => [
                'code' => '<?php
                    /**
                     * @psalm-type SomeType = array{
                     *     parent?: SomeType,
                     *     foo?: int,
                     * }
                     * @psalm-param SomeType $input
                     */
                    function test(array $input):void {}',
                'error_message' => 'InvalidDocblock',
            ],
            'invalidTypeWhenNotImported' => [
                'code' => '<?php

                    /** @psalm-type Foo = string */
                    class A {}

                    /** @template T */
                    interface B {}

                    /** @implements B<Foo> */
                    class C implements B {}',
                'error_message' => 'UndefinedDocblockClass',
            ],
            'invalidTypeWhenNotImportedInsideAnotherTypeAlias' => [
                'code' => '<?php

                    /** @psalm-type Foo = string */
                    class A {}

                    /** @template T */
                    interface B {}

                    /**
                     * @psalm-type Baz=Foo
                     * @implements B<Baz>
                     */
                    class C implements B {}',
                'error_message' => 'UndefinedDocblockClass',
            ],
            'duplicateKeyInArrayShapeOnInterfaceIsReported' => [
                'code' => <<<'PHP'
                    <?php

                    /**
                     * @psalm-type Attributes = array{
                     *   name: string,
                     *   email: string,
                     *   email: string,
                     * }
                     */
                    interface A {
                        /**
                         * @return Attributes
                         */
                        public function getAttributes(): array;
                    }
                    PHP,
                'error_message' => 'InvalidDocblock',
            ],
            'duplicateKeyInArrayShapeOnAClassIsReported' => [
                'code' => <<<'PHP'
                    <?php

                    /**
                     * @psalm-type Attributes = array{
                     *   name: string,
                     *   email: string,
                     *   email: string,
                     * }
                     */
                    class A {
                        /**
                         * @return Attributes
                         */
                        public function getAttributes(): array {
                            return [];
                        }
                    }
                    PHP,
                'error_message' => 'InvalidDocblock',
            ],
            'duplicateKeyInArrayShapeOnATraitIsReported' => [
                'code' => <<<'PHP'
                    <?php

                    /**
                     * @psalm-type Attributes = array{
                     *   name: string,
                     *   email: string,
                     *   email: string,
                     * }
                     */
                    trait A {
                        /**
                         * @return Attributes
                         */
                        public function getAttributes(): array {
                            return [];
                        }
                    }
                    PHP,
                'error_message' => 'InvalidDocblock',
            ],
            'duplicateKeyInArrayShapeOnAnEnumIsReported' => [
                'code' => <<<'PHP'
                    <?php

                    /**
                     * @psalm-type Attributes = array{
                     *   name: string,
                     *   email: string,
                     *   email: string,
                     * }
                     */
                    enum A {
                        case FOO;
                    }
                    PHP,
                'error_message' => 'InvalidDocblock',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
        ];
    }
}
