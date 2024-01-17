<?php

declare(strict_types=1);

namespace Psalm\Tests\ReturnTypeProvider;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class SprintfTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        yield 'sprintfDNonEmpty' => [
            'code' => '<?php
                $val = sprintf("%d", implode("", array()));
            ',
            'assertions' => [
                '$val===' => 'non-empty-string',
            ],
        ];

        yield 'sprintfFormatNonEmpty' => [
            'code' => '<?php
                $val = sprintf("%s %s", "", "");
            ',
            'assertions' => [
                '$val===' => 'non-empty-string',
            ],
        ];

        yield 'sprintfArgnumFormatNonEmpty' => [
            'code' => '<?php
                $val = sprintf("%2\$s %1\$s", "", "");
            ',
            'assertions' => [
                '$val===' => 'non-empty-string',
            ],
        ];

        yield 'sprintfLiteralFormatNonEmpty' => [
            'code' => '<?php
                $val = sprintf("%s hello", "");
            ',
            'assertions' => [
                '$val===' => 'non-empty-string',
            ],
        ];

        yield 'sprintfStringPlaceholderLiteralIntParamFormatNonEmpty' => [
            'code' => '<?php
                $val = sprintf("%s", 15);
            ',
            'assertions' => [
                '$val===' => 'non-empty-string',
            ],
        ];

        yield 'sprintfStringPlaceholderIntParamFormatNonEmpty' => [
            'code' => '<?php
                $val = sprintf("%s", crc32(uniqid()));
            ',
            'assertions' => [
                '$val===' => 'non-empty-string',
            ],
        ];

        yield 'sprintfStringPlaceholderFloatParamFormatNonEmpty' => [
            'code' => '<?php
                $val = sprintf("%s", microtime(true));
            ',
            'assertions' => [
                '$val===' => 'non-empty-string',
            ],
        ];

        yield 'sprintfStringPlaceholderIntStringParamFormatNonEmpty' => [
            'code' => '<?php
                $tmp = rand(0, 10) > 5 ? time() : implode("", array()) . "hello";
                $val = sprintf("%s", $tmp);
            ',
            'assertions' => [
                '$val===' => 'non-empty-string',
            ],
        ];

        yield 'sprintfStringPlaceholderLiteralStringParamFormat' => [
            'code' => '<?php
                $val = sprintf("%s", "");
            ',
            'assertions' => [
                '$val===' => 'string',
            ],
        ];

        yield 'sprintfStringPlaceholderStringParamFormat' => [
            'code' => '<?php
                $val = sprintf("%s", implode("", array()));
            ',
            'assertions' => [
                '$val===' => 'string',
            ],
        ];

        yield 'sprintfStringArgnumPlaceholderStringParamsFormat' => [
            'code' => '<?php
                $val = sprintf("%2\$s%1\$s", "", implode("", array()));
            ',
            'assertions' => [
                '$val===' => 'string',
            ],
        ];

        yield 'sprintfStringPlaceholderIntStringParamFormat' => [
            'code' => '<?php
                $tmp = rand(0, 10) > 5 ? time() : implode("", array());
                $val = sprintf("%s", $tmp);
            ',
            'assertions' => [
                '$val===' => 'string',
            ],
        ];

        yield 'printfSimple' => [
            'code' => '<?php
                $val = printf("%s", "hello");
            ',
            'assertions' => [
                '$val===' => 'int<0, max>',
            ],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];

        yield 'sprintfEmptyStringFormat' => [
            'code' => '<?php
                $val = sprintf("", "abc");
            ',
            'assertions' => [
                '$val===' => '\'\'',
            ],
            'ignored_issues' => [
                'RedundantFunctionCall',
            ],
        ];

        yield 'sprintfPaddedEmptyStringFormat' => [
            'code' => '<?php
                $val = sprintf("%0.0s", "abc");
            ',
            'assertions' => [
                '$val===' => '\'\'',
            ],
            'ignored_issues' => [
                'InvalidArgument',
            ],
        ];

        yield 'sprintfComplexPlaceholderNotYetSupported1' => [
            'code' => '<?php
                $val = sprintf(\'%*.0s\', 0, "abc");
            ',
            'assertions' => [
                '$val===' => 'string',
            ],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];

        yield 'sprintfComplexPlaceholderNotYetSupported2' => [
            'code' => '<?php
                $val = sprintf(\'%0.*s\', 0, "abc");
            ',
            'assertions' => [
                '$val===' => 'string',
            ],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];

        yield 'sprintfComplexPlaceholderNotYetSupported3' => [
            'code' => '<?php
                $val = sprintf(\'%*.*s\', 0, 0, "abc");
            ',
            'assertions' => [
                '$val===' => 'string',
            ],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];

        yield 'sprintfSplatUnpackingArray' => [
            'code' => '<?php
                $a = ["a", "b", "c"];
                $val = sprintf("%s%s%s", ...$a);
            ',
            'assertions' => [
                '$val===' => 'string',
            ],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];

        yield 'sprintfSplatUnpackingArrayNonEmpty' => [
            'code' => '<?php
                $a = ["a", "b", "c"];
                $val = sprintf("%s %s %s", ...$a);
            ',
            'assertions' => [
                '$val===' => 'non-empty-string',
            ],
        ];

        yield 'sprintfSplatUnpackingArraySingleArg' => [
            'code' => '<?php
                $a = ["Hello %s", "Sam"];
                $val = sprintf(...$a);
            ',
            'assertions' => [
                '$val===' => 'string',
            ],
            'ignored_issues' => [
                'RedundantFunctionCall',
            ],
            'php_version' => '8.0',
        ];

        yield 'sprintfMultiplePlaceholdersNoErrorsIssue9941PHP7' => [
            'code' => '<?php
                $val = sprintf("Handling product %d => %d (%d)", 123, 456, 789);
            ',
            'assertions' => [
                '$val===' => 'non-empty-string',
            ],
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];

        yield 'sprintfMultiplePlaceholdersNoErrorsIssue9941PHP8' => [
            'code' => '<?php
                $val = sprintf("Handling product %d => %d (%d)", 123, 456, 789);
            ',
            'assertions' => [
                '$val===' => 'non-empty-string',
            ],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'sprintfOnlyFormatWithoutPlaceholders' => [
                'code' => '<?php
                    $x = sprintf("hello");
                ',
                'error_message' => 'RedundantFunctionCall',
            ],
            'printfOnlyFormatWithoutPlaceholders' => [
                'code' => '<?php
                    $x = sprintf("hello");
                ',
                'error_message' => 'RedundantFunctionCall',
            ],
            'sprintfTooFewArguments' => [
                'code' => '<?php
                    $x = sprintf("%s hello %d", "a");
                ',
                'error_message' => 'TooFewArguments',
            ],
            'sprintfTooManyArguments' => [
                'code' => '<?php
                    $x = sprintf("%s hello", "a", "b");
                ',
                'error_message' => 'TooManyArguments',
            ],
            'sprintfInvalidFormat' => [
                'code' => '<?php
                    $x = sprintf(\'"%" hello\', "a");
                ',
                'error_message' => 'InvalidArgument',
            ],
            'printfTooFewArguments' => [
                'code' => '<?php
                    printf("%s hello %d", "a");
                ',
                'error_message' => 'TooFewArguments',
            ],
            'printfTooManyArguments' => [
                'code' => '<?php
                    printf("%s hello", "a", "b");
                ',
                'error_message' => 'TooManyArguments',
            ],
            'printfInvalidFormat' => [
                'code' => '<?php
                    printf(\'"%" hello\', "a");
                ',
                'error_message' => 'InvalidArgument',
            ],
            'sprintfEmptyFormat' => [
                'code' => '<?php
                    $x = sprintf("", "abc");
                ',
                'error_message' => 'RedundantFunctionCall',
            ],
            'sprintfFormatWithoutPlaceholders' => [
                'code' => '<?php
                    $x = sprintf("hello", "abc");
                ',
                'error_message' => 'TooManyArguments',
                'ignored_issues' => [
                    'RedundantFunctionCall',
                ],
            ],
            'sprintfPaddedComplexEmptyStringFormat' => [
                'code' => '<?php
                    $x = sprintf("%1$+0.0s", "abc");
                ',
                'error_message' => 'InvalidArgument',
            ],
            'printfVariableFormat' => [
                'code' => '<?php
                    /** @var string $bar */
                    printf($bar);
                ',
                'error_message' => 'RedundantFunctionCall',
            ],
        ];
    }
}
