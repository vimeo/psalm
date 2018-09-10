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
            'intersectionIterator' => [
                '<?php
                    /**
                     * @param \Traversable<int>&\Countable $object
                     */
                    function doSomethingUseful($object) : void {
                        echo count($object);
                        foreach ($object as $foo) {}
                    }'
            ],
            'arrayIteratorIteration' => [
                '<?php
                    class Item {
                      /**
                       * @var string
                       */
                      public $prop = "var";
                    }

                    class Collection implements IteratorAggregate {
                      /**
                       * @var Item[]
                       */
                      private $items = [];

                      public function add(Item $item): void
                      {
                          $this->items[] = $item;
                      }

                      /**
                        * @return \ArrayIterator<mixed, Item>
                        */
                      public function getIterator(): \ArrayIterator
                      {
                          return new \ArrayIterator($this->items);
                      }
                    }

                    $collection = new Collection();
                    $collection->add(new Item());
                    foreach ($collection as $item) {
                      echo $item->prop;
                    }'
            ],
            'foreachIntersectionTraversable' => [
                '<?php
                    /** @var Countable&Traversable<int> */
                    $c = null;
                    foreach ($c as $i) {}',
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

                    function bar(A $a): void {}

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
