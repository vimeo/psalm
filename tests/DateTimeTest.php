<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class DateTimeTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'modify' => [
                '<?php
                    function getString(): string
                    {
                        return "";
                    }

                    $datetime = new DateTime();
                    $dateTimeImmutable = new DateTimeImmutable();
                    $a = $datetime->modify(getString());
                    $b = $dateTimeImmutable->modify(getString());
                    ',
                'assertions' => [
                    '$a' => 'DateTime|false',
                    '$b' => 'DateTimeImmutable|false',
                ],
            ],
            'modifyWithValidConstant' => [
                '<?php
                    /**
                     * @return "+1 day"|"+2 day"
                     */
                    function getString(): string
                    {
                        return "+1 day";
                    }

                    $datetime = new DateTime();
                    $dateTimeImmutable = new DateTimeImmutable();
                    $a = $datetime->modify(getString());
                    $b = $dateTimeImmutable->modify(getString());
                    ',
                'assertions' => [
                    '$a' => 'DateTime',
                    '$b' => 'DateTimeImmutable',
                ],
            ],
            'modifyWithInvalidConstant' => [
                '<?php
                    /**
                     * @return "foo"|"bar"
                     */
                    function getString(): string
                    {
                        return "foo";
                    }

                    $datetime = new DateTime();
                    $dateTimeImmutable = new DateTimeImmutable();
                    $a = $datetime->modify(getString());
                    $b = $dateTimeImmutable->modify(getString());
                    ',
                'assertions' => [
                    '$a' => 'false',
                    '$b' => 'false',
                ],
            ],
            'modifyWithBothConstant' => [
                '<?php
                    /**
                     * @return "+1 day"|"bar"
                     */
                    function getString(): string
                    {
                        return "+1 day";
                    }

                    $datetime = new DateTime();
                    $dateTimeImmutable = new DateTimeImmutable();
                    $a = $datetime->modify(getString());
                    $b = $dateTimeImmutable->modify(getString());
                    ',
                'assertions' => [
                    '$a' => 'DateTime|false',
                    '$b' => 'DateTimeImmutable|false',
                ],
            ],
        ];
    }
}
