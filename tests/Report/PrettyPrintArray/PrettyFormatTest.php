<?php

namespace Psalm\Tests\Report\PrettyPrintArray;

use Generator;
use Psalm\Report\PrettyPrintArray\PrettyFormat;
use Psalm\Tests\TestCase;

class PrettyFormatTest extends TestCase
{
    /**
     * @dataProvider providerValidPayload
     */
    public function testFormat(string $payload, string $expected): void
    {
        $this->markTestSkipped('Needs to fix');

        $sut = new PrettyFormat();
        $actual = $sut->format($payload);

        $this->assertSame($expected, $actual);
    }

    /**
     * Because bug I think: https://psalm.dev/r/ccb7da7a53
     * @psalm-suppress InvalidReturnType
     * @return Generator<int, array{string, string, string}>
     */
    public function providerValidPayload(): Generator
    {
        yield [
                'field:',
                'field: '
        ];

        yield [
            'field:value',
            'field: value'
        ];

        $expected = <<<"EOT"
        field: value,
        field2: value2
        EOT;

        yield [
            'field:value,field2:value2',
            $expected
        ];

        $expected = <<<"EOT"
        field: value,
        field2: value2,
        arr1: array {
         foo: bar
        }
        EOT;

        yield [
            'field:value,field2:value2, arr1: array{foo:bar} ',
            $expected
        ];

        $expected = <<<"EOT"
        field: value,
        field2: value2,
        arr1: array {
         arr2: array {
          foo: bar
         }
        }
        EOT;

        yield [
            'field:value,field2:value2, arr1: array{arr2: array{foo:bar}} ',
            $expected
        ];

        $expected = <<<"EOT"
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

        yield [
            'field:value,field2:value2, arr1: array{ arr2: array{ arr3: array{ foo:bar } } }',
            $expected
        ];

        yield [
            'field:value,field2:value2,arr1:array{arr2:array{arr3:array{foo:bar}}}',
            $expected
        ];

        yield [
            'field:value,field2:value2,arr1:array<arr2:array<arr3:array<foo:bar>>>',
            $expected
        ];

        $expected = <<<"EOT"
        field: value,
        field2: value2,
        arr1: array {
         arr2: array {
          arr3: array {
           psalm-key: bar
          }
         }
        }
        EOT;

        yield [
            'field:value,field2:value2, arr1: array{ arr2: array{ arr3: array{ array-key:bar } } }',
            $expected
        ];
    }
}
