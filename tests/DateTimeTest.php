<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class DateTimeTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'modify' => [
                'code' => '<?php
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
                'code' => '<?php
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
                    '$a' => 'DateTime&static',
                    '$b' => 'DateTimeImmutable&static',
                ],
            ],
            'modifyWithInvalidConstant' => [
                'code' => '<?php
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
                'code' => '<?php
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
            'modifyStaticReturn' => [
                'code' => '<?php

                    class Subclass extends DateTimeImmutable
                    {
                    }

                    $foo = new Subclass("2023-01-01 12:12:13");
                    $mod = $foo->modify("+7 days");
                    ',
                'assertions' => [
                    '$mod' => 'Subclass&static',
                ],
            ],
        ];
    }
}
