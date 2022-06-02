<?php

namespace Psalm\Tests\Report\PrettyPrintArray;

use Generator;
use Psalm\Report\PrettyPrintArray\PrettyCompare;
use Psalm\Tests\HelperTest\HelperAssert;
use Psalm\Tests\TestCase;

class PrettyCompareTest extends TestCase
{
    use HelperAssert;

    /**
     * @dataProvider providerValidPayload
     */
    public function testCompare(string $inferred, string $declared, string $expected): void
    {
        $sut = new PrettyCompare();
        $actual = $sut->compare($inferred, $declared);

        $this->assertOutputPrettyPrintEquals($expected, $actual);
    }


    /**
     * @return Generator<int, array{string, string, string}>
     */
    public function providerValidPayload(): Generator
    {
        $inferred = <<<"EOT"
        field: value,
        field2: value2,
        arr1: array {
         arr2: array {
          arr3: array {
           foo: bar
          }
         }
        }
        EOT;

        $declared = <<<"EOT"
        field: value,
        field2: value2,
        arr1: array {
         arr2: array {
          arr3: array {
           foo: bar
          }
         }
        }
        EOT;

        $expected = <<<"EOT"
        | Expected                                           | Provided
        | ---                                                | ---
        | field: value,                                      | field: value,
        | field2: value2,                                    | field2: value2,
        | arr1: array {                                      | arr1: array {
        |  arr2: array {                                     |  arr2: array {
        |   arr3: array {                                    |   arr3: array {
        |    foo: bar                                        |    foo: bar
        |   }                                                |   }
        |  }                                                 |  }
        | }                                                  | }
        |                                                    |
        EOT;

        yield [$inferred, $declared, $expected];
    }
}
