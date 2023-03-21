<?php

declare(strict_types=1);

namespace Psalm\Tests\ReturnTypeProvider;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class PcreFunctionsTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        yield 'preg_grep with string pattern' => [
            'code' => <<<'PHP'
                <?php
                /** @var array<string, string> $arr */
                $arr = [];
                /** @var string $pattern */
                $pattern = '';
                $result = preg_grep($pattern, $arr);

                PHP,
        ];
        yield 'preg_grep with valid literal pattern' => [
            'code' => <<<'PHP'
                <?php
                /** @var array<string, string> $arr */
                $arr = [];
                $result = preg_grep('/valid/', $arr);

                PHP,
            'assertions' => [
                '$result===' => 'array<string, string>',
            ],
        ];
        yield 'preg_grep with invalid literal pattern' => [
            'code' => <<<'PHP'
                <?php
                /** @var array<string, string> $arr */
                $arr = [];
                $result = preg_grep('///', $arr);

                PHP,
            'assertions' => [
                '$result===' => 'false',
            ],
        ];
        yield 'preg_grep with valid literal pattern union' => [
            'code' => <<<'PHP'
                <?php
                /** @var array<string, string> $arr */
                $arr = [];
                /** @var '/foo/'|'/bar/'|'/baz/' $pattern */
                $pattern = '';
                $result = preg_grep($pattern, $arr);

                PHP,
            'assertions' => [
                '$result===' => 'array<string, string>',
            ],
        ];
        yield 'preg_grep with valid and invalid literal pattern union' => [
            'code' => <<<'PHP'
                <?php
                /** @var array<string, string> $arr */
                $arr = [];
                /** @var '/foo/'|'///'|'/baz/' $pattern */
                $pattern = '';
                $result = preg_grep($pattern, $arr);

                PHP,
            'assertions' => [
                '$result===' => 'array<string, string>|false',
            ],
        ];
        yield 'preg_grep with invalid literal pattern union' => [
            'code' => <<<'PHP'
                <?php
                /** @var array<string, string> $arr */
                $arr = [];
                /** @var '///'|'////'|'/////' $pattern */
                $pattern = '';
                $result = preg_grep($pattern, $arr);

                PHP,
            'assertions' => [
                '$result===' => 'false',
            ],
        ];
        yield 'preg_grep with valid and invalid literal pattern union ignores false' => [
            'code' => <<<'PHP'
                <?php
                /** @param array<string, string> $arr */
                function takesArray(array $arr): void {}
                /** @var array<string, string> $arr */
                $arr = [];
                /** @var '/foo/'|'///'|'/baz/' $pattern */
                $pattern = '';
                takesArray(preg_grep($pattern, $arr));

                PHP,
        ];
        yield 'preg_grep with string pattern ignores false' => [
            'code' => <<<'PHP'
                <?php
                /** @param array<string, string> $arr */
                function takesArray(array $arr): void {}
                /** @var array<string, string> $arr */
                $arr = [];
                /** @var string $pattern */
                $pattern = '';
                takesArray(preg_grep($pattern, $arr));

                PHP,
        ];
        yield 'preg_grep with non-empty-array returns array' => [
            'code' => <<<'PHP'
                <?php
                /** @var non-empty-array<string, string> $arr */
                $arr = [];
                $result = preg_grep('/valid/', $arr);

                PHP,
            'assertions' => [
                '$result===' => 'array<string, string>',
            ],
        ];
        yield 'preg_grep with list returns array' => [
            'code' => <<<'PHP'
                <?php
                /** @var list<string, string> $arr */
                $arr = [];
                $result = preg_grep('/valid/', $arr);

                PHP,
            'assertions' => [
                '$result===' => 'array<int<0, max>, string>',
            ],
        ];
        yield 'preg_grep with non-empty-list returns array' => [
            'code' => <<<'PHP'
                <?php
                /** @var non-empty-list<string, string> $arr */
                $arr = [];
                $result = preg_grep('/valid/', $arr);

                PHP,
            'assertions' => [
                '$result===' => 'array<int<0, max>, string>',
            ],
        ];
        yield 'preg_grep with sealed shape array returns sealed shape array' => [
            'code' => <<<'PHP'
                <?php
                /** @var array{bar: 'baz', buzz: 'fizz', foo: 'foo'} string $arr */
                $arr = [];
                $result = preg_grep('/valid/', $arr);

                PHP,
            'assertions' => [
                '$result===' => 'array{bar?: \'baz\', buzz?: \'fizz\', foo?: \'foo\'}',
            ],
        ];
        yield 'preg_grep with unsealed shape array returns unsealed shape array' => [
            'code' => <<<'PHP'
                <?php
                /** @var array{bar: 'baz', buzz: 'fizz', foo: 'foo'}&array<string, string> string $arr */
                $arr = [];
                $result = preg_grep('/valid/', $arr);

                PHP,
            'assertions' => [
                '$result===' => 'array{bar?: \'baz\', buzz?: \'fizz\', foo?: \'foo\', ...<string, string>}',
            ],
        ];
        yield 'preg_match with string pattern' => [
            'code' => <<<'PHP'
                <?php
                /** @var string $pattern */
                $pattern = '';
                $result = preg_match($pattern, 'subject');

                PHP,
            'assertions' => [
                '$result===' => '0|1|false',
            ],
        ];
        yield 'preg_match with valid literal pattern' => [
            'code' => <<<'PHP'
                <?php
                $result = preg_match('/valid/', 'subject');

                PHP,
            'assertions' => [
                '$result===' => '0|1',
            ],
        ];
        yield 'preg_match with invalid literal pattern' => [
            'code' => <<<'PHP'
                <?php
                $result = preg_match('///', 'subject');

                PHP,
            'assertions' => [
                '$result===' => 'false',
            ],
        ];
        yield 'preg_match with valid literal pattern union' => [
            'code' => <<<'PHP'
                <?php
                /** @var '/foo/'|'/bar/'|'/baz/' $pattern */
                $pattern = '';
                $result = preg_match($pattern, 'subject');

                PHP,
            'assertions' => [
                '$result===' => '0|1',
            ],
        ];
        yield 'preg_match with valid and invalid literal pattern union' => [
            'code' => <<<'PHP'
                <?php
                /** @var '/foo/'|'///'|'/baz/' $pattern */
                $pattern = '';
                $result = preg_match($pattern, 'subject');

                PHP,
            'assertions' => [
                '$result===' => '0|1|false',
            ],
        ];
        yield 'preg_match with invalid literal pattern union' => [
            'code' => <<<'PHP'
                <?php
                /** @var '///'|'////'|'/////' $pattern */
                $pattern = '';
                $result = preg_match($pattern, 'subject');

                PHP,
            'assertions' => [
                '$result===' => 'false',
            ],
        ];
        yield 'preg_match with valid and invalid literal pattern union ignores false' => [
            'code' => <<<'PHP'
                <?php
                function takesInt(int $i): void {}
                /** @var '/foo/'|'///'|'/baz/' $pattern */
                $pattern = '';
                takesInt(preg_match($pattern, 'subject'));

                PHP,
        ];
        yield 'preg_match with string pattern ignores false' => [
            'code' => <<<'PHP'
                <?php
                function takesInt(int $i): void {}
                /** @var string $pattern */
                $pattern = '';
                takesInt(preg_match($pattern, 'subject'));

                PHP,
        ];
        yield 'preg_match_all with string pattern' => [
            'code' => <<<'PHP'
                <?php
                /** @var string $pattern */
                $pattern = '';
                $result = preg_match_all($pattern, 'subject');

                PHP,
            'assertions' => [
                '$result===' => 'false|int<0, max>',
            ],
        ];
        yield 'preg_match_all with valid literal pattern' => [
            'code' => <<<'PHP'
                <?php
                $result = preg_match_all('/valid/', 'subject');

                PHP,
            'assertions' => [
                '$result===' => 'int<0, max>',
            ],
        ];
        yield 'preg_match_all with invalid literal pattern' => [
            'code' => <<<'PHP'
                <?php
                $result = preg_match_all('///', 'subject');

                PHP,
            'assertions' => [
                '$result===' => 'false',
            ],
        ];
        yield 'preg_match_all with valid literal pattern union' => [
            'code' => <<<'PHP'
                <?php
                /** @var '/foo/'|'/bar/'|'/baz/' $pattern */
                $pattern = '';
                $result = preg_match_all($pattern, 'subject');

                PHP,
            'assertions' => [
                '$result===' => 'int<0, max>',
            ],
        ];
        yield 'preg_match_all with valid and invalid literal pattern union' => [
            'code' => <<<'PHP'
                <?php
                /** @var '/foo/'|'///'|'/baz/' $pattern */
                $pattern = '';
                $result = preg_match_all($pattern, 'subject');

                PHP,
            'assertions' => [
                '$result===' => 'false|int<0, max>',
            ],
        ];
        yield 'preg_match_all with invalid literal pattern union' => [
            'code' => <<<'PHP'
                <?php
                /** @var '///'|'////'|'/////' $pattern */
                $pattern = '';
                $result = preg_match_all($pattern, 'subject');

                PHP,
            'assertions' => [
                '$result===' => 'false',
            ],
        ];
        yield 'preg_match_all with valid and invalid literal pattern union ignores false' => [
            'code' => <<<'PHP'
                <?php
                function takesInt(int $i): void {}
                /** @var '/foo/'|'///'|'/baz/' $pattern */
                $pattern = '';
                takesInt(preg_match_all($pattern, 'subject'));

                PHP,
        ];
        yield 'preg_match_all with string pattern ignores false' => [
            'code' => <<<'PHP'
                <?php
                function takesInt(int $i): void {}
                /** @var string $pattern */
                $pattern = '';
                takesInt(preg_match_all($pattern, 'subject'));

                PHP,
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        yield 'preg_grep with invalid literal pattern does not ignore false' => [
            'code' => <<<'PHP'
                <?php
                /** @param array<string, string> $arr */
                function takesArray(array $arr): void {}
                /** @var array<string, string> $arr */
                $arr = [];
                takesArray(preg_grep('///', $arr));

                PHP,
            'error_message' => 'Argument 1 of takesArray cannot be false',
        ];
        yield 'preg_grep with invalid literal pattern union does not ignore false' => [
            'code' => <<<'PHP'
                <?php
                /** @param array<string, string> $arr */
                function takesArray(array $arr): void {}
                /** @var array<string, string> $arr */
                $arr = [];
                /** @var '///'|'////'|'/////' $pattern */
                $pattern = '';
                takesArray(preg_grep($pattern, $arr));

                PHP,
            'error_message' => 'Argument 1 of takesArray cannot be false',
        ];
        yield 'preg_match with invalid literal pattern does not ignore false' => [
            'code' => <<<'PHP'
                <?php
                function takesInt(int $i): void {}
                takesInt(preg_match('///', 'subject'));

                PHP,
            'error_message' => 'Argument 1 of takesInt cannot be false',
        ];
        yield 'preg_match with invalid literal pattern union does not ignore false' => [
            'code' => <<<'PHP'
                <?php
                function takesInt(int $i): void {}
                /** @var '///'|'////'|'/////' $pattern */
                $pattern = '';
                takesInt(preg_match($pattern, 'subject'));

                PHP,
            'error_message' => 'Argument 1 of takesInt cannot be false',
        ];
        yield 'preg_match_all with invalid literal pattern does not ignore false' => [
            'code' => <<<'PHP'
                <?php
                function takesInt(int $i): void {}
                takesInt(preg_match_all('///', 'subject'));

                PHP,
            'error_message' => 'Argument 1 of takesInt cannot be false',
        ];
        yield 'preg_match_all with invalid literal pattern union does not ignore false' => [
            'code' => <<<'PHP'
                <?php
                function takesInt(int $i): void {}
                /** @var '///'|'////'|'/////' $pattern */
                $pattern = '';
                takesInt(preg_match_all($pattern, 'subject'));

                PHP,
            'error_message' => 'Argument 1 of takesInt cannot be false',
        ];
    }
}
