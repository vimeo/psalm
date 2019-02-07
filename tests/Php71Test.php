<?php
namespace Psalm\Tests;

class Php71Test extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return array
     */
    public function providerValidCodeParse()
    {
        return [
            'nullableReturnType' => [
                '<?php
                    function a(): ?string
                    {
                        return rand(0, 10) ? "elePHPant" : null;
                    }

                    $a = a();',
                'assertions' => [
                    '$a' => 'string|null',
                ],
            ],
            'nullableReturnTypeInDocblock' => [
                '<?php
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
                '<?php
                    function test(?string $name): ?string
                    {
                        return $name;
                    }

                    test("elePHPant");
                    test(null);',
            ],
            'protectedClassConst' => [
                '<?php
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
                '<?php
                    class A
                    {
                        private const IS_PRIVATE = 1;

                        function fooFoo(): int {
                            return A::IS_PRIVATE;
                        }
                    }',
            ],
            'publicClassConstFetch' => [
                '<?php
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
            'arrayDestructuring' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                    iterator(new SplFixedArray(5));',
            ],
            'traversableObject' => [
                '<?php
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
                '<?php
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
                '<?php
                    function foo(iterable $i): array {
                      if (!is_array($i)) {
                        $i = iterator_to_array($i, false);
                      }

                      return $i;
                    }',
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerInvalidCodeParse()
    {
        return [
            'invalidPrivateClassConstFetch' => [
                '<?php
                    class A
                    {
                        private const IS_PRIVATE = 1;
                    }

                    echo A::IS_PRIVATE;',
                'error_message' => 'InaccessibleClassConstant',
            ],
            'invalidPrivateClassConstFetchFromSubclass' => [
                '<?php
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
                '<?php
                    class A
                    {
                        protected const IS_PROTECTED = 1;
                    }

                    echo A::IS_PROTECTED;',
                'error_message' => 'InaccessibleClassConstant',
            ],
            'invalidIterableArg' => [
                '<?php
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
                '<?php
                    function foo(): void {

                    }',
                'error_message' => 'ReservedWord',
                [],
                false,
                '7.0'
            ],
            'objectDoesntWorkIn71' => [
                '<?php
                    function foo(): object {
                        return new stdClass();
                    }',
                'error_message' => 'ReservedWord',
                [],
                false,
                '7.0'
            ],
        ];
    }
}
