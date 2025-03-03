<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class CoreStubsTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    #[Override]
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
        yield 'sprintf accepts Stringable values' => [
            'code' => '<?php

            $a = sprintf(
                "%s",
                new class implements Stringable { public function __toString(): string { return "hello"; } },
            );
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.0',
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
        yield 'PHP80-str_* function assert non-empty-string' => [
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

                /** @return non-empty-string */
                function after_strpos(): string
                {
                    $string = uniqid();
                    if (strpos($string, "foo") !== false) {
                        return $string;
                    }
                    throw new RuntimeException();
                }

                /** @return non-empty-string */
                function after_stripos(): string
                {
                    $string = uniqid();
                    if (stripos($string, "foo") !== false) {
                        return $string;
                    }
                    throw new RuntimeException();
                }

                $a = after_str_contains();
                $b = after_str_starts_with();
                $c = after_str_ends_with();
                $d = after_strpos();
                $e = after_stripos();
            ',
            'assertions' => [
                '$a===' => 'non-empty-string',
                '$b===' => 'non-empty-string',
                '$c===' => 'non-empty-string',
                '$d===' => 'non-empty-string',
                '$e===' => 'non-empty-string',
            ],
        ];
        yield "PHP80-str_* function doesn't subtract string after assertion" => [
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
        yield 'glob return types' => [
            'code' => <<<'PHP'
                <?php
                /** @var int-mask<GLOB_NOCHECK> */
                $maybeNocheckFlag = 0;
                /** @var int-mask<GLOB_ONLYDIR> */
                $maybeOnlydirFlag = 0;

                /** @var string */
                $string = '';

                $emptyPatternNoFlags = glob( '' );
                $emptyPatternWithoutNocheckFlag1 = glob( '', GLOB_MARK );
                $emptyPatternWithoutNocheckFlag2 = glob( '' , GLOB_NOSORT | GLOB_NOESCAPE);
                $emptyPatternWithoutNocheckFlag3 = glob( '' , GLOB_MARK | GLOB_NOSORT  | GLOB_NOESCAPE | GLOB_BRACE | GLOB_ONLYDIR | GLOB_ERR);
                $emptyPatternWithNocheckFlag1 = glob( ''  , GLOB_NOCHECK);
                $emptyPatternWithNocheckFlag2 = glob( '' , GLOB_NOCHECK | GLOB_MARK);
                $emptyPatternWithNocheckFlag3 = glob( '' , GLOB_NOCHECK | GLOB_MARK | GLOB_NOSORT | GLOB_NOESCAPE | GLOB_BRACE | GLOB_ERR);
                $emptyPatternWithNocheckAndOnlydirFlag1 = glob( '' , GLOB_NOCHECK | GLOB_ONLYDIR);
                $emptyPatternWithNocheckAndOnlydirFlag2 = glob( '' , GLOB_NOCHECK | GLOB_ONLYDIR | GLOB_MARK);
                $emptyPatternWithNocheckAndOnlydirFlag3 = glob( '' , GLOB_NOCHECK | GLOB_ONLYDIR | GLOB_MARK | GLOB_NOSORT | GLOB_NOESCAPE | GLOB_BRACE | GLOB_ERR);
                $emptyPatternWithNocheckFlagAndMaybeOnlydir = glob( '' , GLOB_NOCHECK | $maybeOnlydirFlag);
                $emptyPatternMaybeWithNocheckFlag = glob( '' , $maybeNocheckFlag);
                $emptyPatternMaybeWithNocheckFlagAndOnlydir = glob( '' , $maybeNocheckFlag | GLOB_ONLYDIR);
                $emptyPatternMaybeWithNocheckFlagAndMaybeOnlydir = glob( '' , $maybeNocheckFlag | $maybeOnlydirFlag);

                $nonEmptyPatternNoFlags = glob( 'pattern' );
                $nonEmptyPatternWithoutNocheckFlag1 = glob( 'pattern', GLOB_MARK );
                $nonEmptyPatternWithoutNocheckFlag2 = glob( 'pattern' , GLOB_NOSORT | GLOB_NOESCAPE);
                $nonEmptyPatternWithoutNocheckFlag3 = glob( 'pattern' , GLOB_MARK | GLOB_NOSORT  | GLOB_NOESCAPE | GLOB_BRACE | GLOB_ONLYDIR | GLOB_ERR);
                $nonEmptyPatternWithNocheckFlag1 = glob( 'pattern'  , GLOB_NOCHECK);
                $nonEmptyPatternWithNocheckFlag2 = glob( 'pattern' , GLOB_NOCHECK | GLOB_MARK);
                $nonEmptyPatternWithNocheckFlag3 = glob( 'pattern' , GLOB_NOCHECK | GLOB_MARK | GLOB_NOSORT | GLOB_NOESCAPE | GLOB_BRACE | GLOB_ERR);
                $nonEmptyPatternWithNocheckAndOnlydirFlag1 = glob( 'pattern' , GLOB_NOCHECK | GLOB_ONLYDIR);
                $nonEmptyPatternWithNocheckAndOnlydirFlag2 = glob( 'pattern' , GLOB_NOCHECK | GLOB_ONLYDIR | GLOB_MARK);
                $nonEmptyPatternWithNocheckAndOnlydirFlag3 = glob( 'pattern' , GLOB_NOCHECK | GLOB_ONLYDIR | GLOB_MARK | GLOB_NOSORT | GLOB_NOESCAPE | GLOB_BRACE | GLOB_ERR);
                $nonEmptyPatternWithNocheckFlagAndMaybeOnlydir = glob( 'pattern' , GLOB_NOCHECK | $maybeOnlydirFlag);
                $nonEmptyPatternMaybeWithNocheckFlag = glob( 'pattern' , $maybeNocheckFlag);
                $nonEmptyPatternMaybeWithNocheckFlagAndOnlydir = glob( 'pattern' , $maybeNocheckFlag | GLOB_ONLYDIR);
                $nonEmptyPatternMaybeWithNocheckFlagAndMaybeOnlydir = glob( 'pattern' , $maybeNocheckFlag | $maybeOnlydirFlag);

                $stringPatternNoFlags = glob( $string );
                $stringPatternWithoutNocheckFlag1 = glob( $string, GLOB_MARK );
                $stringPatternWithoutNocheckFlag2 = glob( $string , GLOB_NOSORT | GLOB_NOESCAPE);
                $stringPatternWithoutNocheckFlag3 = glob( $string , GLOB_MARK | GLOB_NOSORT  | GLOB_NOESCAPE | GLOB_BRACE | GLOB_ONLYDIR | GLOB_ERR);
                $stringPatternWithNocheckFlag1 = glob( $string  , GLOB_NOCHECK);
                $stringPatternWithNocheckFlag2 = glob( $string , GLOB_NOCHECK | GLOB_MARK);
                $stringPatternWithNocheckFlag3 = glob( $string , GLOB_NOCHECK | GLOB_MARK | GLOB_NOSORT | GLOB_NOESCAPE | GLOB_BRACE | GLOB_ERR);
                $stringPatternWithNocheckAndOnlydirFlag1 = glob( $string , GLOB_NOCHECK | GLOB_ONLYDIR);
                $stringPatternWithNocheckAndOnlydirFlag2 = glob( $string , GLOB_NOCHECK | GLOB_ONLYDIR | GLOB_MARK);
                $stringPatternWithNocheckAndOnlydirFlag3 = glob( $string , GLOB_NOCHECK | GLOB_ONLYDIR | GLOB_MARK | GLOB_NOSORT | GLOB_NOESCAPE | GLOB_BRACE | GLOB_ERR);
                $stringPatternWithNocheckFlagAndMaybeOnlydir = glob( $string , GLOB_NOCHECK | $maybeOnlydirFlag);
                $stringPatternMaybeWithNocheckFlag = glob( $string , $maybeNocheckFlag);
                $stringPatternMaybeWithNocheckFlagAndOnlydir = glob( $string , $maybeNocheckFlag | GLOB_ONLYDIR);
                $stringPatternMaybeWithNocheckFlagAndMaybeOnlydir = glob( $string , $maybeNocheckFlag | $maybeOnlydirFlag);
                PHP,
            'assertions' => [
                '$emptyPatternNoFlags===' => 'array<never, never>|false',
                '$emptyPatternWithoutNocheckFlag1===' => 'array<never, never>|false',
                '$emptyPatternWithoutNocheckFlag2===' => 'array<never, never>|false',
                '$emptyPatternWithoutNocheckFlag3===' => 'array<never, never>|false',
                '$emptyPatternWithNocheckFlag1===' => 'false|list{\'\'}',
                '$emptyPatternWithNocheckFlag2===' => 'false|list{\'\'}',
                '$emptyPatternWithNocheckFlag3===' => 'false|list{\'\'}',
                '$emptyPatternWithNocheckAndOnlydirFlag1===' => 'array<never, never>|false',
                '$emptyPatternWithNocheckAndOnlydirFlag2===' => 'array<never, never>|false',
                '$emptyPatternWithNocheckAndOnlydirFlag3===' => 'array<never, never>|false',
                '$emptyPatternWithNocheckFlagAndMaybeOnlydir===' => 'false|list{0?: \'\'}',
                '$emptyPatternMaybeWithNocheckFlag===' => 'false|list{0?: \'\'}',
                '$emptyPatternMaybeWithNocheckFlagAndOnlydir===' => 'array<never, never>|false',
                '$emptyPatternMaybeWithNocheckFlagAndMaybeOnlydir===' => 'false|list{0?: \'\'}',

                '$nonEmptyPatternNoFlags===' => 'false|list<non-empty-string>',
                '$nonEmptyPatternWithoutNocheckFlag1===' => 'false|list<non-empty-string>',
                '$nonEmptyPatternWithoutNocheckFlag2===' => 'false|list<non-empty-string>',
                '$nonEmptyPatternWithoutNocheckFlag3===' => 'false|list<non-empty-string>',
                '$nonEmptyPatternWithNocheckFlag1===' => 'false|non-empty-list<non-empty-string>',
                '$nonEmptyPatternWithNocheckFlag2===' => 'false|non-empty-list<non-empty-string>',
                '$nonEmptyPatternWithNocheckFlag3===' => 'false|non-empty-list<non-empty-string>',
                '$nonEmptyPatternWithNocheckAndOnlydirFlag1===' => 'false|list<non-empty-string>',
                '$nonEmptyPatternWithNocheckAndOnlydirFlag2===' => 'false|list<non-empty-string>',
                '$nonEmptyPatternWithNocheckAndOnlydirFlag3===' => 'false|list<non-empty-string>',
                '$nonEmptyPatternWithNocheckFlagAndMaybeOnlydir===' => 'false|list<non-empty-string>',
                '$nonEmptyPatternMaybeWithNocheckFlag===' => 'false|list<non-empty-string>',
                '$nonEmptyPatternMaybeWithNocheckFlagAndOnlydir===' => 'false|list<non-empty-string>',
                '$nonEmptyPatternMaybeWithNocheckFlagAndMaybeOnlydir===' => 'false|list<non-empty-string>',

                '$stringPatternNoFlags===' => 'false|list<non-empty-string>',
                '$stringPatternWithoutNocheckFlag1===' => 'false|list<non-empty-string>',
                '$stringPatternWithoutNocheckFlag2===' => 'false|list<non-empty-string>',
                '$stringPatternWithoutNocheckFlag3===' => 'false|list<non-empty-string>',
                '$stringPatternWithNocheckFlag1===' => 'false|list{string, ...<non-empty-string>}',
                '$stringPatternWithNocheckFlag2===' => 'false|list{string, ...<non-empty-string>}',
                '$stringPatternWithNocheckFlag3===' => 'false|list{string, ...<non-empty-string>}',
                '$stringPatternWithNocheckAndOnlydirFlag1===' => 'false|list<non-empty-string>',
                '$stringPatternWithNocheckAndOnlydirFlag2===' => 'false|list<non-empty-string>',
                '$stringPatternWithNocheckAndOnlydirFlag3===' => 'false|list<non-empty-string>',
                '$stringPatternWithNocheckFlagAndMaybeOnlydir===' => 'false|list{0?: string, ...<non-empty-string>}',
                '$stringPatternMaybeWithNocheckFlag===' => 'false|list{0?: string, ...<non-empty-string>}',
                '$stringPatternMaybeWithNocheckFlagAndOnlydir===' => 'false|list<non-empty-string>',
                '$stringPatternMaybeWithNocheckFlagAndMaybeOnlydir===' => 'false|list{0?: string, ...<non-empty-string>}',
            ],
        ];
        yield 'glob return ignores false' => [
            'code' => <<<'PHP'
                <?php
                /**
                 * @param list $list
                 */
                function takesList(array $list): void {}
                takesList(glob( '' ));
                PHP,
        ];
        yield 'glob accepts GLOB_BRACE' => [
            'code' => <<<'PHP'
                <?php
                $globBrace = glob('abc', GLOB_BRACE);
                PHP,
        ];
        yield "ownerDocument's type is non-nullable DOMDocument and always null on DOMDocument itself" => [
            'code' => '<?php
                $a = (new DOMDocument())->ownerDocument;
                $b = (new DOMNode())->ownerDocument;
                $c = (new DOMElement("p"))->ownerDocument;
                $d = (new DOMNameSpaceNode())->ownerDocument;
            ',
            'assertions' => [
                '$a===' => 'null',
                '$b===' => 'DOMDocument',
                '$c===' => 'DOMDocument',
                '$d===' => 'DOMDocument',
            ],
        ];
    }

    #[Override]
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
