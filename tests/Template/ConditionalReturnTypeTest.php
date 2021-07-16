<?php
namespace Psalm\Tests\Template;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits;

class ConditionalReturnTypeTest extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'conditionalReturnTypeSimple' => [
                '<?php

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
                    $c = (new A)->getAttribute($_GET["foo"]); // typed as string|array<string, string>',
                [
                    '$a' => 'string',
                    '$b' => 'array<string, string>',
                    '$c' => 'array<string, string>|string'
                ]
            ],
            'nestedConditionalOnIntReturnType' => [
                '<?php
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
                    }'
            ],
            'nestedConditionalOnStringsReturnType' => [
                '<?php
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
                    }'
            ],
            'nestedConditionalOnClassStringsReturnType' => [
                '<?php
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
                    }'
            ],
            'userlandVarExport' => [
                '<?php
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
                    }'
            ],
            'userlandAddition' => [
                '<?php
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
                [
                    '$int' => 'int',
                    '$float1' => 'float|int',
                    '$float2' => 'float',
                    '$float3' => 'float|int',
                ]
            ],
            'possiblyNullArgumentStillMatchesType' => [
                '<?php
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
                [
                    '$int' => 'int',
                ]
            ],
            'nestedClassConstantConditionalComparison' => [
                '<?php
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
                [
                    '$string' => 'string',
                    '$int' => 'int',
                    '$bool' => 'bool',
                    '$string2' => 'string',
                    '$int2' => 'int',
                    '$bool2' => 'bool',
                ]
            ],
            'variableConditionalSyntax' => [
                '<?php
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
                    }'
            ],
            'variableConditionalSyntaxWithNewlines' => [
                '<?php
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
                    }'
            ],
            'nullableClassString' => [
                '<?php
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
                    app()->test2();'
            ],
            'refineTypeInConditionalWithString' => [
                '<?php
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
                [
                    '$a' => 'string',
                    '$b' => 'string',
                ]
            ],
            'refineTypeInConditionalWithClassName' => [
                '<?php
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
                [
                    '$a' => 'AChild',
                    '$b' => 'A',
                ]
            ],
            'isTemplateArrayCheck' => [
                '<?php
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
                    }'
            ],
            'combineConditionalArray' => [
                '<?php
                    /**
                     * @psalm-return ($idOnly is true ? array<int> : array<stdClass>)
                     */
                    function test(bool $idOnly = false) {
                        if ($idOnly) {
                            return [0, 1];
                        }

                        return [new stdClass(), new stdClass()];
                    }'
            ],
            'promiseConditional' => [
                '<?php
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
                [
                    '$c1' => 'Promise<int>',
                    '$c2' => 'Promise<int>',
                ]
            ],
            'conditionalReturnShouldMatchInherited' => [
                '<?php
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
                    }'
            ],
            'conditionalOnArgCount' => [
                '<?php
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
                [
                    '$a' => 'false',
                    '$b' => 'string',
                    '$c' => 'string',
                ]
            ],
            'namespaceFuncNumArgs' => [
                '<?php
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
                '<?php
                    /**
                     * @psalm-return ($name is "foo" ? string : null)
                     */
                    function get(string $name) : ?string {
                        if ($name === "foo") {
                            return "hello";
                        }
                        return null;
                    }'
            ],
            'conditionalOrDefault' => [
                '<?php
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
                    }'
            ],
            'literalStringIsNotAClassString' => [
                '<?php
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
                    }'
            ],
            'inheritConditional' => [
                '<?php
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
                    }'
            ],
            'checkNullOrFalse' => [
                '<?php
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
                    }'
            ],
            'identicalToTrue' => [
                '<?php
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
                    }'
            ],
            'stringOrClassStringT' => [
                '<?php
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
                [
                    '$expect_mixed' => 'mixed',
                    '$expect_object' => 'object',
                    '$expect_a_object' => 'A',
                    '$expect_mixed_from_literal' => 'mixed',
                ]
            ],
            'isArryCheckOnTemplate' => [
                '<?php
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
                    }'
            ],
            'optional' => [
                '<?php
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
                    }'
            ],
            'reconcileCallableFunctionTemplateParam' => [
                '<?php
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
                    }'
            ],
            'reconcileCallableClassTemplateParam' => [
                '<?php
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
                    }'
            ],
            'classConstantDefault' => [
                '<?php
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
                         * @psalm-taint-source
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
                    echo (new Request)->getParams(Request::SOURCE_GET)["a"];'
            ],
            'conditionalArrayValues' => [
                '<?php
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
                    }'
            ],
        ];
    }
}
