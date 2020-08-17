<?php
namespace Psalm\Tests;

class TypeAnnotationTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'typeAliasBeforeClass' => [
                '<?php
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
            'classTypeAlias' => [
                '<?php
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
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse()
    {
        return [
            'invalidTypeAlias' => [
                '<?php
                    /**
                     * @psalm-type CoolType = A|B>
                     */

                    class A {}',
                'error_message' => 'InvalidDocblock',
            ],
            'typeAliasInObjectLike' => [
                '<?php
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
                /**
                 * @psalm-import-type PhoneType from Phone
                 */
                class User {}',
                'error_message' => 'UndefinedDocblockClass',
            ],
            'malformedImportMissingFrom' => [
                '<?php
                    /** @psalm-import-type Thing */
                    class C {}
                ',
                'error_message' => 'InvalidTypeImport',
            ],
            'malformedImportMissingSourceClass' => [
                '<?php
                    /** @psalm-import-type Thing from */
                    class C {}
                ',
                'error_message' => 'InvalidTypeImport',
            ],
            'malformedImportMisspelledFrom' => [
                '<?php
                    /** @psalm-import-type Thing morf */
                    class C {}
                ',
                'error_message' => 'InvalidTypeImport',
            ],
            'malformedImportMissingAlias' => [
                '<?php
                    /** @psalm-import-type Thing from Somewhere as */
                    class C {}
                ',
                'error_message' => 'InvalidTypeImport',
            ],
            'noCrashWithPriorReference' => [
                '<?php
                    /**
                     * @psalm-type _C=array{c:_CC}
                     * @psalm-type _CC=float
                     */
                    class A {

                    }',
                'error_message' => 'InvalidDocblock',
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
        ];
    }
}
