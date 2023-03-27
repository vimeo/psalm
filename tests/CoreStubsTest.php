<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class CoreStubsTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        yield 'RecursiveArrayIterator::CHILD_ARRAYS_ONLY (#6464)' => [
            'code' => '<?php

            new RecursiveArrayIterator([], RecursiveArrayIterator::CHILD_ARRAYS_ONLY);',
        ];
        yield 'proc_open() named arguments' => [
            'code' => '<?php

            proc_open(
                command: "ls",
                descriptor_spec: [],
                pipes: $pipes,
                cwd: null,
                env_vars: null,
                options: null
            );',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];
        yield 'Iterating over \DatePeriod (#5954) PHP7 Traversable' => [
            'code' => '<?php

            $period = new DatePeriod(
                new DateTimeImmutable("now"),
                DateInterval::createFromDateString("1 day"),
                new DateTime("+1 week")
            );
            $dt = null;
            foreach ($period as $dt) {
                echo $dt->format("Y-m-d");
            }',
            'assertions' => [
                '$period' => 'DatePeriod<DateTimeImmutable>',
                '$dt' => 'DateTimeInterface|null',
            ],
            'ignored_issues' => [],
            'php_version' => '7.3',
        ];
        yield 'Iterating over \DatePeriod (#5954) PHP8 IteratorAggregate' => [
            'code' => '<?php

            $period = new DatePeriod(
                new DateTimeImmutable("now"),
                DateInterval::createFromDateString("1 day"),
                new DateTime("+1 week")
            );
            $dt = null;
            foreach ($period as $dt) {
                echo $dt->format("Y-m-d");
            }',
            'assertions' => [
                '$period' => 'DatePeriod<DateTimeImmutable>',
                '$dt' => 'DateTimeImmutable|null',
            ],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];
        yield 'Iterating over \DatePeriod (#5954), ISO string' => [
            'code' => '<?php

            $period = new DatePeriod("R4/2012-07-01T00:00:00Z/P7D");
            $dt = null;
            foreach ($period as $dt) {
                echo $dt->format("Y-m-d");
            }',
            'assertions' => [
                '$period' => 'DatePeriod<string>',
                '$dt' => 'DateTime|null',
            ],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];
        yield 'DatePeriod implements only Traversable on PHP 7' => [
            'code' => '<?php

            $period = new DatePeriod("R4/2012-07-01T00:00:00Z/P7D");
            if ($period instanceof IteratorAggregate) {}',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '7.3',
        ];
        yield 'DatePeriod implements IteratorAggregate on PHP 8' => [
            'code' => '<?php

            $period = new DatePeriod("R4/2012-07-01T00:00:00Z/P7D");
            if ($period instanceof IteratorAggregate) {}',
            'assertions' => [],
            'ignored_issues' => ['RedundantCondition'],
            'php_version' => '8.0',
        ];
        yield 'sprintf yields a non-empty-string for non-empty-string value' => [
            'code' => '<?php

            /**
             * @param non-empty-string $foo
             * @return non-empty-string
             */
            function foo(string $foo): string
            {
                return sprintf("%s", $foo);
            }
            ',
        ];
        yield 'sprintf yields a string for possible empty string param' => [
            'code' => '<?php

            $a = sprintf("%s", "");
            ',
            'assertions' => [
                '$a===' => 'string',
            ],
        ];
        yield 'json_encode returns a non-empty-string provided JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE' => [
            'code' => '<?php
                $a = json_encode([], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            ',
            'assertions' => [
                '$a===' => 'non-empty-string',
            ],
        ];
        yield 'json_encode returns a non-empty-string with JSON_THROW_ON_ERROR' => [
            'code' => '<?php
                $a = json_encode([], JSON_THROW_ON_ERROR | JSON_HEX_TAG);
                $b = json_encode([], JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
                $c = json_encode([], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $d = json_encode([], JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
                $e = json_encode([], JSON_PRESERVE_ZERO_FRACTION);
                $f = json_encode([], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            ',
            'assertions' => [
                '$a===' => 'non-empty-string',
                '$b===' => 'non-empty-string',
                '$c===' => 'non-empty-string',
                '$d===' => 'non-empty-string',
                '$e===' => 'false|non-empty-string',
                '$f===' => 'false|non-empty-string',
            ],
        ];
        yield 'str_starts_with/str_ends_with/str_contains redundant condition detection' => [
            'code' => '<?php
                $a1 = str_starts_with(uniqid(), "");
                /** @psalm-suppress InvalidLiteralArgument */
                $b1 = str_starts_with("", "random string");
                $c1 = str_starts_with(uniqid(), "random string");

                $a2 = str_ends_with(uniqid(), "");
                /** @psalm-suppress InvalidLiteralArgument */
                $b2 = str_ends_with("", "random string");
                $c2 = str_ends_with(uniqid(), "random string");

                $a3 = str_contains(uniqid(), "");
                /** @psalm-suppress InvalidLiteralArgument */
                $b3 = str_contains("", "random string");
                $c3 = str_contains(uniqid(), "random string");
            ',
            'assertions' => [
                '$a1===' => 'true',
                '$b1===' => 'false',
                '$c1===' => 'bool',
                '$a2===' => 'true',
                '$b2===' => 'false',
                '$c2===' => 'bool',
                '$a3===' => 'true',
                '$b3===' => 'false',
                '$c3===' => 'bool',
            ],
        ];
        yield 'PHP8 str_* function assert non-empty-string' => [
            'code' => '<?php
                /** @return non-empty-string */
                function after_str_contains(): string
                {
                    $string = file_get_contents("");
                    if (str_contains($string, "foo")) {
                        return $string;
                    }
                    throw new RuntimeException();
                }

                /** @return non-empty-string */
                function after_str_starts_with(): string
                {
                    $string = file_get_contents("");
                    if (str_starts_with($string, "foo")) {
                        return $string;
                    }
                    throw new RuntimeException();
                }

                /** @return non-empty-string */
                function after_str_ends_with(): string
                {
                    $string = file_get_contents("");
                    if (str_ends_with($string, "foo")) {
                        return $string;
                    }
                    throw new RuntimeException();
                }
                $a = after_str_contains();
                $b = after_str_starts_with();
                $c = after_str_ends_with();
            ',
            'assertions' => [
                '$a===' => 'non-empty-string',
                '$b===' => 'non-empty-string',
                '$c===' => 'non-empty-string',
            ],
        ];
        yield "PHP8 str_* function doesn't subtract string after assertion" => [
            'code' => '<?php
                /** @return false|string */
                function after_str_contains()
                {
                    $string = file_get_contents("");
                    if (!str_contains($string, "foo")) {
                        return $string;
                    }
                    throw new RuntimeException();
                }

                /** @return false|string */
                function after_str_starts_with()
                {
                    $string = file_get_contents("");
                    if (!str_starts_with($string, "foo")) {
                        return $string;
                    }
                    throw new RuntimeException();
                }

                /** @return false|string */
                function after_str_ends_with()
                {
                    $string = file_get_contents("");
                    if (!str_ends_with($string, "foo")) {
                        return $string;
                    }
                    throw new RuntimeException();
                }
                $a = after_str_contains();
                $b = after_str_starts_with();
                $c = after_str_ends_with();
            ',
            'assertions' => [
                '$a===' => 'false|string',
                '$b===' => 'false|string',
                '$c===' => 'false|string',
            ],
        ];
        yield "str_contains doesn't yield InvalidLiteralArgument for __DIR__" => [
            'code' => '<?php
                $d = __DIR__;
                echo str_contains($d, "psalm");
            ',
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        yield 'json_decode invalid depth' => [
            'code' => '<?php
                json_decode("true", depth: -1);
            ',
            'error_message' => 'InvalidArgument',
        ];
        yield 'json_encode invalid depth' => [
            'code' => '<?php
                json_encode([], depth: 439877348953739);
            ',
            'error_message' => 'InvalidArgument',
        ];
        yield 'str_contains literal haystack' => [
            'code' => '<?php
                str_contains("literal", "");
            ',
            'error_message' => 'InvalidLiteralArgument',
        ];
        yield 'str_starts_with literal haystack' => [
            'code' => '<?php
                str_starts_with("literal", "");
            ',
            'error_message' => 'InvalidLiteralArgument',
        ];
        yield 'str_ends_with literal haystack' => [
            'code' => '<?php
                str_ends_with("literal", "");
            ',
            'error_message' => 'InvalidLiteralArgument',
        ];
    }
}
