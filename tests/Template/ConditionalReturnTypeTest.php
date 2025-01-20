<?php

namespace Psalm\Tests\Template;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ConditionalReturnTypeTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
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
            'inheritedConditionalTemplatedReturnType' => [
                'code' => '<?php
                    /** @template InstanceType */
                    interface ContainerInterface
                    {
                        /**
                         * @template TRequestedInstance extends InstanceType
                         * @param class-string<TRequestedInstance>|string $name
                         * @return ($name is class-string ? TRequestedInstance : InstanceType)
                         */
                        public function build(string $name): mixed;
                    }

                    /**
                     * @template InstanceType
                     * @template-implements ContainerInterface<InstanceType>
                     */
                    abstract class MixedContainer implements ContainerInterface
                    {
                        /** @param InstanceType $instance */
                        public function __construct(private readonly mixed $instance)
                        {}

                        public function build(string $name): mixed
                        {
                            return $this->instance;
                        }
                    }

                    /**
                     * @template InstanceType of object
                     * @template-extends MixedContainer<InstanceType>
                     */
                    abstract class ObjectContainer extends MixedContainer
                    {
                        public function build(string $name): object
                        {
                            return parent::build($name);
                        }
                    }

                    /** @template-extends ObjectContainer<stdClass> */
                    final class SpecificObjectContainer extends ObjectContainer
                    {
                    }

                    final class SpecificObject extends stdClass {}

                    $container = new SpecificObjectContainer(new stdClass());
                    $object = $container->build(SpecificObject::class);
                    $nonSpecificObject = $container->build("whatever");

                    /** @var ObjectContainer<object> $container */
                    $container = null;
                    $justObject = $container->build("whatever");
                    $specificObject = $container->build(stdClass::class);
                ',
                'assertions' => [
                    '$object===' => 'SpecificObject',
                    '$nonSpecificObject===' => 'stdClass',
                    '$justObject===' => 'object',
                    '$specificObject===' => 'stdClass',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
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
        ];
    }
}
