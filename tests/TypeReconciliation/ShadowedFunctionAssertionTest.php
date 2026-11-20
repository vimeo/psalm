<?php

declare(strict_types=1);

namespace Psalm\Tests\TypeReconciliation;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

/**
 * A condition on a call only gets the assertions of a built-in function when the call resolves to it.
 */
final class ShadowedFunctionAssertionTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'countComparisonOfANamespaceFunction' => [
                'code' => '<?php
                    namespace App;

                    function count(array $_a): int {
                        return 1;
                    }

                    /** @return list<int> */
                    function ints(): array {
                        return [];
                    }

                    $x = ints();
                    $r = null;
                    if (count($x) > 0) {
                        $r = $x;
                    }',
                'assertions' => [
                    '$r' => 'list<int>|null',
                ],
            ],
            'countEqualityOfANamespaceFunction' => [
                'code' => '<?php
                    namespace App;

                    function sizeof(array $_a): int {
                        return 1;
                    }

                    /** @return list<int> */
                    function ints(): array {
                        return [];
                    }

                    $x = ints();
                    $r = null;
                    if (sizeof($x) === 2) {
                        $r = $x;
                    }',
                'assertions' => [
                    '$r' => 'list<int>|null',
                ],
            ],
            'truthyCountOfANamespaceFunction' => [
                'code' => '<?php
                    namespace App;

                    function count(array $_a): int {
                        return 1;
                    }

                    /** @return list<int> */
                    function ints(): array {
                        return [];
                    }

                    $x = ints();
                    $r = null;
                    if (count($x)) {
                        $r = $x;
                    }',
                'assertions' => [
                    '$r' => 'list<int>|null',
                ],
            ],
            'gettypeOfANamespaceFunction' => [
                'code' => '<?php
                    namespace App;

                    /** @param mixed $_x */
                    function gettype($_x): string {
                        return "string";
                    }

                    function value(): int|string {
                        return 1;
                    }

                    $x = value();
                    $r = null;
                    if (gettype($x) === "string") {
                        $r = $x;
                    }',
                'assertions' => [
                    '$r' => 'int|null|string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'getClassOfANamespaceFunction' => [
                'code' => '<?php
                    namespace App;

                    class A {}
                    final class B extends A {}

                    function get_class(object $_o): string {
                        return B::class;
                    }

                    function a(): A {
                        return new A();
                    }

                    $x = a();
                    $r = null;
                    if (get_class($x) === B::class) {
                        $r = $x;
                    }',
                'assertions' => [
                    '$r' => 'App\A|null',
                ],
            ],
            'inArrayOfANamespaceFunction' => [
                'code' => '<?php
                    namespace App;

                    /** @param mixed $_needle */
                    function in_array($_needle, array $_haystack, bool $_strict = false): bool {
                        return true;
                    }

                    function value(): int|string {
                        return 1;
                    }

                    $x = value();
                    $r = null;
                    if (in_array($x, ["a", "b"], true)) {
                        $r = $x;
                    }',
                'assertions' => [
                    '$r' => 'int|null|string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'arrayKeyExistsOfANamespaceFunction' => [
                'code' => '<?php
                    namespace App;

                    /** @param array-key $_key */
                    function array_key_exists($_key, array $_a): bool {
                        return true;
                    }

                    /** @return array{a?: int} */
                    function shape(): array {
                        return [];
                    }

                    $x = shape();
                    $r = null;
                    if (array_key_exists("a", $x)) {
                        $r = $x;
                    }',
                'assertions' => [
                    '$r' => 'array{a?: int}|null',
                ],
            ],
            'isAOfANamespaceFunction' => [
                'code' => '<?php
                    namespace App;

                    function is_a(object $_o, string $_class): bool {
                        return true;
                    }

                    function o(): object {
                        return new \stdClass();
                    }

                    $x = o();
                    $r = null;
                    if (is_a($x, \Exception::class)) {
                        $r = $x;
                    }',
                'assertions' => [
                    '$r' => 'null|object',
                ],
            ],
            'classExistsOfANamespaceFunction' => [
                'code' => '<?php
                    namespace App;

                    function class_exists(string $_class): bool {
                        return true;
                    }

                    function name(): string {
                        return "x";
                    }

                    $x = name();
                    $r = null;
                    if (class_exists($x)) {
                        $r = $x;
                    }',
                'assertions' => [
                    '$r' => 'null|string',
                ],
            ],
            'gettypeMatchOfANamespaceFunction' => [
                'code' => '<?php
                    namespace App;

                    /** @param mixed $_x */
                    function gettype($_x): string {
                        return "string";
                    }

                    function value(): int|string {
                        return 1;
                    }

                    $x = value();
                    $r = match (gettype($x)) {
                        "string" => $x,
                        default => "",
                    };',
                'assertions' => [
                    '$r' => 'int|string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'customAssertionOfANamespaceFunctionNamedLikeABuiltIn' => [
                'code' => '<?php
                    namespace App;

                    /** @psalm-assert-if-true int $needle */
                    function in_array(int|string $needle, array $_haystack, bool $_strict = false): bool {
                        return \is_int($needle);
                    }

                    function value(): int|string {
                        return 1;
                    }

                    $x = value();
                    $r = null;
                    if (in_array($x, ["a"], true)) {
                        $r = $x;
                    }',
                'assertions' => [
                    '$r' => 'int|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'isTypeOfAFunctionImportedUnderABuiltInName' => [
                'code' => '<?php
                    namespace Lib {
                        /** @param mixed $_x */
                        function check($_x): bool {
                            return true;
                        }
                    }

                    namespace App {
                        use function Lib\check as is_string;

                        function value(): int|string {
                            return 1;
                        }

                        $x = value();
                        $r = null;
                        if (is_string($x)) {
                            $r = $x;
                        }
                    }',
                'assertions' => [
                    '$r' => 'int|null|string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'isTypeOfABuiltInImportedUnderAnotherName' => [
                'code' => '<?php
                    namespace App;

                    use function is_string as isString;

                    function value(): int|string {
                        return 1;
                    }

                    $x = value();
                    $r = null;
                    if (isString($x)) {
                        $r = $x;
                    }',
                'assertions' => [
                    '$r' => 'null|string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'fullyQualifiedBuiltInBesideANamespaceFunction' => [
                'code' => '<?php
                    namespace App;

                    function count(array $_a): int {
                        return 1;
                    }

                    /** @return list<int> */
                    function ints(): array {
                        return [];
                    }

                    $x = ints();
                    $r = null;
                    if (\count($x) > 0) {
                        $r = $x;
                    }',
                'assertions' => [
                    '$r' => 'non-empty-list<int>|null',
                ],
            ],
            'builtInFallbackInANamespace' => [
                'code' => '<?php
                    namespace App;

                    /** @return list<int> */
                    function ints(): array {
                        return [];
                    }

                    function value(): int|string {
                        return 1;
                    }

                    $x = ints();
                    $y = value();
                    $r = null;
                    $s = null;
                    if (count($x) > 0) {
                        $r = $x;
                    }
                    if (is_string($y)) {
                        $s = $y;
                    }',
                'assertions' => [
                    '$r' => 'non-empty-list<int>|null',
                    '$s' => 'null|string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'classExistsOfANamespaceFunctionDoesNotGuardANewerClass' => [
                'code' => '<?php
                    namespace App;

                    function class_exists(string $_class): bool {
                        return true;
                    }

                    if (class_exists("WeakMap")) {
                        $_m = new \WeakMap();
                    }',
                'error_message' => 'UndefinedClass',
            ],
        ];
    }
}
