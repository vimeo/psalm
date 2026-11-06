<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class SuperGlobalsTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        yield 'http_response_headerIsList' => [
            'code' => '<?php
                /** @return non-empty-list<non-falsy-string> */
                function returnsList(): array {
                    if (!isset($http_response_header)) {
                        throw new \RuntimeException();
                    }
                    return $http_response_header;
                }
            ',
            'assertions' => [],
        ];

        yield 'COOKIE keys can be ints' => [
            'code' => '<?php
                $cookies = $_COOKIE;
            ',
            'assertions' => [
                '$cookies===' => 'array<int|non-empty-string, string>',
            ],
        ];

        yield 'ENV has scalar entries only' => [
            'code' => '<?php
                /** @return array<array-key, scalar> */
                function f(): array {
                    return $_ENV;
                }
            ',
        ];

        yield 'session_unset clears SESSION' => [
            'code' => '<?php
                $_SESSION["foo"] = 42;
                session_unset();
                $session = $_SESSION;
            ',
            'assertions' => [
                '$session===' => 'array<never, never>',
            ],
        ];

        yield 'SERVER REQUEST_TIME range does not depend on the current time' => [
            'code' => '<?php
                $time = null;
                if (isset($_SERVER["REQUEST_TIME"])) {
                    $time = $_SERVER["REQUEST_TIME"];
                }
            ',
            'assertions' => [
                '$time===' => 'int<1, max>|null',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        yield 'undefined http_response_header' => [
            'code' => '<?php
                /** @return non-empty-list<non-falsy-string> */
                function returnsList(): array {
                    return $http_response_header;
                }
            ',
            'error_message' => 'InvalidReturnStatement',
        ];

        yield 'access SESSION key after session_unset' => [
            'code' => '<?php
                $_SESSION["foo"] = 42;
                session_unset();
                echo $_SESSION["foo"];
            ',
            'error_message' => 'EmptyArrayAccess',
        ];

        yield 'SERVER REQUEST_TIME shape in issue message does not depend on the current time' => [
            'code' => '<?php
                echo $_SERVER["REQUEST_TIME"];
            ',
            'error_message' => 'REQUEST_TIME?: int<1, max',
        ];
    }
}
