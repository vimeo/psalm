<?php

namespace Psalm\Tests\ReturnTypeProvider;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class SprintfTest extends TestCase
{
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
    }
}
