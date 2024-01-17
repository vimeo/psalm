<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Exception;
use Psalm\Context;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const PHP_VERSION_ID;

class DateTimeTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    public function testModifyWithInvalidConstant(): void
    {
        $context = new Context();

        if (PHP_VERSION_ID >= 8_03_00) {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('DateTime::modify(): Failed to parse time string (foo) at position 0 (f)');
        }

        $this->addFile(
            'somefile.php',
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
                $b = $dateTimeImmutable->modify(getString());',
        );

        $this->analyzeFile('somefile.php', $context);

        $this->assertSame('false', $context->vars_in_scope['$a']->getId(true));
        $this->assertSame('false', $context->vars_in_scope['$b']->getId(true));
    }

    public function testModifyWithBothConstant(): void
    {
        $context = new Context();

        if (PHP_VERSION_ID >= 8_03_00) {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('DateTime::modify(): Failed to parse time string (bar) at position 0 (b)');
        }

        $this->addFile(
            'somefile.php',
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
                $b = $dateTimeImmutable->modify(getString());',
        );

        $this->analyzeFile('somefile.php', $context);

        $this->assertSame('DateTime|false', $context->vars_in_scope['$a']->getId(false));
        $this->assertSame('DateTimeImmutable|false', $context->vars_in_scope['$b']->getId(false));
    }

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
                    '$a' => 'DateTime',
                    '$b' => 'DateTimeImmutable',
                ],
            ],
            'otherMethodAfterModify' => [
                'code' => '<?php
                    $datetime = new DateTime();
                    $dateTimeImmutable = new DateTimeImmutable();
                    $a = $datetime->modify("+1 day")->setTime(0, 0);
                    $b = $dateTimeImmutable->modify("+1 day")->setTime(0, 0);
                    ',
                'assertions' => [
                    '$a' => 'DateTime',
                    '$b' => 'DateTimeImmutable',
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
                    '$mod' => 'Subclass',
                ],
            ],
            'otherMethodAfterModifyStaticReturn' => [
                'code' => '<?php

                    class Subclass extends DateTimeImmutable
                    {
                    }

                    $datetime = new Subclass();
                    $mod = $datetime->modify("+1 day")->setTime(0, 0);
                    ',
                'assertions' => [
                    '$mod' => 'Subclass',
                ],
            ],
            'formatAfterModify' => [
                'code' => '<?php
                    $datetime = new DateTime();
                    $dateTimeImmutable = new DateTimeImmutable();
                    $a = $datetime->modify("+1 day")->format("Y-m-d");
                    $b = $dateTimeImmutable->modify("+1 day")->format("Y-m-d");
                    ',
                'assertions' => [
                    '$a' => 'false|string',
                    '$b' => 'string',
                ],
            ],
            'formatAfterModifyStaticReturn' => [
                'code' => '<?php

                    class Subclass extends DateTimeImmutable
                    {
                    }

                    $datetime = new Subclass();
                    $format = $datetime->modify("+1 day")->format("Y-m-d");
                    ',
                'assertions' => [
                    '$format' => 'string',
                ],
            ],
        ];
    }
}
