<?php
namespace Psalm\Tests;

class TypeAnnotationTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'typeAliasBeforeClass' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                    }'
            ],
            'classTypeAliasImportWithAlias' => [
                '<?php
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
                    }'
            ],
            'classTypeAliasDirectUsage' => [
                '<?php
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
                    }'
            ],
            'classTypeAliasFromExternalNamespace' => [
                '<?php
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
                }'
            ],
            'importTypeForParam' => [
                '<?php
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
                    }'
            ],
            'usedInVarForForeach' => [
                '<?php
                /** @psalm-type _B=array{p1:string} */
                function e(array $a): void
                {
                    /** @var _B $elt */
                    foreach ($a as $elt) {
                        echo $elt["p1"];
                    }
                }'
            ],
            'objectWithPropertiesAlias' => [
                '<?php
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
                    }'
            ],
            'sameDocBlockTypeAliasAsTypeParameterForInterface' => [
                '<?php
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
                [
                    '$output' => 'string',
                ],
            ],
            'sameDocBlockTypeAliasAsTypeParameterForExtendedRegularClass' => [
                '<?php
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
                [
                    '$output' => 'string',
                ],
            ],
            'sameDocBlockTypeAliasAsTypeParameterForExtendedAbstractClass' => [
                '<?php
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
                [
                    '$output' => 'string',
                ],
            ],
            'importedTypeAliasAsTypeParameterForImplementation' => [
                '<?php
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
            'importedTypeAliasAsTypeParameterForExtendedClass' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                [
                    '$output' => 'string',
                ],
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'invalidTypeAlias' => [
                '<?php
                    namespace Barrr;

                    /**
                     * @psalm-type CoolType = A|B>
                     */

                    class A {}',
                'error_message' => 'InvalidDocblock',
            ],
            'typeAliasInTKeyedArray' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    namespace Barrr;

                    /**
                     * @psalm-import-type PhoneType from Phone
                     */
                    class User {}',
                'error_message' => 'UndefinedDocblockClass',
            ],
            'malformedImportMissingFrom' => [
                '<?php
                    namespace Barrr;

                    /** @psalm-import-type Thing */
                    class C {}
                ',
                'error_message' => 'InvalidTypeImport',
            ],
            'malformedImportMissingSourceClass' => [
                '<?php
                    namespace Barrr;

                    /** @psalm-import-type Thing from */
                    class C {}
                ',
                'error_message' => 'InvalidTypeImport',
            ],
            'malformedImportMisspelledFrom' => [
                '<?php
                    namespace Barrr;

                    /** @psalm-import-type Thing morf */
                    class C {}
                ',
                'error_message' => 'InvalidTypeImport',
            ],
            'malformedImportMissingAlias' => [
                '<?php
                    namespace Barrr;

                    /** @psalm-import-type Thing from Somewhere as */
                    class C {}
                ',
                'error_message' => 'InvalidTypeImport',
            ],
            'noCrashWithPriorReference' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php

                    /** @psalm-type Foo = string */
                    class A {}

                    /** @template T */
                    interface B {}

                    /** @implements B<Foo> */
                    class C implements B {}',
                'error_message' => 'UndefinedDocblockClass',
            ],
            'invalidTypeWhenNotImportedInsideAnotherTypeAlias' => [
                '<?php

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
        ];
    }
}
