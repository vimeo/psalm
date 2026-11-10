<?php

declare(strict_types=1);

namespace Psalm\Tests\Template;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class ConditionalReturnTypeTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'arrayAccessOnAParamWithAConditionalType' => [
                'code' => '<?php
                    /**
                     * @template T of string|null
                     * @param T $str
                     * @param (T is null ? non-empty-list<string> : list<string>) $items
                     */
                    function test(array $items, ?string $str = null): string {
                        return $str ?? $items[0] ?? "";
                    }',
            ],
            'conditionalReturnTypeSimple' => [
                'code' => '<?php

                    class A {
                        /** @var array<string, string> */
                        private array $itemAttr = [];

                        /**
                         * @template T as ?string
                         * @param T $name
                         * @return string|string[]
                         * @psalm-return (T is string ? string : array<string, string>)
                         */
                        public function getAttribute(?string $name, string $default = "")
                        {
                            if (null === $name) {
                                return $this->itemAttr;
                            }
                            return isset($this->itemAttr[$name]) ? $this->itemAttr[$name] : $default;
                        }
                    }

                    $a = (new A)->getAttribute("colour", "red"); // typed as string
                    $b = (new A)->getAttribute(null); // typed as array<string, string>
                    /** @psalm-suppress MixedArgument */
                    $c = (new A)->getAttribute($GLOBALS["foo"]); // typed as string|array<string, string>',
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'array<string, string>',
                    '$c' => 'array<string, string>|string',
                ],
            ],
            'conditionalReturnTypeWithNullableBranches' => [
                'code' => '<?php
                    /**
                     * @return ($format is "U" ? ?int : ?string)
                     */
                    function formatDate(string $format) {
                        if ($format === "") {
                            return null;
                        }
                        return $format === "U" ? time() : date($format);
                    }

                    $a = formatDate("U");
                    $b = formatDate("Y-m-d");',
                'assertions' => [
                    '$a' => 'int|null',
                    '$b' => 'null|string',
                ],
            ],
            'conditionalReturnTypeWithIntersectionInIfBranch' => [
                'code' => '<?php
                    /**
                     * @return ($value is "" ? non-empty-string&lowercase-string : string)
                     */
                    function lower(string $value): string {
                        return $value === "" ? "a" : $value;
                    }

                    $a = lower("");',
                'assertions' => [
                    '$a===' => 'non-empty-lowercase-string',
                ],
            ],
            'negatedConditionalReturnType' => [
                'code' => '<?php
                    /**
                     * @return ($handle is not non-empty-string ? false : true)
                     */
                    function query(string $handle): bool {
                        return $handle !== "";
                    }

                    $a = query("");
                    $b = query("jquery");',
                'assertions' => [
                    '$a===' => 'false',
                    '$b===' => 'true',
                ],
            ],
            'conditionalReturnTypeOnValueOfTemplate' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $input
                     * @return array<key-of<T>, (value-of<T> is string ? non-empty-string : value-of<T>)>
                     */
                    function quote(array $input): array {
                        throw new RuntimeException();
                    }

                    /** @param array{a: "x", b: int} $x */
                    function f(array $x): array {
                        return quote($x);
                    }

                    $a = quote(["a" => "x", "b" => 1]);
                    $b = quote(["a" => 1, "b" => 2]);',
                'assertions' => [
                    '$a===' => "array<'a'|'b', 1|non-empty-string>",
                    '$b===' => "array<'a'|'b', 1|2>",
                ],
            ],
            'conditionalReturnTypeOnValueOfTemplateNarrowsEachBranch' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $input
                     * @return array<key-of<T>, (value-of<T> is string ? non-empty-string : value-of<T>)>
                     */
                    function quote(array $input): array {
                        throw new RuntimeException();
                    }

                    /**
                     * @template T of array
                     * @param T $input
                     * @return array<key-of<T>, (value-of<T> is not string ? value-of<T> : non-empty-string)>
                     */
                    function quoteUnlessNotString(array $input): array {
                        throw new RuntimeException();
                    }

                    /**
                     * @template T of array
                     * @param T $input
                     * @return (value-of<T> is int ? list<value-of<T>> : value-of<T>)
                     */
                    function ints(array $input) {
                        throw new RuntimeException();
                    }

                    /** @var array<string, int|string> */
                    $input = [];
                    $a = quote($input);
                    $b = quoteUnlessNotString($input);
                    $c = ints($input);',
                'assertions' => [
                    '$a===' => 'array<string, int|non-empty-string>',
                    '$b===' => 'array<string, int|non-empty-string>',
                    '$c===' => 'list<int>|string',
                ],
            ],
            'conditionalReturnTypeOnCandidateOverlappingTheCondition' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param T $value
                     * @return (T is non-empty-string ? "A" : "B")
                     */
                    function kind($value): string {
                        return is_string($value) && $value !== "" ? "A" : "B";
                    }

                    /**
                     * @template T of array
                     * @param T $input
                     * @return (value-of<T> is non-empty-string ? "A" : "B")
                     */
                    function valueKind(array $input): string {
                        throw new RuntimeException();
                    }

                    /** @var string|int */
                    $string_or_int = 1;
                    /** @var list<string|int> */
                    $list = [];
                    $a = kind($string_or_int);
                    $b = kind(1);
                    $c = kind("x");
                    $d = valueKind($list);',
                'assertions' => [
                    '$a===' => "'A'|'B'",
                    '$b===' => "'B'",
                    '$c===' => "'A'",
                    '$d===' => "'A'|'B'",
                ],
            ],
            'conditionalReturnTypeOnArrayThatMayBeEmpty' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, int>
                     * @param T $x
                     * @return (T is non-empty-array ? "ne" : "e")
                     */
                    function emptiness(array $x): string {
                        return $x ? "ne" : "e";
                    }

                    /**
                     * @param mixed $mixed
                     * @param array<string, int> $typed
                     */
                    function f($mixed, array $untyped, array $typed): void {
                        /** @psalm-suppress MixedArgument */
                        $a = emptiness($mixed);
                        /** @psalm-check-type-exact $a = "e"|"ne" */
                        /** @psalm-suppress MixedArgumentTypeCoercion */
                        $b = emptiness($untyped);
                        /** @psalm-check-type-exact $b = "e"|"ne" */
                        $c = emptiness($typed);
                        /** @psalm-check-type-exact $c = "e"|"ne" */
                        $d = emptiness(["a" => 1]);
                        /** @psalm-check-type-exact $d = "ne" */
                    }',
            ],
            'conditionalReturnTypeOnUnionSubjectWithNonEmptyArray' => [
                'code' => '<?php
                    /**
                     * @param mixed $value
                     * @return ($value is (bool|non-empty-array) ? false : ($value is array<never, never> ? true : bool))
                     */
                    function isBlank($value): bool {
                        return $value === [];
                    }

                    /** @param array<string, int> $array */
                    function f(array $array): void {
                        $empty = isBlank([]);
                        /** @psalm-check-type-exact $empty = true */
                        $nonEmpty = isBlank([1]);
                        /** @psalm-check-type-exact $nonEmpty = false */
                        $bool = isBlank(true);
                        /** @psalm-check-type-exact $bool = false */
                        $any = isBlank($array);
                        /** @psalm-check-type-exact $any = bool */
                    }',
            ],
            'conditionalReturnTypeOnIterableThatMayBeAnArray' => [
                'code' => '<?php
                    /**
                     * @template T of iterable<mixed>
                     * @param T $x
                     * @return (T is array<array-key, mixed> ? "Y" : "N")
                     */
                    function isArray(iterable $x): string {
                        return is_array($x) ? "Y" : "N";
                    }

                    /** @param mixed $mixed */
                    function f($mixed, iterable $iterable): void {
                        /** @psalm-suppress MixedArgument */
                        $a = isArray($mixed);
                        /** @psalm-check-type-exact $a = "N"|"Y" */
                        $b = isArray($iterable);
                        /** @psalm-check-type-exact $b = "N"|"Y" */
                        $c = isArray([1]);
                        /** @psalm-check-type-exact $c = "Y" */
                    }',
            ],
            'nestedConditionalOnIntReturnType' => [
                'code' => '<?php
                    /**
                     * @template T as int
                     * @param T $i
                     * @psalm-return (T is 0 ? string : (T is 1 ? int : bool))
                     */
                    function getDifferentType(int $i) {
                        if ($i === 0) {
                            return "hello";
                        }

                        if ($i === 1) {
                            return 5;
                        }

                        return true;
                    }',
            ],
            'nestedConditionalOnStringsReturnType' => [
                'code' => '<?php
                    /**
                     * @template T as string
                     * @param T $i
                     * @psalm-return (T is "0" ? string : (T is "1" ? int : bool))
                     */
                    function getDifferentType(string $i) {
                        if ($i === "0") {
                            return "hello";
                        }

                        if ($i === "1") {
                            return 5;
                        }

                        return true;
                    }',
            ],
            'nestedConditionalOnClassStringsReturnType' => [
                'code' => '<?php
                    class A {}
                    class B {}

                    /**
                     * @template T as string
                     * @param T $i
                     * @psalm-return (T is A::class ? string : (T is B::class ? int : bool))
                     */
                    function getDifferentType(string $i) {
                        if ($i === A::class) {
                            return "hello";
                        }

                        if ($i === B::class) {
                            return 5;
                        }

                        return true;
                    }',
            ],
            'userlandVarExport' => [
                'code' => '<?php
                    /**
                     * @template TReturnFlag as bool
                     * @param mixed $expression
                     * @param TReturnFlag $return
                     * @psalm-return (TReturnFlag is true ? string : void)
                     */
                    function my_var_export($expression, bool $return = false) {
                        if ($return) {
                            return var_export($expression, true);
                        }

                        var_export($expression);
                    }',
            ],
            'userlandAddition' => [
                'code' => '<?php
                    /**
                     * @template T as int|float
                     * @param T $a
                     * @param T $b
                     * @return int|float
                     * @psalm-return (T is int ? int : float)
                     */
                    function add($a, $b) {
                        return $a + $b;
                    }

                    $int = add(3, 5);
                    $float1 = add(2.5, 3);
                    $float2 = add(2.7, 3.1);
                    $float3 = add(3, 3.5);
                    /** @psalm-suppress PossiblyNullArgument */
                    $int = add(rand(0, 1) ? null : 1, 1);',
                'assertions' => [
                    '$int' => 'int',
                    '$float1' => 'float|int',
                    '$float2' => 'float',
                    '$float3' => 'float|int',
                ],
            ],
            'possiblyNullArgumentStillMatchesType' => [
                'code' => '<?php
                    /**
                     * @template T as int|float
                     * @param T $a
                     * @param T $b
                     * @return int|float
                     * @psalm-return (T is int ? int : float)
                     */
                    function add($a, $b) {
                        return $a + $b;
                    }

                    /** @psalm-suppress PossiblyNullArgument */
                    $int = add(rand(0, 1) ? null : 1, 4);',
                'assertions' => [
                    '$int' => 'int',
                ],
            ],
            'nestedClassConstantConditionalComparison' => [
                'code' => '<?php
                    class A {
                        const TYPE_STRING = 0;
                        const TYPE_INT = 1;

                        /**
                         * @template T as int
                         * @param T $i
                         * @psalm-return (
                         *     T is self::TYPE_STRING
                         *     ? string
                         *     : (T is self::TYPE_INT ? int : bool)
                         * )
                         */
                        public static function getDifferentType(int $i) {
                            if ($i === self::TYPE_STRING) {
                                return "hello";
                            }

                            if ($i === self::TYPE_INT) {
                                return 5;
                            }

                            return true;
                        }
                    }

                    $string = A::getDifferentType(0);
                    $int = A::getDifferentType(1);
                    $bool = A::getDifferentType(4);
                    $string2 = (new A)->getDifferentType(0);
                    $int2 = (new A)->getDifferentType(1);
                    $bool2 = (new A)->getDifferentType(4);',
                'assertions' => [
                    '$string' => 'string',
                    '$int' => 'int',
                    '$bool' => 'bool',
                    '$string2' => 'string',
                    '$int2' => 'int',
                    '$bool2' => 'bool',
                ],
            ],
            'variableConditionalSyntax' => [
                'code' => '<?php
                    /**
                     * @psalm-return ($i is 0 ? string : ($i is 1 ? int : bool))
                     */
                    function getDifferentType(int $i) {
                        if ($i === 0) {
                            return "hello";
                        }

                        if ($i === 1) {
                            return 5;
                        }

                        return true;
                    }',
            ],
            'variableConditionalSyntaxWithNewlines' => [
                'code' => '<?php
                    /**
                     * @psalm-return (
                     *      $i is 0
                     *      ? string
                     *      : (
                     *          $i is 1
                     *          ? int
                     *          : bool
                     *      )
                     *  )
                     */
                    function getDifferentType(int $i) {
                        if ($i === 0) {
                            return "hello";
                        }

                        if ($i === 1) {
                            return 5;
                        }

                        return true;
                    }',
            ],
            'nullableClassString' => [
                'code' => '<?php
                    namespace Foo;

                    class A {
                        public function test1() : void {}
                    }

                    class Application {
                        public function test2() : void {}
                    }

                    /**
                     * @template T of object
                     * @template TName as class-string<T>|null
                     *
                     * @psalm-param TName $className
                     *
                     * @psalm-return (TName is null ? Application : T)
                     */
                    function app(?string $className = null) {
                        if ($className === null) {
                            return new Application();
                        }

                        /**
                         * @psalm-suppress MixedMethodCall
                         */
                        return new $className();
                    }

                    app(A::class)->test1();
                    app()->test2();',
            ],
            'refineTypeInConditionalWithString' => [
                'code' => '<?php
                    /**
                     * @template TInput
                     *
                     * @param TInput $input
                     *
                     * @return (TInput is string ? TInput : \'hello\')
                     */
                    function foobaz($input): string {
                        if (is_string($input)) {
                            return $input;
                        }

                        return "hello";
                    }

                    $a = foobaz("boop");
                    $b = foobaz(4);',
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'string',
                ],
            ],
            'refineTypeInConditionalWithClassName' => [
                'code' => '<?php
                    class A {}
                    class AChild extends A {}
                    class B {}

                    /**
                     * @template TInput as object
                     *
                     * @param TInput $input
                     *
                     * @return (TInput is A ? TInput : A)
                     */
                    function foobaz(object $input): A {
                        if ($input instanceof A) {
                            return $input;
                        }

                        return new A();
                    }

                    $a = foobaz(new AChild());
                    $b = foobaz(new B());',
                'assertions' => [
                    '$a' => 'AChild',
                    '$b' => 'A',
                ],
            ],
            'isTemplateArrayCheck' => [
                'code' => '<?php
                    /**
                     * @param string|array $pv_var
                     *
                     * @psalm-return ($pv_var is array ? array : string)
                     */
                    function test($pv_var) {
                        $return = $pv_var;
                        if(!is_array($pv_var)) {
                            $return = utf8_encode($pv_var);
                        }

                        return $return;
                    }',
            ],
            'combineConditionalArray' => [
                'code' => '<?php
                    /**
                     * @psalm-return ($idOnly is true ? array<int> : array<stdClass>)
                     */
                    function test(bool $idOnly = false) {
                        if ($idOnly) {
                            return [0, 1];
                        }

                        return [new stdClass(), new stdClass()];
                    }',
            ],
            'promiseConditional' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    class Promise {
                        /** @var T */
                        private $t;

                        /** @param T $t */
                        public function __construct($t) {
                            $this->t = $t;
                        }
                    }

                    /**
                     * @template T
                     * @extends Promise<T>
                     */
                    class Success extends Promise {}

                    /**
                     * @template TReturn
                     * @template TPromise
                     *
                     * @template T as Promise<TPromise>|TReturn
                     *
                     * @param callable(): T $callback
                     *
                     * @return Promise
                     * @psalm-return (T is Promise ? Promise<TPromise> : Promise<TReturn>)
                     */
                    function call(callable $callback): Promise {
                        $result = $callback();

                        if ($result instanceof Promise) {
                            return $result;
                        }

                        return new Promise($result);
                    }

                    $ret_int_promise = function (): Promise {
                        return new Success(9);
                    };

                    $c1 = call($ret_int_promise);

                    $c2 = call(function (): int {
                        return 42;
                    });',
                'assertions' => [
                    '$c1' => 'Promise<int>',
                    '$c2' => 'Promise<int>',
                ],
            ],
            'conditionalReturnShouldMatchInherited' => [
                'code' => '<?php
                    interface I {
                        public function test1(bool $idOnly): array;
                    }

                    class Test implements I
                    {
                        /**
                         * @template T1 as bool
                         * @param T1 $idOnly
                         * @psalm-return (T1 is true ? array : array)
                         */
                        public function test1(bool $idOnly): array {
                            return [];
                        }
                    }',
            ],
            'conditionalOnArgCount' => [
                'code' => '<?php
                    /**
                     * @return (func_num_args() is 0 ? false : string)
                     */
                    function zeroArgsFalseOneArgString(string $s = "") {
                        if (func_num_args() === 0) {
                            return false;
                        }

                        return $s;
                    }

                    $a = zeroArgsFalseOneArgString();
                    $b = zeroArgsFalseOneArgString("");
                    $c = zeroArgsFalseOneArgString("hello");',
                'assertions' => [
                    '$a' => 'false',
                    '$b' => 'string',
                    '$c' => 'string',
                ],
            ],
            'InheritFuncNumArgs' => [
                'code' => '<?php
                    abstract class A
                    {
                        /**
                         * @psalm-return (func_num_args() is 1 ? string : int)
                         */
                        public static function get(bool $a, ?bool $b = null)
                        {
                            if ($b) {
                                return 1;
                            }
                            return "";
                        }
                    }

                    class B extends A
                    {

                        public static function getB(bool $a): int
                        {
                            return self::get($a, true);
                        }
                    }',
            ],
            'namespaceFuncNumArgs' => [
                'code' => '<?php
                    namespace Foo;

                    /**
                     * @return (func_num_args() is 0 ? false : string)
                     */
                    function zeroArgsFalseOneArgString(string $s = "") {
                        if (func_num_args() === 0) {
                            return false;
                        }

                        return $s;
                    }

                    $a = zeroArgsFalseOneArgString("hello");',
            ],
            'nullableReturnType' => [
                'code' => '<?php
                    /**
                     * @psalm-return ($name is "foo" ? string : null)
                     */
                    function get(string $name) : ?string {
                        if ($name === "foo") {
                            return "hello";
                        }
                        return null;
                    }',
            ],
            'conditionalOrDefault' => [
                'code' => '<?php
                    /**
                     * @template TKey
                     * @template TValue
                     */
                    interface C {
                        /**
                         * @template TDefault
                         * @param TKey $key
                         * @param TDefault $default
                         * @return (
                         *     func_num_args() is 1
                         *     ? TValue
                         *     : TValue|TDefault
                         * )
                         */
                        public function get($key, $default = null);
                    }

                    /** @param C<string, DateTime> $c */
                    function getDateTime(C $c) : DateTime {
                        return $c->get("t");
                    }

                    /** @param C<string, DateTime> $c */
                    function getNullableDateTime(C $c) : ?DateTime {
                        return $c->get("t", null);
                    }',
            ],
            'literalStringIsNotAClassString' => [
                'code' => '<?php
                    interface SerializerInterface
                    {
                        /**
                         * Deserializes the given data to the specified type.
                         *
                         * @psalm-template TClass
                         * @psalm-template TType as \'array\'|class-string<TClass>
                         * @psalm-param TType $type
                         * @psalm-return (
                         *     TType is \'array\'
                         *     ? array
                         *     : TClass
                         * )
                         *
                         * @return mixed
                         */
                        public function deserialize(string $data, string $type);
                    }

                    function foo(SerializerInterface $i, string $data): Exception {
                        return $i->deserialize($data, Exception::class);
                    }

                    function bar(SerializerInterface $i, string $data): array {
                        return $i->deserialize($data, \'array\');
                    }',
            ],
            'inheritConditional' => [
                'code' => '<?php
                    /**
                     * @template E
                     */
                    interface AInterface {
                        /**
                         * @template T
                         * @template T2 as "int"|class-string<T>
                         * @param T2 $type
                         * @return (T2 is "int" ? static<int> : static<T>)
                         */
                        public static function ofType(string $type);
                    }


                    /**
                     * @template E
                     * @implements AInterface<E>
                     * @psalm-consistent-constructor
                     * @psalm-consistent-templates
                     */
                    class BClass implements AInterface {
                        protected string $type;

                        protected function __construct(string $type)
                        {
                            $this->type   = $type;
                        }

                        /**
                         * @template T
                         * @template T2 as "int"|class-string<T>
                         * @param T2 $type
                         * @return (T2 is "int" ? static<int> : static<T>)
                         */
                        public static function ofType(string $type) {
                            return new static($type);
                        }
                    }',
            ],
            'checkNullOrFalse' => [
                'code' => '<?php
                    /**
                     * @template T of mixed|false|null
                     * @param T $i
                     * @return (T is false ? no-return : T is null ? no-return : T)
                     */
                    function orThrow($i) {
                        if ($i === false || $i === null) {
                            throw new RuntimeException("Example");
                        }
                        return $i;
                    }',
            ],
            'identicalToTrue' => [
                'code' => '<?php
                    class A{
                        /**
                         * @psalm-return ($id_only is true ? int[] : string[])
                         */
                        public function get_liste_tdm_by_cod_s(bool $id_only = false): array {
                            if ($id_only === true) {
                                return [0];
                            }

                            return [""];
                        }
                    }',
            ],
            'stringOrClassStringT' => [
                'code' => '<?php
                    class A {}

                    /**
                     * @template T
                     * @param literal-string|class-string<T> $name
                     * @return ($name is class-string ? T : mixed)
                     */
                    function get(string $name) {
                        return;
                    }

                    $lowercase_a = "a";

                    /** @var class-string $class_string */
                    $class_string = "b";

                    /** @psalm-suppress MixedAssignment */
                    $expect_mixed = get($lowercase_a);
                    $expect_object = get($class_string);

                    $expect_a_object = get(A::class);

                    /** @psalm-suppress MixedAssignment */
                    $expect_mixed_from_literal = get("LiteralDirect");',
                'assertions' => [
                    '$expect_mixed' => 'mixed',
                    '$expect_object' => 'object',
                    '$expect_a_object' => 'A',
                    '$expect_mixed_from_literal' => 'mixed',
                ],
            ],
            'isArrayCheckOnTemplate' => [
                'code' => '<?php
                    /**
                     * @template TResult as string|list<string>
                     * @param TResult $result
                     * @return (TResult is array ? list<string> : string)
                     */
                    function recursion($result) {
                        if (\is_array($result)) {
                            return $result;
                        }

                        return strtoupper($result);
                    }',
            ],
            'optional' => [
                'code' => '<?php
                    class User {
                        public string $name = "Dave";
                    }

                    /** @return User|NullObject */
                    function takesNullableUser(?User $user) {
                        return optional($user);
                    }

                    class NullObject {
                        /**
                         * @return null
                         */
                        public function __call(string $_name, array $args) {
                            return null;
                        }

                        /**
                         * @return null
                         */
                        public function __get(string $s) {
                            return null;
                        }

                        public function __set(string $_name, string $_value) : void {
                        }
                    }

                    /**
                     * @template TVar as object|null
                     * @param TVar $var
                     * @return (TVar is object ? TVar : NullObject)
                     */
                    function optional($var) {
                        if ($var) {
                            return $var;
                        }

                        return new NullObject();
                    }',
            ],
            'reconcileCallableFunctionTemplateParam' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @template TOptionalClosure as (callable():T)|null
                     * @param TOptionalClosure $cb
                     * @return (TOptionalClosure is null ? int : T)
                     */
                    function f($cb) {
                        if (is_callable($cb)) {
                            return $cb();
                        }

                        return 1;
                    }',
            ],
            'reconcileCallableClassTemplateParam' => [
                'code' => '<?php
                    class C {
                        /**
                         * @template T
                         * @template TOptionalClosure as (callable():T)|null
                         * @param TOptionalClosure $cb
                         * @return (TOptionalClosure is null ? int : T)
                         */
                        public static function f($cb) {
                            if (is_callable($cb)) {
                                return $cb();
                            }

                            return 1;
                        }
                    }',
            ],
            'classConstantDefault' => [
                'code' => '<?php
                    class Request {
                        const SOURCE_GET = "GET";
                        const SOURCE_POST = "POST";
                        const SOURCE_BODY = "BODY";

                        private function getBody() : string {
                            return "";
                        }

                        /**
                         * @template TSource as self::SOURCE_*
                         * @param TSource $source
                         * @return (TSource is "BODY" ? object|list : array)
                         * @psalm-taint-source input
                         */
                        public function getParams(
                            string $source = self::SOURCE_GET
                        ) {
                            if ($source === "GET") {
                                return $_GET;
                            }

                            if ($source === "POST") {
                                throw new \UnexpectedValueException("bad");
                            }

                            /** @psalm-suppress MixedAssignment */
                            $decoded = json_decode($this->getBody(), false);

                            if (!is_object($decoded) && !is_array($decoded)) {
                                throw new \UnexpectedValueException("bad");
                            }

                            return $decoded;
                        }
                    }

                    /** @psalm-suppress MixedArgument */
                    echo (new Request)->getParams()["a"];

                    /** @psalm-suppress MixedArgument */
                    echo (new Request)->getParams(Request::SOURCE_GET)["a"];',
            ],
            'conditionalArrayValues' => [
                'code' => '<?php
                    /**
                     * @template TValue
                     * @template TIterable of ?iterable<TValue>
                     * @param TIterable $iterable
                     * @return (TIterable is null ? null : list<TValue>)
                     */
                    function toList(?iterable $iterable): ?array {
                        if (null === $iterable) {
                            return null;
                        }

                        if (is_array($iterable)) {
                            return array_values($iterable);
                        }

                        return iterator_to_array($iterable, false);
                    }',
            ],
            'dontChokeOnFalsyAssertionsWithTemplatesInLoop' => [
                'code' => '<?php
                    /**
                     * @psalm-return ($list_output is true ? list : array)
                     */
                    function scope(bool $list_output = true): array
                    {
                        for ($i = 0; $i < 5; $i++) {
                            $list_output ? [] : [];
                        }

                        return [];
                    }
                    ',
            ],
            'dontChokeOnFalsyAssertionsWithTemplatesOutsideLoop' => [
                'code' => '<?php
                    class A {}
                    class B {}

                    /**
                     * @psalm-return ($a is true ? list<A> : list<B>)
                     */
                    function get_liste_designation_client(bool $a = false) {
                        (!$a ? "a" : "b");
                        (!$a ? "a" : "b");
                        return [];
                    }
                    ',
            ],
            'strlenReturnsIntForLowercaseString' => [
                'code' => '<?php
                    /**
                     * @psalm-return (
                     *     $string is non-empty-string
                     *     ? positive-int
                     *     : int
                     * )
                     */
                    function strlen2(string $string) : int { return 1;}

                    function test(string $s): void {
                        if (strlen2(strtolower($s))) {
                            echo 1;
                        }
                    }
                    ',
            ],
            'returnTypeBasedOnPhpVersionId' => [
                'code' => '<?php
                    /**
                     * @psalm-return (PHP_VERSION_ID is int<70300, max> ? string : int)
                     */
                    function getSomething()
                    {
                        return mt_rand(1, 10) > 5 ? "a value" : 42;
                    }

                    /**
                     * @psalm-return (PHP_VERSION_ID is int<70100, max> ? string : int)
                     */
                    function getSomethingElse()
                    {
                        return mt_rand(1, 10) > 5 ? "a value" : 42;
                    }

                    $something = getSomething();
                    $somethingElse = getSomethingElse();
                ',
                'assertions' => [
                    '$something' => 'int',
                    '$somethingElse' => 'string',
                ],
                'ignored_issues' => [],
                'php_version' => '7.2',
            ],
            'ineritedreturnTypeBasedOnPhpVersionId' => [
                'code' => '<?php
                    class A {
                    /**
                     * @psalm-return (PHP_VERSION_ID is int<70300, max> ? string : int)
                     */
                    function getSomething()
                    {
                        return mt_rand(1, 10) > 5 ? "a value" : 42;
                    }

                    /**
                     * @psalm-return (PHP_VERSION_ID is int<70100, max> ? string : int)
                     */
                    function getSomethingElse()
                    {
                        return mt_rand(1, 10) > 5 ? "a value" : 42;
                    }
                    }

                    class B extends A {}

                    $class = new B();
                    $something = $class->getSomething();
                    $somethingElse = $class->getSomethingElse();
                ',
                'assertions' => [
                    '$something' => 'int',
                    '$somethingElse' => 'string',
                ],
                'ignored_issues' => [],
                'php_version' => '7.2',
            ],
            'nonEmptyLiteralString' => [
                'code' => '<?php
                    /**
                     * @param literal-string $string
                     * @psalm-return ($string is non-empty-literal-string ? string : int)
                     */
                    function getSomething(string $string)
                    {
                        if (!$string) {
                            return 1;
                        }

                        return "";
                    }

                    /** @var literal-string $literalString */
                    $literalString;
                    $something = getSomething($literalString);
                    /** @var non-empty-literal-string $nonEmptyliteralString */
                    $nonEmptyliteralString;
                    $something2 = getSomething($nonEmptyliteralString);
                ',
                'assertions' => [
                    '$something' => 'int|string',
                    '$something2' => 'string',
                ],
                'ignored_issues' => [],
            ],
            'lowercaseStringIsNotNonEmptyWithUnion' => [
                'code' => '<?php
                    /**
                     * @param string|int $stringOrInt
                     * @psalm-return ($stringOrInt is (non-empty-string)|int ? string : int)
                     */
                    function getSomething($stringOrInt)
                    {
                        if (!$stringOrInt) {
                            return 1;
                        }
                        return "";
                    }
                    /** @var string $string */
                    $string;
                    $something = getSomething($string);
                    /** @var non-empty-string $nonEmptyString */
                    $nonEmptyString;
                    $something2 = getSomething($nonEmptyString);
                ',
                'assertions' => [
                    '$something' => 'int|string',
                    '$something2' => 'string',
                ],
                'ignored_issues' => [],
            ],
            'literalStringIsNotNonEmpty' => [
                'code' => '<?php
                    /**
                     * @param literal-string $literalString
                     * @psalm-return ($literalString is non-empty-string ? string : int)
                     */
                    function getSomething(string $literalString)
                    {
                        if (!$literalString) {
                            return 1;
                        }
                        return "";
                    }
                    /** @var literal-string $literalString */
                    $literalString;
                    $something = getSomething($literalString);
                    /** @var non-empty-literal-string $nonEmptyliteralString */
                    $nonEmptyliteralString;
                    $something2 = getSomething($nonEmptyliteralString);
                ',
                'assertions' => [
                    '$something' => 'int|string',
                    '$something2' => 'string',
                ],
                'ignored_issues' => [],
            ],
            'literalStringIsNotNonEmptyWithUnion' => [
                'code' => '<?php
                    /**
                     * @param literal-string|int $literalStringOrInt
                     * @psalm-return ($literalStringOrInt is non-empty-string|int ? string : int)
                     */
                    function getSomething($literalStringOrInt)
                    {
                        if (!$literalStringOrInt) {
                            return 1;
                        }
                        return "";
                    }
                    /** @var literal-string $literalString */
                    $literalString;
                    $something = getSomething($literalString);
                    /** @var non-empty-literal-string $nonEmptyliteralString */
                    $nonEmptyliteralString;
                    $something2 = getSomething($nonEmptyliteralString);
                ',
                'assertions' => [
                    '$something' => 'int|string',
                    '$something2' => 'string',
                ],
                'ignored_issues' => [],
            ],
            'lowercaseStringAndNotEmpty' => [
                'code' => '<?php
                    /**
                     * @param lowercase-string $string
                     * @psalm-return ($string is non-empty-string ? string : int)
                     */
                    function lowercaseIsNonEmpty($string)
                    {
                        if (!$string) {
                            return 1;
                        }
                        return "";
                    }
                    /**
                     * @param lowercase-string $string
                     * @psalm-return ($string is non-empty-lowercase-string ? string : int)
                     */
                    function lowercaseIsNonEmptyLowercase($string)
                    {
                        if (!$string) {
                            return 1;
                        }
                        return "";
                    }

                    /** @var lowercase-string $lowercaseString */
                    $lowercaseString;
                    /** @var non-empty-lowercase-string $nonEmptyLowercaseString */
                    $nonEmptyLowercaseString;

                    $lowercaseString1 = lowercaseIsNonEmpty($lowercaseString);
                    $lowercaseString2 = lowercaseIsNonEmptyLowercase($lowercaseString);

                    $nonEmptyLowercaseString1 = lowercaseIsNonEmpty($nonEmptyLowercaseString);
                    $nonEmptyLowercaseString2 = lowercaseIsNonEmptyLowercase($nonEmptyLowercaseString);
                ',
                'assertions' => [
                    '$lowercaseString1' => 'int|string',
                    '$lowercaseString2' => 'int|string',
                    '$nonEmptyLowercaseString1' => 'string',
                    '$nonEmptyLowercaseString2' => 'string',
                ],
                'ignored_issues' => [],
            ],
            'nestedConditionalReturnResolution' => [
                'code' => '<?php
                    /**
                     * @template T as int
                     * @param T $i
                     * @return (T is 1 ? string : (T is 2 ? int : (T is 3 ? float : bool)))
                     */
                    function h(int $i) {
                        if ($i === 1) { return "x"; }
                        if ($i === 2) { return 5; }
                        if ($i === 3) { return 1.5; }
                        return true;
                    }

                    /** @return float */
                    function t3() { return h(3); }
                    /** @return int */
                    function t2() { return h(2); }
                    /** @return string */
                    function t1() { return h(1); }

                    $one = t1();
                    $two = t2();
                    $three = t3();',
                'assertions' => [
                    '$one' => 'string',
                    '$two' => 'int',
                    '$three' => 'float',
                ],
            ],
            'conditionalReturnNeverOnListWithIterableArgument' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param iterable<mixed, T> $iterable
                     * @return ($iterable is list ? never : list<T>)
                     */
                    function iterableToList(iterable $iterable): array
                    {
                        return array_values(iterator_to_array($iterable));
                    }

                    /** @param iterable<int, string> $iterable */
                    function f(iterable $iterable): void {
                        $list = iterableToList($iterable);
                        /** @psalm-check-type-exact $list = list<string> */
                        echo count($list);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'emptyConditionalSubjectMatchesFalsyValues' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param T $value
                     * @return (T is empty ? "falsy" : "truthy")
                     */
                    function check(mixed $value): string {
                        return $value ? "truthy" : "falsy";
                    }

                    /**
                     * @template T
                     * @param T $value
                     * @return (T is empty ? T : never)
                     */
                    function onlyFalsy(mixed $value): mixed {
                        return $value;
                    }

                    function getBool(): bool { return (bool) rand(0, 1); }
                    function getString(): string { return (string) rand(0, 1); }
                    function getInt(): int { return rand(0, 1); }
                    function getFloat(): float { return rand(0, 1) / 2; }
                    function getMixed(): mixed { return rand(0, 1); }
                    function getNullableObject(): ?object { return null; }
                    function getObject(): object { return new stdClass(); }
                    function getArray(): array { return []; }
                    /** @return non-falsy-string */
                    function getNonFalsyString(): string { return "a"; }

                    $bool = check(getBool());
                    $false = check(false);
                    $true = check(true);
                    $null = check(null);
                    $zero = check(0);
                    $one = check(1);
                    $emptyString = check("");
                    $zeroString = check("0");
                    $string = check(getString());
                    $int = check(getInt());
                    $float = check(getFloat());
                    $mixed = check(getMixed());
                    $nullableObject = check(getNullableObject());
                    $object = check(getObject());
                    $emptyArray = check([]);
                    $array = check(getArray());
                    $nonFalsyString = check(getNonFalsyString());

                    // the else branch is never, so the code after the call is reachable only for falsy arguments
                    $onlyFalsyBool = onlyFalsy(getBool());
                    $onlyFalsyNull = onlyFalsy(null);
                    $onlyFalsyNullableObject = onlyFalsy(getNullableObject());',
                'assertions' => [
                    '$bool===' => "'falsy'|'truthy'",
                    '$false===' => "'falsy'",
                    '$true===' => "'truthy'",
                    '$null===' => "'falsy'",
                    '$zero===' => "'falsy'",
                    '$one===' => "'truthy'",
                    '$emptyString===' => "'falsy'",
                    '$zeroString===' => "'falsy'",
                    '$string===' => "'falsy'|'truthy'",
                    '$int===' => "'falsy'|'truthy'",
                    '$float===' => "'falsy'|'truthy'",
                    '$mixed===' => "'falsy'|'truthy'",
                    '$nullableObject===' => "'falsy'|'truthy'",
                    '$object===' => "'truthy'",
                    '$emptyArray===' => "'falsy'",
                    '$array===' => "'falsy'|'truthy'",
                    '$nonFalsyString===' => "'truthy'",
                    '$onlyFalsyBool===' => 'false',
                    '$onlyFalsyNull===' => 'null',
                    '$onlyFalsyNullableObject===' => 'null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'falsyConditionalInNamespaceAndNegated' => [
                'code' => '<?php
                    namespace App;

                    /**
                     * @template T
                     * @param T $value
                     * @return (T is falsy ? "falsy" : "truthy")
                     */
                    function check(mixed $value): string {
                        return $value ? "truthy" : "falsy";
                    }

                    /**
                     * @template T
                     * @param T $value
                     * @return (T is not empty ? "truthy" : "falsy")
                     */
                    function checkNegated(mixed $value): string {
                        return $value ? "truthy" : "falsy";
                    }

                    $zero = check(0);
                    $one = check(1);
                    $zeroNegated = checkNegated(0);
                    $oneNegated = checkNegated(1);',
                'assertions' => [
                    '$zero===' => "'falsy'",
                    '$one===' => "'truthy'",
                    '$zeroNegated===' => "'falsy'",
                    '$oneNegated===' => "'truthy'",
                ],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'conditionalOnAClassNamedFalsyIsNotTheFalsyKeyword' => [
                'code' => '<?php
                    final class falsy {}

                    /**
                     * @template T
                     * @param T $value
                     * @return (T is \\falsy ? "class" : "other")
                     */
                    function check(mixed $value): string {
                        return $value instanceof falsy ? "class" : "other";
                    }

                    $object = check(new falsy());
                    $zero = check(0);',
                'assertions' => [
                    '$object===' => "'class'",
                    '$zero===' => "'other'",
                ],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'conditionalOnAConstantOfAClassNamedFalsy' => [
                'code' => '<?php
                    final class falsy {
                        public const VALUE = 1;
                    }

                    /**
                     * @template T
                     * @param T $value
                     * @return (T is falsy::VALUE ? "match" : "other")
                     */
                    function check(mixed $value): string {
                        return $value === falsy::VALUE ? "match" : "other";
                    }

                    $one = check(1);
                    $zero = check(0);',
                'assertions' => [
                    '$one===' => "'match'",
                    '$zero===' => "'other'",
                ],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'falsyConditionalSubjectMatchesFalsyValues' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param T $value
                     * @return (T is falsy ? "falsy" : "truthy")
                     */
                    function check(mixed $value): string {
                        return $value ? "truthy" : "falsy";
                    }

                    /**
                     * @template T
                     * @param T $value
                     * @return (T is falsy ? T : never)
                     */
                    function onlyFalsy(mixed $value): mixed {
                        return $value;
                    }

                    function getBool(): bool { return (bool) rand(0, 1); }
                    function getString(): string { return (string) rand(0, 1); }
                    function getInt(): int { return rand(0, 1); }
                    function getFloat(): float { return rand(0, 1) / 2; }
                    function getMixed(): mixed { return rand(0, 1); }
                    function getNullableObject(): ?object { return null; }
                    function getObject(): object { return new stdClass(); }
                    function getArray(): array { return []; }
                    /** @return non-falsy-string */
                    function getNonFalsyString(): string { return "a"; }

                    $bool = check(getBool());
                    $false = check(false);
                    $true = check(true);
                    $null = check(null);
                    $zero = check(0);
                    $one = check(1);
                    $emptyString = check("");
                    $zeroString = check("0");
                    $string = check(getString());
                    $int = check(getInt());
                    $float = check(getFloat());
                    $mixed = check(getMixed());
                    $nullableObject = check(getNullableObject());
                    $object = check(getObject());
                    $emptyArray = check([]);
                    $array = check(getArray());
                    $nonFalsyString = check(getNonFalsyString());

                    // the else branch is never, so the code after the call is reachable only for falsy arguments
                    $onlyFalsyBool = onlyFalsy(getBool());
                    $onlyFalsyNull = onlyFalsy(null);
                    $onlyFalsyNullableObject = onlyFalsy(getNullableObject());',
                'assertions' => [
                    '$bool===' => "'falsy'|'truthy'",
                    '$false===' => "'falsy'",
                    '$true===' => "'truthy'",
                    '$null===' => "'falsy'",
                    '$zero===' => "'falsy'",
                    '$one===' => "'truthy'",
                    '$emptyString===' => "'falsy'",
                    '$zeroString===' => "'falsy'",
                    '$string===' => "'falsy'|'truthy'",
                    '$int===' => "'falsy'|'truthy'",
                    '$float===' => "'falsy'|'truthy'",
                    '$mixed===' => "'falsy'|'truthy'",
                    '$nullableObject===' => "'falsy'|'truthy'",
                    '$object===' => "'truthy'",
                    '$emptyArray===' => "'falsy'",
                    '$array===' => "'falsy'|'truthy'",
                    '$nonFalsyString===' => "'truthy'",
                    '$onlyFalsyBool===' => 'false',
                    '$onlyFalsyNull===' => 'null',
                    '$onlyFalsyNullableObject===' => 'null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'paramConditionalOnTypedCallableOfThisOrStatic' => [
                'code' => '<?php
                    final class Box {
                        /**
                         * @param (callable($this): mixed)|null $callback
                         * @return ($callback is null ? int : $this)
                         */
                        public function tapThis(?callable $callback = null) {
                            throw new RuntimeException();
                        }

                        /**
                         * @param (callable(static): mixed)|null $callback
                         * @return ($callback is null ? int : static)
                         */
                        public static function tapStatic(?callable $callback = null) {
                            throw new RuntimeException();
                        }
                    }

                    $box = new Box();
                    $this_ = $box->tapThis(fn (Box $_b): int => 1);
                    $null = $box->tapThis(null);
                    $none = $box->tapThis();
                    $instance = $box->tapStatic(fn (Box $_b): int => 1);
                    $static = Box::tapStatic(fn (Box $_b): int => 1);
                    $staticNone = Box::tapStatic();',
                'assertions' => [
                    '$this_===' => 'Box',
                    '$null===' => 'int',
                    '$none===' => 'int',
                    '$instance===' => 'Box',
                    '$static===' => 'Box',
                    '$staticNone===' => 'int',
                ],
            ],
            'paramConditionalOnTypedCallableOfStaticOnSubclassReceiver' => [
                'code' => '<?php
                    class Base {
                        /**
                         * @param (callable($this): mixed)|null $callback
                         * @return ($callback is null ? int : $this)
                         */
                        public function tap(?callable $callback = null) {
                            throw new RuntimeException();
                        }

                        /**
                         * @param (callable(static): mixed)|null $callback
                         * @return ($callback is null ? int : static)
                         */
                        public static function stap(?callable $callback = null) {
                            throw new RuntimeException();
                        }
                    }

                    final class FinalChild extends Base {}

                    class OpenChild extends Base {}

                    function openChild(): OpenChild {
                        return new OpenChild();
                    }

                    $final = (new FinalChild())->tap(fn (FinalChild $_x): int => 1);
                    $finalByBase = (new FinalChild())->tap(fn (Base $_x): int => 1);
                    $open = openChild()->tap(fn (OpenChild $_x): int => 1);
                    $finalStatic = FinalChild::stap(fn (FinalChild $_x): int => 1);
                    $openStatic = OpenChild::stap(fn (OpenChild $_x): int => 1);',
                'assertions' => [
                    '$final===' => 'FinalChild',
                    '$finalByBase===' => 'FinalChild',
                    '$open===' => 'OpenChild&static',
                    '$finalStatic===' => 'FinalChild',
                    '$openStatic===' => 'OpenChild',
                ],
            ],
            'paramConditionalOnTypedCallableOfThisKeepsTheTypeArgumentsOfTheReceiver' => [
                'code' => '<?php
                    /** @template T */
                    class Coll {
                        /**
                         * @param (callable($this): mixed)|null $callback
                         * @return ($callback is null ? int : $this)
                         */
                        public function tap(?callable $callback = null) {
                            throw new RuntimeException();
                        }
                    }

                    /** @var Coll<int> */
                    $coll = new Coll();
                    $tapped = $coll->tap(fn (Coll $_c): int => 1);',
                'assertions' => [
                    '$tapped===' => 'Coll<int>&static',
                ],
            ],
            'paramConditionalOnStaticParam' => [
                'code' => '<?php
                    class Node {
                        /**
                         * @param static|null $other
                         * @return ($other is null ? int : string)
                         */
                        public function cmp(?self $other = null) {
                            throw new RuntimeException();
                        }

                        /**
                         * @param array<static>|null $others
                         * @return ($others is null ? int : string)
                         */
                        public function cmpAll(?array $others = null) {
                            throw new RuntimeException();
                        }
                    }

                    final class Leaf extends Node {}

                    $leaf = new Leaf();
                    $one = $leaf->cmp($leaf);
                    $all = $leaf->cmpAll([$leaf]);
                    $none = $leaf->cmp();
                    $allNone = $leaf->cmpAll(null);',
                'assertions' => [
                    '$one===' => 'string',
                    '$all===' => 'string',
                    '$none===' => 'int',
                    '$allNone===' => 'int',
                ],
            ],
            'paramConditionalOnCallableOfStaticInConstructorAndSelfOut' => [
                'code' => '<?php
                    /** @template T */
                    final class Gen {
                        /**
                         * @param (callable(static): mixed)|null $callback
                         * @return ($callback is null ? Gen<int> : Gen<string>)
                         */
                        public function __construct(?callable $callback = null) {}
                    }

                    /** @template T */
                    final class Mut {
                        /**
                         * @param (callable(static): mixed)|null $callback
                         * @psalm-self-out ($callback is null ? Mut<int> : Mut<string>)
                         */
                        public function set(?callable $callback = null): void {}
                    }

                    $gen = new Gen(fn (Gen $_g): int => 1);
                    $genNone = new Gen();

                    /** @var Mut<bool> */
                    $mut = new Mut();
                    $mut->set(fn (Mut $_m): int => 1);

                    /** @var Mut<bool> */
                    $mutNone = new Mut();
                    $mutNone->set();',
                'assertions' => [
                    '$gen===' => 'Gen<string>',
                    '$genNone===' => 'Gen<int>',
                    '$mut===' => 'Mut<string>',
                    '$mutNone===' => 'Mut<int>',
                ],
            ],
            'untypedClosureParamOfCallableOfStaticIsTheCalledClass' => [
                'code' => '<?php
                    class Base {
                        /**
                         * @param (callable($this): mixed)|null $callback
                         * @return ($callback is null ? int : $this)
                         */
                        public function tap(?callable $callback = null) {
                            throw new RuntimeException();
                        }

                        /** @param callable(static): mixed $callback */
                        public function each(callable $callback): void {}
                    }

                    final class Items extends Base {}

                    final class Consumer {
                        public function run(Items $items): void {
                            $items->each(function ($item): void {
                                /** @psalm-check-type-exact $item = Items */
                            });
                        }
                    }

                    function run(Items $items): void {
                        $items->each(function ($item): void {
                            /** @psalm-check-type-exact $item = Items */
                        });
                    }

                    $tapped = (new Items())->tap(fn ($_i): int => 1);',
                'assertions' => [
                    '$tapped===' => 'Items',
                ],
            ],
            'paramConditionalKeepsTheStaticOfTheCallerInTheBound' => [
                'code' => '<?php
                    class Base {
                        public function run(Child $child): void {
                            $templated = $child->choose($this);
                            /** @psalm-check-type-exact $templated = int|string */
                        }
                    }

                    class Child extends Base {
                        /**
                         * @template T
                         * @param T $value
                         * @return ($value is Child ? int : string)
                         */
                        public function choose($value) {
                            throw new RuntimeException();
                        }
                    }',
            ],
            'paramConditionalOnStaticParamWithNonFinalReceiver' => [
                'code' => '<?php
                    class Node {
                        /**
                         * @param static|null $other
                         * @return ($other is null ? int : string)
                         */
                        public function cmp(?self $other = null) {
                            throw new RuntimeException();
                        }
                    }

                    $node = new Node();
                    $result = $node->cmp($node);',
                'assertions' => [
                    '$result===' => 'int|string',
                ],
                'ignored_issues' => ['ArgumentTypeCoercion'],
            ],
            'untypedClosureParamOfCallableOfStaticKeepsTheTypeArgumentsOfTheReceiver' => [
                'code' => '<?php
                    /** @template TValue */
                    final class FinalColl {
                        /**
                         * @template U
                         * @param callable(static): U $callback
                         * @return U
                         */
                        public function pipe(callable $callback) {
                            throw new RuntimeException();
                        }
                    }

                    /** @template TValue */
                    class Coll {
                        /**
                         * @template U
                         * @param callable(static): U $callback
                         * @return U
                         */
                        public function pipe(callable $callback) {
                            throw new RuntimeException();
                        }
                    }

                    /** @var FinalColl<int> */
                    $final = new FinalColl();
                    /** @var Coll<int> */
                    $open = new Coll();
                    $finalResult = $final->pipe(fn ($x) => $x);
                    $openResult = $open->pipe(fn ($x) => $x);',
                'assertions' => [
                    '$finalResult===' => 'FinalColl<int>',
                    '$openResult===' => 'Coll<int>&static',
                ],
            ],
            'untypedClosureParamOfCallableOfStaticOnTemplateReceiverKeepsTheTemplate' => [
                'code' => '<?php
                    class Box {
                        /**
                         * @template U
                         * @param callable(static): U $callback
                         * @return U
                         */
                        public function pipe(callable $callback) {
                            throw new RuntimeException();
                        }

                        /**
                         * @param (callable(static): mixed)|null $callback
                         * @return ($callback is null ? int : static)
                         */
                        public function tap(?callable $callback = null) {
                            throw new RuntimeException();
                        }
                    }

                    /**
                     * @template T of Box
                     * @param T $box
                     * @return T
                     */
                    function identity(Box $box): Box {
                        $result = $box->pipe(fn ($value) => $value);
                        /** @psalm-check-type-exact $result = T */;

                        return $box->tap(fn ($_value): int => 1);
                    }',
                'ignored_issues' => ['InvalidArgument'],
            ],
            'untypedClosureParamOfCallableOfStaticOnStaticCallOfTemplateClassKeepsTheTemplate' => [
                'code' => '<?php
                    class Box {
                        /**
                         * @template U
                         * @param callable(static): U $callback
                         * @return U
                         */
                        public static function pipe(callable $callback) {
                            throw new RuntimeException();
                        }
                    }

                    /**
                     * @template T of Box
                     * @param class-string<T> $class
                     * @return T
                     */
                    function identity(string $class): Box {
                        $result = $class::pipe(fn ($value) => $value);
                        /** @psalm-check-type-exact $result = T */;

                        return $result;
                    }',
                'ignored_issues' => ['InvalidArgument'],
            ],
            'untypedClosureParamOfCallableOfStaticInForwardingCallIsTheCallersStatic' => [
                'code' => '<?php
                    class Base {
                        /** @param callable(static): mixed $callback */
                        public function each(callable $callback): void {}

                        /** @param callable(static): mixed $callback */
                        public static function eachStatic(callable $callback): void {}
                    }

                    class Child extends Base {
                        public function onlyChild(): void {}

                        public function run(): void {
                            parent::each(function ($item): void {
                                /** @psalm-check-type-exact $item = Child&static */
                                $item->onlyChild();
                            });
                            self::each(function ($item): void {
                                /** @psalm-check-type-exact $item = Child&static */
                                $item->onlyChild();
                            });
                            static::each(function ($item): void {
                                /** @psalm-check-type-exact $item = Child&static */
                                $item->onlyChild();
                            });
                            parent::eachStatic(function ($item): void {
                                /** @psalm-check-type-exact $item = Child&static */
                                $item->onlyChild();
                            });
                            static::eachStatic(function ($item): void {
                                /** @psalm-check-type-exact $item = Child&static */
                                $item->onlyChild();
                            });
                        }
                    }',
            ],
            'paramConditionalOnStaticParamOfNamedStaticCallFromAnotherClass' => [
                'code' => '<?php
                    class Node {
                        /**
                         * @param static|null $other
                         * @return ($other is null ? int : string)
                         */
                        public static function cmp(?self $other = null) {
                            throw new RuntimeException();
                        }
                    }

                    class Leaf extends Node {}

                    final class Other {
                        public static function run(Leaf $leaf): void {
                            $result = Leaf::cmp($leaf);
                            /** @psalm-check-type-exact $result = string */;
                        }
                    }',
            ],
            'paramConditionalOnUntypedCallableOfStaticOnIntersectionReceiver' => [
                'code' => '<?php
                    interface HasName {}

                    class IBox {
                        /**
                         * @param (callable(static): mixed)|null $callback
                         * @return ($callback is null ? int : static)
                         */
                        public function tap(?callable $callback = null) {
                            throw new RuntimeException();
                        }
                    }

                    /** @return IBox&HasName */
                    function named(): IBox {
                        throw new RuntimeException();
                    }

                    $tapped = named()->tap(fn ($_x): int => 1);',
                'assertions' => [
                    '$tapped===' => 'IBox&HasName',
                ],
                'ignored_issues' => ['MissingClosureParamType'],
            ],
            'paramConditionalOnUntypedCallableOfStaticThroughMixinIsTheMixinUser' => [
                'code' => '<?php
                    class MixTarget {
                        /**
                         * @param (callable(static): mixed)|null $callback
                         * @return ($callback is null ? int : static)
                         */
                        public function tap(?callable $callback = null) {
                            throw new RuntimeException();
                        }
                    }

                    /** @mixin MixTarget */
                    class MixUser {
                        /**
                         * @param list<mixed> $args
                         * @return mixed
                         */
                        public function __call(string $name, array $args) {
                            throw new RuntimeException();
                        }
                    }

                    $tapped = (new MixUser())->tap(fn ($_t): int => 1);',
                'assertions' => [
                    '$tapped===' => 'MixUser&static',
                ],
                'ignored_issues' => ['InvalidArgument', 'MissingClosureParamType'],
            ],
            'typedClosureParamOfCallableOfStaticInTraitKeepsItsDeclaredType' => [
                'code' => '<?php
                    class Model {}

                    trait BuildsQueries {
                        public function paginate(): void {
                            $add = function (self $builder, int $i) use (&$add): void {
                                $builder->where(function (self $second) use ($add, $i): void {
                                    $add($second, $i + 1);
                                });
                            };

                            $add($this, 0);
                        }
                    }

                    /** @template TModel of Model */
                    class EloquentBuilder {
                        use BuildsQueries;

                        /**
                         * @param (Closure(static): mixed)|string $column
                         * @return $this
                         */
                        public function where($column) {
                            return $this;
                        }
                    }

                    class QueryBuilder {
                        use BuildsQueries;

                        /**
                         * @param Closure|string $column
                         * @return $this
                         */
                        public function where($column) {
                            return $this;
                        }
                    }',
                'ignored_issues' => ['InvalidArgument'],
            ],
            'paramConditionalOnClosureOfSupertypeParam' => [
                'code' => '<?php
                    final class Box {
                        /**
                         * @param (callable(static): mixed)|null $callback
                         * @return ($callback is null ? int : static)
                         */
                        public function tap(?callable $callback = null) {
                            throw new RuntimeException();
                        }
                    }

                    $box = new Box();
                    $object = $box->tap(fn (object $_x): int => 1);
                    $mixed = $box->tap(fn (mixed $_x): int => 1);',
                'assertions' => [
                    '$object===' => 'Box',
                    '$mixed===' => 'Box',
                ],
                'php_version' => '8.0',
            ],
            'paramConditionalOnClosureOfSupertypeParamInLoop' => [
                'code' => '<?php
                    final class Box {
                        /**
                         * @param (callable(static): mixed)|null $callback
                         * @return ($callback is null ? int : static)
                         */
                        public function tap(?callable $callback = null) {
                            throw new RuntimeException();
                        }
                    }

                    /** @param list<Box> $boxes */
                    function tapAll(array $boxes): void {
                        foreach ($boxes as $box) {
                            $tapped = $box->tap(fn (object $x): object => $x);
                            /** @psalm-check-type-exact $tapped = Box */;
                        }
                    }',
            ],
            'untypedClosureParamOfCallableOfStaticOnGenericReceiverIsTheReceiver' => [
                'code' => '<?php
                    class Model {}

                    final class User extends Model {}

                    /** @template TModel of Model */
                    class Builder {
                        /**
                         * @param (Closure(static): mixed)|string $column
                         * @return $this
                         */
                        public function where($column) {
                            return $this;
                        }
                    }

                    /** @param Builder<User> $query */
                    function scope(Builder $query): void {
                        $query->where(function ($sub): void {
                            /** @psalm-check-type-exact $sub = Builder<User>&static */
                            $sub->where("a");
                        });
                    }',
            ],
            'untypedClosureParamOfCallableOfGenericStaticInTrait' => [
                'code' => '<?php
                    /**
                     * @template TKey of array-key
                     * @template TValue
                     */
                    trait ChunksValues {
                        /** @return static<int, static<TKey, TValue>> */
                        public function chunkByValue() {
                            return $this->chunkWhile(function ($value, $_key, $chunk): bool {
                                /** @psalm-check-type-exact $chunk = Bag<int, mixed> */
                                return $chunk->last() === $value;
                            });
                        }
                    }

                    /**
                     * @template TKey of array-key
                     * @template TValue
                     */
                    final class Bag {
                        /** @use ChunksValues<TKey, TValue> */
                        use ChunksValues;

                        /**
                         * @param callable(TValue, TKey, static<int, TValue>): bool $callback
                         * @return static<int, static<TKey, TValue>>
                         */
                        public function chunkWhile(callable $callback) {
                            throw new RuntimeException();
                        }

                        /** @return TValue|null */
                        public function last() {
                            throw new RuntimeException();
                        }
                    }',
            ],
            'untypedClosureParamOfCallableOfStaticInNamedCallOfInstanceMethodIsTheCallersStatic' => [
                'code' => '<?php
                    class Base {
                        /** @param callable(static): mixed $callback */
                        public function each(callable $callback): void {}
                    }

                    class Child extends Base {
                        public function onlyChild(): void {}

                        public function run(): void {
                            Base::each(function ($item): void {
                                /** @psalm-check-type-exact $item = Child&static */
                                $item->onlyChild();
                            });
                        }
                    }',
            ],
            'paramConditionalOnUntypedCallableOfStaticInParentCall' => [
                'code' => '<?php
                    class Base {
                        /**
                         * @param (callable(static): mixed)|null $callback
                         * @return ($callback is null ? int : static)
                         */
                        public function tap(?callable $callback = null) {
                            throw new RuntimeException();
                        }
                    }

                    class Child extends Base {
                        public function onlyChild(): void {}

                        public function run(): void {
                            $tapped = parent::tap(function ($item): void {
                                /** @psalm-check-type-exact $item = Child&static */
                                $item->onlyChild();
                            });
                            /** @psalm-check-type-exact $tapped = Child&static */
                            $tapped->onlyChild();
                        }
                    }',
            ],
            'paramConditionalOnUntypedCallableOfStaticInNamedStaticCall' => [
                'code' => '<?php
                    class SC {
                        /**
                         * @param (callable(static): mixed)|null $callback
                         * @return ($callback is null ? int : string)
                         */
                        public static function scmp(?callable $callback = null) {
                            throw new RuntimeException();
                        }

                        public function onlySC(): void {}
                    }

                    $result = SC::scmp(function ($x): void {
                        /** @psalm-check-type-exact $x = SC */
                        $x->onlySC();
                    });',
                'assertions' => [
                    '$result===' => 'string',
                ],
            ],
            'paramConditionalOnUntypedCallableOfStaticInConstructor' => [
                'code' => '<?php
                    /** @template T */
                    final class NGen {
                        /**
                         * @param (callable(static): mixed)|null $callback
                         * @return ($callback is null ? NGen<int> : NGen<string>)
                         */
                        public function __construct(?callable $callback = null) {}

                        public function onlyGen(): void {}
                    }

                    $gen = new NGen(function ($g): void {
                        /** @psalm-check-type-exact $g = NGen */
                        $g->onlyGen();
                    });',
                'assertions' => [
                    '$gen===' => 'NGen<string>',
                ],
            ],
            'staticReturnOfNamedCallOfInstanceMethodIsTheCallersStatic' => [
                'code' => '<?php
                    class RBase {
                        /** @return static */
                        public function me() {
                            return $this;
                        }
                    }

                    class RChild extends RBase {
                        public function run(): void {
                            $me = RBase::me();
                            /** @psalm-check-type-exact $me = RChild&static */
                            $me->run();
                        }
                    }',
            ],
            'classStringOfStaticReturnOfNamedCallOfInstanceMethodIsTheCallersStatic' => [
                'code' => '<?php
                    class RBase {
                        /** @return class-string<static> */
                        public function name(): string {
                            return static::class;
                        }
                    }

                    class RChild extends RBase {
                        public function run(): void {
                            $name = RBase::name();
                            /** @psalm-check-type-exact $name = class-string<RChild&static> */
                            echo $name;
                        }
                    }',
            ],
            'firstClassCallableOfNamedCallOfInstanceMethodReturnsTheCallersStatic' => [
                'code' => '<?php
                    class RBase {
                        /** @return static */
                        public function me() {
                            return $this;
                        }
                    }

                    class RChild extends RBase {
                        public function run(): void {
                            $me = RBase::me(...);
                            $result = $me();
                            /** @psalm-check-type-exact $result = RChild&static */
                            $result->run();
                        }
                    }',
                'php_version' => '8.1',
            ],
            'firstClassCallableOfMagicStaticMethodFromASubclass' => [
                'code' => '<?php
                    class Base {
                        /** @param list<mixed> $args */
                        public static function __callStatic(string $name, array $args): mixed {
                            return null;
                        }
                    }

                    class Child extends Base {
                        public function run(): void {
                            $magic = Base::magic(...);
                            /** @psalm-check-type $magic = Closure */
                            $magic();
                        }
                    }',
                'php_version' => '8.1',
            ],
            'staticParamOfConstructorOfNonFinalClass' => [
                'code' => '<?php
                    class Node {
                        /** @param static|null $parent */
                        public function __construct(?self $parent = null) {}
                    }

                    $root = new Node();
                    $child = new Node($root);',
                'assertions' => [
                    '$child===' => 'Node',
                ],
            ],
            'untypedClosureParamOfCallableOfStaticOnThisOfTemplateClassKeepsItsTemplate' => [
                'code' => '<?php
                    class Model {}

                    /** @template T of Model */
                    class Coll {
                        /** @param callable(static): mixed $callback */
                        public function each(callable $callback): void {}

                        /** @return T */
                        public function first() {
                            throw new RuntimeException();
                        }

                        public function run(): void {
                            $this->each(function ($item): void {
                                $first = $item->first();
                                /** @psalm-check-type-exact $first = T */
                                echo 1;
                            });
                        }
                    }',
            ],
            'untypedClosureParamOfCallableOfStaticInTraitUsedByTwoClassesHasNoIssue' => [
                'code' => '<?php
                    trait Filters {
                        public function run(): void {
                            $this->where(function ($query): void {
                                $query->extra();
                            });
                        }
                    }

                    final class EloquentBuilder {
                        use Filters;

                        /**
                         * @param (Closure(static): mixed)|string $column
                         * @return $this
                         */
                        public function where($column) {
                            return $this;
                        }

                        public function extra(): void {}
                    }

                    final class QueryBuilder {
                        use Filters;

                        /**
                         * @param (Closure(static): mixed)|string $column
                         * @return $this
                         */
                        public function where($column) {
                            return $this;
                        }

                        public function extra(): void {}
                    }',
            ],
            'untypedClosureParamOfCallableOfStaticOnUnionReceiver' => [
                'code' => '<?php
                    class Model {}

                    /** @template TModel of Model */
                    class EloquentBuilder {
                        /**
                         * @param (Closure(static): mixed)|string $column
                         * @return $this
                         */
                        public function where($column) {
                            return $this;
                        }
                    }

                    class QueryBuilder {
                        /**
                         * @param Closure|string $column
                         * @return $this
                         */
                        public function where($column) {
                            return $this;
                        }
                    }

                    /** @param EloquentBuilder<Model>|QueryBuilder $query */
                    function scope($query): void {
                        $query->where(function ($q): void {
                            $q->where("a");
                        });
                    }',
            ],
            'typedClosureParamOfCallableOfStaticOnThisIsTheLateStaticClass' => [
                'code' => '<?php
                    class Base {
                        /** @param callable(static): mixed $callback */
                        public function each(callable $callback): void {}

                        public function run(): void {
                            $this->each(function (Base $item): static {
                                /** @psalm-check-type-exact $item = Base&static */
                                return $item;
                            });
                        }
                    }',
            ],
            'untypedClosureParamOfCallableOfStaticOnEnumCaseIsTheEnum' => [
                'code' => '<?php
                    enum Suit {
                        case Hearts;
                        case Spades;

                        /** @param static $other */
                        public function same($other): bool {
                            return $this === $other;
                        }

                        /** @param callable(static): bool $callback */
                        public function check(callable $callback): bool {
                            throw new RuntimeException();
                        }

                        /**
                         * @param static|null $other
                         * @return ($other is null ? int : string)
                         */
                        public function cmp($other = null) {
                            throw new RuntimeException();
                        }
                    }

                    $same = Suit::Hearts->same(Suit::Spades);
                    $r = Suit::Hearts->cmp(Suit::Spades);
                    $checked = Suit::Hearts->check(function ($suit): bool {
                        /** @psalm-check-type-exact $suit = Suit */
                        return $suit === Suit::Spades;
                    });
                    $typed = Suit::Hearts->check(function (Suit $s): bool {
                        /** @psalm-check-type-exact $s = Suit */
                        return $s === Suit::Spades;
                    });',
                'assertions' => [
                    '$same===' => 'bool',
                    '$r===' => 'string',
                ],
                'php_version' => '8.1',
            ],
            'untypedClosureParamOfCallableOfStaticIntersectionIsTheReceiverClass' => [
                'code' => '<?php
                    class Foo {
                        /** @param callable(static&Countable): mixed $callback */
                        public function each(callable $callback): void {}
                    }

                    class Child extends Foo {
                        public function onlyChild(): int {
                            return 1;
                        }
                    }

                    function run(Child $child): void {
                        $child->each(function ($x): void {
                            /** @psalm-check-type-exact $x = Countable&Child&static */
                            $x->onlyChild();
                        });
                    }',
            ],
            'untypedClosureParamOfCallableOfStaticIntersectionOnThisOfGenericSubclass' => [
                'code' => '<?php
                    /** @template T */
                    class Coll {
                        /** @param callable(static&Countable): mixed $callback */
                        public function each(callable $callback): void {}
                    }

                    /** @extends Coll<int> */
                    abstract class IntColl extends Coll implements Countable {
                        public function run(): void {
                            $this->each(function ($x): void {
                                /** @psalm-check-type-exact $x = IntColl&static&Countable */
                                $x->run();
                            });
                        }
                    }',
            ],
            'untypedClosureParamOfCallableOfStaticIntersectionWithGenericIsTheReceiverClass' => [
                'code' => '<?php
                    /** @template T */
                    interface G {}

                    class Foo {
                        /** @param callable(static&G<int>): mixed $callback */
                        public function gen(callable $callback): void {}
                    }

                    class Child extends Foo {
                        public function onlyChild(): void {}
                    }

                    function run(Child $child): void {
                        $child->gen(function ($x): void {
                            /** @psalm-check-type-exact $x = G<int>&Child&static */
                            $x->onlyChild();
                        });
                    }',
            ],
            'untypedClosureParamOfCallableOfStaticInForwardingCallFromStaticMethodIsTheCallersStatic' => [
                'code' => '<?php
                    class Base {
                        /** @param callable(static): mixed $callback */
                        public static function each(callable $callback): void {}
                    }

                    class Child extends Base {
                        public function onlyChild(): void {}

                        public static function run(): void {
                            parent::each(function ($item): void {
                                /** @psalm-check-type-exact $item = Child&static */
                                $item->onlyChild();
                            });
                            static::each(function ($item): void {
                                /** @psalm-check-type-exact $item = Child&static */
                                $item->onlyChild();
                            });
                        }
                    }

                    final class FinalChild extends Base {
                        public function onlyFinalChild(): void {}

                        public static function run(): void {
                            self::each(function ($item): void {
                                /** @psalm-check-type-exact $item = FinalChild */
                                $item->onlyFinalChild();
                            });
                        }
                    }',
            ],
            'typedClosureParamsOnUnionReceiverWithAHintFromOneAtom' => [
                'code' => '<?php
                    class ZA {
                        /** @param Closure(static): mixed $callback */
                        public function where(Closure $callback): void {}
                    }

                    class ZB {
                        public function where(Closure $callback): void {}
                    }

                    /** @param ZA|ZB $query */
                    function scope($query): void {
                        $query->where(function (ZA $q): void {
                            /** @psalm-check-type-exact $q = ZA */
                            echo 1;
                        });
                        $query->where(function (object $_q): void {});
                    }',
            ],
            'typedClosureParamOfCallableOfStaticOnRefinedReceiverIsTheClass' => [
                'code' => '<?php
                    final class Box {
                        /** @param Closure(static): mixed $callback */
                        public function visit(Closure $callback): void {}

                        public function __get(string $_name): string {
                            return "";
                        }
                    }

                    /** @param Box&object{value: string} $box */
                    function demo(Box $box): void {
                        $box->visit(function (Box $item): void {
                            /** @psalm-check-type-exact $item = Box */
                            echo 1;
                        });
                    }',
            ],
            'closureParamOfCallableOfGenericStaticOnSubclassBindingTheTemplatesIsThatClass' => [
                'code' => '<?php
                    /** @template T */
                    class Bag {
                        /** @param Closure(static<int>): void $callback */
                        public function each(Closure $callback): void {}
                    }

                    /** @extends Bag<int> */
                    final class IntBag extends Bag {}

                    (new IntBag())->each(function ($bag): void {
                        /** @psalm-check-type-exact $bag = IntBag */
                        echo 1;
                    });
                    (new IntBag())->each(function (IntBag $bag): void {
                        /** @psalm-check-type-exact $bag = IntBag */
                        echo 1;
                    });',
            ],
            'untypedClosureParamOfCallableOfGenericStaticKeepsArgsCompletedByTemplateDefaults' => [
                'code' => '<?php
                    /**
                     * @template A
                     * @template B = string
                     */
                    final class Box {
                        /** @param Closure(static<int>): void $callback */
                        public static function each(Closure $callback): void {}
                    }

                    Box::each(function ($box): void {
                        /** @psalm-check-type-exact $box = Box<int, string> */
                        echo 1;
                    });',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'conditionalReturnTypeWithoutIsTypeIsReportedInsteadOfCrashing' => [
                'code' => '<?php
                    /**
                     * @param ?int $key
                     * @return ($key is ? int : string)
                     */
                    function f(?int $key = null) {
                        return $key === null ? "a" : $key;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'typedClosureParamOfCallableOfStaticKeepsItsDeclaredType' => [
                'code' => '<?php
                    class Base {
                        /** @param callable(static): mixed $callback */
                        public function each(callable $callback): void {
                            $callback($this);
                        }

                        public function run(Base $other): void {
                            $other->each(function (Base $x): static {
                                return $x;
                            });
                        }
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'untypedClosureParamOfCallableOfStaticInTraitIsNotSharedWithOtherUsers' => [
                'code' => '<?php
                    trait Filters {
                        public function run(): void {
                            $this->where(function ($query): void {
                                $query->extra();
                            });
                        }
                    }

                    final class EloquentBuilder {
                        use Filters;

                        /**
                         * @param (Closure(static): mixed)|string $column
                         * @return $this
                         */
                        public function where($column) {
                            return $this;
                        }

                        public function extra(): void {}
                    }

                    final class QueryBuilder {
                        use Filters;

                        /**
                         * @param Closure|string $column
                         * @return $this
                         */
                        public function where($column) {
                            return $this;
                        }
                    }',
                'error_message' => 'Method QueryBuilder::extra does not exist',
            ],
            'staticParamOfCallOnAnotherInstanceIsNotTheCallersStatic' => [
                'code' => '<?php
                    class Base {
                        /**
                         * @param static|null $other
                         * @return ($other is null ? int : string)
                         */
                        public function cmp($other = null) {
                            throw new RuntimeException();
                        }

                        public function run(Child $child): void {
                            $child->cmp($this);
                        }
                    }

                    class Child extends Base {}',
                'error_message' => 'Argument 1 of Child::cmp expects Child&static|null, but parent type Base&static provided',
            ],
            'typedClosureParamOfCallableOfStaticOnResultOfStaticReturnKeepsItsDeclaredType' => [
                'code' => '<?php
                    class Base {
                        /** @return static */
                        public function me() {
                            return $this;
                        }

                        /** @param callable(static): mixed $callback */
                        public function each(callable $callback): void {}

                        public function run(Base $other): void {
                            $other->me()->each(function (Base $x): static {
                                return $x;
                            });
                        }
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'untypedClosureParamOnUnionReceiverIsEachAtomInAPassWithoutHint' => [
                'code' => '<?php
                    class ZA {
                        /** @param Closure(static): mixed $c */
                        public function where(Closure $c): void {}

                        public function onlyA(): void {}
                    }

                    class ZB {
                        public function where(Closure $c): void {}
                    }

                    final class ZUser {
                        /** @param ZA|ZB $x */
                        public function plain($x): void {
                            $x->where(function ($q): void {
                                $q->onlyA();
                            });
                        }
                    }',
                'error_message' => 'Method ZB::onlyA does not exist',
            ],
        ];
    }
}
