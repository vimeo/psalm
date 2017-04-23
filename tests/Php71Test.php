<?php
namespace Psalm\Tests;

class Php71Test extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'nullable-return-type' => [
                '<?php
                    function a(): ?string
                    {
                        return rand(0, 10) ? "elePHPant" : null;
                    }
            
                    $a = a();',
                'assertions' => [
                    ['string|null' => '$a']
                ]
            ],
            'nullable-return-type-in-docblock' => [
                '<?php
                    /** @return ?string */
                    function a() {
                        return rand(0, 10) ? "elePHPant" : null;
                    }
            
                    $a = a();',
                'assertions' => [
                    ['null|string' =>'$a']
                ]
            ],
            'nullable-argument' => [
                '<?php
                    function test(?string $name) : ?string
                    {
                        return $name;
                    }
            
                    test("elePHPant");
                    test(null);'
            ],
            'protected-class-const' => [
                '<?php
                    class A
                    {
                        protected const IS_PROTECTED = 1;
                    }
            
                    class B extends A
                    {
                        function fooFoo() : int {
                            return A::IS_PROTECTED;
                        }
                    }',
            ],
            'private-class-const' => [
                '<?php
                    class A
                    {
                        private const IS_PRIVATE = 1;
            
                        function fooFoo() : int {
                            return A::IS_PRIVATE;
                        }
                    }'
            ],
            'public-class-const-fetch' => [
                '<?php
                    class A
                    {
                        public const IS_PUBLIC = 1;
                        const IS_ALSO_PUBLIC = 2;
                    }
            
                    class B extends A
                    {
                        function fooFoo() : int {
                            echo A::IS_PUBLIC;
                            return A::IS_ALSO_PUBLIC;
                        }
                    }
            
                    echo A::IS_PUBLIC;
                    echo A::IS_ALSO_PUBLIC;'
            ],
            'array-destructuring' => [
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
                    ['string|int' => '$id1'],
                    ['string|int' => '$name1'],
                    ['string|int' => '$id2'],
                    ['string|int' => '$name2']
                ]
            ],
            'array-destructuring-in-foreach' => [
                '<?php
                    $data = [
                        [1, "Tom"],
                        [2, "Fred"],
                    ];
            
                    // [] style
                    foreach ($data as [$id, $name]) {
                        echo $id;
                        echo $name;
                    }'
            ],
            'array-destructuring-with-keys' => [
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
                    ['int' => '$id1'],
                    ['string' => '$name1'],
                    ['int' => '$id2'],
                    ['string' => '$name2']
                ]
            ],
            'array-list-destructuring-in-foreach-with-keys' => [
                '<?php
                    $data = [
                        ["id" => 1, "name" => "Tom"],
                        ["id" => 2, "name" => "Fred"],
                    ];
            
                    $last_id = null;
                    $last_name = null;
            
                    // list() style
                    foreach ($data as list("id" => $id, "name" => $name)) {
                        $last_id = $id;
                        $last_name = $name;
                    }',
                'assertions' => [
                    ['null|int' => '$last_id'],
                    ['null|string' => '$last_name']
                ]
            ],
            'array-destructuring-in-foreach-with-keys' => [
                '<?php
                    $data = [
                        ["id" => 1, "name" => "Tom"],
                        ["id" => 2, "name" => "Fred"],
                    ];
            
                    $last_id = null;
                    $last_name = null;
            
                    // [] style
                    foreach ($data as ["id" => $id, "name" => $name]) {
                        $last_id = $id;
                        $last_name = $name;
                    }',
                'assertions' => [
                    ['null|int' => '$last_id'],
                    ['null|string' => '$last_name']
                ]
            ],
            'iterable-arg' => [
                '<?php
                    /**
                     * @param  iterable<int, int> $iter
                     */
                    function iterator(iterable $iter) : void
                    {
                        foreach ($iter as $val) {
                            //
                        }
                    }
            
                    iterator([1, 2, 3, 4]);
                    iterator(new SplFixedArray(5));'
            ],
            'traversable-object' => [
                '<?php
                    class IteratorObj implements Iterator {
                        function rewind() : void {}
                        /** @return mixed */
                        function current() { return null; }
                        function key() : int { return 0; }
                        function next() : void {}
                        function valid() : bool { return false; }
                    }
            
                    function foo(\Traversable $t) : void {
                    }
            
                    foo(new IteratorObj);'
            ]
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'invalid-private-class-const-fetch' => [
                '<?php
                    class A
                    {
                        private const IS_PRIVATE = 1;
                    }
            
                    echo A::IS_PRIVATE;',
                'error_message' => 'InaccessibleClassConstant'
            ],
            'invalid-private-class-const-fetch-from-subclass' => [
                '<?php
                    class A
                    {
                        private const IS_PRIVATE = 1;
                    }
            
                    class B extends A
                    {
                        function fooFoo() : int {
                            return A::IS_PRIVATE;
                        }
                    }',
                'error_message' => 'InaccessibleClassConstant'
            ],
            'invalid-protected-class-const-fetch' => [
                '<?php
                    class A
                    {
                        protected const IS_PROTECTED = 1;
                    }
            
                    echo A::IS_PROTECTED;',
                'error_message' => 'InaccessibleClassConstant'
            ],
            'invalid-iterable-arg' => [
                '<?php
                    /**
                     * @param  iterable<string> $iter
                     */
                    function iterator(iterable $iter) : void
                    {
                        foreach ($iter as $val) {
                            //
                        }
                    }
            
                    class A {
                    }
            
                    iterator(new A());',
                'error_message' => 'InvalidArgument'
            ]
        ];
    }
}
