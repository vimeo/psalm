<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class Php71Test extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'nullableReturnType' => [
                'code' => '<?php
                    function a(): ?string
                    {
                        return rand(0, 10) ? "elePHPant" : null;
                    }

                    $a = a();',
                'assertions' => [
                    '$a' => 'null|string',
                ],
            ],
            'nullableReturnTypeInDocblock' => [
                'code' => '<?php
                    /** @return ?string */
                    function a() {
                        return rand(0, 10) ? "elePHPant" : null;
                    }

                    $a = a();',
                'assertions' => [
                    '$a' => 'null|string',
                ],
            ],
            'nullableArgument' => [
                'code' => '<?php
                    function test(?string $name): ?string
                    {
                        return $name;
                    }

                    test("elePHPant");
                    test(null);',
            ],
            'protectedClassConst' => [
                'code' => '<?php
                    class A
                    {
                        protected const IS_PROTECTED = 1;
                    }

                    class B extends A
                    {
                        function fooFoo(): int {
                            return A::IS_PROTECTED;
                        }
                    }',
            ],
            'privateClassConst' => [
                'code' => '<?php
                    class A
                    {
                        private const IS_PRIVATE = 1;

                        function fooFoo(): int {
                            return A::IS_PRIVATE;
                        }
                    }',
            ],
            'publicClassConstFetch' => [
                'code' => '<?php
                    class A
                    {
                        public const IS_PUBLIC = 1;
                        const IS_ALSO_PUBLIC = 2;
                    }

                    class B extends A
                    {
                        function fooFoo(): int {
                            echo A::IS_PUBLIC;
                            return A::IS_ALSO_PUBLIC;
                        }
                    }

                    echo A::IS_PUBLIC;
                    echo A::IS_ALSO_PUBLIC;',
            ],
            'arrayDestructuringList' => [
                'code' => '<?php
                    $data = [
                        [1, "Tom"],
                        [2, "Fred"],
                    ];

                    // list() style
                    list($id1, $name1) = $data[0];

                    // [] style
                    [$id2, $name2] = $data[1];',
                'assertions' => [
                    '$id1' => 'int',
                    '$name1' => 'string',
                    '$id2' => 'int',
                    '$name2' => 'string',
                ],
            ],
            'arrayDestructuringInForeach' => [
                'code' => '<?php
                    $data = [
                        [1, "Tom"],
                        [2, "Fred"],
                    ];

                    // [] style
                    foreach ($data as [$id, $name]) {
                        echo $id;
                        echo $name;
                    }',
            ],
            'arrayDestructuringWithKeys' => [
                'code' => '<?php
                    $data = [
                        ["id" => 1, "name" => "Tom"],
                        ["id" => 2, "name" => "Fred"],
                    ];

                    // list() style
                    list("id" => $id1, "name" => $name1) = $data[0];

                    // [] style
                    ["id" => $id2, "name" => $name2] = $data[1];',
                'assertions' => [
                    '$id1' => 'int',
                    '$name1' => 'string',
                    '$id2' => 'int',
                    '$name2' => 'string',
                ],
            ],
            'arrayListDestructuringInForeachWithKeys' => [
                'code' => '<?php
                    $data = [
                        ["id" => 1, "name" => "Tom"],
                        ["id" => 2, "name" => "Fred"],
                    ];

                    // list() style
                    foreach ($data as list("id" => $id, "name" => $name)) {
                        $last_id = $id;
                        $last_name = $name;
                    }',
                'assertions' => [
                    '$last_id' => 'int',
                    '$last_name' => 'string',
                ],
            ],
            'arrayDestructuringInForeachWithKeys' => [
                'code' => '<?php
                    $data = [
                        ["id" => 1, "name" => "Tom"],
                        ["id" => 2, "name" => "Fred"],
                    ];

                    // [] style
                    foreach ($data as ["id" => $id, "name" => $name]) {
                        $last_id = $id;
                        $last_name = $name;
                    }',
                'assertions' => [
                    '$last_id' => 'int',
                    '$last_name' => 'string',
                ],
            ],
            'iterableArg' => [
                'code' => '<?php
                    /**
                     * @param  iterable<int, int> $iter
                     */
                    function iterator(iterable $iter): void
                    {
                        foreach ($iter as $val) {
                            //
                        }
                    }

                    iterator([1, 2, 3, 4]);
                    /** @psalm-suppress MixedArgumentTypeCoercion */
                    iterator(new SplFixedArray(5));',
            ],
            'traversableObject' => [
                'code' => '<?php
                    /**
                     * @implements Iterator<0, mixed>
                     */
                    class IteratorObj implements Iterator {
                        function rewind(): void {}
                        /** @return mixed */
                        function current() { return null; }
                        function key(): int { return 0; }
                        function next(): void {}
                        function valid(): bool { return false; }
                    }

                    function foo(\Traversable $t): void {
                    }

                    foo(new IteratorObj);',
            ],
            'iterableIsArrayOrTraversable' => [
                'code' => '<?php
                    function castToArray(iterable $arr): array {
                        if ($arr instanceof \Traversable) {
                            return iterator_to_array($arr, false);
                        }

                        return $arr;
                    }

                    function castToArray2(iterable $arr): array {
                        if (is_array($arr)) {
                            return $arr;
                        }

                        return iterator_to_array($arr, false);
                    }',
            ],
            'substituteIterable' => [
                'code' => '<?php
                    function foo(iterable $i): array {
                      if (!is_array($i)) {
                        $i = iterator_to_array($i, false);
                      }

                      return $i;
                    }',
            ],
            'iterator_to_arrayMixedKey' => [
                'code' => '<?php
                    /**
                     * @template TKey
                     * @template TValue
                     * @param Traversable<TKey, TValue> $traversable
                     * @return array<TValue>
                     */
                    function toArray(Traversable $traversable): array
                    {
                        return iterator_to_array($traversable);
                    }',
            ],
            'noReservedWordInDocblock' => [
                'code' => '<?php
                    /**
                     * @param Closure():(resource|false) $op
                     * @return resource|false
                     */
                    function create_resource($op) {
                        return $op();
                    }',
            ],
            'arrayDestructuringOnArrayObject' => [
                'code' => '<?php
                    $var = new ArrayObject([0 => "first", "dos" => "second"]);
                    [0 => $first, "dos" => $second] = $var;
                    echo $first;
                    echo $second;',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'invalidPrivateClassConstFetch' => [
                'code' => '<?php
                    class A
                    {
                        private const IS_PRIVATE = 1;
                    }

                    echo A::IS_PRIVATE;',
                'error_message' => 'InaccessibleClassConstant',
            ],
            'invalidPrivateClassConstFetchFromSubclass' => [
                'code' => '<?php
                    class A
                    {
                        private const IS_PRIVATE = 1;
                    }

                    class B extends A
                    {
                        function fooFoo(): int {
                            return A::IS_PRIVATE;
                        }
                    }',
                'error_message' => 'InaccessibleClassConstant',
            ],
            'invalidProtectedClassConstFetch' => [
                'code' => '<?php
                    class A
                    {
                        protected const IS_PROTECTED = 1;
                    }

                    echo A::IS_PROTECTED;',
                'error_message' => 'InaccessibleClassConstant',
            ],
            'invalidIterableArg' => [
                'code' => '<?php
                    /**
                     * @param  iterable<string> $iter
                     */
                    function iterator(iterable $iter): void
                    {
                        foreach ($iter as $val) {
                            //
                        }
                    }

                    class A {
                    }

                    iterator(new A());',
                'error_message' => 'InvalidArgument',
            ],
            'voidDoesntWorkIn70' => [
                'code' => '<?php
                    function foo(): void {

                    }',
                'error_message' => 'ReservedWord',
                'ignored_issues' => [],
                'php_version' => '7.0',
            ],
            'objectDoesntWorkIn71' => [
                'code' => '<?php
                    function foo(): object {
                        return new stdClass();
                    }',
                'error_message' => 'ReservedWord',
                'ignored_issues' => [],
                'php_version' => '7.0',
            ],
            'arrayDestructuringInvalidList' => [
                'code' => '<?php
                    $a = 42;

                    list($id1, $name1) = $a;',
                'error_message' => 'InvalidArrayOffset',
            ],
            'arrayDestructuringInvalidArray' => [
                'code' => '<?php
                    $a = 42;

                    [$id2, $name2] = $a;',
                'error_message' => 'InvalidArrayOffset',
            ],
        ];
    }
}
