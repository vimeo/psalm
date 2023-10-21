<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class GeneratorTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'generator' => [
                'code' => '<?php
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
                'code' => '<?php
                    /** @return Generator<int, stdClass> */
                    function g():Generator { yield new stdClass; }

                    $g = g();',
                'assertions' => [
                    '$g' => 'Generator<int, stdClass, mixed, mixed>',
                ],
            ],
            'generatorSend' => [
                'code' => '<?php
                    /** @return Generator<int, string, DateTimeInterface, void> */
                    function g(): Generator {
                        $date = yield 1 => "string";
                        $date->format("m");
                    }
                    g()->send(new \DateTime("now"));
                ',
            ],
            'generatorSendInvalidArgument' => [
                'code' => '<?php
                    /** @return Generator<int, string, DateTimeInterface, void> */
                    function g(): Generator {
                        yield 1 => "string";
                    }
                    g()->send(1);
                ',
                'assertions' => [],
                'ignored_issues' => ['InvalidArgument'],
            ],
            'generatorDelegation' => [
                'code' => '<?php
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
                'ignored_issues' => ['MixedAssignment'],
            ],
            'yieldFromArray' => [
                'code' => '<?php
                    /**
                     * @return Generator<int, int, mixed, void>
                     */
                    function Bar() : Generator {
                        yield from [1];
                    }',
            ],
            'generatorWithNestedYield' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    function example() : Generator {
                        yield from [2];
                        return null;
                    }',
            ],
            'yieldFromIterable' => [
                'code' => '<?php
                    /**
                     * @param iterable<int, string> $s
                     * @return Generator<int, string>
                     */
                    function foo(iterable $s) : Traversable {
                        yield from $s;
                    }',
            ],
            'yieldWithReturn' => [
                'code' => '<?php
                    /** @return Generator<int, string, int, int> */
                    function gen(): Generator {
                        yield 3 => "abc";

                        $foo = 4;

                        return $foo;
                    }',
            ],
            'echoYield' => [
                'code' => '<?php
                    /** @return Generator<void, void, string, void> */
                    function gen(): Generator {
                        echo yield;
                    }',
            ],
            'yieldFromTwiceWithVoidSend' => [
                'code' => '<?php
                    /**
                     * @return \Generator<int, string, void, string>
                     */
                    function test(): \Generator {
                        return yield "value";
                    }

                    function load(string $rsa_key): \Generator {
                        echo (yield from test()) . (yield from test());
                        return 5;
                    }',
            ],
            'iteratorUnion' => [
                'code' => '<?php
                    /** @return Iterator|IteratorAggregate */
                    function getIteratorOrAggregate() {
                        yield 2;
                    }
                    echo json_encode(iterator_to_array(getIteratorOrAggregate()));',
            ],
            'yieldNonExistentClass' => [
                'code' => '<?php
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
                'assertions' => [],
                'ignored_issues' => ['UndefinedClass'],
            ],
            'fillTemplatesForIteratorFromGenerator' => [
                'code' => '<?php
                    /**
                     * @return Generator<int, string>
                     */
                    function generator(): Generator
                    {
                        yield "test";
                    }

                    $iterator = new NoRewindIterator(generator());
                    ',
                'assertions' => [
                    '$iterator' => 'NoRewindIterator<int, string, Generator<int, string, mixed, mixed>>',
                ],
            ],
            'detectYieldInNew' => [
                'code' => '<?php
                    /** @psalm-suppress MissingClosureReturnType */
                    $_a = function() { return new RuntimeException(yield "a"); };
                    ',
                'assertions' => [
                    '$_a' => 'pure-Closure():Generator<int, string, mixed, RuntimeException>',
                ],
            ],
            'detectYieldInArray' => [
                'code' => '<?php
                    /** @psalm-suppress MissingClosureReturnType */
                    $_a = function() { return [yield "a"]; };
                    ',
                'assertions' => [
                    '$_a' => 'pure-Closure():Generator<int, string, mixed, list{string}>',
                ],
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'shouldWarnAboutNoGeneratorReturn' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    function example() : int {
                        return 0;
                    }

                    function example2() : Generator {
                        yield from example();
                    }',
                'error_message' => 'InvalidIterator',
            ],
            'rawObjectIteration' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
