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
        yield 'aCopyOfASuperGlobalIsDefined' => [
            'code' => '<?php
                function save(): void {
                    $session = $_SESSION;

                    try {
                        session_write_close();
                    } finally {
                        if ($_SESSION) {
                            $_SESSION = $session;
                        }
                    }

                    echo count($session);
                }',
            'assertions' => [],
        ];

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
            'ignored_issues' => ['DeprecatedSyntax'],
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

        yield 'globalSuperGlobalKeepsItsType' => [
            'code' => '<?php
                function printers(): string {
                    global $_SESSION;

                    $session = $_SESSION;
                    /** @psalm-check-type-exact $session = array<non-empty-string, mixed> */;

                    return var_export($_SESSION["printers"], true);
                }
            ',
        ];

        yield 'globalSuperGlobalReadInTheFileScopeKeepsItsType' => [
            'code' => '<?php
                echo count($_SESSION);

                function printers(): string {
                    global $_SESSION;

                    return var_export($_SESSION["printers"], true);
                }
            ',
        ];

        yield 'globalSuperGlobalReadInAFileScopeTryIsDefined' => [
            'code' => '<?php
                try {
                    echo count($_SESSION);
                } catch (Exception $e) {
                }

                function snapshot(): void {
                    global $_SESSION;

                    $snapshot = $_SESSION;
                    var_export($snapshot);
                }
            ',
        ];

        yield 'superGlobalPassedByReferenceInAFunction' => [
            'code' => '<?php
                function byRef(array &$a): void {}

                function f(): void {
                    byRef($_SESSION);
                }
            ',
        ];

        yield 'superGlobalPassedByReferenceInTheFileScope' => [
            'code' => '<?php
                function byRef(array &$a): void {}

                byRef($_SESSION);
            ',
        ];

        yield 'superGlobalReadThenPassedByReference' => [
            'code' => '<?php
                function byRef(array &$a): void {}

                echo count($_SESSION);
                byRef($_SESSION);
            ',
        ];

        yield 'superGlobalPassedByReferenceTakesTheOutType' => [
            'code' => '<?php
                /** @param-out list<int> $a */
                function byRef(array &$a): void {
                    $a = [1];
                }

                function f(): void {
                    byRef($_GET);
                    $get = $_GET;
                    /** @psalm-check-type-exact $get = list<int> */;
                }
            ',
        ];

        yield 'superGlobalPassedToAMethodOfAnUnknownClass' => [
            'code' => '<?php
                /** @psalm-suppress MixedMethodCall */
                function f(mixed $o): void {
                    $o->save($_SESSION);
                    $session = $_SESSION;
                    /** @psalm-check-type-exact $session = array<non-empty-string, mixed> */;
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];

        yield 'superGlobalPassedToAnArraySortFunction' => [
            'code' => '<?php
                function f(): void {
                    asort($_SESSION);
                    $session = $_SESSION;
                    /** @psalm-check-type-exact $session = array<non-empty-string, mixed> */;
                }
            ',
        ];

        yield 'globalHttpResponseHeaderIsNullWhenUnset' => [
            'code' => '<?php
                function headers(): void {
                    global $http_response_header;
                    /** @psalm-check-type-exact $http_response_header = non-empty-list<non-falsy-string>|null */;
                }
            ',
        ];

        yield 'superGlobalAssignedInAnIfOnlyKeepsItsDefaultType' => [
            'code' => '<?php
                function f(): void {
                    if (rand(0, 1)) {
                        $_SESSION = ["a" => 1];
                    }

                    $session = $_SESSION;
                    /** @psalm-check-type-exact $session = array<non-empty-string, mixed> */;
                }
            ',
        ];

        yield 'superGlobalAssignedInATryOnlyKeepsItsDefaultType' => [
            'code' => '<?php
                function f(): void {
                    try {
                        $_SESSION = ["a" => 1];
                    } catch (Exception) {
                    }

                    $session = $_SESSION;
                    /** @psalm-check-type-exact $session = array<non-empty-string, mixed> */;
                }
            ',
        ];

        yield 'superGlobalAssignedInALoopOnlyKeepsItsDefaultType' => [
            'code' => '<?php
                function f(int $n): void {
                    while ($n-- > 0) {
                        $_SESSION = ["a" => 1];
                    }

                    $session = $_SESSION;
                    /** @psalm-check-type-exact $session = array<non-empty-string, mixed> */;
                }
            ',
        ];

        yield 'superGlobalAssignedOnEveryPathHasTheAssignedType' => [
            'code' => '<?php
                function f(): void {
                    if (rand(0, 1)) {
                        $_SESSION = ["a" => 1];
                    } else {
                        $_SESSION = ["a" => 2];
                    }

                    $session = $_SESSION;
                    /** @psalm-check-type-exact $session = array{a: 1|2} */;
                }
            ',
        ];

        yield 'superGlobalAssignedUnconditionallyHasTheAssignedType' => [
            'code' => '<?php
                function f(): void {
                    $_SESSION = ["a" => 1];

                    $session = $_SESSION;
                    /** @psalm-check-type-exact $session = array{a: 1} */;
                }
            ',
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
            'error_levels' => ['DeprecatedSyntax'],
        ];

        yield 'aCopyOfHttpResponseHeaderIsPossiblyUndefined' => [
            'code' => '<?php
                function headers(): void {
                    $headers = $http_response_header;
                    echo count($headers);
                }
            ',
            'error_message' => 'PossiblyUndefinedVariable',
            'error_levels' => ['DeprecatedSyntax'],
        ];

        yield 'superGlobalPassedByReferenceKeepsItsType' => [
            'code' => '<?php
                function byRef(string &$a): void {}

                function f(): void {
                    byRef($_SESSION);
                }
            ',
            'error_message' => 'InvalidArgument',
        ];

        yield 'httpResponseHeaderPassedByReferenceMayNotBeSet' => [
            'code' => '<?php
                function byRef(array &$a): void {}

                function f(): void {
                    byRef($http_response_header);
                }
            ',
            'error_message' => 'NullReference',
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
