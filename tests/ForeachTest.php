<?php
namespace Psalm\Tests;

class ForeachTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'iteratorAggregateIteration' => [
                '<?php
                    class C implements IteratorAggregate
                    {
                        public function getIterator(): Iterator
                        {
                            return new ArrayIterator([]);
                        }
                    }

                    function loopT(Traversable $coll): void
                    {
                        foreach ($coll as $item) {}
                    }

                    function loopI(IteratorAggregate $coll): void
                    {
                        foreach ($coll as $item) {}
                    }

                    loopT(new C);
                    loopI(new C);',
                'assignments' => [],
                'error_levels' => [
                    'MixedAssignment', 'UndefinedThisPropertyAssignment',
                ],
            ],
            'bleedVarIntoOuterContext' => [
                '<?php
                    $tag = null;
                    foreach (["a", "b", "c"] as $tag) {
                    }',
                'assignments' => [
                    '$tag' => 'null|string',
                ],
            ],
            'bleedVarIntoOuterContextWithRedefinedAsNull' => [
                '<?php
                    $tag = null;
                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        $tag = null;
                      } else {
                        $tag = null;
                      }
                    }',
                'assignments' => [
                    '$tag' => 'null',
                ],
            ],
            'bleedVarIntoOuterContextWithRedefinedAsNullAndBreak' => [
                '<?php
                    $tag = null;
                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        $tag = null;
                        break;
                      } else {
                        $tag = null;
                        break;
                      }
                    }',
                'assignments' => [
                    '$tag' => 'null',
                ],
            ],
            'bleedVarIntoOuterContextWithBreak' => [
                '<?php
                    $tag = null;
                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        $tag = null;
                      } else {
                        break;
                      }
                    }',
                'assignments' => [
                    '$tag' => 'null|string',
                ],
            ],
            'bleedVarIntoOuterContextWithRedefineAndBreak' => [
                '<?php
                    $tag = null;
                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        $tag = null;
                      } else {
                        $tag = null;
                        break;
                      }
                    }',
                'assignments' => [
                    '$tag' => 'null',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'continueOutsideLoop' => [
                '<?php
                    continue;',
                'error_message' => 'ContinueOutsideLoop',
            ],
            'invalidIterator' => [
                '<?php
                    foreach (5 as $a) {

                    }',
                'error_message' => 'InvalidIterator',
            ],
            'rawObjectIteration' => [
                '<?php
                    class A {
                        /** @var ?string */
                        public $foo;
                    }

                    class B extends A {}

                    function bar(A $a) : void {}

                    $arr = [];

                    if (rand(0, 10) > 5) {
                        $arr[] = new A;
                    } else {
                        $arr = new B;
                    }

                    foreach ($arr as $a) {
                        bar($a);
                    }',
                'error_message' => 'RawObjectIteration',
            ],
        ];
    }
}
