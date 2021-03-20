<?php
namespace Psalm\Tests;

class GeneratorTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'generator' => [
                '<?php
                    /**
                     * @param  int  $start
                     * @param  int  $limit
                     * @param  int  $step
                     * @return Generator<int>
                     */
                    function xrange($start, $limit, $step = 1) {
                        for ($i = $start; $i <= $limit; $i += $step) {
                            yield $i;
                        }
                    }

                    $a = null;

                    /*
                     * Note that an array is never created or returned,
                     * which saves memory.
                     */
                    foreach (xrange(1, 9, 2) as $number) {
                        $a = $number;
                    }',
                'assertions' => [
                    '$a' => 'int|null',
                ],
            ],
            'generatorReturnType' => [
                '<?php
                    /** @return Generator<int, stdClass> */
                    function g():Generator { yield new stdClass; }

                    $g = g();',
                'assertions' => [
                    '$g' => 'Generator<int, stdClass, mixed, mixed>',
                ],
            ],
            'generatorWithReturn' => [
                '<?php
                    /**
                     * @return Generator<int,int>
                     * @psalm-generator-return string
                     */
                    function fooFoo(int $i): Generator {
                        if ($i === 1) {
                            return "bash";
                        }

                        yield 1;
                    }',
            ],
            'generatorSend' => [
                '<?php
                    /** @return Generator<int, string, DateTimeInterface, void> */
                    function g(): Generator {
                        $date = yield 1 => "string";
                        $date->format("m");
                    }
                    g()->send(new \DateTime("now"));
                ',
            ],
            'generatorSendInvalidArgument' => [
                '<?php
                    /** @return Generator<int, string, DateTimeInterface, void> */
                    function g(): Generator {
                        yield 1 => "string";
                    }
                    g()->send(1);
                ',
                'assertions' => [],
                'error_levels' => ['InvalidArgument'],
            ],
            'generatorDelegation' => [
                '<?php
                    /**
                     * @return Generator<int, int, mixed, int>
                     */
                    function count_to_ten(): Generator {
                        yield 1;
                        yield 2;
                        yield from [3, 4];
                        yield from new ArrayIterator([5, 6]);
                        yield from seven_eight();
                        return yield from nine_ten();
                    }

                    /**
                     * @return Generator<int, int>
                     */
                    function seven_eight(): Generator {
                        yield 7;
                        yield from eight();
                    }

                    /**
                     * @return Generator<int,int>
                     */
                    function eight(): Generator {
                        yield 8;
                    }

                    /**
                     * @return Generator<int,int, mixed, int>
                     */
                    function nine_ten(): Generator {
                        yield 9;
                        return 10;
                    }

                    $gen = count_to_ten();
                    foreach ($gen as $num) {
                        echo "$num ";
                    }
                    $gen2 = $gen->getReturn();',
                'assertions' => [
                    '$gen' => 'Generator<int, int, mixed, int>',
                    '$gen2' => 'int',
                ],
                'error_levels' => ['MixedAssignment'],
            ],
            'yieldFromArray' => [
                '<?php
                    /**
                     * @return Generator<int, int, mixed, void>
                     */
                    function Bar() : Generator {
                        yield from [1];
                    }',
            ],
            'generatorWithNestedYield' => [
                '<?php
                    function other_generator(): Generator {
                      yield "traffic";
                      return 1;
                    }
                    function foo(): Generator {
                      /** @var int */
                      $value = yield from other_generator();
                      var_export($value);
                    }',
            ],
            'generatorVoidReturn' => [
                '<?php
                    /**
                     * @return Generator
                     */
                    function generator2() : Generator {
                        if (rand(0,1)) {
                            return;
                        }
                        yield 2;
                    }',
            ],
            'returnType' => [
                '<?php
                    function takesInt(int $i) : void {
                        echo $i;
                    }

                    function takesString(string $s) : void {
                        echo $s;
                    }

                    /**
                     * @return Generator<int, string, mixed, int>
                     */
                    function other_generator() : Generator {
                        yield "traffic";
                        return 1;
                    }

                    /**
                     * @return Generator<int, string>
                     */
                    function foo() : Generator {
                        $a = yield from other_generator();
                        takesInt($a);
                    }

                    foreach (foo() as $s) {
                        takesString($s);
                    }',
            ],
            'expectNonNullableTypeWithYield' => [
                '<?php
                    function example() : Generator {
                        yield from [2];
                        return null;
                    }',
            ],
            'yieldFromIterable' => [
                '<?php
                    /**
                     * @param iterable<int, string> $s
                     * @return Generator<int, string>
                     */
                    function foo(iterable $s) : Traversable {
                        yield from $s;
                    }',
            ],
            'yieldWithReturn' => [
                '<?php
                    /** @return Generator<int, string, int, int> */
                    function gen(): Generator {
                        yield 3 => "abc";

                        $foo = 4;

                        return $foo;
                    }'
            ],
            'echoYield' => [
                '<?php
                    /** @return Generator<void, void, string, void> */
                    function gen(): Generator {
                        echo yield;
                    }'
            ],
            'yieldFromTwiceWithVoidSend' => [
                '<?php
                    /**
                     * @return \Generator<int, string, void, string>
                     */
                    function test(): \Generator {
                        return yield "value";
                    }

                    function load(string $rsa_key): \Generator {
                        echo (yield from test()) . (yield from test());
                        return 5;
                    }'
            ],
            'iteratorUnion' => [
                '<?php
                    /** @return Iterator|IteratorAggregate */
                    function getIteratorOrAggregate() {
                        yield 2;
                    }
                    echo json_encode(iterator_to_array(getIteratorOrAggregate()));'
            ],
            'yieldNonExistentClass' => [
                '<?php
                    class T {
                        private const FACTORIES = [
                            ClassNotExisting::class,
                        ];

                        function f() : Generator {
                            foreach (self::FACTORIES as $f) {
                                if (class_exists($f)) {
                                    yield new $f();
                                }
                            }
                        }
                    }',
                [],
                ['UndefinedClass']
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'shouldWarnAboutNoGeneratorReturn' => [
                '<?php
                    function generator2() : Generator {
                        if (rand(0,1)) {
                            return;
                        }
                        yield 2;
                    }

                    /**
                     * @psalm-suppress InvalidNullableReturnType
                     */
                    function notagenerator() : Generator {
                        if (rand(0, 1)) {
                            return;
                        }
                        return generator2();
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'expectNonNullableTypeWithNullReturn' => [
                '<?php
                    function example() : Generator {
                        yield from [2];
                        return null;
                    }

                    function example2() : Generator {
                        if (rand(0, 1)) {
                            return example();
                        }
                        return null;
                    }',
                'error_message' => 'NullableReturnStatement',
            ],
            'invalidIterator' => [
                '<?php
                    function example() : int {
                        return 0;
                    }

                    function example2() : Generator {
                        yield from example();
                    }',
                'error_message' => 'InvalidIterator',
            ],
            'rawObjectIteration' => [
                '<?php
                    class A {
                        /** @var ?string */
                        public $foo;
                    }
                    function example() : Generator {
                        $arr = new A;

                        yield from $arr;
                    }',
                'error_message' => 'RawObjectIteration',
            ],
            'possibleRawObjectIteration' => [
                '<?php
                    class A {
                        /** @var ?string */
                        public $foo;
                    }

                    class B extends A {}

                    function bar(A $a): void {}

                    function gen() : Generator {
                        $arr = [];

                        if (rand(0, 10) > 5) {
                            $arr[] = new A;
                        } else {
                            $arr = new B;
                        }

                        yield from $arr;
                    }',
                'error_message' => 'PossibleRawObjectIteration',
            ],
            'possibleRawObjectIterationFromIsset' => [
                '<?php
                    function foo(array $a) : Generator {
                        if (isset($a["a"]["b"])) {
                            yield from $a["a"];
                        }
                    }',
                'error_message' => 'PossibleRawObjectIteration',
            ],
        ];
    }
}
