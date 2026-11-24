<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Config;
use Psalm\Context;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class FunctionCallTest extends TestCase
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
            'urlencodeOfANonEmptyStringIsNonEmpty' => [
                'code' => '<?php
                    /** @param non-empty-string $input */
                    function encode(string $input, string $any): void {
                        /** @psalm-check-type-exact $encoded = non-empty-string */
                        $encoded = urlencode($input);
                        /** @psalm-check-type-exact $rawEncoded = non-empty-string */
                        $rawEncoded = rawurlencode($input);
                        /** @psalm-check-type-exact $maybeEmpty = string */
                        $maybeEmpty = urlencode($any);
                        /** @psalm-check-type-exact $decoded = non-empty-string */
                        $decoded = urldecode($input);
                        /** @psalm-check-type-exact $rawDecoded = non-empty-string */
                        $rawDecoded = rawurldecode($input);
                        /** @psalm-check-type-exact $maybeEmptyDecoded = string */
                        $maybeEmptyDecoded = urldecode($any);
                        /** @psalm-check-type-exact $maybeEmptyRawDecoded = string */
                        $maybeEmptyRawDecoded = rawurldecode($any);
                    }',
            ],
            'filterVarThrowOnFailureHasNoFailureValue' => [
                'code' => '<?php
                    $input = (string) rand();

                    $int = filter_var($input, FILTER_VALIDATE_INT, FILTER_THROW_ON_FAILURE);
                    $bool = filter_var($input, FILTER_VALIDATE_BOOLEAN, FILTER_THROW_ON_FAILURE);
                    $ignoredDefault = filter_var(
                        $input,
                        FILTER_VALIDATE_INT,
                        ["flags" => FILTER_THROW_ON_FAILURE, "options" => ["default" => "x"]],
                    );
                    $failureValue = filter_var($input, FILTER_VALIDATE_INT);
                    $fromInput = filter_input(INPUT_GET, "x", FILTER_VALIDATE_INT, FILTER_THROW_ON_FAILURE);',
                'assertions' => [
                    '$fromInput===' => 'int',
                    '$int===' => 'int',
                    '$bool===' => 'bool',
                    '$ignoredDefault===' => 'int',
                    '$failureValue===' => 'false|int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'filterVarGlobalRangeBeforePhp85KeepsTheFailureValue' => [
                'code' => '<?php
                    /** @var string */
                    $input = $_GET["ip"];
                    $ip = filter_var($input, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE);
                    $fromInput = filter_input(INPUT_GET, "x", FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE);',
                'assertions' => [
                    '$ip===' => 'false|non-falsy-string',
                    '$fromInput===' => 'false|non-falsy-string|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'filterVarGlobalRangeFromPhp85KeepsTheFailureValue' => [
                'code' => '<?php
                    /** @var string */
                    $input = $_GET["ip"];
                    $ip = filter_var($input, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE);
                    $fromInput = filter_input(INPUT_GET, "x", FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE);

                    function ip(string $s): string {
                        $ip = filter_var($s, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE);

                        if ($ip === false) {
                            return "not global";
                        }

                        return $ip;
                    }',
                'assertions' => [
                    '$ip===' => 'false|non-falsy-string',
                    '$fromInput===' => 'false|non-falsy-string|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'ignoredNullableReturnInAnExtraEntryOfAnUnsealedShape' => [
                'code' => '<?php
                    /** @psalm-ignore-nullable-return */
                    function maybeName(): ?string {
                        return rand(0, 1) ? "a" : null;
                    }

                    /** @param array{id: int, ...<string, string>} $a */
                    function takesShape(array $a): void {}

                    takesShape(["id" => 1, "name" => maybeName()]);
                ',
            ],
            'ignoredFalsableReturnInAnExtraEntryOfAnUnsealedShape' => [
                'code' => '<?php
                    /** @psalm-ignore-falsable-return */
                    function maybeName(): string|false {
                        return rand(0, 1) ? "a" : false;
                    }

                    /** @param array{id: int, ...<string, string>} $a */
                    function takesShape(array $a): void {}

                    takesShape(["id" => 1, "name" => maybeName()]);
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'containedEntriesOfAnUnsealedShape' => [
                'code' => '<?php
                    /** @param array{id: int, ...<string, int>} $a */
                    function takesShape(array $a): void {}

                    /** @param array{id: positive-int, ...<non-empty-string, positive-int>} $b */
                    function passesShape(array $b): void {
                        takesShape($b);
                        takesShape(["id" => 1, "a" => 2, "b" => 3]);
                    }
                ',
            ],
            'strtolowerOfStringLiteralsIsTheLoweredLiterals' => [
                'code' => '<?php
                    /** @return \'Interface\'|\'Trait\'|\'Enum\'|\'Class\' */
                    function kind(): string {
                        return \'Class\';
                    }

                    /** @param \'interface\'|\'trait\'|\'enum\'|\'class\' $kind */
                    function check(string $kind): void {}

                    check(strtolower(kind()));
                    check(mb_strtolower(kind()));

                    $literal = strtolower("ABC");
                    $multibyte = mb_strtolower("ÄBC");
                    $other = strtolower(rand() ? "ABC" : (string) rand());
                    $encoding = mb_strtolower("ABC", "UTF-8");',
                'assertions' => [
                    '$literal===' => '\'abc\'',
                    '$multibyte===' => '\'äbc\'',
                    '$other===' => 'non-empty-lowercase-string',
                    '$encoding===' => 'non-empty-string',
                ],
            ],
            'pathinfoWithAllElementsReturnsTheArray' => [
                'code' => '<?php
                    /** @param non-empty-string $path */
                    function f(string $path, int $flags): void {
                        $all = pathinfo($path, PATHINFO_ALL);
                        $default = pathinfo($path);
                        $mask = pathinfo($path, 15);
                        $extension = pathinfo($path, PATHINFO_EXTENSION);
                        $unknown = pathinfo($path, $flags);

                        /** @psalm-check-type-exact $all = array{dirname: string, basename: string, extension?: string, filename: string} */
                        /** @psalm-check-type-exact $default = array{dirname: string, basename: string, extension?: string, filename: string} */
                        /** @psalm-check-type-exact $mask = array{dirname: string, basename: string, extension?: string, filename: string} */
                        /** @psalm-check-type-exact $extension = string */
                        /** @psalm-check-type-exact $unknown = array{dirname: string, basename: string, extension?: string, filename: string}|string */
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'aStubWithoutAReturnTypeKeepsTheCallMapOne' => [
                'code' => '<?php
                    function postgres(): PgSql\\Connection {
                        throw new RuntimeException();
                    }
                    $mysql = mysqli_real_escape_string(new mysqli(), "a\'b");
                    $postgres = pg_escape_literal(postgres(), "a\'b");',
                'assertions' => [
                    '$mysql===' => 'string',
                    '$postgres===' => 'false|string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'functionImportedWithAnAliasIsCalledWithoutAWrongCaseIssue' => [
                'code' => '<?php
                    namespace App;

                    use function json_encode as encode;
                    use function strtoupper as up;

                    $json = encode([1]);
                    $upper = up("x");',
                'assertions' => [
                    '$upper===' => 'non-falsy-string',
                ],
            ],
            'functionCalledWithItsDeclaredCasing' => [
                'code' => '<?php
                    namespace Foo;

                    function fooBar(): int {
                        return 1;
                    }

                    /** @param callable $callable */
                    function runThis($callable): void {
                        $callable();
                    }

                    $a = fooBar();
                    $b = \\Foo\\fooBar();
                    $c = strlen("foo");
                    $d = \\strlen("foo");
                    runThis("Foo\\\\fooBar");',
                'assertions' => [
                    '$a===' => 'int',
                    '$c===' => 'int<1, max>',
                ],
            ],
            'fileTestFunctionsKeepAMixedArgument' => [
                'code' => <<<'PHP'
                    <?php
                    function f(mixed $a, mixed $b, mixed $c): void {
                        if (is_link($a)) {
                            /** @psalm-check-type-exact $a = mixed */
                        }

                        if (file_exists($b)) {
                            /** @psalm-check-type-exact $b = mixed */
                        }

                        if (is_dir($c) || is_readable($c)) {
                            /** @psalm-check-type-exact $c = mixed */
                        }
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => ['MixedArgument'],
                'php_version' => '8.1',
            ],
            'fileTestFunctionsKeepAnArgumentContainingMixed' => [
                'code' => <<<'PHP'
                    <?php
                    function f(array $a, string $s): void {
                        $x = $a['k'];
                        if ($x === null) {
                            $x = $s;
                        }

                        if (is_link($x)) {
                            /** @psalm-check-type-exact $x = mixed|string */
                        }
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment', 'MixedArgument'],
                'php_version' => '8.1',
            ],
            'fileTestFunctionsKeepAnSplFileInfoArgument' => [
                'code' => <<<'PHP'
                    <?php
                    function f(SplFileInfo $a, SplFileInfo $b): void {
                        if (is_link($a)) {
                            /** @psalm-check-type-exact $a = SplFileInfo */
                        }

                        if (is_file($b) || file_exists($b)) {
                            /** @psalm-check-type-exact $b = SplFileInfo */
                        }
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => ['ImplicitToStringCast'],
                'php_version' => '8.1',
            ],
            'fileTestFunctionsStillNarrowAStringArgument' => [
                'code' => <<<'PHP'
                    <?php
                    function f(string $a, string $b): void {
                        if (is_link($a)) {
                            /** @psalm-check-type-exact $a = non-empty-string */
                        }

                        if (file_exists($b)) {
                            /** @psalm-check-type-exact $b = non-empty-string */
                        }
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'fileTestFunctionsAssertANonEmptyString' => [
                'code' => '<?php
                    /** @param non-empty-string $path */
                    function takesNonEmptyString(string $path): void {}

                    function check(string $path): void {
                        if (file_exists($path)) {
                            takesNonEmptyString($path);
                        }
                        if (is_file($path)) {
                            takesNonEmptyString($path);
                        }
                        if (is_dir($path)) {
                            takesNonEmptyString($path);
                        }
                        if (is_link($path)) {
                            takesNonEmptyString($path);
                        }
                        if (is_readable($path)) {
                            takesNonEmptyString($path);
                        }
                        if (is_writable($path)) {
                            takesNonEmptyString($path);
                        }
                        if (is_writeable($path)) {
                            takesNonEmptyString($path);
                        }
                        if (is_executable($path)) {
                            takesNonEmptyString($path);
                        }
                    }',
            ],
            'fileTestFunctionsKeepAPossiblyEmptyStringWhenFalse' => [
                'code' => '<?php
                    function check(string $path): void {
                        if (!file_exists($path)) {
                            $a = $path;
                        } else {
                            $a = "x";
                        }
                        if (is_dir($path)) {
                            $b = "x";
                        } else {
                            $b = $path;
                        }
                    }

                    /** @var string $path */
                    $path = "";
                    if (is_file($path)) {
                        $c = "x";
                    } else {
                        $c = $path;
                    }',
                'assertions' => [
                    '$c===' => 'string',
                ],
            ],
            'filterVarEmptyStringNullOnlyForAnEmptyInput' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @param string $s
                     * @param non-empty-string $non_empty
                     */
                    function filter(string $s, string $non_empty, bool $flag): void {
                        $stripped = filter_var($s, FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL | FILTER_FLAG_STRIP_LOW);
                        $stripped_non_empty = filter_var(
                            $non_empty,
                            FILTER_DEFAULT,
                            FILTER_FLAG_EMPTY_STRING_NULL | FILTER_FLAG_STRIP_LOW,
                        );
                        $literal = filter_var('abc', FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
                        $false = filter_var(false, FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
                        $bool = filter_var($flag, FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);
                        $unsafe_raw = filter_var($s, FILTER_UNSAFE_RAW, FILTER_FLAG_EMPTY_STRING_NULL);
                        $encoded_literal = filter_var(
                            'a&b',
                            FILTER_DEFAULT,
                            FILTER_FLAG_EMPTY_STRING_NULL | FILTER_FLAG_ENCODE_AMP,
                        );
                        $stripped_literal = filter_var('abc', FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL | FILTER_FLAG_STRIP_LOW);
                        $null = filter_var(null, FILTER_DEFAULT, FILTER_FLAG_EMPTY_STRING_NULL);

                        /** @psalm-check-type-exact $stripped = string|null */
                        /** @psalm-check-type-exact $stripped_non_empty = string */
                        /** @psalm-check-type-exact $literal = 'abc' */
                        /** @psalm-check-type-exact $false = null */
                        /** @psalm-check-type-exact $bool = '1'|null */
                        /** @psalm-check-type-exact $unsafe_raw = non-empty-string|null */
                        /** @psalm-check-type-exact $encoded_literal = non-empty-string */
                        /** @psalm-check-type-exact $stripped_literal = string */
                        /** @psalm-check-type-exact $null = null */
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'filterVarEmptyStringNullOfTheDefaultFilter' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @param string $s
                     * @param non-empty-string $non_empty
                     */
                    function filter(string $s, string $non_empty): void {
                        $maybe_empty = filter_var($s, options: FILTER_FLAG_EMPTY_STRING_NULL);
                        $empty = filter_var('', options: FILTER_FLAG_EMPTY_STRING_NULL);
                        $filled = filter_var($non_empty, options: FILTER_FLAG_EMPTY_STRING_NULL);

                        /** @psalm-check-type-exact $maybe_empty = non-empty-string|null */
                        /** @psalm-check-type-exact $empty = null */
                        /** @psalm-check-type-exact $filled = non-empty-string */
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'filterVarSanitizesNullAsAnEmptyString' => [
                'code' => <<<'PHP'
                    <?php
                    $default = filter_var(null);
                    $special = filter_var(null, FILTER_SANITIZE_SPECIAL_CHARS);
                    $full_special = filter_var(null, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    $encoded = filter_var(null, FILTER_SANITIZE_ENCODED);
                    $slashes = filter_var(null, FILTER_SANITIZE_ADD_SLASHES);
                    $email = filter_var(null, FILTER_SANITIZE_EMAIL);
                    $url = filter_var(null, FILTER_SANITIZE_URL);
                    $number_int = filter_var(null, FILTER_SANITIZE_NUMBER_INT);
                    $number_float = filter_var(null, FILTER_SANITIZE_NUMBER_FLOAT);
                    $unsafe_raw = filter_var(null, FILTER_UNSAFE_RAW);
                    $stripped = filter_var(null, FILTER_DEFAULT, FILTER_FLAG_STRIP_LOW);
                    $sanitize_string = filter_var(null, FILTER_SANITIZE_STRING);
                    $forced_array = filter_var(null, FILTER_DEFAULT, FILTER_FORCE_ARRAY);

                    /** @psalm-check-type-exact $default = '' */
                    /** @psalm-check-type-exact $special = '' */
                    /** @psalm-check-type-exact $full_special = '' */
                    /** @psalm-check-type-exact $encoded = '' */
                    /** @psalm-check-type-exact $slashes = '' */
                    /** @psalm-check-type-exact $email = '' */
                    /** @psalm-check-type-exact $url = '' */
                    /** @psalm-check-type-exact $number_int = '' */
                    /** @psalm-check-type-exact $number_float = '' */
                    /** @psalm-check-type-exact $unsafe_raw = '' */
                    /** @psalm-check-type-exact $stripped = '' */
                    /** @psalm-check-type-exact $sanitize_string = string */
                    /** @psalm-check-type-exact $forced_array = list{''} */
                    PHP,
                'assertions' => [],
                'ignored_issues' => ['DeprecatedConstant'],
            ],
            'filterVarSanitizesANullableInputWithoutFailing' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @param ?string $s
                     * @param ?non-empty-string $non_empty
                     * @param ?int $i
                     * @param array<?string> $array
                     */
                    function filter(?string $s, ?string $non_empty, ?int $i, array $array): void {
                        $default = filter_var($s);
                        $special = filter_var($non_empty, FILTER_SANITIZE_SPECIAL_CHARS);
                        $email = filter_var($s, FILTER_SANITIZE_EMAIL);
                        $number = filter_var($i, FILTER_SANITIZE_NUMBER_INT);
                        $elements = filter_var($array, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);

                        /** @psalm-check-type-exact $default = string */
                        /** @psalm-check-type-exact $special = string */
                        /** @psalm-check-type-exact $email = string */
                        /** @psalm-check-type-exact $number = ''|numeric-string */
                        /** @psalm-check-type-exact $elements = array<array-key, string>|false */
                    }
                    PHP,
            ],
            'filterVarValidatesNullAsAnEmptyString' => [
                'code' => <<<'PHP'
                    <?php
                    $bool = filter_var(null, FILTER_VALIDATE_BOOLEAN);
                    $bool_null_on_failure = filter_var(null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    $int = filter_var(null, FILTER_VALIDATE_INT);
                    $int_null_on_failure = filter_var(null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
                    $domain = filter_var(null, FILTER_VALIDATE_DOMAIN);
                    $hostname = filter_var(null, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
                    $regexp = filter_var(null, FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/^$/']]);

                    /** @psalm-check-type-exact $bool = false */
                    /** @psalm-check-type-exact $bool_null_on_failure = false */
                    /** @psalm-check-type-exact $int = false */
                    /** @psalm-check-type-exact $int_null_on_failure = null */
                    /** @psalm-check-type-exact $domain = '' */
                    /** @psalm-check-type-exact $hostname = false */
                    /** @psalm-check-type-exact $regexp = ''|false */
                    PHP,
            ],
            'filterVarValidatesABoolAsAString' => [
                'code' => <<<'PHP'
                    <?php
                    function f(bool $b): void {
                        $true_domain = filter_var(true, FILTER_VALIDATE_DOMAIN);
                        $false_hostname = filter_var(false, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
                        $domain = filter_var($b, FILTER_VALIDATE_DOMAIN);
                        $hostname = filter_var($b, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
                        $regexp = filter_var($b, FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/^1?$/']]);

                        /** @psalm-check-type-exact $true_domain = '1' */
                        /** @psalm-check-type-exact $false_hostname = false */
                        /** @psalm-check-type-exact $domain = ''|'1' */
                        /** @psalm-check-type-exact $hostname = '1'|false */
                        /** @psalm-check-type-exact $regexp = ''|'1'|false */
                    }
                    PHP,
            ],
            'filterVarSanitizesAnEmptyLiteralToTheEmptyString' => [
                'code' => <<<'PHP'
                    <?php
                    $special = filter_var('', FILTER_SANITIZE_SPECIAL_CHARS);
                    $full = filter_var('', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    $encoded = filter_var('', FILTER_SANITIZE_ENCODED);
                    $slashes = filter_var('', FILTER_SANITIZE_ADD_SLASHES);
                    $email = filter_var('', FILTER_SANITIZE_EMAIL);
                    $url = filter_var('', FILTER_SANITIZE_URL);
                    $stripped = filter_var('', FILTER_DEFAULT, FILTER_FLAG_STRIP_LOW);

                    /** @psalm-check-type-exact $special = '' */
                    /** @psalm-check-type-exact $full = '' */
                    /** @psalm-check-type-exact $encoded = '' */
                    /** @psalm-check-type-exact $slashes = '' */
                    /** @psalm-check-type-exact $email = '' */
                    /** @psalm-check-type-exact $url = '' */
                    /** @psalm-check-type-exact $stripped = '' */
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'filterVarSanitizeStringWithEmptyStringNull' => [
                'code' => <<<'PHP'
                    <?php
                    function f(string $s): void {
                        $empty = filter_var('', FILTER_SANITIZE_STRING, FILTER_FLAG_EMPTY_STRING_NULL);
                        $string = filter_var($s, FILTER_SANITIZE_STRING, FILTER_FLAG_EMPTY_STRING_NULL);

                        /** @psalm-check-type-exact $empty = null */
                        /** @psalm-check-type-exact $string = null|string */
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => ['DeprecatedConstant'],
                'php_version' => '8.0',
            ],
            'classExistsComparedWithTrueGuardsTheRightOperandOfAnd' => [
                'code' => '<?php
                    function check(): bool {
                        return class_exists(Optional::class) === true && Optional::available();
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'classExistsComparedWithFalseGuardsTheOperandsOfAndAndOr' => [
                'code' => '<?php
                    function check(): bool {
                        return false !== class_exists(Optional::class) && Optional::available();
                    }

                    function checkOr(): bool {
                        return class_exists(Optional::class) === false || Optional::available();
                    }

                    function checkLoose(): bool {
                        return class_exists(Optional::class) == true && Optional::available();
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'lvaluesCanBePassedToByRefParams' => [
                'code' => '<?php
                    class A {
                        public int $p = 1;
                        public static int $s = 1;
                    }
                    function fooFoo(int &$v): void {}
                    $a = ["k" => 1];
                    $o = new A();
                    $v = 1;
                    fooFoo($v);
                    fooFoo($a["k"]);
                    fooFoo($o->p);
                    fooFoo(A::$s);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'classExistsInAFormTheOperandsDoNotFollowGuardsTheRightOperandAsBefore' => [
                'code' => '<?php
                    function check(): bool {
                        return (bool) class_exists(Optional::class) && Optional::available();
                    }

                    function checkOr(): bool {
                        return !(bool) class_exists(Optional::class) || Optional::available();
                    }',
                'assertions' => [],
                'ignored_issues' => ['RedundantCast'],
                'php_version' => '8.1',
            ],
            'negatedClassExistsInAFormTheOperandsDoNotFollowGuardsTheRightOperandOfOrInAnIf' => [
                'code' => '<?php
                    function check(bool $a, bool $b, bool $c, bool $d): void {
                        if (!(bool) class_exists(Optional::class) || Optional::available()) {
                            echo "short";
                        }

                        if ($a || $b || $c || !(bool) class_exists(Optional::class) || Optional::available()) {
                            echo "deep";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['RedundantCast'],
                'php_version' => '8.1',
            ],
            'classExistsAfterUnrelatedAndOperand' => [
                'code' => <<<'PHP'
                    <?php
                    if (rand(0, 1) && class_exists(Foo::class)) {
                        new Foo();
                    }
                    PHP,
            ],
            'classExistsBeforeUnrelatedAndOperand' => [
                'code' => <<<'PHP'
                    <?php
                    if (class_exists(Foo::class) && rand(0, 1)) {
                        new Foo();
                    }
                    PHP,
            ],
            'classExistsBetweenUnrelatedAndOperands' => [
                'code' => <<<'PHP'
                    <?php
                    if (rand(0, 1) && class_exists(Foo::class) && rand(0, 1)) {
                        new Foo();
                    }
                    PHP,
            ],
            'classExistsInElseifAnd' => [
                'code' => <<<'PHP'
                    <?php
                    if (rand(0, 1)) {
                        echo 1;
                    } elseif (rand(0, 1) && class_exists(Foo::class)) {
                        new Foo();
                    }
                    PHP,
            ],
            'classExistsInAndChainExpression' => [
                'code' => <<<'PHP'
                    <?php
                    echo rand(0, 1) && class_exists(Foo::class) && new Foo();
                    PHP,
            ],
            'classExistsInAndTernary' => [
                'code' => <<<'PHP'
                    <?php
                    echo rand(0, 1) && class_exists(Foo::class) ? json_encode(new Foo()) : '';
                    PHP,
            ],
            'internalFunctionsPreferringRefsAcceptAnAssignment' => [
                'code' => '<?php
                    $read = [STDIN];
                    $r = stream_select($read, $w = null, $e = null, 0);',
                'assertions' => [],
            ],
            'existenceChecksOfALowercaseOrLiteralStringKeepTheNameType' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param non-empty-lowercase-string $function */
                    function functionOf(string $function): void {
                        if (function_exists($function)) {
                            /** @psalm-check-type-exact $function = callable-string */
                        }
                    }

                    /** @param lowercase-string $class */
                    function classOf(string $class): void {
                        if (class_exists($class)) {
                            /** @psalm-check-type-exact $class = class-string */
                        }
                    }

                    /** @param literal-string $name */
                    function interfaceOf(string $name): void {
                        if (interface_exists($name)) {
                            /** @psalm-check-type-exact $name = interface-string */
                        }
                    }

                    /** @param non-empty-lowercase-string $name */
                    function callableOf(string $name): void {
                        if (is_callable($name)) {
                            /** @psalm-check-type-exact $name = callable-string */
                        }
                    }

                    /** @param non-empty-literal-string $name */
                    function literalCallableOf(string $name): void {
                        if (is_callable($name)) {
                            /** @psalm-check-type-exact $name = callable-string */
                        }
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'zeroIsANonEmptyLowercaseString' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param non-empty-lowercase-string $s */
                    function takesNonEmptyLowercase(string $s): void {}

                    takesNonEmptyLowercase('0');
                    takesNonEmptyLowercase('a');
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'propertyExistsOfAnUnknownOrDynamicPropertyIsNotReported' => [
                'code' => '<?php
                    class Page {
                        public string $title = "";
                    }

                    function check(Page $page, object $object, string $name): void {
                        if (property_exists($page, "missing")) {
                            echo "dynamic";
                        }

                        if (property_exists($page, $name)) {
                            echo "named";
                        }

                        if (property_exists($object, "title")) {
                            echo "object";
                        }

                        if (property_exists(Page::class, "title") || property_exists("Other", "title")) {
                            echo "class name";
                        }
                    }',
            ],
            'arrayFilterWithAStringCallableNarrowsTheResultOfACall' => [
                'code' => '<?php
                    /** @return list<object|null> */
                    function objects(): array {
                        return [];
                    }

                    /** @return list<int|string|null> */
                    function scalars(): array {
                        return [];
                    }

                    $objects = array_values(array_filter(objects(), "is_object"));
                    $ints = array_filter(scalars(), "is_int");
                    $unchanged = array_filter(objects(), "boolval");',
                'assertions' => [
                    '$objects===' => 'list<object>',
                    '$ints===' => 'array<int<0, max>, int>',
                    '$unchanged===' => 'array<int<0, max>, null|object>',
                ],
            ],
            'fcloseTakesTheStandardStreamConstants' => [
                'code' => '<?php
                    fclose(STDIN);
                    fclose(STDOUT);
                    fclose(STDERR);',
            ],
            'domXPathEvaluateReturnsATypedResult' => [
                'code' => '<?php
                    $xpath = new DOMXPath(new DOMDocument());
                    $value = $xpath->evaluate("count(//a)");

                    if (is_numeric($value)) {
                        echo "number";
                    }

                    if (is_string($value)) {
                        echo "string";
                    }

                    if ($value instanceof DOMNodeList) {
                        echo "nodes";
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'filterVarIntWithFloatLiteralsOutsideTheRangeOfAnInt' => [
                'code' => '<?php
                    $big = filter_var(1e30, FILTER_VALIDATE_INT);
                    $notANumber = filter_var(NAN, FILTER_VALIDATE_INT);
                    $whole = filter_var(5.0, FILTER_VALIDATE_INT);
                    $bigMin = filter_var(5, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1e30]]);
                    $notANumberMax = filter_var(5, FILTER_VALIDATE_INT, ["options" => ["max_range" => NAN]]);
                    $knownMinAndNotANumberMax = filter_var(
                        5,
                        FILTER_VALIDATE_INT,
                        ["options" => ["min_range" => 0, "max_range" => NAN]],
                    );
                    $bigMinAndKnownMax = filter_var(
                        5,
                        FILTER_VALIDATE_INT,
                        ["options" => ["min_range" => 1e30, "max_range" => 10]],
                    );
                    $knownRange = filter_var(5, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0, "max_range" => 10]]);',
                'assertions' => [
                    '$big===' => 'false',
                    '$notANumber===' => 'false',
                    '$whole===' => '5',
                    '$bigMin===' => 'false|int',
                    '$notANumberMax===' => 'false|int',
                    '$knownMinAndNotANumberMax===' => 'false|int',
                    '$bigMinAndKnownMax===' => 'false|int',
                    '$knownRange===' => '5',
                ],
            ],

            'multibyteLiteralArgument' => [
                'code' => '<?php
                    function startsWithGreeting(string $s): bool {
                        return str_starts_with($s, "здравствуй");
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'classHierarchyFunctionsOfAnObjectNeverReturnFalse' => [
                'code' => '<?php
                    /** @var mixed $mixed */
                    $mixed = null;
                    /** @var object $object */
                    $object = new stdClass();
                    /** @var string $string */
                    $string = "";
                    /** @var object|string $either */
                    $either = "";
                    /** @var bool $autoload */
                    $autoload = true;

                    if (!is_object($mixed)) {
                        throw new Exception();
                    }

                    $parents = class_parents($mixed);
                    $interfaces = class_implements($object, $autoload);
                    $traits = class_uses(object_or_class: $object, autoload: false);
                    $parents_of_name = class_parents($string);
                    $interfaces_of_name = class_implements($string, false);
                    $traits_of_name = class_uses($string);
                    $parents_of_either = class_parents($either);',
                'assertions' => [
                    '$parents===' => 'array<class-string, class-string>',
                    '$interfaces===' => 'array<interface-string, interface-string>',
                    '$traits===' => 'array<trait-string, trait-string>',
                    '$parents_of_name===' => 'array<class-string, class-string>|false',
                    '$interfaces_of_name===' => 'array<interface-string, interface-string>|false',
                    '$traits_of_name===' => 'array<trait-string, trait-string>|false',
                    '$parents_of_either===' => 'array<class-string, class-string>|false',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'classHierarchyFunctionsOfAClassNameAreNotPossiblyFalseArguments' => [
                'code' => '<?php
                    interface I {}

                    /** @param class-string $class */
                    function f(string $class): bool {
                        return in_array(I::class, class_implements($class), true)
                            || in_array(I::class, class_parents($class), true)
                            || in_array(I::class, class_uses($class), true);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'substrOfANonEmptyStringFromItsStart' => [
                'code' => '<?php
                    /** @var non-empty-string $in */
                    $in = "a";
                    /** @var string $any */
                    $any = "";
                    /** @var int $int */
                    $int = 1;
                    /** @var int<1, max> $positive */
                    $positive = 1;
                    /** @var int<min, -1> $negative */
                    $negative = -1;
                    /** @var int<0, max> $not_negative */
                    $not_negative = 0;
                    /** @var int<min, 0> $not_positive */
                    $not_positive = 0;

                    $prefix = substr($in, 0, 4);
                    $tail = substr($in, -3);
                    $whole = substr($in, 0);
                    $explicit_null = substr($in, 0, null);
                    $dynamic_length = substr($in, 0, $positive);
                    $dynamic_offset = substr($in, $not_positive, 2);
                    $literal = substr("abc", 0, 2);

                    $zero_length = substr($in, 0, 0);
                    $unknown_length = substr($in, 0, $int);
                    $not_negative_length = substr($in, 0, $not_negative);
                    $negative_length = substr($in, 0, $negative);
                    $later_offset = substr($in, 1, 4);
                    $unknown_offset = substr($in, $int, 4);
                    $positive_offset = substr($in, $positive, 4);
                    $maybe_empty_input = substr($any, 0, 4);
                    $empty_literal = substr("", 0, 4);',
                'assertions' => [
                    '$prefix===' => 'non-empty-string',
                    '$tail===' => 'non-empty-string',
                    '$whole===' => 'non-empty-string',
                    '$explicit_null===' => 'non-empty-string',
                    '$dynamic_length===' => 'non-empty-string',
                    '$dynamic_offset===' => 'non-empty-string',
                    '$literal===' => 'non-empty-string',
                    '$zero_length===' => 'string',
                    '$unknown_length===' => 'string',
                    '$not_negative_length===' => 'string',
                    '$negative_length===' => 'string',
                    '$later_offset===' => 'string',
                    '$unknown_offset===' => 'string',
                    '$positive_offset===' => 'string',
                    '$maybe_empty_input===' => 'string',
                    '$empty_literal===' => 'string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'substrBeforePhp8CanReturnFalse' => [
                'code' => '<?php
                    /** @var non-empty-string $in */
                    $in = "a";
                    $prefix = substr($in, 0, 4);',
                'assertions' => [
                    '$prefix===' => 'false|string',
                ],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'errorGetLastHasBacktraceSincePhp85' => [
                'code' => '<?php
                    $error = error_get_last();',
                'assertions' => [
                    '$error===' => 'array{file: string, line: int, message: string, trace?: list<array{args?: list<mixed>, class?: class-string, file?: string, function: string, line?: int, object?: object, type?: string}>, type: int}|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'errorGetLastHasNoBacktraceBeforePhp85' => [
                'code' => '<?php
                    $error = error_get_last();',
                'assertions' => [
                    '$error===' => 'array{file: string, line: int, message: string, type: int}|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'peclFunctionsOfExtensionsThePhp86ImageLacks' => [
                'code' => '<?php
                    $session = ssh2_connect("example.com", 22);
                    $version = swoole_version();
                    $string = _string("abc");
                    $serialized = igbinary_serialize([1]);',
                'assertions' => [
                    '$version===' => 'string',
                    '$string===' => 'Swoole\StringObject',
                ],
                'ignored_issues' => ['MixedAssignment'],
                'php_version' => '8.6',
            ],
            'clampOfIntegers' => [
                'code' => '<?php
                    /** @var int $i */;
                    $result = clamp($i, 0, 100);',
                'assertions' => [
                    '$result===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'clampOfFloats' => [
                'code' => '<?php
                    /** @var float $f */;
                    $result = clamp($f, 0.0, 1.0);',
                'assertions' => [
                    '$result===' => 'float',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'clampOfMixedIntAndFloat' => [
                'code' => '<?php
                    /** @var int $i */;
                    /** @var float $f */;
                    $result = clamp($i, 0.5, $f);',
                'assertions' => [
                    '$result===' => 'float|int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'clampOfObjects' => [
                'code' => '<?php
                    $d = new DateTimeImmutable();
                    $min = new DateTimeImmutable("yesterday");
                    $max = new DateTimeImmutable("tomorrow");
                    $result = clamp($d, $min, $max);',
                'assertions' => [
                    '$result===' => 'DateTimeImmutable',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'clampOfStrings' => [
                'code' => '<?php
                    /** @var string $s */;
                    $result = clamp($s, "a", "z");',
                'assertions' => [
                    '$result===' => 'string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'callTemplatedFunctionWithUnionArgument' => [
                'code' => '<?php
                    /** @template T */
                    interface Message {}

                    /** @implements Message<int> */
                    final class FirstMessage implements Message {}

                    /** @implements Message<int> */
                    final class SecondMessage implements Message {}

                    /**
                     * @template T
                     * @param Message<T> $msg
                     */
                    function test(Message $msg): void {}

                    /** @var FirstMessage|SecondMessage $message */;
                    test($message);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'callFunctionWithTemplateClassStringWillNotFail' => [
                'code' => '<?php
                    /** @param class-string<SplFixedArray<string>> $classString */
                    function acceptTemplatedClassString(string $classString): void
                    {
                    }

                    /** @param class-string<SplFixedArray<string>> $classString */
                    function app(string $classString): void
                    {
                        acceptTemplatedClassString($classString);
                    }',
            ],
            'inferGenericListFromTuple' => [
                'code' => '<?php
                    /**
                     * @template A
                     * @param list<A> $list
                     * @return list<A>
                     */
                    function testList(array $list): array { return $list; }
                    /**
                     * @template A
                     * @param non-empty-list<A> $list
                     * @return non-empty-list<A>
                     */
                    function testNonEmptyList(array $list): array { return $list; }
                    /**
                     * @template A of list<mixed>
                     * @param A $list
                     * @return A
                     */
                    function testGenericList(array $list): array { return $list; }
                    $list = testList([1, 2, 3]);
                    $nonEmptyList = testNonEmptyList([1, 2, 3]);
                    $genericList = testGenericList([1, 2, 3]);',
                'assertions' => [
                    '$list===' => 'list<1|2|3>',
                    '$nonEmptyList===' => 'non-empty-list<1|2|3>',
                    '$genericList===' => 'list{1, 2, 3}',
                ],
            ],
            'inferIterableFromTraversable' => [
                'code' => '<?php
                    /**
                     * @return SplFixedArray<string>
                     */
                    function getStrings(): SplFixedArray
                    {
                        return SplFixedArray::fromArray(["fst", "snd", "thr"]);
                    }
                    /**
                     * @return SplFixedArray<int>
                     */
                    function getIntegers(): SplFixedArray
                    {
                        return SplFixedArray::fromArray([1, 2, 3]);
                    }
                    /**
                     * @template K
                     * @template A
                     * @template B
                     * @param iterable<K, A> $lhs
                     * @param iterable<K, B> $rhs
                     * @return iterable<K, A|B>
                     */
                    function mergeIterable(iterable $lhs, iterable $rhs): iterable
                    {
                        foreach ($lhs as $k => $v) { yield $k => $v; }
                        foreach ($rhs as $k => $v) { yield $k => $v; }
                    }
                    $iterable = mergeIterable(getStrings(), getIntegers());',
                'assertions' => [
                    '$iterable===' => 'iterable<int, int|string>',
                ],
            ],
            'inferTypeFromAnonymousObjectWithTemplatedProperty' => [
                'code' => '<?php
                    /** @template T */
                    final class Value
                    {
                        /** @param T $value */
                        public function __construct(public readonly mixed $value) {}
                    }
                    /**
                     * @template T
                     * @param object{value: T} $object
                     * @return T
                     */
                    function getValue(object $object): mixed
                    {
                        return $object->value;
                    }
                    /**
                     * @template T
                     * @param object{value: object{value: T}} $object
                     * @return T
                     */
                    function getNestedValue(object $object): mixed
                    {
                        return $object->value->value;
                    }
                    $object = new Value(new Value(42));
                    $value = getValue($object);
                    $nestedValue = getNestedValue($object);',
                'assertions' => [
                    '$value===' => 'Value<42>',
                    '$nestedValue===' => '42',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'paramDefaultNewObjectImplementingInterface' => [
                'code' => '<?php
                    function f(Traversable $p = new ArrayObject()): void {}
                    function g(Countable&Traversable $p = new ArrayObject()): void {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'paramDefaultNonFinalClassMayImplementInterface' => [
                'code' => '<?php
                    interface I {}
                    class Foo {}
                    class Impl extends Foo implements I {}
                    /** @var Foo */
                    const C = new Impl();
                    function f(I $p = C): void {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'inferTypeFromAnonymousObjectWithTemplatedPropertyFromTemplatedAncestor' => [
                'code' => '<?php
                    /** @template T */
                    abstract class AbstractValue
                    {
                        /** @param T $value */
                        public function __construct(public readonly mixed $value) {}
                    }
                    /**
                     * @template TValue
                     * @extends AbstractValue<TValue>
                     */
                    final class ConcreteValue extends AbstractValue
                    {
                        /**
                         * @param TValue $value
                         */
                        public function __construct(mixed $value)
                        {
                            parent::__construct($value);
                        }
                    }
                    /**
                     * @template T
                     * @param object{value: T} $object
                     * @return T
                     */
                    function getValue(object $object): mixed
                    {
                        return $object->value;
                    }
                    /**
                     * @template T
                     * @param object{value: object{value: T}} $object
                     * @return T
                     */
                    function getNestedValue(object $object): mixed
                    {
                        return $object->value->value;
                    }
                    $object = new ConcreteValue(new ConcreteValue(42));
                    $value = getValue($object);
                    $nestedValue = getNestedValue($object);',
                'assertions' => [
                    '$value===' => 'ConcreteValue<42>',
                    '$nestedValue===' => '42',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'inferTypeFromAnonymousObjectWithTemplatedPropertyFromConcreteAncestor' => [
                'code' => '<?php
                    /** @template T */
                    abstract class AbstractValue
                    {
                        /** @param T $value */
                        public function __construct(public readonly mixed $value) {}
                    }
                    /** @extends AbstractValue<int> */
                    final class IntValue extends AbstractValue {}
                    final class Nested
                    {
                        public function __construct(public readonly IntValue $value) {}
                    }
                    /**
                     * @template T
                     * @param object{value: T} $object
                     * @return T
                     */
                    function getValue(object $object): mixed
                    {
                        return $object->value;
                    }
                    /**
                     * @template T
                     * @param object{value: object{value: T}} $object
                     * @return T
                     */
                    function getNestedValue(object $object): mixed
                    {
                        return $object->value->value;
                    }
                    $object = new Nested(new IntValue(42));
                    $value = getValue($object);
                    $nestedValue = getNestedValue($object);',
                'assertions' => [
                    '$value===' => 'IntValue',
                    '$nestedValue===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'countShapedArrays' => [
                'code' => '<?php
                    /** @var array{a?: int} */
                    $a = [];
                    $aCount = count($a);

                    /** @var array{a: int} */
                    $b = [];
                    $bCount = count($b);

                    /** @var array{a: int, b?: int} */
                    $c = [];
                    $cCount = count($c);

                    /** @var array{a: int}&array */
                    $d = [];
                    $dCount = count($d);

                    /** @var list{0?: int} */
                    $e = [];
                    $eCount = count($e);

                    /** @var list{int} */
                    $f = [];
                    $fCount = count($f);

                    /** @var list{0: int, 1?: int} */
                    $g = [];
                    $gCount = count($g);

                    /** @var list{0: int, 1?: int}&array */
                    $h = [];
                    $hCount = count($h);',
                'assertions' => [
                    '$aCount===' => 'int<0, 1>',
                    '$bCount===' => '1',
                    '$cCount===' => 'int<1, 2>',
                    '$dCount===' => 'int<1, max>',
                    '$eCount===' => 'int<0, 1>',
                    '$fCount===' => '1',
                    '$gCount===' => 'int<1, 2>',
                    '$hCount===' => 'int<1, max>',
                ],
            ],
            'preg_grep' => [
                'code' => '<?php
                  /**
                   * @param array<int,string> $strings
                   * @return array<int,string>
                   */
                  function filter(array $strings): array {
                     return preg_grep("/search/", $strings, PREG_GREP_INVERT);
                  }
                ',
            ],
            'pregGrepCanReturnFalse' => [
                'code' => '<?php
                    $rc = preg_grep("/^foo/i", ["foobar", "toobar"]);

                    if ($rc === false) {
                        $r = null;
                    } else {
                        $r = $rc;
                    }',
                'assertions' => [
                    '$rc===' => 'array<int<0, 1>, string>|false',
                    '$r===' => 'array<int<0, 1>, string>|null',
                ],
            ],
            'typedArrayWithDefault' => [
                'code' => '<?php
                    class A {}

                    /** @param array<A> $a */
                    function fooFoo(array $a = []): void {

                    }',
            ],
            'abs' => [
                'code' => '<?php
                    $a = abs(-5);
                    $b = abs(-7.5);
                    $c = $_GET["c"];
                    $c = is_numeric($c) ? abs($c) : null;',
                'assertions' => [
                    '$a' => 'int<0, max>',
                    '$b' => 'float',
                    '$c' => 'float|int<0, max>|null',
                ],
                'ignored_issues' => ['MixedAssignment', 'MixedArgument'],
            ],
            'validDocblockParamDefault' => [
                'code' => '<?php
                    /**
                     * @param  int|false $p
                     * @return void
                     */
                    function f($p = false) {}',
            ],
            'byRefNewString' => [
                'code' => '<?php
                    function fooFoo(?string &$v): void {}
                    fooFoo($a);',
            ],
            'byRefVariableFunctionExistingArray' => [
                'code' => '<?php
                    $arr = [];
                    function fooFoo(array &$v): void {}
                    $function = "fooFoo";
                    $function($arr);
                    if ($arr) {}',
            ],
            'byRefProperty' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        public $foo = "hello";
                    }

                    $a = new A();

                    function fooFoo(string &$v): void {}

                    fooFoo($a->foo);',
            ],
            'namespaced' => [
                'code' => '<?php
                    namespace A;

                    /** @return void */
                    function f(int $p) {}
                    f(5);',
            ],
            'namespacedRootFunctionCall' => [
                'code' => '<?php
                    namespace {
                        /** @return void */
                        function foo() { }
                    }
                    namespace A\B\C {
                        foo();
                    }',
            ],
            'namespacedAliasedFunctionCall' => [
                'code' => '<?php
                    namespace Aye {
                        /** @return void */
                        function foo() { }
                    }
                    namespace Bee {
                        use Aye as A;

                        A\foo();
                    }',
            ],
            'noRedundantConditionAfterArrayObjectCountCheck' => [
                'code' => '<?php
                    /** @var ArrayObject<int, int> */
                    $a = [];
                    $b = 5;
                    if (count($a)) {}',
            ],
            'noRedundantConditionAfterMixedOrEmptyArrayCountCheck' => [
                'code' => '<?php
                    function foo(string $s) : void {
                        $a = $GLOBALS["s"] ?: [];
                        if (count($a)) {}
                        if (!count($a)) {}
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment', 'MixedArgument'],
            ],
            'noRedundantErrorForCallableStrToLower' => [
                'code' => <<<'PHP'
                    <?php
                    /** @var callable-string */
                    $function = "strlen";
                    strtolower($function);
                PHP,
            ],
            'objectLikeArrayAssignmentInConditional' => [
                'code' => '<?php
                    $a = [];

                    if (rand(0, 1)) {
                        $a["a"] = 5;
                    }

                    if (count($a)) {}
                    if (!count($a)) {}',
            ],
            'noRedundantConditionAfterCheckingExplodeLength' => [
                'code' => '<?php
                    /** @var string */
                    $s = "hello";
                    $segments = explode(".", $s);
                    if (count($segments) === 1) {}',
            ],
            'arrayPopNonEmptyAfterThreeAssertions' => [
                'code' => '<?php
                    class A {}
                    class B extends A {
                        /** @var array<int, string> */
                        public $arr = [];
                    }

                    /** @var array<A> */
                    $replacement_stmts = [];

                    if (!$replacement_stmts
                        || !$replacement_stmts[0] instanceof B
                        || count($replacement_stmts[0]->arr) > 1
                    ) {
                        return null;
                    }

                    $b = $replacement_stmts[0]->arr;',
                'assertions' => [
                    '$b' => 'array<int, string>',
                ],
            ],
            'countMoreThan0CanBeInverted' => [
                'code' => '<?php
                    $a = [];

                    if (rand(0, 1)) {
                        $a[] = "hello";
                    }

                    if (count($a) > 0) {
                        exit;
                    }',
                    'assertions' => [
                        '$a' => 'array<never, never>',
                    ],
            ],
            'countCheckOnNonEmptyArray' => [
                'code' => '<?php
                    /** @param non-empty-array<string> $arr */
                    function foo(array $arr): void {
                        if (count($arr) > 5) {}
                    }',
            ],
            'byRefAfterCallable' => [
                'code' => '<?php
                    /**
                     * @param callable $callback
                     * @return void
                     */
                    function route($callback) {
                      if (!is_callable($callback)) {  }
                      $a = preg_match("//", "", $b);
                      if ($b[0]) {}
                    }',
                'assertions' => [],
                'ignored_issues' => [
                    'MixedAssignment',
                    'MixedArrayAccess',
                    'RedundantConditionGivenDocblockType',
                ],
            ],
            'ignoreNullablePregReplace' => [
                'code' => '<?php
                    function foo(string $s): string {
                        $s = preg_replace("/hello/", "", $s);
                        if ($s === null) {
                            return "hello";
                        }
                        return $s;
                    }
                    function bar(string $s): string {
                        $s = preg_replace("/hello/", "", $s);
                        return $s;
                    }
                    function bat(string $s): ?string {
                        $s = preg_replace("/hello/", "", $s);
                        return $s;
                    }',
            ],
            'pregReplaceArrayValueType' => [
                'code' => '<?php
                    /**
                     * @param string[] $s
                     * @return string[]
                     */
                    function foo($s): array {
                        return preg_replace("/hello/", "", $s);
                    }',
            ],
            'extractVarCheck' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress InvalidReturnType
                     * @return array{a: 15, ...}
                     */
                    function getUnsealedArray() {}
                    function takesString(string $str): void {}

                    $foo = "foo";
                    $a = getUnsealedArray();
                    extract($a);
                    takesString($foo);',
                'assertions' => [],
                'ignored_issues' => [
                    'MixedArgument',
                ],
            ],
            'extractVarCheckValid' => [
                'code' => '<?php
                    function takesInt(int $i): void {}

                    $foo = "foo";
                    $a = [$foo => 15];
                    extract($a);
                    takesInt($foo);',
            ],
            'extractSkipExtr' => [
                'code' => '<?php
                    $a = 1;

                    extract(["a" => "x", "b" => "y"], EXTR_SKIP);',
                'assertions' => [
                    '$a===' => '1',
                    '$b===' => '\'y\'',
                ],
            ],
            'extractSkipDefinesAPossiblyUndefinedVariable' => [
                'code' => '<?php
                    if (rand(0, 1)) {
                        $a = 1;
                    }

                    extract(["a" => 2], EXTR_SKIP);

                    $b = $a;',
                'assertions' => [
                    '$b===' => '1|2',
                ],
            ],
            'extractSkipDefinesAVariableOfAPreviousIteration' => [
                'code' => '<?php
                    function f(array $xs): void {
                        foreach ($xs as $_) {
                            extract(["a" => 2], EXTR_SKIP);
                            /** @psalm-check-type-exact $a = 1|2 */;
                            echo $a;
                            $a = 1;
                        }
                    }',
            ],
            'compact' => [
                'code' => '<?php
                    /**
                     * @return array<string, mixed>
                     */
                    function test(): array {
                        return compact(["val"]);
                    }',
            ],
            'objectLikeKeyChecksAgainstGeneric' => [
                'code' => '<?php
                    /**
                     * @param array<string, string> $b
                     */
                    function a($b): string
                    {
                      return $b["a"];
                    }

                    a(["a" => "hello"]);',
            ],
            'objectLikeKeyChecksAgainstTKeyedArray' => [
                'code' => '<?php
                    /**
                     * @param array{a: string} $b
                     */
                    function a($b): string
                    {
                      return $b["a"];
                    }

                    a(["a" => "hello"]);',
            ],
            'getenvCalledWithOtherCasing' => [
                'code' => '<?php
                    $a = getEnv("some_key");
                    $b = GETENV();
                    $c = getenv("some_key", true);',
                'assertions' => [
                    '$a===' => 'false|string',
                    '$b===' => 'array<string, string>',
                    '$c===' => 'false|string',
                ],
                'ignored_issues' => ['WrongCaseFunction'],
            ],
            'functionExistsGuardsTheRightOperandInsideAnotherNarrowingCondition' => [
                'code' => '<?php
                    function a(int $arg): void {
                        if ($arg) {
                            if (function_exists("do_something_a") && rand(0, 1)) {
                                do_something_a("hello");
                            }
                        }
                    }

                    function b(int $arg): void {
                        if (!empty($arg)) {
                            if (function_exists("do_something_b") && do_something_b("hello")) {
                                echo 1;
                            }
                        }
                    }

                    function c(int $arg): void {
                        if ($arg) {
                            if (defined("SOME_MISSING_CONSTANT") && SOME_MISSING_CONSTANT) {
                                echo 1;
                            }
                        }
                    }',
            ],
            'functionExistsGuardsOnlyTheFunctionsItNames' => [
                'code' => '<?php
                    function callable_string(int $arg): void {
                        if (function_exists("guarded_callback")) {
                            array_map("guarded_callback", [1]);
                        }
                    }

                    function namespaced_name(int $arg): void {
                        if ($arg) {
                            if (function_exists("\\guarded_root") && \guarded_root()) {
                                echo 1;
                            }
                        }
                    }

                    function guarded_class(int $arg): void {
                        if ($arg) {
                            if (class_exists("GuardedClass") && GuardedClass::run()) {
                                echo 1;
                            }
                        }
                    }

                    function guarded_class_body(): void {
                        if (class_exists("GuardedBodyClass") && rand(0, 1)) {
                            GuardedBodyClass::run();
                        }
                    }',
            ],
            'getenv' => [
                'code' => '<?php
                    $a = getenv();
                    $b = getenv("some_key");',
                'assertions' => [
                    '$a' => 'array<string, string>',
                    '$b' => 'false|string',
                ],
            ],
            'ignoreFalsableFileGetContents' => [
                'code' => '<?php
                    function foo(string $s): string {
                        return file_get_contents($s);
                    }
                    function bar(string $s): string {
                        $a = file_get_contents($s);
                        if ($a === false) {
                            return "hello";
                        }
                        return $a;
                    }
                    /**
                     * @return false|string
                     */
                    function bat(string $s) {
                        return file_get_contents($s);
                    }',
            ],
            'validCallables' => [
                'code' => '<?php
                    class A {
                        public static function b() : void {}
                    }

                    function c() : void {}

                    ["a", "b"]();
                    "A::b"();
                    "c"();',
            ],
            'noInvalidOperandForCoreFunctions' => [
                'code' => '<?php
                    function foo(string $a, string $b) : int {
                        $aTime = strtotime($a);
                        $bTime = strtotime($b);

                        return $aTime - $bTime;
                    }',
            ],
            'functionCallInGlobalScope' => [
                'code' => '<?php
                    $a = function() use ($argv) : void {};',
            ],
            'varExport' => [
                'code' => '<?php
                    $a = var_export(["a"], true);',
                'assertions' => [
                    '$a' => 'string',
                ],
            ],
            'varExportConstFetch' => [
                'code' => '<?php
                    class Foo {
                        const BOOL_VAR_EXPORT_RETURN = true;

                        /**
                         * @param mixed $mixed
                         */
                        public static function Baz($mixed) : string {
                            return var_export($mixed, self::BOOL_VAR_EXPORT_RETURN);
                        }
                    }',
            ],
            'explode' => [
                'code' => '<?php
                    /** @var string $string */
                    $elements = explode(" ", $string);',
                'assertions' => [
                    '$elements' => 'non-empty-list<string>',
                ],
            ],
            'pathinfoOfStringMayLackDirname' => [
                'code' => '<?php
                    /** @var string $path */
                    $info = pathinfo($path);',
                'assertions' => [
                    '$info===' => 'array{basename: string, dirname?: string, extension?: string, filename: string}',
                ],
            ],
            'pathinfoOfEmptyStringMayLackDirname' => [
                'code' => '<?php
                    $info = pathinfo("");',
                'assertions' => [
                    '$info===' => 'array{basename: string, dirname?: string, extension?: string, filename: string}',
                ],
            ],
            'pathinfoOfNonEmptyStringHasDirname' => [
                'code' => '<?php
                    /** @var non-empty-string $path */
                    $info = pathinfo($path);
                    $dir = $info["dirname"];',
                'assertions' => [
                    '$info===' => 'array{basename: string, dirname: string, extension?: string, filename: string}',
                    '$dir===' => 'string',
                ],
            ],
            'pathinfoOfLiteralPathHasDirname' => [
                'code' => '<?php
                    $dir = pathinfo("a/b.c")["dirname"];',
                'assertions' => [
                    '$dir===' => 'string',
                ],
            ],
            'pathinfoOfStringWithFlagReturnsString' => [
                'code' => '<?php
                    /** @var string $path */
                    $dir = pathinfo($path, PATHINFO_DIRNAME);',
                'assertions' => [
                    '$dir===' => 'string',
                ],
            ],
            'explodeWithPositiveLimit' => [
                'code' => '<?php
                    /** @var string $string */
                    $elements = explode(" ", $string, 5);',
                'assertions' => [
                    '$elements' => 'non-empty-list<string>',
                ],
            ],
            'explodeWithNegativeLimit' => [
                'code' => '<?php
                    /** @var string $string */
                    $elements = explode(" ", $string, -5);',
                'assertions' => [
                    '$elements===' => 'list<string>',
                ],
            ],
            'explodeWithMinusOneLimit' => [
                'code' => '<?php
                    $elements = explode("-", "", -1);',
                'assertions' => [
                    '$elements===' => 'list<lowercase-string>',
                ],
            ],
            'explodeWithMinusOneLimitOnString' => [
                'code' => '<?php
                    /** @var string $string */
                    $elements = explode(" ", $string, -1);',
                'assertions' => [
                    '$elements===' => 'list<string>',
                ],
            ],
            'explodeWithPossiblyNegativeLimit' => [
                'code' => '<?php
                    /**
                     * @var string $string
                     * @var int<-1, 1> $limit
                     */
                    $elements = explode(" ", $string, $limit);',
                'assertions' => [
                    '$elements===' => 'list<string>',
                ],
            ],
            'explodeWithNamedNegativeLimit' => [
                'code' => '<?php
                    /** @var string $string */
                    $elements = explode(limit: -1, string: $string, separator: " ");',
                'assertions' => [
                    '$elements===' => 'list<string>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'explodeWithNegativeLimitAccessFirst' => [
                'code' => '<?php
                    function first(string $string): string {
                        return explode(" ", $string, -2)[0] ?? "";
                    }',
            ],
            'explodeWithZeroLimit' => [
                'code' => '<?php
                    /** @var string $string */
                    $elements = explode(" ", $string, 0);',
                'assertions' => [
                    '$elements===' => 'list{string}',
                ],
            ],
            'explodeWithIntMaxLimit' => [
                'code' => '<?php
                    /**
                     * @return non-empty-list<string>
                     */
                    function exploder(string $string): array {
                        return explode(" ", $string, PHP_INT_MAX);
                    }',
            ],
            'explodeLowercaseWithoutLimit' => [
                'code' => '<?php
                    /** @var lowercase-string $string */
                    $elements = explode(" ", $string);',
                'assertions' => [
                    '$elements===' => 'non-empty-list<lowercase-string>',
                ],
            ],
            'explodeWithDynamicLimit' => [
                'code' => '<?php
                    /**
                     * @var string $string
                     * @var int $limit
                     */
                    $elements = explode(" ", $string, $limit);',
                'assertions' => [
                    '$elements' => 'list{0?: string, 1?: string, 2?: string, ...<string>}',
                ],
            ],
            'explodeWithDynamicDelimiter' => [
                'code' => '<?php
                    /**
                     * @var non-empty-string $delim
                     * @var string $string
                     */
                    $elements = explode($delim, $string);',
                'assertions' => [
                    '$elements' => 'non-empty-list<string>',
                ],
            ],
            'explodeWithDynamicDelimiterAndSmallPositiveLimit' => [
                'code' => '<?php
                    /**
                     * @var non-empty-string $delim
                     * @var string $string
                     */
                    $elements = explode($delim, $string, 2);',
                'assertions' => [
                    '$elements' => 'list{0: string, 1?: string}',
                ],
            ],
            'explodeWithDynamicDelimiterAndPositiveLimit' => [
                'code' => '<?php
                    /**
                     * @var non-empty-string $delim
                     * @var string $string
                     */
                    $elements = explode($delim, $string, 5);',
                'assertions' => [
                    '$elements' => 'non-empty-list<string>',
                ],
            ],
            'explodeWithDynamicDelimiterAndNegativeLimit' => [
                'code' => '<?php
                    /**
                     * @var non-empty-string $delim
                     * @var string $string
                     */
                    $elements = explode($delim, $string, -5);',
                'assertions' => [
                    '$elements===' => 'list<string>',
                ],
            ],
            'explodeWithDynamicDelimiterAndLimit' => [
                'code' => '<?php
                    /**
                     * @var non-empty-string $delim
                     * @var string $string
                     * @var int $limit
                     */
                    $elements = explode($delim, $string, $limit);',
                'assertions' => [
                    '$elements' => 'list{0?: string, 1?: string, 2?: string, ...<string>}',
                ],
            ],
            'explodeWithDynamicNonEmptyDelimiter' => [
                'code' => '<?php
                    /**
                     * @var non-empty-string $delim
                     * @var string $string
                     */
                    $elements = explode($delim, $string);',
                'assertions' => [
                    '$elements' => 'non-empty-list<string>',
                ],
            ],
            'explodeWithLiteralNonEmptyDelimiter' => [
                'code' => '<?php
                    /**
                     * @var string $string
                     */
                    $elements = explode(" ", $string);',
                'assertions' => [
                    '$elements' => 'non-empty-list<string>',
                ],
            ],
            'explodeWithPossiblyFalse' => [
                'code' => '<?php
                    /**
                     * @param non-empty-string $d
                     * @return non-empty-list<string>
                     */
                    function exploder(string $d, string $s) : array {
                        return explode($d, $s);
                    }',
            ],
            'assertAcceptsObjectAndNarrowsIt' => [
                'code' => '<?php
                    function f(?DateTime $d): DateTime {
                        assert($d);
                        return $d;
                    }
                    /** @var DateTime|null $foo */
                    $foo = null;
                    assert($foo);',
                'assertions' => [
                    '$foo===' => 'DateTime',
                ],
            ],
            'assertAcceptsArrayAndFloat' => [
                'code' => '<?php
                    function f(array $a, float $f): void {
                        assert($a);
                        assert($f);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
            ],
            'allowPossiblyUndefinedClassInClassExists' => [
                'code' => <<<'PHP'
                    <?php
                    if (class_exists(Foo::class)) {}
                    PHP,
            ],
            'allowPossiblyUndefinedClassInInterfaceExists' => [
                'code' => <<<'PHP'
                    <?php
                    if (interface_exists(Foo::class)) {}
                    PHP,
            ],
            'allowPossiblyUndefinedClassInTraitExists' => [
                'code' => <<<'PHP'
                    <?php
                    if (trait_exists(Foo::class)) {}
                    PHP,
            ],
            'allowPossiblyUndefinedClassInEnumExists' => [
                'code' => <<<'PHP'
                    <?php
                    if (enum_exists(Foo::class)) {}
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'allowConstructorAfterClassExists' => [
                'code' => '<?php
                    function foo(string $s) : void {
                        if (class_exists($s)) {
                            new $s();
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedMethodCall'],
            ],
            'next' => [
                'code' => '<?php
                    $arr = ["one", "two", "three"];
                    $n = next($arr);',
                'assertions' => [
                    '$n' => 'false|string',
                ],
            ],
            'iteratorToArray' => [
                'code' => '<?php
                    /**
                     * @return Generator<stdClass>
                     */
                    function generator(): Generator {
                        yield new stdClass;
                    }

                    $a = iterator_to_array(generator());',
                'assertions' => [
                    '$a' => 'array<array-key, stdClass>',
                ],
            ],
            'iteratorToArrayWithGetIterator' => [
                'code' => '<?php
                    /**
                     * @implements IteratorAggregate<int, string>
                     */
                    class C implements IteratorAggregate {
                        /**
                         * @return Traversable<int,string>
                         */
                        public function getIterator() {
                            yield 1 => "1";
                        }
                    }
                    $a = iterator_to_array(new C);',
                'assertions' => [
                    '$a' => 'array<int, string>',
                ],
            ],
            'iteratorToArrayWithGetIteratorReturningList' => [
                'code' => '<?php
                    /**
                     * @implements IteratorAggregate<int, string>
                     */
                    class C implements IteratorAggregate {
                        /**
                         * @return Traversable<int, string>
                         */
                        public function getIterator() {
                            yield 1 => "1";
                        }
                    }
                    $a = iterator_to_array(new C, false);',
                'assertions' => [
                    '$a' => 'list<string>',
                ],
            ],
            'strtrWithPossiblyFalseFirstArg' => [
                'code' => '<?php
                    /**
                     * @param false|string $str
                     * @param array<string, string> $replace_pairs
                     * @return string
                     */
                    function strtr_wrapper($str, array $replace_pairs) {
                        /** @psalm-suppress PossiblyFalseArgument */
                        return strtr($str, $replace_pairs);
                    }',
            ],
            'versionCompare' => [
                'code' => '<?php
                    /** @return "="|"==" */
                    function getString() : string {
                        return rand(0, 1) ? "==" : "=";
                    }

                    $a = version_compare("5.0.0", "7.0.0");
                    $b = version_compare("5.0.0", "7.0.0", "==");
                    $c = version_compare("5.0.0", "7.0.0", getString());
                ',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'bool',
                    '$c' => 'bool',
                ],
            ],
            'getTimeOfDay' => [
                'code' => '<?php
                    $a = gettimeofday(true) - gettimeofday(true);
                    $b = gettimeofday();
                    $c = gettimeofday(false);',
                'assertions' => [
                    '$a' => 'float',
                    '$b' => 'array<string, int>',
                    '$c' => 'array<string, int>',
                ],
            ],
            'parseUrlArray' => [
                'code' => '<?php
                    function foo(string $s) : string {
                        $parts = parse_url($s);
                        return $parts["host"] ?? "";
                    }

                    function hereisanotherone(string $s) : string {
                        $parsed = parse_url($s);

                        if (isset($parsed["host"])) {
                            return $parsed["host"];
                        }

                        return "";
                    }

                    function hereisthelastone(string $s) : string {
                        $parsed = parse_url($s);

                        if (isset($parsed["host"])) {
                            return $parsed["host"];
                        }

                        return "";
                    }

                    function portisint(string $s) : int {
                        $parsed = parse_url($s);

                        if (isset($parsed["port"])) {
                            return $parsed["port"];
                        }

                        return 80;
                    }

                    function portismaybeint(string $s) : ? int {
                        $parsed = parse_url($s);

                        return $parsed["port"] ?? null;
                    }

                    $porta = parse_url("", PHP_URL_PORT);
                    $porte = parse_url("localhost:443", PHP_URL_PORT);',
                'assertions' => [
                    '$porta' => 'false|int|null',
                    '$porte' => 'false|int|null',
                ],
                'ignored_issues' => ['MixedReturnStatement'],
            ],
            'parseUrlComponent' => [
                'code' => '<?php
                    function foo(string $s) : string {
                        return parse_url($s, PHP_URL_HOST) ?? "";
                    }

                    function bar(string $s) : string {
                        return parse_url($s, PHP_URL_HOST);
                    }

                    function bag(string $s) : string {
                        $host = parse_url($s, PHP_URL_HOST);

                        if (is_string($host)) {
                            return $host;
                        }

                        return "";
                    }',
            ],
            'parseUrlTypes' => [
                'code' => '<?php
                    $url = "foo";
                    $components = parse_url($url);
                    $scheme = parse_url($url, PHP_URL_SCHEME);
                    $host = parse_url($url, PHP_URL_HOST);
                    $port = parse_url($url, PHP_URL_PORT);
                    $user = parse_url($url, PHP_URL_USER);
                    $pass = parse_url($url, PHP_URL_PASS);
                    $path = parse_url($url, PHP_URL_PATH);
                    $query = parse_url($url, PHP_URL_QUERY);
                    $fragment = parse_url($url, PHP_URL_FRAGMENT);',
                'assertions' => [
                    '$components' => 'array{fragment?: string, host?: string, pass?: string, path?: string, port?: int, query?: string, scheme?: string, user?: string}|false',
                    '$scheme' => 'false|null|string',
                    '$host' => 'false|null|string',
                    '$port' => 'false|int|null',
                    '$user' => 'false|null|string',
                    '$pass' => 'false|null|string',
                    '$path' => 'false|null|string',
                    '$query' => 'false|null|string',
                    '$fragment' => 'false|null|string',
                ],
            ],
            'parseUrlDefaultComponent' => [
                'code' => '<?php
                    $component = -1;
                    $url = "foo";
                    $a = parse_url($url, -1);
                    $b = parse_url($url, -42);
                    $c = parse_url($url, $component);',
                'assertions' => [
                    '$a' => 'array{fragment?: string, host?: string, pass?: string, path?: string, port?: int, query?: string, scheme?: string, user?: string}|false',
                    '$b' => 'array{fragment?: string, host?: string, pass?: string, path?: string, port?: int, query?: string, scheme?: string, user?: string}|false',
                    '$c' => 'array{fragment?: string, host?: string, pass?: string, path?: string, port?: int, query?: string, scheme?: string, user?: string}|false',
                ],
            ],
            'triggerUserError' => [
                'code' => '<?php
                    function mightLeave() : string {
                        if (rand(0, 1)) {
                            trigger_error("bad", E_USER_ERROR);
                        } else {
                            return "here";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['DeprecatedFunction'],
            ],
            'getParentClass' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}

                    $b = get_parent_class(new A());
                    if ($b === false) {}
                    $c = new $b();',
                'assertions' => [],
                'ignored_issues' => ['MixedMethodCall'],
            ],
            'suppressError' => [
                'code' => '<?php
                    $a = @file_get_contents("foo");',
                'assertions' => [
                    '$a' => 'false|string',
                ],
            ],
            'echo' => [
                'code' => '<?php
                echo false;',
            ],
            'printrOutput' => [
                'code' => '<?php
                    function foo(string $s) : void {
                        echo $s;
                    }

                    foo(print_r(1, true));',
            ],
            'microtime' => [
                'code' => '<?php
                    $a = microtime(true);
                    $b = microtime();
                    /** @psalm-suppress InvalidScalarArgument */
                    $c = microtime(1);
                    $d = microtime(false);',
                'assertions' => [
                    '$a' => 'float',
                    '$b' => 'string',
                    '$c' => 'float|string',
                    '$d' => 'string',
                ],
            ],
            'filterInput' => [
                'code' => '<?php
                    $a = filter_input(INPUT_GET, "foo", options: FILTER_FORCE_ARRAY);
                    assert(is_array($a));

                    function filterInt(string $s) : int {
                        $filtered = filter_var($s, FILTER_VALIDATE_INT);
                        if ($filtered === false) {
                            return 0;
                        }
                        return $filtered;
                    }
                    function filterNullableInt(string $s) : ?int {
                        return filter_var($s, FILTER_VALIDATE_INT, ["options" => ["default" => null]]);
                    }
                    function filterIntWithDefault(string $s) : int {
                        return filter_var($s, FILTER_VALIDATE_INT, ["options" => ["default" => 5]]);
                    }
                    function filterBool(string $s) : bool {
                        return filter_var($s, FILTER_VALIDATE_BOOLEAN);
                    }
                    function filterNullableBool(string $s) : ?bool {
                        return filter_var($s, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    }
                    function filterNullableBoolWithFlagsArray(string $s) : ?bool {
                        return filter_var($s, FILTER_VALIDATE_BOOLEAN, ["flags" => FILTER_NULL_ON_FAILURE]);
                    }
                    function filterFloat(string $s) : float {
                        $filtered = filter_var($s, FILTER_VALIDATE_FLOAT);
                        if ($filtered === false) {
                            return 0.0;
                        }
                        return $filtered;
                    }
                    function filterFloatWithDefault(string $s) : float {
                        return filter_var($s, FILTER_VALIDATE_FLOAT, ["options" => ["default" => 5.0]]);
                    }',
            ],
            'filterVar' => [
                'code' => '<?php
                    function namedArgs(): string {
                        $a = filter_var("a", options: FILTER_FORCE_ARRAY);
                        return $a[0];
                    }

                    function filterInt(string $s) : int {
                        $filtered = filter_var($s, FILTER_VALIDATE_INT);
                        if ($filtered === false) {
                            return 0;
                        }
                        return $filtered;
                    }
                    function filterNullableInt(string $s) : ?int {
                        return filter_var($s, FILTER_VALIDATE_INT, ["options" => ["default" => null]]);
                    }
                    function filterIntWithDefault(string $s) : int {
                        return filter_var($s, FILTER_VALIDATE_INT, ["options" => ["default" => 5]]);
                    }
                    function filterBool(string $s) : bool {
                        return filter_var($s, FILTER_VALIDATE_BOOLEAN);
                    }
                    function filterNullableBool(string $s) : ?bool {
                        return filter_var($s, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    }
                    function filterNullableBoolWithFlagsArray(string $s) : ?bool {
                        return filter_var($s, FILTER_VALIDATE_BOOLEAN, ["flags" => FILTER_NULL_ON_FAILURE]);
                    }
                    function filterFloat(string $s) : float {
                        $filtered = filter_var($s, FILTER_VALIDATE_FLOAT);
                        if ($filtered === false) {
                            return 0.0;
                        }
                        return $filtered;
                    }
                    function filterFloatWithDefault(string $s) : float {
                        return filter_var($s, FILTER_VALIDATE_FLOAT, ["options" => ["default" => 5.0]]);
                    }

                    /**
                     * @param mixed $c
                     * @return int<1, 100>|stdClass|array<never, never>
                     */
                    function filterNumericIntWithDefault($c) {
                        if (is_numeric($c)) {
                            return filter_var($c, FILTER_VALIDATE_INT, [
                             "options" => [
                                "default"   => new stdClass(),
                                "min_range" => 1,
                                "max_range" => 100,
                            ],
                            ]);
                        }

                        return array();
                    }',
            ],
            'callVariableVar' => [
                'code' => '<?php
                    class Foo
                    {
                        public static function someInt(): int
                        {
                            return 1;
                        }
                    }

                    /**
                     * @return int
                     */
                    function makeInt()
                    {
                        $fooClass = Foo::class;
                        return $fooClass::someInt();
                    }',
            ],
            'expectsIterable' => [
                'code' => '<?php
                    function foo(iterable $i) : void {}
                    function bar(array $a) : void {
                        foo($a);
                    }',
            ],
            'getTypeHasValues' => [
                'code' => '<?php
                    /**
                     * @param mixed $maybe
                     */
                    function matchesTypes($maybe) : void {
                        $t = gettype($maybe);
                        if ($t === "object") {}
                    }',
            ],
            'getTypeSwitchClosedResource' => [
                'code' => '<?php
                    $data = "foo";
                    switch (gettype($data)) {
                        case "resource (closed)":
                        case "unknown type":
                            return "foo";
                    }',
            ],
            'getTypeSwitchFallingThroughAnImpossibleCase' => [
                'code' => '<?php
                    function f(int $data): string {
                        switch (gettype($data)) {
                            case "string":
                                echo "string";
                            case "unknown type":
                                return "s";
                        }

                        return "";
                    }',
            ],
            'functionResolutionInNamespace' => [
                'code' => '<?php
                    namespace Foo;
                    function sort(int $_) : void {}
                    sort(5);',
            ],
            'rangeWithIntStep' => [
                'code' => '<?php

                    function foo(int $bar) : string {
                        return (string) $bar;
                    }

                    foreach (range(1, 10, 1) as $x) {
                        foo($x);
                    }',
            ],
            'rangeWithNoStep' => [
                'code' => '<?php

                    function foo(int $bar) : string {
                        return (string) $bar;
                    }

                    foreach (range(1, 10) as $x) {
                        foo($x);
                    }',
            ],
            'rangeWithNoStepAndString' => [
                'code' => '<?php

                    function foo(string $bar) : void {}

                    foreach (range("a", "z") as $x) {
                        foo($x);
                    }',
            ],
            'rangeWithFloatStep' => [
                'code' => '<?php

                    function foo(float $bar) : string {
                        return (string) $bar;
                    }

                    foreach (range(1, 10, .3) as $x) {
                        foo($x);
                    }',
            ],
            'rangeWithFloatStart' => [
                'code' => '<?php

                    function foo(float $bar) : string {
                        return (string) $bar;
                    }

                    foreach (range(1.5, 10) as $x) {
                        foo($x);
                    }',
            ],
            'rangeWithIntOrFloatStep' => [
                'code' => '<?php
                    /** @var int|float */
                    $step = 1;
                    $a = range(1, 10, $step);

                    /** @var int */
                    $step = 1;
                    $b = range(1, 10, $step);

                    /** @var float */
                    $step = 1.;
                    $c = range(1, 10, $step);
                ',
                'assertions' => [
                    '$a' => 'non-empty-list<float|int>',
                    '$b' => 'non-empty-list<int>',
                    '$c' => 'non-empty-list<float>',
                ],
            ],
            'duplicateNamespacedFunction' => [
                'code' => '<?php
                    namespace Bar;

                    function sort() : void {}',
            ],
            'arrayMapAfterFunctionMissingFile' => [
                'code' => '<?php
                    require_once(FOO);
                    $urls = array_map("strval", [1, 2, 3]);',
                'assertions' => [],
                'ignored_issues' => ['UndefinedConstant', 'UnresolvableInclude'],
            ],
            'noNamespaceClash' => [
                'code' => '<?php
                    namespace FunctionNamespace {
                        function foo() : void {}
                    }

                    namespace ClassNamespace {
                        class Foo {}
                    }

                    namespace {
                        use ClassNamespace\Foo;
                        use function FunctionNamespace\foo;

                        new Foo();

                        foo();
                    }',
            ],
            'hashInit70' => [
                'code' => '<?php
                    $h = hash_init("sha256");',
                'assertions' => [
                    '$h' => 'resource',
                ],
                'ignored_issues' => [],
                'php_version' => '7.1',
            ],
            'hashInit71' => [
                'code' => '<?php
                    $h = hash_init("sha256");',
                'assertions' => [
                    '$h' => 'resource',
                ],
                'ignored_issues' => [],
                'php_version' => '7.1',
            ],
            'hashInit72' => [
                'code' => '<?php
                    $h = hash_init("sha256");',
                'assertions' => [
                    '$h' => 'HashContext|false',
                ],
                'ignored_issues' => [],
                'php_version' => '7.2',
            ],
            'hashInit73' => [
                'code' => '<?php
                    $h = hash_init("sha256");',
                'assertions' => [
                    '$h' => 'HashContext|false',
                ],
                'ignored_issues' => [],
                'php_version' => '7.3',
            ],
            'hashInit80' => [
                'code' => '<?php
                    $h = hash_init("sha256");',
                'assertions' => [
                    '$h' => 'HashContext',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'nullableByRef' => [
                'code' => '<?php
                    function foo(?string &$s) : void {}

                    function bar() : void {
                        foo($bar);
                    }',
            ],
            'getClassNewInstance' => [
                'code' => '<?php
                    interface I {}
                    class C implements I {}

                    class Props {
                        /** @var class-string<I>[] */
                        public $arr = [];
                    }

                    (new Props)->arr[] = get_class(new C);',
            ],
            'getClassVariable' => [
                'code' => '<?php
                    interface I {}
                    class C implements I {}
                    $c_instance = new C;

                    class Props {
                        /** @var class-string<I>[] */
                        public $arr = [];
                    }

                    (new Props)->arr[] = get_class($c_instance);',
            ],
            'getClassAnonymousNewInstance' => [
                'code' => '<?php
                    interface I {}

                    class Props {
                        /** @var class-string<I>[] */
                        public $arr = [];
                    }

                    (new Props)->arr[] = get_class(new class implements I{});',
            ],
            'getClassAnonymousVariable' => [
                'code' => '<?php
                    interface I {}
                    $anon_instance = new class implements I {};

                    class Props {
                        /** @var class-string<I>[] */
                        public $arr = [];
                    }

                    (new Props)->arr[] = get_class($anon_instance);',
            ],
            'mktime' => [
                'code' => '<?php
                    /** @psalm-suppress InvalidScalarArgument */
                    $a = mktime("foo");
                    /** @psalm-suppress MixedArgument */
                    $b = mktime($GLOBALS["foo"]);
                    $c = mktime(1, 2, 3);',
                'assertions' => [
                    '$a' => 'false|int',
                    '$b' => 'false|int',
                    '$c' => 'int',
                ],
            ],
            'hrtime' => [
                'code' => '<?php
                    $a = hrtime(true);
                    $b = hrtime();
                    /** @psalm-suppress InvalidScalarArgument */
                    $c = hrtime(1);
                    $d = hrtime(false);',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'list{int, int}',
                    '$c' => 'int|list{int, int}',
                    '$d' => 'list{int, int}',
                ],
            ],
            'builtinReturnTypeProvidersOnLessCommonCalls' => [
                'code' => '<?php
                    /** @return non-empty-list<int> */
                    function getList(): array {
                        return [1];
                    }
                    /** @return array{a: int, b?: int} */
                    function getShape(): array {
                        return ["a" => 1];
                    }
                    $a = sizeof(getList());
                    $b = COUNT(getShape());
                    $c = get_called_class();
                    $d = get_parent_class();
                    $e = count(...);
                    $f = hrtime(...);
                    $g = mb_strtolower(...);',
                'assertions' => [
                    '$a===' => 'int<1, max>',
                    '$b===' => 'int<1, 2>',
                    '$c===' => 'class-string',
                    '$d===' => 'class-string|false',
                    '$e===' => 'Closure[pure](Countable|array<array-key, mixed>, int=):int<0, max>',
                    '$f===' => 'Closure[time](true=):(false|float|int)',
                    '$g===' => 'Closure[pure](string, string=):lowercase-string',
                ],
                'ignored_issues' => ['DeprecatedFunction'],
            ],
            'hrtimePolyfillBeforePhp73KeepsItsDeclaredType' => [
                'code' => '<?php
                    function hrtime(bool $as_number = false): string {
                        return "polyfill";
                    }
                    $a = hrtime(true);',
                'assertions' => [
                    '$a===' => 'string',
                ],
                'ignored_issues' => [],
                'php_version' => '7.2',
            ],
            'hrtimeCanBeFloat' => [
                'code' => '<?php
                    $a = hrtime(true);

                    if (is_int($a)) {}
                    if (is_float($a)) {}',
            ],
            'min' => [
                'code' => '<?php
                    $a = min(0, 1);
                    $b = min([0, 1]);
                    $c = min("a", "b");
                    $d = min(1, 2, 3, 4);
                    $e = min(1, 2, 3, 4, 5);
                    $f = min(...[1, 2, 3]);',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'int',
                    '$c' => 'string',
                    '$d' => 'int',
                    '$e' => 'int',
                    '$f' => 'int',
                ],
            ],
            'minUnpackedArg' => [
                'code' => '<?php
                    $f = min(...[1, 2, 3]);',
                'assertions' => [
                    '$f' => 'int',
                ],
            ],
            'sscanf' => [
                'code' => '<?php
                    sscanf("10:05:03", "%d:%d:%d", $hours, $minutes, $seconds);',
                'assertions' => [
                    '$hours' => 'float|int|null|string',
                    '$minutes' => 'float|int|null|string',
                    '$seconds' => 'float|int|null|string',
                ],
            ],
            'noImplicitAssignmentToStringFromMixedWithDocblockTypes' => [
                'code' => '<?php
                    /** @param string $s */
                    function takesString($s) : void {}
                    function takesInt(int $i) : void {}

                    /**
                     * @param mixed $s
                     * @psalm-suppress MixedArgument
                     */
                    function bar($s) : void {
                        takesString($s);
                        takesInt($s);
                    }',
            ],
            'ignoreNullableIssuesAfterMixedCoercion' => [
                'code' => '<?php
                    function takesNullableString(?string $s) : void {}
                    function takesString(string $s) : void {}

                    /**
                     * @param mixed $s
                     * @psalm-suppress MixedArgument
                     */
                    function bar($s) : void {
                        takesNullableString($s);
                        takesString($s);
                    }',
            ],
            'countableSimpleXmlElement' => [
                'code' => '<?php
                    $xml = new SimpleXMLElement("<?xml version=\"1.0\"?><a><b></b><b></b></a>");
                    echo count($xml);',
            ],
            'countableCallableArray' => [
                'code' => '<?php
                    /** @param callable|false $x */
                    function example($x) : void {
                        if (is_array($x)) {
                            echo "Count is: " . count($x);
                        }
                    }',
            ],
            'countNonEmptyArrayShouldBePositiveInt' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @param non-empty-list $x
                     * @return positive-int
                     */
                    function example($x) : int {
                        return count($x);
                    }',
            ],
            'countListShouldBeZeroOrPositive' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @param list $x
                     * @return positive-int|0
                     */
                    function example($x) : int {
                        return count($x);
                    }',
            ],
            'countArrayShouldBeZeroOrPositive' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @param array $x
                     * @return positive-int|0
                     */
                    function example($x) : int {
                        return count($x);
                    }',
            ],
            'countEmptyArrayShouldBeZero' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @param array<never, never> $x
                     * @return 0
                     */
                    function example($x) : int {
                        return count($x);
                    }',
            ],
            'countConstantSizeArrayShouldBeConstantInteger' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @param array{int, int, string} $x
                     * @return 3
                     */
                    function example($x) : int {
                        return count($x);
                    }',
            ],
            'countCallableArrayShouldBe2' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @return 2
                     */
                    function example(callable $x) : int {
                        assert(is_array($x));
                        return count($x);
                    }',
            ],
            'countOnObjectShouldBePositive' => [
                'code' => '<?php
                    /** @return positive-int|0 */
                    function example(\Countable $x) : int {
                        return count($x);
                    }',
            ],
            'countOnPureObjectIsPure' => [
                'code' => '<?php
                    class PureCountable implements \Countable {
                        /** @psalm-pure */
                        public function count(): int { return 1; }
                    }
                    /** @psalm-pure */
                    function example(PureCountable $x) : int {
                        return count($x);
                    }',
            ],
            'refineWithTraitExists' => [
                'code' => '<?php
                    function foo(string $s) : void {
                        if (trait_exists($s)) {
                            new ReflectionClass($s);
                        }
                    }',
            ],
            'refineWithClassExistsOrTraitExists' => [
                'code' => '<?php
                    function foo(string $s) : void {
                        if (trait_exists($s) || class_exists($s)) {
                            new ReflectionClass($s);
                        }
                    }

                    function bar(string $s) : void {
                        if (class_exists($s) || trait_exists($s)) {
                            new ReflectionClass($s);
                        }
                    }

                    function baz(string $s) : void {
                        if (class_exists($s) || interface_exists($s) || trait_exists($s)) {
                            new ReflectionClass($s);
                        }
                    }',
            ],
            'minSingleArg' => [
                'code' => '<?php
                    /** @psalm-suppress TooFewArguments */
                    min(0);',
            ],
            'allowIsCountableToInformType' => [
                'code' => '<?php
                    function getObject() : iterable{
                       return [];
                    }

                    $iterableObject = getObject();

                    if (is_countable($iterableObject)) {
                       if (count($iterableObject) === 0) {}
                    }',
            ],
            'versionCompareAsCallable' => [
                'code' => '<?php
                    $a = ["1.0", "2.0"];
                    usort($a, "version_compare");',
            ],
            'coerceToObjectAfterBeingCalled' => [
                'code' => '<?php
                    class Foo {
                        public function bar() : void {}
                    }

                    function takesFoo(Foo $foo) : void {}

                    /** @param mixed $f */
                    function takesMixed($f) : void {
                        if (rand(0, 1)) {
                            $f = new Foo();
                        }
                        /** @psalm-suppress MixedArgument */
                        takesFoo($f);
                        $f->bar();
                    }',
            ],
            'functionExists' => [
                'code' => '<?php
                    if (!function_exists("in_array")) {
                        function in_array($a, $b) {
                            return true;
                        }
                    }',
            ],
            'callableArgumentWithFunctionExists' => [
                'code' => <<<'PHP'
                    <?php
                    if (function_exists('foo')) {
                        register_shutdown_function('foo');
                    }
                    PHP,
            ],
            'pregMatch' => [
                'code' => '<?php
                    function takesInt(int $i) : void {}

                    takesInt(preg_match("{foo}", "foo"));',
            ],
            'pregMatch2' => [
                'code' => '<?php
                    $r = preg_match("{foo}", "foo");',
                'assertions' => [
                    '$r===' => '0|1|false',
                ],
            ],
            'pregMatchWithMatches' => [
                'code' => '<?php
                    /** @param string[] $matches */
                    function takesMatches(array $matches) : void {}

                    preg_match("{foo}", "foo", $matches);

                    takesMatches($matches);',
            ],
            'pregMatchWithMatches2' => [
                'code' => '<?php
                    $r = preg_match("{foo}", "foo", $matches);',
                'assertions' => [
                    '$r===' => '0|1|false',
                    '$matches===' => 'array<array-key, string>',
                ],
            ],
            'pregMatchWithOffset' => [
                'code' => '<?php
                    /** @param string[] $matches */
                    function takesMatches(array $matches) : void {}

                    preg_match("{foo}", "foo", $matches, 0, 10);

                    takesMatches($matches);',
            ],
            'pregMatchWithOffset2' => [
                'code' => '<?php
                    $r = preg_match("{foo}", "foo", $matches, 0, 10);',
                'assertions' => [
                    '$r===' => '0|1|false',
                    '$matches===' => 'array<array-key, string>',
                ],
            ],
            'pregMatchWithFlags' => [
                'code' => '<?php
                    function takesInt(int $i) : void {}

                    if (preg_match("{foo}", "this is foo", $matches, PREG_OFFSET_CAPTURE)) {
                        takesInt($matches[0][1]);
                    }',
            ],
            'pregMatchWithFlagOffsetCapture' => [
                'code' => '<?php
                    $r = preg_match("{foo}", "foo", $matches, PREG_OFFSET_CAPTURE);',
                'assertions' => [
                    '$r===' => '0|1|false',
                    '$matches===' => 'array<array-key, list{string, int<-1, max>}>',
                ],
            ],
            'pregMatchWithFlagUnmatchedAsNull' => [
                'code' => '<?php
                    $r = preg_match("{foo}", "foo", $matches, PREG_UNMATCHED_AS_NULL);',
                'assertions' => [
                    '$r===' => '0|1|false',
                    '$matches===' => 'array<array-key, null|string>',
                ],
            ],
            'pregMatchWithFlagOffsetCaptureAndUnmatchedAsNull' => [
                'code' => '<?php
                    $r = preg_match("{foo}", "foo", $matches, PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL);',
                'assertions' => [
                    '$r===' => '0|1|false',
                    '$matches===' => 'array<array-key, list{null|string, int<-1, max>}>',
                ],
            ],
            'pregReplaceCallback' => [
                'code' => '<?php
                    function foo(string $s) : string {
                        return preg_replace_callback(
                            \'/<files (psalm-version="[^"]+") (?:php-version="(.+)">\n)/\',
                            /** @param string[] $matches */
                            function (array $matches) : string {
                                return $matches[1];
                            },
                            $s
                        );
                    }',
            ],
            'pregReplaceCallbackWithArray' => [
                'code' => '<?php
                    /**
                     * @param string[] $ids
                     */
                    function(array $ids): array {
                        return \preg_replace_callback(
                            "//",
                            fn (array $matches) => $matches[4],
                            $ids
                        );
                    };',
                    'assertions' => [],
                    'ignored_issues' => [],
                    'php_version' => '7.4',
            ],
            'compactDefinedVariable' => [
                'code' => '<?php
                    /**
                     * @return array<string, mixed>
                     */
                    function foo(int $a, string $b, bool $c) : array {
                        return compact("a", "b", "c");
                    }',
            ],
            'setCookiePhp73' => [
                'code' => '<?php
                    setcookie(
                        "name",
                        "value",
                        [
                            "path"     => "/",
                            "expires"  => 0,
                            "httponly" => true,
                            "secure"   => true,
                            "samesite" => "Lax"
                        ]
                    );',
            ],
            'printrBadArg' => [
                'code' => '<?php
                    /** @psalm-suppress InvalidScalarArgument */
                    $a = print_r([], 1);
                    echo $a;',
            ],
            'dontCoerceCallMapArgs' => [
                'code' => '<?php
                    function getStr() : ?string {
                        return rand(0,1) ? "test" : null;
                    }

                    function test() : void {
                        $g = getStr();
                        /** @psalm-suppress PossiblyNullArgument */
                        $x = strtoupper($g);
                        $c = "prefix " . (strtoupper($g ?? "") === "x" ? "xa" : "ya");
                        echo "$x, $c\n";
                    }',
            ],
            'mysqliRealConnectFunctionAllowsNullParameters' => [
                'code' => '<?php
                    $mysqli = mysqli_init();
                    mysqli_real_connect($mysqli, null, \'test\', null);',
            ],
            'callUserFunc' => [
                'code' => '<?php
                    $func = function(int $arg1, int $arg2) : int {
                        return $arg1 * $arg2;
                    };

                    $a = call_user_func($func, 2, 4);',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'callUserFuncArray' => [
                'code' => '<?php
                    $func = function(int $arg1, int $arg2) : int {
                        return $arg1 * $arg2;
                    };

                    $a = call_user_func_array($func, [2, 4]);',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'dateTest' => [
                'code' => '<?php
                    $y = date("Y");
                    $ym = date("Ym");
                    $y_m = date("Y-m");
                    $m = date("m");
                    $F = date("F");
                    $y2 = date("Y", 10000);
                    $F2 = date("F", 10000);
                    /** @psalm-suppress MixedArgument */
                    $F3 = date("F", $GLOBALS["F3"]);
                    $gm_y = gmdate("Y");
                    $gm_ym = gmdate("Ym");
                    $gm_m = gmdate("m");
                    $gm_F = gmdate("F");
                    $gm_y2 = gmdate("Y", 10000);
                    $gm_F2 = gmdate("F", 10000);
                    /** @psalm-suppress MixedArgument */
                    $gm_F3 = gmdate("F", $GLOBALS["F3"]);',
                'assertions' => [
                    '$y===' => 'numeric-string',
                    '$ym===' => 'numeric-string',
                    '$y_m===' => 'string',
                    '$m===' => 'numeric-string',
                    '$F===' => 'string',
                    '$y2===' => 'numeric-string',
                    '$F2===' => 'string',
                    '$F3===' => 'string',
                    '$gm_y===' => 'numeric-string',
                    '$gm_ym===' => 'numeric-string',
                    '$gm_m===' => 'numeric-string',
                    '$gm_F===' => 'string',
                    '$gm_y2===' => 'numeric-string',
                    '$gm_F2===' => 'string',
                    '$gm_F3===' => 'string',
                ],
            ],
            'sscanfReturnTypeWithTwoParameters' => [
                'code' => '<?php
                    $data = sscanf("42 psalm road", "%s %s");',
                'assertions' => [
                    '$data' => 'list<float|int|null|string>|null',
                ],
            ],
            'sscanfReturnTypeWithMoreThanTwoParameters' => [
                'code' => '<?php
                    $n = sscanf("42 psalm road", "%s %s", $p1, $p2);',
                'assertions' => [
                    '$n' => 'int',
                    '$p1' => 'float|int|null|string',
                    '$p2' => 'float|int|null|string',
                ],
            ],
            'writeArgsAllowed' => [
                'code' => '<?php
                    /**
                     * @param non-empty-string $pattern
                     * @param 0|256|512|768 $flags
                     * @return false|int
                     */
                    function safeMatch(string $pattern, string $subject, ?array $matches = null, int $flags = 0) {
                        return \preg_match($pattern, $subject, $matches, $flags);
                    }

                    safeMatch("/a/", "b");',
            ],
            'fgetcsv' => [
                'code' => '<?php
                    $headers = fgetcsv(fopen("test.txt", "r"));
                    if (empty($headers)) {
                        throw new Exception("invalid headers");
                    }
                    print_r(array_map("strval", $headers));',
                'assertions' => [],
                'ignored_issues' => ['DeprecatedFunction'],
            ],
            'allowListEqualToRange' => [
                'code' => '<?php
                    /** @param array<int, int> $two */
                    function collectCommit(array $one, array $two) : void {
                        if ($one && array_values($one) === array_values($two)) {}
                    }',
            ],
            'pregMatchAll' => [
                'code' => '<?php
                    /**
                     * @return array<list<string>>
                     */
                    function extractUsernames(string $input): array {
                        preg_match_all(\'/([a-zA-Z])*/\', $input, $matches);

                        return $matches;
                    }',
            ],
            'pregMatchAllOffsetCapture' => [
                'code' => '<?php
                    function foo(string $input): array {
                        preg_match_all(\'/([a-zA-Z])*/\', $input, $matches, PREG_OFFSET_CAPTURE);

                        return $matches[0];
                    }',
            ],
            'pregMatchAllReturnsFalse' => [
                'code' => '<?php
                    /**
                     * @return int|false
                     */
                    function badpattern() {
                        return @preg_match_all("foo", "foo", $matches);
                    }',
            ],
            'strposAllowDictionary' => [
                'code' => '<?php
                    function sayHello(string $format): void {
                        if (strpos("abcdefghijklmno", $format) !== false) {}
                    }',
            ],
            'curlInitIsResourceAllowedIn7x' => [
                'code' => '<?php
                    $ch = curl_init();
                    if (!is_resource($ch)) {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'pregSplit' => [
                'code' => '<?php
                    /** @return non-empty-list */
                    function foo(string $s) {
                        return preg_split("/ /", $s);
                    }',
            ],
            'pregSplitWithFlags' => [
                'code' => '<?php
                    /** @return list<string> */
                    function foo(string $s) {
                        return preg_split("/ /", $s, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
                    }',
            ],
            'mbConvertEncodingWithArray' => [
                'code' => '<?php
                    /**
                     * @param array<int, string> $str
                     * @return array<int, string>
                     */
                    function test2(array $str): array {
                        return mb_convert_encoding($str, "UTF-8", "UTF-8");
                    }',
            ],
            'getDebugType' => [
                'code' => '<?php
                    function foo(mixed $var) : void {
                        switch (get_debug_type($var)) {
                            case "string":
                                echo "a string";
                                break;

                            case Exception::class;
                                echo "an Exception with message " . $var->getMessage();
                                break;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['DeprecatedSyntax'],
                'php_version' => '8.0',
            ],
            'getTypeDoubleThenInt' => [
                'code' => '<?php
                    function safe_float(mixed $val): bool {
                        switch (gettype($val)) {
                            case "double":
                            case "integer":
                                return true;
                            // ... more cases omitted
                            default:
                                return false;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'maxWithFloats' => [
                'code' => '<?php
                    function foo(float $_float): void
                    {}

                    foo(max(1.1, 1.2));',
            ],
            'maxWithObjects' => [
                'code' => '<?php
                    function foo(DateTimeImmutable $fooDate): string
                    {
                        return $fooDate->format("Y");
                    }

                    foo(max(new DateTimeImmutable(), new DateTimeImmutable()));',
            ],
            'maxWithMisc' => [
                'code' => '<?php
                    $a = max(new DateTimeImmutable(), 1.2);',
                'assertions' => [
                    '$a' => 'DateTimeImmutable|float',
                ],
            ],
            'maxUnpackArray' => [
                'code' => '<?php
                    $files = [
                        __FILE__,
                        __FILE__,
                        __FILE__,
                        __FILE__,
                    ];

                    $a = array_map("filemtime", $files);
                    $b = array_map(
                        function (string $file): int {
                            return filemtime($file);
                        },
                        $files,
                    );
                    $A = max(filemtime(__FILE__), ...$a);
                    $B = max(filemtime(__FILE__), ...$b);

                    echo date("c", $A), "\n", date("c", $B);
                ',
            ],
            'maxUnpackArrayWithNonInt' => [
                'code' => '<?php
                    $max = max(1, 2, ...[new DateTime(), 3, 4]);
                ',
                'assertions' => [
                    '$max===' => '1|2|3|4|DateTime',
                ],
            ],
            'strtolowerEmptiness' => [
                'code' => '<?php
                    /** @param non-empty-string $s */
                    function foo(string $s) : void {
                        $s = strtolower($s);

                        foo($s);
                    }',
            ],
            'preventObjectLeakingFromCallmapReference' => [
                'code' => '<?php
                    function one(): void
                    {
                        try {
                            exec("", $output);
                        } catch (Exception $e){
                        }
                    }

                    function two(): array
                    {
                        exec("", $lines);
                        return $lines;
                    }',
            ],
            'execOutputEmptyArrayIsListOfStrings' => [
                'code' => '<?php
                    $bar = array();
                    exec("pwd", $bar);

                    $baz = [];
                    exec("pwd", $baz, $code);

                    $foo = null;
                    exec("pwd", $foo);

                    /** @var array<string> $existing */
                    $existing = [];
                    exec("pwd", $existing);',
                'assertions' => [
                    '$bar===' => 'list<string>',
                    '$baz===' => 'list<string>',
                    '$foo===' => 'list<string>',
                    '$existing===' => 'array<array-key, mixed>',
                ],
            ],
            'array_is_list' => [
                'code' => '<?php
                    function getArray() : array {
                        return [];
                    }
                    $s = getArray();
                    assert(array_is_list($s));
                    ',
                'assertions' => [
                    '$s' => 'list<mixed>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'trimOfALiteralStringIsALiteralString' => [
                'code' => '<?php
                    $a = trim("  abc \n");
                    $b = ltrim("  abc  ");
                    $c = rtrim("  abc  ");
                    $d = chop("abc \n");
                    $e = trim("xxabcxx", "x");
                    $f = trim("abc", "");
                    $g = trim("");
                    $h = trim("\0\x0B\t abc \r");
                    $i = ltrim("0012", "0");

                    /** @param "string"|"int"|"float" $type */
                    function takesAType(string $type): void {}

                    takesAType(trim("string "));',
                'assertions' => [
                    '$a===' => '\'abc\'',
                    '$b===' => '\'abc  \'',
                    '$c===' => '\'  abc\'',
                    '$d===' => '\'abc\'',
                    '$e===' => '\'abc\'',
                    '$f===' => '\'abc\'',
                    '$g===' => '\'\'',
                    '$h===' => '\'abc\'',
                    '$i===' => '\'12\'',
                ],
            ],
            'trimOfALiteralStringIgnoresTheCaseOfTheCall' => [
                'code' => '<?php
                    $a = LTRIM("  abc  ");
                    $b = \\rtrim("  abc  ");
                    $c = Chop("  abc  ");
                    $d = \\TRIM("  abc  ");',
                'assertions' => [
                    '$a===' => '\'abc  \'',
                    '$b===' => '\'  abc\'',
                    '$c===' => '\'  abc\'',
                    '$d===' => '\'abc\'',
                ],
                'ignored_issues' => ['WrongCaseFunction'],
            ],
            'trimThatIsNotOfOneLiteralKeepsTheStubType' => [
                'code' => '<?php
                    /** @var string $s */
                    $s = getenv("X");
                    /** @var "a "|"b " $union */
                    $union = rand(0, 1) ? "a " : "b ";
                    /** @var string $chars */
                    $chars = getenv("Y");

                    $a = trim($s);
                    $b = trim($union);
                    $c = trim("abc ", $chars);
                    $d = trim("abc", "a..c");
                    $e = trim(string: " abc ");
                    $f = trim(...["abc "]);
                    $g = trim(" abc ", " ", "extra");',
                'assertions' => [
                    '$a===' => 'string',
                    '$b===' => 'lowercase-string',
                    '$c===' => 'lowercase-string',
                    '$d===' => 'lowercase-string',
                    '$e===' => 'lowercase-string',
                    '$f===' => 'lowercase-string',
                ],
                'ignored_issues' => ['TooManyArguments'],
            ],
            'strtolowerOfAStringableObjectIsNotRedundant' => [
                'code' => '<?php
                    class Test {
                        public function __toString(): string {
                            return "AAA";
                        }
                    }

                    function lower(Test $test, Stringable $stringable): string {
                        return strtolower($test) . strtolower($stringable);
                    }',
                'assertions' => [],
                'ignored_issues' => ['ImplicitToStringCast'],
                'php_version' => '8.1',
            ],
            'objectWithRefinedToStringReturnMatchesStringParameter' => [
                'code' => '<?php
                    final class LowercaseText {
                        /** @return lowercase-string */
                        public function __toString(): string {
                            return "lowercase";
                        }
                    }

                    /** @param lowercase-string $value */
                    function acceptsLowercase(string $value): void {}

                    /** @psalm-suppress ImplicitToStringCast */
                    acceptsLowercase(new LowercaseText());',
                'php_version' => '8.1',
            ],
            'genericObjectWithRefinedToStringReturnMatchesStringParameter' => [
                'code' => '<?php
                    /** @template T of string */
                    final class Box {
                        /** @param T $value */
                        public function __construct(private string $value) {}

                        /** @return T */
                        public function __toString(): string {
                            return $this->value;
                        }
                    }

                    /** @param lowercase-string $value */
                    function acceptsLowercase(string $value): void {}

                    /** @var Box<lowercase-string> $box */
                    $box = new Box("lowercase");

                    /** @psalm-suppress ImplicitToStringCast */
                    acceptsLowercase($box);',
                'php_version' => '8.1',
            ],
            'stringConditionOnObjectPreservesObjectType' => [
                'code' => '<?php
                    final class Path {
                        public function __toString(): string {
                            return "path";
                        }

                        public function getElement(): string {
                            return "element";
                        }
                    }

                    function getElement(Path $path): string {
                        if (str_starts_with($path, "p")) {
                            return $path->getElement();
                        }

                        return "";
                    }',
                'ignored_issues' => ['ImplicitToStringCast'],
                'php_version' => '8.1',
            ],
            'failedNestedStringCastDoesNotLeakFromArray' => [
                'code' => '<?php
                    final class Join {
                        public function __toString(): string {
                            return "join";
                        }
                    }

                    /**
                     * @param string|object|list<string>|array{join: array<int|string, object>} $part
                     */
                    function add(string|object|array $part): void {}

                    add(["root" => new Join()]);',
                'ignored_issues' => ['InvalidScalarArgument'],
                'php_version' => '8.1',
            ],
            'strReplaceWithAsManyReplacementsAsSearches' => [
                'code' => '<?php
                    /** @var string $subject */
                    $subject = getenv("X");
                    /** @var list<string> $searches */
                    $searches = [];
                    /** @var list<string> $replacements */
                    $replacements = [];
                    /** @var list{0?: string, 1?: string} $optional */
                    $optional = [];

                    $a = str_replace(["a", "b"], ["x", "y"], $subject);
                    $b = str_replace(["a", "b"], ["x", "y", "z"], $subject);
                    $c = str_replace(["a", "b"], "x", $subject);
                    $d = str_replace(["a", "b"], [], $subject);
                    $e = str_replace($searches, ["x"], $subject);
                    $f = str_replace(["a", "b"], $replacements, $subject);
                    $g = str_replace(["a", "b"], $optional, $subject);
                    $h = str_replace("a", "x", $subject);
                    $i = str_replace(...["a", "x", $subject]);
                    $j = str_replace(subject: $subject, replace: ["x"], search: ["a", "b"]);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'array_is_list_on_empty_array' => [
                'code' => '<?php
                    $a = [];
                    if(array_is_list($a)) {
                        //$a is still empty array
                    }
                    ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'array_is_list_keeps_keyed_array_shape' => [
                'code' => '<?php
                    /** @param array{0: string, 1: string[]} $arr */
                    function f(array $arr): string {
                        if (!array_is_list($arr)) {
                            throw new Exception();
                        }
                        [$a0, $a1] = $arr;
                        return $a0 . implode("", $a1);
                    }

                    /** @var array{0: string, 1: int} $s */
                    $s = [];
                    assert(array_is_list($s));
                    ',
                'assertions' => [
                    '$s===' => 'list{string, int}',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'array_is_list_keeps_optional_keys_of_keyed_array' => [
                'code' => '<?php
                    /** @var array{0: string, 1?: int} $s */
                    $s = [];
                    assert(array_is_list($s));

                    /** @var array{0?: string, 1: int} $t */
                    $t = [];
                    assert(array_is_list($t));
                    ',
                'assertions' => [
                    '$s===' => 'list{0: string, 1?: int}',
                    '$t===' => 'list{string, int}',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'array_is_list_keeps_callable_keyed_array' => [
                'code' => '<?php
                    /** @var array $c */
                    $c = [];
                    assert(is_callable($c));
                    assert(array_is_list($c));
                    ',
                'assertions' => [
                    '$c===' => 'callable-list{class-string|object, non-empty-string}',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'array_is_list_on_keyed_array_with_extra_keys' => [
                'code' => '<?php
                    /** @var array{0: string, ...<int, bool>} $s */
                    $s = [];
                    assert(array_is_list($s));
                    ',
                'assertions' => [
                    '$s===' => 'non-empty-list<bool|string>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'possiblyUndefinedArrayDestructurationOnOptionalArg' => [
                'code' => '<?php
                    class A
                    {
                    }

                    function foo(A $a1, A $a2 = null): void
                    {
                    }

                    $arguments = [new A()];
                    if (mt_rand(1, 10) > 5) {
                        // when this is done outside if - no errors
                        $arguments[] = new A();
                    }

                    foo(...$arguments);
                    ',
                'assertions' => [],
                'ignored_issues' => ['DeprecatedSyntax'],
            ],
            'is_aWithStringableClass' => [
                'code' => '<?php
                    /**
                     * @psalm-var class-string<Throwable> $exceptionType
                     */
                    if (\is_a(new Exception(), $exceptionType)) {}
                    ',
            ],
            'strposFirstParamAllowClassString' => [
                'code' => '<?php
                    function sayHello(string $needle): void {
                        if (strpos(DateTime::class, $needle) !== false) {}
                    }',
            ],
            'mb_strtolowerProducesStringWithSecondArgument' => [
                'code' => '<?php
                    /** @var non-empty-string $a */
                    $a = "École";
                    $r = mb_strtolower($a, "BASE64");
                    /** @var string $b */
                    $b = "";
                    $s = mb_strtolower($b, "BASE64");
                    $t = mb_strtolower("ABC", "BASE64");
                    $u = mb_strtolower("", "BASE64");
                ',
                'assertions' => [
                    '$r===' => 'non-empty-string',
                    '$s===' => 'string',
                    '$t===' => 'non-empty-string',
                    '$u===' => 'string',
                ],
                'ignored_issues' => ['DeprecatedFunction'],
            ],
            'mb_strtolowerProducesLowercaseStringWithNullOrAbsentEncoding' => [
                'code' => '<?php
                    /** @var non-empty-string $i */
                    $i = "École";
                    /** @var string $j */
                    $j = "";
                    $a = mb_strtolower($i);
                    $b = mb_strtolower($i, null);
                    $c = mb_strtolower($j);
                    $d = mb_strtolower($j, null);
                    $e = mb_strtolower("AAA");
                    $f = mb_strtolower("AAA", null);
                    $g = mb_strtolower("");
                    $h = mb_strtolower("", null);
                ',
                'assertions' => [
                    '$a===' => 'non-empty-lowercase-string',
                    '$b===' => 'non-empty-lowercase-string',
                    '$c===' => 'lowercase-string',
                    '$d===' => 'lowercase-string',
                    '$e===' => '\'aaa\'',
                    '$f===' => '\'aaa\'',
                    '$g===' => "''",
                    '$h===' => "''",
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'count_charsProducesArrayOrString' => [
                'code' => '<?php
                    $a = count_chars("foo");
                    $b = count_chars("foo", 1);
                    $c = count_chars("foo", 2);
                    $d = count_chars("foo", 3);
                    $e = count_chars("foo", 4);
                ',
                'assertions' => [
                    '$a===' => 'array<int, int>',
                    '$b===' => 'array<int, int>',
                    '$c===' => 'array<int, int>',
                    '$d===' => 'string',
                    '$e===' => 'string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'number_formatNamedArgument' => [
                'code' => '<?php
                    echo number_format(10.363, 1, thousands_separator: " ");
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'round_literalValue' => [
                'code' => '<?php
                    $a = round(10.363, 2);
                ',
                'assertions' => [
                    '$a===' => 'float(10.36)',
                ],
            ],
            'allowConstructorAfterEnumExists' => [
                'code' => '<?php
                    function foo(string $s) : void {
                        if (enum_exists($s)) {
                            new $s();
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedMethodCall'],
                'php_version' => '8.1',
            ],
            'refineWithEnumExists' => [
                'code' => '<?php
                    function foo(string $s) : void {
                        if (enum_exists($s)) {
                            new ReflectionClass($s);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'refineWithClassExistsOrEnumExists' => [
                'code' => '<?php
                    function foo(string $s) : void {
                        if (trait_exists($s) || enum_exists($s)) {
                            new ReflectionClass($s);
                        }
                    }

                    function bar(string $s) : void {
                        if (enum_exists($s) || trait_exists($s)) {
                            new ReflectionClass($s);
                        }
                    }

                    function baz(string $s) : void {
                        if (enum_exists($s) || interface_exists($s) || trait_exists($s)) {
                            new ReflectionClass($s);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'trimSavesLowercaseAttribute' => [
                'code' => '<?php
                    $a = random_bytes(2);
                    $b = trim(strtolower($a));
                ',
                'assertions' => [
                    '$b===' => 'lowercase-string',
                ],
            ],
            'ltrimSavesLowercaseAttribute' => [
                'code' => '<?php
                    $a = random_bytes(2);
                    $b = ltrim(strtolower($a));
                ',
                'assertions' => [
                    '$b===' => 'lowercase-string',
                ],
            ],
            'rtrimSavesLowercaseAttribute' => [
                'code' => '<?php
                    $a = random_bytes(2);
                    $b = rtrim(strtolower($a));
                ',
                'assertions' => [
                    '$b===' => 'lowercase-string',
                ],
            ],
            'passingStringableObjectToStringableParam' => [
                'code' => '<?php
                    function acceptsStringable(Stringable $_p): void {}
                    /** @param stringable-object $p */
                    function f(object $p): void
                    {
                        f($p);
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'passingStringableToStringableObjectParam' => [
                'code' => '<?php
                    /** @param stringable-object $_o */
                    function acceptsStringableObject(object $_o): void {}

                    function f(Stringable $o): void
                    {
                        acceptsStringableObject($o);
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'passingImplicitStringableObjectToStringableObjectParam' => [
                'code' => '<?php
                    /** @param stringable-object $o */
                    function acceptsStringableObject(object $o): void {}

                    class C { public function __toString(): string { return __CLASS__; }}

                    acceptsStringableObject(new C);
                ',
            ],
            'passingExplicitStringableObjectToStringableObjectParam' => [
                'code' => '<?php
                    /** @param stringable-object $o */
                    function acceptsStringableObject(object $o): void {}

                    class C implements Stringable { public function __toString(): string { return __CLASS__; }}

                    acceptsStringableObject(new C);
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'noNeverReturnError' => [
                'code' => '<?php
                    /**
                     * @return string
                     */
                    function foo() {
                        if (random_int(0, 1)) {
                            exit;
                        }

                        return "foobar";
                    }
                ',
            ],
            'noNeverReturnErrorOnlyThrows' => [
                'code' => '<?php
                    /**
                     * https://3v4l.org/vCSF4#v8.1.12
                     */
                    function foo(): string {
                         throw new \Exception("foo");
                    }
                ',
            ],
            'noInvalidReturnTypeVoidNeverExplicit' => [
                'code' => '<?php
                    /**
                     * @return void|never
                     */
                    function foo() {
                        if ( rand( 0, 10 ) > 5 ) {
                            exit;
                        }
                    }
                ',
            ],
            'getHeadersAssociativeIn8x' => [
                'code' => '<?php
                    $a = get_headers("https://psalm.dev", true);',
                'assertions' => [
                    '$a' => 'array<string, list<string>|string>|false',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'getHeadersAssociativeIn7x' => [
                'code' => '<?php
                    $a = get_headers("https://psalm.dev", 0);',
                'assertions' => [
                    '$a' => 'false|list<string>',
                ],
                'ignored_issues' => [],
                'php_version' => '7.0',
            ],
            'unserializeWithMaxDepthOption' => [
                'code' => '<?php
                    unserialize("", ["allowed_classes" => false, "max_depth" => 1]);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'pureFunctionCalledWithLiteralsRemembersWhatTheConditionTold' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function lookup(string $name): ?string {
                        return $name === "test" ? "found" : null;
                    }

                    $a = "";
                    if (lookup("test") !== null) {
                        $a = lookup("test");
                    }',
                'assertions' => [
                    '$a===' => 'string',
                ],
            ],
            'pureFunctionCalledWithLiteralsRemembersAnEarlyReturnGuard' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function lookup(string $name): ?string {
                        return $name === "test" ? "found" : null;
                    }

                    function guarded(): string {
                        if (lookup("test") === null) {
                            return "";
                        }

                        return lookup("test");
                    }',
            ],
            'pureFunctionCalledWithLiteralsInALoopIsNotPossiblyUndefined' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function lookup(string $name): ?string {
                        return $name === "test" ? "found" : null;
                    }

                    function loop(int $times): void {
                        for ($i = 0; $i < $times; $i++) {
                            $found = lookup("test");
                            echo $found;
                        }
                    }',
            ],
            'pureFunctionCalledWithAnIntAndAFloatIsAnotherCall' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @return ($x is int ? "int" : "float")
                     */
                    function kind(int|float $x): string {
                        return is_int($x) ? "int" : "float";
                    }

                    $a = kind(1);
                    $b = kind(1.0);',
                'assertions' => [
                    '$a===' => '\'int\'',
                    '$b===' => '\'float\'',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'pureFunctionCalledWithOtherBytesIsAnotherCall' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function bad(string $s): ?string {
                        return $s === "\xff" ? null : $s;
                    }

                    if (bad("\xff") !== null) {
                        $x = bad("\xfe");
                        /** @psalm-check-type-exact $x = null|string */
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'pureFunctionCalledWithOtherArgumentsIsAnotherCall' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function lookup(string $name): ?string {
                        return $name === "test" ? "found" : null;
                    }

                    $other = "";
                    if (lookup("test") !== null) {
                        $other = lookup("other");
                    }',
                'assertions' => [
                    '$other===' => 'null|string',
                ],
            ],
            'pregReplaceCallbackWithNamedUnmatchedAsNullFlag' => [
                'code' => '<?php
                    preg_replace_callback(
                        pattern: "#^([a-z]+)$|^([0-9]+)$#",
                        callback: fn (array $m): string => $m[1] === null ? "1" : "a",
                        subject: "1234567890",
                        flags: PREG_UNMATCHED_AS_NULL,
                    );',
                'assertions' => [],
                'ignored_issues' => [],
            ],
            'pregReplaceCallbackWithFlagsThatAreNotKnown' => [
                'code' => '<?php
                    function spread(): void {
                        $args = ["#(a)#", fn (array $m): string => "x", "a", -1, null, PREG_UNMATCHED_AS_NULL];
                        preg_replace_callback(...$args);
                    }

                    function variable(int $flags): void {
                        preg_replace_callback(
                            "#(a)#",
                            fn (array $m): string => "x",
                            "a",
                            -1,
                            $count,
                            $flags,
                        );
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedArgument', 'ArgumentTypeCoercion', 'PossiblyInvalidArgument', 'InvalidArgument'],
            ],
            'pregReplaceCallbackWithUnmatchedAsNullFlag' => [
                'code' => '<?php
                    $a = preg_replace_callback(
                        "#^([a-z]+)$|^([0-9]+)$#",
                        fn ($m) => null === $m[1] ? "1" : "a",
                        "1234567890",
                        1,
                        $count,
                        PREG_UNMATCHED_AS_NULL,
                    );

                    $b = preg_replace_callback(
                        "#^([a-z]+)$|^([0-9]+)$#",
                        function (array $m): string {
                            return $m[1][0] === null ? "1" : "a";
                        },
                        "1234567890",
                        -1,
                        $count,
                        PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL,
                    );

                    $c = preg_replace_callback(
                        "#^([a-z]+)$|^([0-9]+)$#",
                        function (array $m): string {
                            return $m[1][0] . $m[1][1];
                        },
                        "1234567890",
                        -1,
                        $count,
                        PREG_OFFSET_CAPTURE,
                    );',
                'assertions' => [],
                'ignored_issues' => ['PossiblyUndefinedIntArrayOffset'],
            ],
            'filterVarIpGlobalRangeFlag' => [
                'code' => <<<'PHP'
                    <?php
                    /** @psalm-pure */
                    function isGlobalIp(string $ip): bool
                    {
                        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE) !== false;
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'arrayIsListOfAShapeWithAGapInItsListKeys' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param array{0?: string, 2?: int} $gap */
                    function gap(array $gap): void {
                        if (array_is_list($gap)) {
                            /** @psalm-check-type-exact $gap = list{0?: string} */
                        }
                    }

                    /** @param array{0?: string, 1: int} $requiredLast */
                    function requiredLast(array $requiredLast): void {
                        if (array_is_list($requiredLast)) {
                            /** @psalm-check-type-exact $requiredLast = list{string, int} */
                        }
                    }

                    /** @param array{1?: int, 0: string} $outOfOrder */
                    function outOfOrder(array $outOfOrder): void {
                        if (array_is_list($outOfOrder)) {
                            /** @psalm-check-type-exact $outOfOrder = list{0: string, 1?: int} */
                        }
                    }

                    /** @param array{2?: bool, 0?: string, 1: int} $outOfOrderWithRequiredKey */
                    function outOfOrderWithRequiredKey(array $outOfOrderWithRequiredKey): void {
                        if (array_is_list($outOfOrderWithRequiredKey)) {
                            /** @psalm-check-type-exact $outOfOrderWithRequiredKey = list{0: string, 1: int, 2?: bool} */
                        }
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'arrayIsListOfASealedShapeWithOptionalKeys' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param array{a?: string} $onlyAString */
                    function stringKey(array $onlyAString): void {
                        if (array_is_list($onlyAString)) {
                            /** @psalm-check-type-exact $onlyAString = array<never, never> */
                        }
                    }

                    /** @param array{0?: string, 1?: int} $listKeys */
                    function listKeys(array $listKeys): void {
                        if (array_is_list($listKeys)) {
                            /** @psalm-check-type-exact $listKeys = list{0?: string, 1?: int} */
                        }
                    }

                    /** @param array{0?: string, a?: int} $mixedKeys */
                    function mixedKeys(array $mixedKeys): void {
                        if (array_is_list($mixedKeys)) {
                            /** @psalm-check-type-exact $mixedKeys = list{0?: string} */
                        }
                    }

                    /** @param array{0?: string, 1?: int} $listKeys */
                    function nonEmpty(array $listKeys): void {
                        assert(array_is_list($listKeys) && $listKeys !== []);
                        /** @psalm-check-type-exact $listKeys = list{0: string, 1?: int} */
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'curlMultiGetHandlesReturnsAListOfCurlHandles' => [
                'code' => '<?php
                    $mh = curl_multi_init();
                    $handles = curl_multi_get_handles($mh);',
                'assertions' => ['$handles===' => 'list<CurlHandle>'],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'procOpenOnlyWritesPipes' => [
                'code' => <<<'PHP'
                    <?php
                    $pipes = null;
                    $process = proc_open('ls', [1 => ['pipe', 'w']], $pipes);
                    PHP,
                'assertions' => ['$pipes===' => 'array<array-key, resource>'],
            ],
            'procOpenOnlyWritesPipesOnPhp80' => [
                'code' => <<<'PHP'
                    <?php
                    $pipes = 'not an array';
                    $process = proc_open(['ls'], [1 => ['pipe', 'w']], $pipes);
                    PHP,
                'assertions' => ['$pipes===' => 'array<array-key, resource>'],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'splFileObjectFscanfOnlyWritesVars' => [
                'code' => <<<'PHP'
                    <?php
                    function f(SplFileObject $file, SplTempFileObject $temp): void {
                        $hours = [];
                        $file->fscanf('%d', $hours);
                        /** @psalm-check-type-exact $hours = float|int|string */
                        $minutes = [];
                        $temp->fscanf('%d', $minutes);
                        /** @psalm-check-type-exact $minutes = float|int|string */
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'intltzOnlyWritesOutParams' => [
                'code' => <<<'PHP'
                    <?php
                    function f(): void {
                        $tz = intltz_get_gmt();
                        $raw = null;
                        $dst = null;
                        intltz_get_offset($tz, 0.0, false, $raw, $dst);
                        /** @psalm-check-type-exact $raw = int */
                        /** @psalm-check-type-exact $dst = int */
                        $is_system = 'unknown';
                        intltz_get_canonical_id('Europe/Paris', $is_system);
                        /** @psalm-check-type-exact $is_system = bool */
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'intltzGetCanonicalIdOnlyWritesIsSystemIdOnPhp74' => [
                'code' => <<<'PHP'
                    <?php
                    $is_system = 'unknown';
                    intltz_get_canonical_id('Europe/Paris', $is_system);
                    PHP,
                'assertions' => ['$is_system===' => 'bool'],
            ],
            'opensslSealOnlyWritesIv' => [
                'code' => <<<'PHP'
                    <?php
                    function f(OpenSSLAsymmetricKey $key): void {
                        $iv = null;
                        openssl_seal('data', $sealed, $keys, [$key], 'aes-256-cbc', $iv);
                        /** @psalm-check-type-exact $iv = string */
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'socketAddrinfoLookupTakesAnErrorCodeOnPhp86' => [
                'code' => <<<'PHP'
                    <?php
                    $error_code = null;
                    $info = socket_addrinfo_lookup('localhost', null, [], $error_code);
                    PHP,
                'assertions' => [
                    '$error_code===' => 'int|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'socketCmsgSpaceReturnsIntOnPhp86' => [
                'code' => <<<'PHP'
                    <?php
                    $space = socket_cmsg_space(1, 1);
                    PHP,
                'assertions' => [
                    '$space===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'ldapFreeResultReturnsTrueOnPhp86' => [
                'code' => <<<'PHP'
                    <?php
                    /** @var mixed $result */
                    $freed = ldap_free_result($result);
                    PHP,
                'assertions' => [
                    '$freed===' => 'true',
                ],
                'ignored_issues' => ['MixedArgument'],
                'php_version' => '8.6',
            ],
            'streamGetMetaDataOnlyAlwaysSetsModeSeekableStreamTypeAndUnreadBytes' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param resource $stream */
                    function f($stream): void {
                        $meta = stream_get_meta_data($stream);
                        $mode = $meta['mode'];
                        $seekable = $meta['seekable'];
                        $stream_type = $meta['stream_type'];
                        $unread_bytes = $meta['unread_bytes'];
                        $uri = $meta['uri'] ?? null;
                        $wrapper_type = isset($meta['wrapper_type']) ? $meta['wrapper_type'] : null;
                        $timed_out = $meta['timed_out'] ?? false;
                        $mediatype = $meta['mediatype'] ?? null;
                        $charset = $meta['charset'] ?? null;
                        $base64 = $meta['base64'] ?? false;
                        /** @psalm-check-type-exact $mode = string */
                        /** @psalm-check-type-exact $seekable = bool */
                        /** @psalm-check-type-exact $stream_type = string */
                        /** @psalm-check-type-exact $unread_bytes = int */
                        /** @psalm-check-type-exact $uri = string|null */
                        /** @psalm-check-type-exact $wrapper_type = string|null */
                        /** @psalm-check-type-exact $timed_out = bool */
                        /** @psalm-check-type-exact $mediatype = string|null */
                        /** @psalm-check-type-exact $charset = string|null */
                        /** @psalm-check-type-exact $base64 = bool */
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
            ],
            'streamGetMetaDataShapeOnPhp85' => [
                'code' => <<<'PHP'
                    <?php
                    $stream = fopen('php://memory', 'r+');
                    if ($stream === false) {
                        exit;
                    }
                    $meta = stream_get_meta_data($stream);
                    PHP,
                'assertions' => [
                    '$meta===' => 'array{base64?: bool, blocked?: bool, charset?: string, crypto?: array{cipher_bits: int, cipher_name: string, cipher_version: string, protocol: string}, eof?: bool, mediatype?: string, mode: string, seekable: bool, stream_type: string, timed_out?: bool, unread_bytes: int, uri?: string, wrapper_data?: mixed, wrapper_type?: string}',
                ],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'nativeConditionalUsesStringableArgument' => [
                'code' => <<<'PHP'
                    <?php
                    function test(Throwable $error): void {
                        $result = mb_convert_encoding($error, 'UTF-8');
                        /** @psalm-check-type-exact $result = false|string */
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => ['ImplicitToStringCast'],
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
            'getTypeSwitchFallingThroughAnImpossibleCaseOfTheSameVariable' => [
                'code' => '<?php
                    function f(int $data): string {
                        switch (gettype($data)) {
                            case "string":
                            case "integer":
                                return "s";
                        }

                        return "";
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'strtolowerOfDocblockLiteralsKeepsFromDocblock' => [
                'code' => '<?php
                    /** @param \'A\'|\'B\' $k */
                    function f(string $k): void {
                        $l = strtolower($k);
                        if ($l === \'c\') {}
                    }',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'mbStrtolowerOfDocblockLiteralsKeepsFromDocblock' => [
                'code' => '<?php
                    /** @param \'A\'|\'B\' $k */
                    function f(string $k): void {
                        $l = mb_strtolower($k);
                        if ($l === \'c\') {}
                    }',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'strtolowerOfNativeLiteralsIsStillATypeContradiction' => [
                'code' => '<?php
                    function f(): void {
                        $l = strtolower(rand() ? \'A\' : \'B\');
                        if ($l === \'c\') {}
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'callUserFuncWithADifferentCase' => [
                'code' => '<?php
                    function takesInt(int $i): string {
                        return (string) $i;
                    }

                    echo CALL_USER_FUNC(\'takesInt\', \'x\');',
                'error_message' => 'InvalidScalarArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:53',
            ],
            'callUserFuncArrayWithADifferentCase' => [
                'code' => '<?php
                    function takesInt(int $i): string {
                        return (string) $i;
                    }

                    echo Call_User_Func_Array(\'takesInt\', [\'x\']);',
                'error_message' => 'InvalidScalarArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:59',
            ],
            'aliasOfAUserFunctionImportedWithAnotherCasingIsReported' => [
                'code' => '<?php
                    namespace Lib {
                        function fooBar(): int {
                            return 1;
                        }
                    }

                    namespace App {
                        use function Lib\FOOBAR as run;

                        echo run();
                    }',
                'error_message' => 'WrongCaseFunction - src' . DIRECTORY_SEPARATOR . 'somefile.php:11:30 - Function FOOBAR is declared as fooBar and has wrong casing',
            ],
            'wrongCaseOfAFunctionCall' => [
                'code' => '<?php
                    function fooBar(): void {}

                    foobar();',
                'error_message' => 'WrongCaseFunction - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:21 - '
                    . 'Function foobar is declared as fooBar and has wrong casing',
            ],
            'wrongCaseOfAFunctionCallInANamespace' => [
                'code' => '<?php
                    namespace Foo;

                    function fooBar(): void {}

                    FOOBAR();',
                'error_message' => 'WrongCaseFunction - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:21',
            ],
            'wrongCaseOfAFunctionPassedAsACallableString' => [
                'code' => '<?php
                    function abc(): void {}

                    /** @param callable $callable */
                    function runThis(string $string, $callable): void {
                        $callable($string);
                    }

                    runThis("foo", "aBc");',
                'error_message' => 'WrongCaseFunction - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:36',
            ],
            'wrongCaseOfAFunctionImportedWithAnAlias' => [
                'code' => '<?php
                    namespace App;

                    use function StrToUpper as up;

                    echo up("x");',
                'error_message' => 'WrongCaseFunction - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:26 - '
                    . 'Function StrToUpper is declared as strtoupper and has wrong casing',
            ],
            'wrongCaseOfABuiltinFunctionCall' => [
                'code' => '<?php
                    echo STRLEN("foo");',
                'error_message' => 'WrongCaseFunction - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:26 - '
                    . 'Function STRLEN is declared as strlen and has wrong casing',
            ],
            'fileTestFunctionOnAnEmptyStringIsAlwaysFalse' => [
                'code' => '<?php
                    $path = "";
                    if (is_file($path)) {
                        echo "never";
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'fileTestFunctionsStayImpure' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function exists(string $path): bool {
                        return file_exists($path);
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'filterVarEmptyStringNullIsRedundantForAFilterWithoutIt' => [
                'code' => <<<'PHP'
                    <?php
                    function filter(string $s): void {
                        filter_var($s, FILTER_VALIDATE_INT, FILTER_FLAG_EMPTY_STRING_NULL);
                    }
                    PHP,
                'error_message' => 'RedundantFlag',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'filterVarNullOnFailureWithThrowOnFailureIsAValueError' => [
                'code' => <<<'PHP'
                    <?php
                    function filter(string $s): void {
                        filter_var($s, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE | FILTER_THROW_ON_FAILURE);
                    }
                    PHP,
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:5 - FILTER_NULL_ON_FAILURE cannot be combined with FILTER_THROW_ON_FAILURE, it is a ValueError',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'filterVarGlobalRangeBeforePhp85IsNotAFlagOfEveryFilter' => [
                'code' => <<<'PHP'
                    <?php
                    function filter(string $s): void {
                        filter_var($s, FILTER_VALIDATE_INT, 268435456); // FILTER_FLAG_GLOBAL_RANGE up to PHP 8.4
                    }
                    PHP,
                'error_message' => 'RedundantFlag - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:5 - Not all flags used are supported by the filter used',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'classExistsIdenticalToFalseDoesNotGuardTheRightOperandOfAnd' => [
                'code' => '<?php
                    function check(): bool {
                        return class_exists(Optional::class) === false && Optional::available();
                    }',
                'error_message' => 'UndefinedClass',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'classExistsInAndDoesNotLeakAfterTheCondition' => [
                'code' => <<<'PHP'
                    <?php
                    if (rand(0, 1) && class_exists(Foo::class)) {
                    }
                    new Foo();
                    PHP,
                'error_message' => 'UndefinedClass',
            ],
            'pregReplaceCallbackWithOffsetCaptureGivesPairsNotStrings' => [
                'code' => '<?php
                    preg_replace_callback(
                        "#^([a-z]+)$|^([0-9]+)$#",
                        fn ($m) => strtoupper($m[1]),
                        "1234567890",
                        1,
                        $count,
                        PREG_OFFSET_CAPTURE,
                    );',
                'error_message' => 'InvalidArgument',
                'error_levels' => ['PossiblyUndefinedIntArrayOffset'],
            ],
            'streamSelectDoesNotTakeAContextBeforePhp86' => [
                'code' => '<?php
                    $read = [STDIN];
                    $write = null;
                    $except = null;

                    stream_select($read, $write, $except, 0, 0, null);',
                'error_message' => 'TooManyArguments',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'callableAndScalarUnionIsPossiblyInvalidForInt' => [
                'code' => '<?php
                    /** @param int $i */
                    function takesInt($i): void {}

                    /** @param callable|scalar $x */
                    function f($x): void {
                        takesInt($x);
                    }',
                'error_message' => 'PossiblyInvalidArgument',
            ],
            'scalarAndCallableUnionIsPossiblyInvalidForInt' => [
                'code' => '<?php
                    /** @param int $i */
                    function takesInt($i): void {}

                    /** @param scalar|callable $x */
                    function f($x): void {
                        takesInt($x);
                    }',
                'error_message' => 'PossiblyInvalidArgument',
            ],
            'arrayAndScalarUnionIsAPlainMismatchForString' => [
                'code' => '<?php
                    function takesString(string $s): void {}

                    /** @param array<mixed>|scalar $x */
                    function f($x): void {
                        takesString($x);
                    }',
                'error_message' => 'PossiblyInvalidArgument',
            ],
            'stringableAndArrayAndScalarUnionIsPossiblyInvalidForStringable' => [
                'code' => '<?php
                    function takesStringable(Stringable $s): void {}

                    /** @param Stringable|UnitEnum|array<mixed>|int|null|string $x */
                    function g($x): void {
                        takesStringable($x);
                    }',
                'error_message' => 'PossiblyInvalidArgument',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'floatAndIntUnionIsAnInvalidOffsetOfAConstantArray' => [
                'code' => '<?php
                    final class A {
                        const S = [1 => "a", 1024 => "b"];

                        /** @param float|int $x */
                        public static function f($x): string {
                            return self::S[$x];
                        }
                    }',
                'error_message' => 'InvalidArrayOffset - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:36',
            ],
            'intAndFloatUnionIsAnInvalidOffsetOfAConstantArray' => [
                'code' => '<?php
                    final class A {
                        const S = [1 => "a", 1024 => "b"];

                        /** @param int|float $x */
                        public static function f($x): string {
                            return self::S[$x];
                        }
                    }',
                'error_message' => 'InvalidArrayOffset - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:36',
            ],
            'emptyStringIsNotANonEmptyLowercaseString' => [
                'code' => '<?php
                    /** @param non-empty-lowercase-string $s */
                    function takesNonEmptyLowercase(string $s): void {}

                    takesNonEmptyLowercase("");',
                'error_message' => 'InvalidArgument',
            ],
            'propertyExistsOfADeclaredPropertyIsAlwaysTrue' => [
                'code' => '<?php
                    class Page {
                        public string $title = "";
                    }

                    function check(Page $page): void {
                        if (property_exists($page, "title")) {
                            echo "has title";
                        }
                    }',
                'error_message' => 'RedundantCondition - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:29 - Operand of type true is always truthy',
            ],
            'negatedPropertyExistsOfAnInheritedPropertyIsAlwaysFalse' => [
                'code' => '<?php
                    class Page {
                        public string $title = "";
                    }

                    final class Post extends Page {}

                    function check(Post $post): void {
                        if (!property_exists($post, "title")) {
                            echo "no title";
                        }
                    }',
                'error_message' => 'TypeDoesNotContainType - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:29 - Operand of type false is always falsy',
            ],
            'functionExistsDoesNotGuardAnotherFunctionInsideAnotherNarrowingCondition' => [
                'code' => '<?php
                    function run(int $arg): void {
                        if ($arg) {
                            if (function_exists("strlen") && unknown_function()) {
                                echo 1;
                            }
                        }
                    }',
                'error_message' => 'UndefinedFunction - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:62 - Function unknown_function does not exist',
            ],
            'functionExistsDoesNotGuardAnotherFunctionInTheSameCondition' => [
                'code' => '<?php
                    function run(): void {
                        if (function_exists("strlen") && unknown_function()) {
                            echo 1;
                        }
                    }',
                'error_message' => 'UndefinedFunction - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:58 - Function unknown_function does not exist',
            ],
            'functionExistsDoesNotGuardAnotherFunctionInItsBody' => [
                'code' => '<?php
                    function run(): void {
                        if (function_exists("guarded_function")) {
                            guarded_function();
                            unknown_function();
                        }
                    }',
                'error_message' => 'UndefinedFunction - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:29 - Function unknown_function does not exist',
            ],
            'functionExistsDoesNotGuardAnotherCallableString' => [
                'code' => '<?php
                    function run(): void {
                        if (function_exists("guarded_callback")) {
                            array_map("unknown_callback", [1]);
                        }
                    }',
                'error_message' => 'UndefinedFunction',
            ],
            'builtInByReferenceParameterRejectsAConstant' => [
                'code' => '<?php
                    const NUMBERS = [3, 1, 2];
                    sort(NUMBERS);',
                'error_message' => 'InvalidPassByReference - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:26 - Parameter 1 of sort expects a variable',
            ],
            'mysqliBindResultRequiresOutputVariable' => [
                'code' => '<?php
                    function bind(mysqli_stmt $statement): void {
                        mysqli_stmt_bind_result($statement);
                    }',
                'error_message' => 'TooFewArguments',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'invalidScalarArgument' => [
                'code' => '<?php
                    function fooFoo(int $a): void {}
                    fooFoo("string");',
                'error_message' => 'InvalidScalarArgument',
            ],
            'invalidArgumentCallableWithoutArgsUnion' => [
                'code' => '<?php
                    function foo(int $a): void {}

                    /**
                     * @param callable()|float $callable
                     * @return void
                     */
                    function acme($callable) {}
                    acme("foo");',
                'error_message' => 'InvalidArgument',
            ],
            'invalidArgumentWithDeclareStrictTypes' => [
                'code' => '<?php declare(strict_types=1);
                    function fooFoo(int $a): void {}
                    fooFoo("string");',
                'error_message' => 'InvalidArgument',
            ],
            'invalidArgumentFalseTrueExpected' => [
                'code' => '<?php
                    /**
                     * @param true|string $arg
                     * @return void
                     */
                    function foo($arg) {}

                    foo(false);',
                'error_message' => 'InvalidArgument',
            ],
            'builtinFunctioninvalidArgumentWithWeakTypes' => [
                'code' => '<?php
                    $s = substr(5, 4);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'builtinFunctioninvalidArgumentWithDeclareStrictTypes' => [
                'code' => '<?php declare(strict_types=1);
                    $s = substr(5, 4);',
                'error_message' => 'InvalidArgument',
            ],
            'builtinFunctioninvalidArgumentWithDeclareStrictTypesInClass' => [
                'code' => '<?php declare(strict_types=1);
                    class A {
                        public function foo() : void {
                            $s = substr(5, 4);
                        }
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'clearIssetContext' => [
                'code' => '<?php
                    function greet(bool $arg): ?string
                    {
                        return $arg ? "hi" : null;
                    }

                    echo greet($undef) ?? "bye";',
                'error_message' => 'UndefinedGlobalVariable',
            ],
            'mixedArgument' => [
                'code' => '<?php
                    function fooFoo(int $a): void {}
                    /** @var mixed */
                    $a = "hello";
                    fooFoo($a);',
                'error_message' => 'MixedArgument',
            ],
            'nullArgument' => [
                'code' => '<?php
                    function fooFoo(int $a): void {}
                    fooFoo(null);',
                'error_message' => 'NullArgument',
            ],
            'tooFewArguments' => [
                'code' => '<?php
                    function fooFoo(int $a): void {}
                    fooFoo();',
                'error_message' => 'TooFewArguments',
            ],
            'tooManyArguments' => [
                'code' => '<?php
                    function fooFoo(int $a): void {}
                    fooFoo(5, "dfd");',
                'error_message' => 'TooManyArguments - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:21 - Too many arguments for fooFoo '
                    . '- expecting 1 but saw 2',
            ],
            'tooManyArgumentsForConstructor' => [
                'code' => '<?php
                  class A { }
                  new A("hello");',
                'error_message' => 'TooManyArguments',
            ],
            'typeCoercion' => [
                'code' => '<?php
                    class A {}
                    class B extends A{}

                    function fooFoo(B $b): void {}
                    fooFoo(new A());',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'arrayTypeCoercion' => [
                'code' => '<?php
                    class A {}
                    class B extends A{}

                    /**
                     * @param  B[]  $b
                     * @return void
                     */
                    function fooFoo(array $b) {}
                    fooFoo([new A()]);',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'duplicateParam' => [
                'code' => '<?php
                    /**
                     * @return void
                     */
                    function f($p, $p) {}',
                'error_message' => 'DuplicateParam',
                'error_levels' => ['MissingParamType'],
            ],
            'invalidParamDefault' => [
                'code' => '<?php
                    function f(int $p = false) {}',
                'error_message' => 'InvalidParamDefault',
            ],
            'invalidParamDefaultNewObjectNotImplementingInterface' => [
                'code' => '<?php
                    function f(Traversable $p = new stdClass()): void {}',
                'error_message' => 'InvalidParamDefault',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'invalidParamDefaultNewObjectNotInUnion' => [
                'code' => '<?php
                    function f(Countable|int $p = new stdClass()): void {}',
                'error_message' => 'InvalidParamDefault',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'invalidDocblockParamDefault' => [
                'code' => '<?php
                    /**
                     * @param  int $p
                     * @return void
                     */
                    function f($p = false) {}',
                'error_message' => 'InvalidParamDefault',
            ],
            'badByRef' => [
                'code' => '<?php
                    function fooFoo(string &$v): void {}
                    fooFoo("a");',
                'error_message' => 'InvalidPassByReference',
            ],
            'badByRefWithAssignArgument' => [
                'code' => '<?php
                    function fooFoo(mixed &$v): void {}
                    $t = "a";
                    $n = 1;
                    $o = new stdClass();
                    fooFoo($s = "a");',
                'error_message' => 'InvalidPassByReference',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'badByRefWithAssignOpArgument' => [
                'code' => '<?php
                    function fooFoo(mixed &$v): void {}
                    $t = "a";
                    $n = 1;
                    $o = new stdClass();
                    fooFoo($t .= "a");',
                'error_message' => 'InvalidPassByReference',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'badByRefWithBooleanNotArgument' => [
                'code' => '<?php
                    function fooFoo(mixed &$v): void {}
                    $t = "a";
                    $n = 1;
                    $o = new stdClass();
                    fooFoo(!$t);',
                'error_message' => 'InvalidPassByReference',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'badByRefWithUnaryMinusArgument' => [
                'code' => '<?php
                    function fooFoo(mixed &$v): void {}
                    $t = "a";
                    $n = 1;
                    $o = new stdClass();
                    fooFoo(-$n);',
                'error_message' => 'InvalidPassByReference',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'badByRefWithPostIncArgument' => [
                'code' => '<?php
                    function fooFoo(mixed &$v): void {}
                    $t = "a";
                    $n = 1;
                    $o = new stdClass();
                    fooFoo($n++);',
                'error_message' => 'InvalidPassByReference',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'badByRefWithPreIncArgument' => [
                'code' => '<?php
                    function fooFoo(mixed &$v): void {}
                    $t = "a";
                    $n = 1;
                    $o = new stdClass();
                    fooFoo(++$n);',
                'error_message' => 'InvalidPassByReference',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'badByRefWithIssetArgument' => [
                'code' => '<?php
                    function fooFoo(mixed &$v): void {}
                    $t = "a";
                    $n = 1;
                    $o = new stdClass();
                    fooFoo(isset($t));',
                'error_message' => 'InvalidPassByReference',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'badByRefWithEmptyArgument' => [
                'code' => '<?php
                    function fooFoo(mixed &$v): void {}
                    $t = "a";
                    $n = 1;
                    $o = new stdClass();
                    fooFoo(empty($t));',
                'error_message' => 'InvalidPassByReference',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'badByRefWithInstanceofArgument' => [
                'code' => '<?php
                    function fooFoo(mixed &$v): void {}
                    $t = "a";
                    $n = 1;
                    $o = new stdClass();
                    fooFoo($t instanceof stdClass);',
                'error_message' => 'InvalidPassByReference',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'badByRefWithCloneArgument' => [
                'code' => '<?php
                    function fooFoo(mixed &$v): void {}
                    $t = "a";
                    $n = 1;
                    $o = new stdClass();
                    fooFoo(clone $o);',
                'error_message' => 'InvalidPassByReference',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'badByRefWithClosureArgument' => [
                'code' => '<?php
                    function fooFoo(mixed &$v): void {}
                    $t = "a";
                    $n = 1;
                    $o = new stdClass();
                    fooFoo(function (): void {});',
                'error_message' => 'InvalidPassByReference',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'badByRefWithArrowFunctionArgument' => [
                'code' => '<?php
                    function fooFoo(mixed &$v): void {}
                    $t = "a";
                    $n = 1;
                    $o = new stdClass();
                    fooFoo(fn(): int => 1);',
                'error_message' => 'InvalidPassByReference',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'badByRefWithMatchArgument' => [
                'code' => '<?php
                    function fooFoo(mixed &$v): void {}
                    $t = "a";
                    $n = 1;
                    $o = new stdClass();
                    fooFoo(match (1) { default => 2 });',
                'error_message' => 'InvalidPassByReference',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'badByRefWithErrorSuppressArgument' => [
                'code' => '<?php
                    function fooFoo(mixed &$v): void {}
                    $a = 1;
                    fooFoo(@$a);',
                'error_message' => 'InvalidPassByReference',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'badByRefWithPrintArgument' => [
                'code' => '<?php
                    function fooFoo(mixed &$v): void {}
                    fooFoo(print "a");',
                'error_message' => 'InvalidPassByReference',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'badByRefWithNewArgument' => [
                'code' => '<?php
                    function fooFoo(mixed &$v): void {}
                    fooFoo(new stdClass());',
                'error_message' => 'InvalidPassByReference',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'badByRefWithAssignArgumentOfAMethod' => [
                'code' => '<?php
                    class A {
                        public function m(mixed &$v): void {}
                    }
                    (new A())->m($s = "a");',
                'error_message' => 'InvalidPassByReference',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'badByRefWithAssignArgumentOfAStaticMethod' => [
                'code' => '<?php
                    class A {
                        public static function m(mixed &$v): void {}
                    }
                    A::m($s = "a");',
                'error_message' => 'InvalidPassByReference',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'badByRefWithAssignArgumentOfAVariadicParam' => [
                'code' => '<?php
                    function fooFoo(mixed &...$v): void {}
                    $a = 1;
                    fooFoo($a, $s = "a");',
                'error_message' => 'InvalidPassByReference',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'badArrayByRef' => [
                'code' => '<?php
                    function fooFoo(array &$a): void {}
                    fooFoo([1, 2, 3]);',
                'error_message' => 'InvalidPassByReference',
            ],
            'invalidArgAfterCallable' => [
                'code' => '<?php
                    /**
                     * @param callable $callback
                     * @return void
                     */
                    function route($callback) {
                      if (!is_callable($callback)) {  }
                      takes_int("string");
                    }

                    function takes_int(int $i) {}',
                'error_message' => 'InvalidScalarArgument',
            ],
            'objectToStringReturnDoesNotMatchLowercaseStringParameter' => [
                'code' => '<?php
                    final class UppercaseText {
                        /** @return "AAA" */
                        public function __toString(): string {
                            return "AAA";
                        }
                    }

                    /** @param lowercase-string $value */
                    function acceptsLowercase(string $value): void {}

                    /** @psalm-suppress ImplicitToStringCast */
                    acceptsLowercase(new UppercaseText());',
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'objectToStringReturnCoercesToLowercaseStringParameter' => [
                'code' => '<?php
                    final class Text {
                        public function __toString(): string {
                            return "text";
                        }
                    }

                    /** @param lowercase-string $value */
                    function acceptsLowercase(string $value): void {}

                    /** @psalm-suppress ImplicitToStringCast */
                    acceptsLowercase(new Text());',
                'error_message' => 'ArgumentTypeCoercion',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'objectToStringReturnCoercionReportsImplicitCast' => [
                'code' => '<?php
                    final class Text {
                        public function __toString(): string {
                            return "text";
                        }
                    }

                    /** @param lowercase-string $value */
                    function acceptsLowercase(string $value): void {}

                    /** @psalm-suppress ArgumentTypeCoercion */
                    acceptsLowercase(new Text());',
                'error_message' => 'ImplicitToStringCast',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'stringableUnionWithNonStringableObjectDoesNotCoerce' => [
                'code' => '<?php
                    final class Text {
                        public function __toString(): string {
                            return "text";
                        }
                    }

                    final class Other {}

                    /** @param lowercase-string $value */
                    function acceptsLowercase(string $value): void {}

                    /** @param Text|Other $value */
                    function passObject($value): void {
                        /** @psalm-suppress ImplicitToStringCast */
                        acceptsLowercase($value);
                    }',
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'untypedObjectToStringReturnCoercesToLowercaseStringParameter' => [
                'code' => '<?php
                    final class Text {
                        public function __toString() {
                            return "text";
                        }
                    }

                    /** @param lowercase-string $value */
                    function acceptsLowercase(string $value): void {}

                    /** @psalm-suppress ImplicitToStringCast */
                    acceptsLowercase(new Text());',
                'error_message' => 'ArgumentTypeCoercion',
                'error_levels' => [],
                'php_version' => '7.4',
            ],
            'objectToStringReturnCoercesToClassStringParameter' => [
                'code' => '<?php
                    final class Text {
                        public function __toString(): string {
                            return "text";
                        }
                    }

                    /** @param class-string $value */
                    function acceptsClassString(string $value): void {}

                    /** @psalm-suppress ImplicitToStringCast */
                    acceptsClassString(new Text());',
                'error_message' => 'ArgumentTypeCoercion',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'objectToStringReturnCoercesToNumericStringParameter' => [
                'code' => '<?php
                    final class Text {
                        public function __toString(): string {
                            return "text";
                        }
                    }

                    /** @param numeric-string $value */
                    function acceptsNumericString(string $value): void {}

                    /** @psalm-suppress ImplicitToStringCast */
                    acceptsNumericString(new Text());',
                'error_message' => 'ArgumentTypeCoercion',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'objectToStringReturnDoesNotMatchLiteralStringParameter' => [
                'code' => '<?php
                    final class Text {
                        /** @return "AAA" */
                        public function __toString(): string {
                            return "AAA";
                        }
                    }

                    /** @param "BBB"|"CCC" $value */
                    function acceptsOtherLiterals(string $value): void {}

                    /** @psalm-suppress ImplicitToStringCast */
                    acceptsOtherLiterals(new Text());',
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'objectToStringReturnDoesNotMatchNonEmptyStringParameter' => [
                'code' => '<?php
                    final class EmptyText {
                        /** @return "" */
                        public function __toString(): string {
                            return "";
                        }
                    }

                    /** @param non-empty-string $value */
                    function acceptsNonEmpty(string $value): void {}

                    /** @psalm-suppress ImplicitToStringCast */
                    acceptsNonEmpty(new EmptyText());',
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'objectToStringReturnCoercesToNonEmptyStringParameter' => [
                'code' => '<?php
                    final class Text {
                        public function __toString(): string {
                            return "text";
                        }
                    }

                    /** @param non-empty-string $value */
                    function acceptsNonEmpty(string $value): void {}

                    /** @psalm-suppress ImplicitToStringCast */
                    acceptsNonEmpty(new Text());',
                'error_message' => 'ArgumentTypeCoercion',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'clampIsUndefinedBeforePhp86' => [
                'code' => '<?php
                    $result = clamp(5, 0, 10);',
                'error_message' => 'UndefinedFunction',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'undefinedFunctionInArrayMap' => [
                'code' => '<?php
                    array_map(
                        "undefined_function",
                        [1, 2, 3]
                    );',
                'error_message' => 'UndefinedFunction',
            ],
            'objectLikeKeyChecksAgainstDifferentGeneric' => [
                'code' => '<?php
                    /**
                     * @param array<string, int> $b
                     */
                    function a($b): int
                    {
                      return $b["a"];
                    }

                    a(["a" => "hello"]);',
                'error_message' => 'InvalidArgument',
            ],
            'objectLikeKeyChecksAgainstDifferentTKeyedArray' => [
                'code' => '<?php
                    /**
                     * @param array{a: int} $b
                     */
                    function a($b): int
                    {
                      return $b["a"];
                    }

                    a(["a" => "hello"]);',
                'error_message' => 'InvalidArgument',
            ],
            'possiblyNullFunctionCall' => [
                'code' => '<?php
                    $a = rand(0, 1) ? function(): void {} : null;
                    $a();',
                'error_message' => 'PossiblyNullFunctionCall',
            ],
            'possiblyInvalidFunctionCall' => [
                'code' => '<?php
                    $a = rand(0, 1) ? function(): void {} : 23515;
                    $a();',
                'error_message' => 'PossiblyInvalidFunctionCall',
            ],
            'varExportAssignmentToVoid' => [
                'code' => '<?php
                    $a = var_export(["a"]);',
                'error_message' => 'AssignmentToVoid',
            ],
            'explodeWithNegativeLimitIsPossiblyEmpty' => [
                'code' => '<?php
                    /**
                     * @return non-empty-list<string>
                     */
                    function exploder(string $s): array {
                        return explode("-", $s, -1);
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'explodeWithEmptyString' => [
                'code' => '<?php
                    function exploder(string $s) : array {
                        return explode("", $s);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'complainAboutArrayToIterable' => [
                'code' => '<?php
                    class A {}
                    class B {}
                    /**
                     * @param iterable<mixed,A> $p
                     */
                    function takesIterableOfA(iterable $p): void {}

                    takesIterableOfA([new B]); // should complain',
                'error_message' => 'InvalidArgument',
            ],
            'complainAboutArrayToIterableSingleParam' => [
                'code' => '<?php
                    class A {}
                    class B {}
                    /**
                     * @param iterable<A> $p
                     */
                    function takesIterableOfA(iterable $p): void {}

                    takesIterableOfA([new B]); // should complain',
                'error_message' => 'InvalidArgument',
            ],
            'putInvalidTypeMessagesFirst' => [
                'code' => '<?php
                    $q = rand(0,1) ? new stdClass : false;
                    strlen($q);',
                'error_message' => 'InvalidArgument',
            ],
            'getTypeInvalidValue' => [
                'code' => '<?php
                    /**
                     * @param mixed $maybe
                     */
                    function matchesTypes($maybe) : void {
                        $t = gettype($maybe);
                        if ($t === "bool") {}
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'rangeWithFloatStep' => [
                'code' => '<?php

                    function foo(int $bar) : string {
                        return (string) $bar;
                    }

                    foreach (range(1, 10, .3) as $x) {
                        foo($x);
                    }',
                'error_message' => 'InvalidScalarArgument',
            ],
            'rangeWithFloatStart' => [
                'code' => '<?php

                    function foo(int $bar) : string {
                        return (string) $bar;
                    }

                    foreach (range(1.4, 10) as $x) {
                        foo($x);
                    }',
                'error_message' => 'InvalidScalarArgument',
            ],
            'duplicateFunction' => [
                'code' => '<?php
                    function f() : void {}
                    function f() : void {}',
                'error_message' => 'DuplicateFunction',
            ],
            'duplicateCoreFunction' => [
                'code' => '<?php
                    function sort() : void {}',
                'error_message' => 'DuplicateFunction',
            ],
            'functionCallOnMixed' => [
                'code' => '<?php
                    /** @var mixed $s */
                    $s = 1;
                    $s();',
                'error_message' => 'MixedFunctionCall',
            ],
            'iterableOfObjectCannotAcceptIterableOfInt' => [
                'code' => '<?php
                    /** @param iterable<string,object> $_p */
                    function accepts(iterable $_p): void {}

                    /** @return iterable<int,int> */
                    function iterable() { yield 1; }

                    accepts(iterable());',
                'error_message' => 'InvalidArgument',
            ],
            'iterableOfObjectCannotAcceptTraversableOfInt' => [
                'code' => '<?php
                    /** @param iterable<string,object> $_p */
                    function accepts(iterable $_p): void {}

                    /** @return Traversable<int,int> */
                    function traversable() { yield 1; }

                    accepts(traversable());',
                'error_message' => 'InvalidArgument',
            ],
            'iterableOfObjectCannotAcceptGeneratorOfInt' => [
                'code' => '<?php
                    /** @param iterable<string,object> $_p */
                    function accepts(iterable $_p): void {}

                    /** @return Generator<int,int,mixed,void> */
                    function generator() { yield 1; }

                    accepts(generator());',
                'error_message' => 'InvalidArgument',
            ],
            'iterableOfObjectCannotAcceptArrayOfInt' => [
                'code' => '<?php
                    /** @param iterable<string,object> $_p */
                    function accepts(iterable $_p): void {}

                    /** @return array<int,int> */
                    function arr() { return [1]; }

                    accepts(arr());',
                'error_message' => 'InvalidArgument',
            ],
            'nonNullableByRef' => [
                'code' => '<?php
                    function foo(string &$s) : void {}

                    function bar() : void {
                        foo($bar);
                    }',
                'error_message' => 'NullReference',
            ],
            'intCastByRef' => [
                'code' => '<?php
                    function foo(int &$i) : void {}

                    $a = rand(0, 1) ? null : 5;
                    foo((int) $a);',
                'error_message' => 'InvalidPassByReference',
            ],
            'implicitAssignmentToStringFromMixed' => [
                'code' => '<?php
                    /** @param "a"|"b" $s */
                    function takesString(string $s) : void {}
                    function takesInt(int $i) : void {}

                    /**
                     * @param mixed $s
                     * @psalm-suppress MixedArgument
                     */
                    function bar($s) : void {
                        takesString($s);
                        takesInt($s);
                    }',
                'error_message' => 'InvalidScalarArgument',
            ],
            'tooFewArgsAccurateCount' => [
                'code' => '<?php
                    preg_match(\'/adsf/\');',
                'error_message' => 'TooFewArguments - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:21 - Too few arguments for preg_match - expecting subject to be passed',
            ],
            'tooFewArgsForCallableNamesTheCallable' => [
                'code' => '<?php
                    /** @param callable(mixed): bool $c */
                    function f(callable $c): void {
                        $c();
                    }',
                'error_message' => 'TooFewArguments - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:25 - Too few arguments for callable(mixed) - expecting parameter #1 to be passed',
            ],
            'tooFewArgsForCallableCountsTheMissingParameterFromTheStart' => [
                'code' => '<?php
                    /** @param callable(int, string): void $c */
                    function f(callable $c): void {
                        $c(1);
                    }',
                'error_message' => 'TooFewArguments - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:25 - Too few arguments for callable(int, string) - expecting parameter #2 to be passed',
            ],
            'tooFewArgsForCallableWithNamedParametersNamesTheParameter' => [
                'code' => '<?php
                    /** @param callable(int $a, string $b): void $c */
                    function f(callable $c): void {
                        $c(1);
                    }',
                'error_message' => 'TooFewArguments - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:25 - Too few arguments for callable(int, string) - expecting b to be passed',
            ],
            'tooManyArgsForCallableNamesTheCallable' => [
                'code' => '<?php
                    /** @param callable(int, string): void $c */
                    function f(callable $c): void {
                        $c(1, "a", 3);
                    }',
                'error_message' => 'TooManyArguments - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:25 - Too many arguments for callable(int, string) - expecting 2 but saw 3',
            ],
            'compactUndefinedVariable' => [
                'code' => '<?php
                    /**
                     * @return array<string, mixed>
                     */
                    function foo() : array {
                        return compact("a", "b", "c");
                    }',
                'error_message' => 'UndefinedVariable',
            ],
            'countCallableArrayShouldBeTwo' => [
                'code' => '<?php
                    /** @param callable|false $x */
                    function example($x) : void {
                        if (is_array($x)) {
                            $c = count($x);
                            if ($c !== 2) {}
                        }
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'countOnUnknownObjectCannotBePure' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function example(\Countable $x) : int {
                        return count($x);
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'coerceCallMapArgsInStrictMode' => [
                'code' => '<?php
                    declare(strict_types=1);

                    function getStr() : ?string {
                        return rand(0,1) ? "test" : null;
                    }

                    function test() : void {
                        $g = getStr();
                        /** @psalm-suppress PossiblyNullArgument */
                        $x = strtoupper($g);
                        $c = "prefix " . (strtoupper($g ?? "") === "x" ? "xa" : "ya");
                        echo "$x, $c\n";
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'noCrashOnEmptyArrayPush' => [
                'code' => '<?php
                    array_push();',
                'error_message' => 'TooFewArguments',
            ],
            'printOnlyString' => [
                'code' => '<?php
                    print [];',
                'error_message' => 'InvalidArgument',
            ],
            'printReturns1' => [
                'code' => '<?php
                    (print "test") === 2;',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'sodiumMemzeroNullifyString' => [
                'code' => '<?php
                    function returnsStr(): string {
                        $str = "x";
                        sodium_memzero($str);
                        return $str;
                    }',
                'error_message' => 'NullableReturnStatement',
            ],
            'noCrashWithPattern' => [
                'code' => '<?php
                    echo !\is_callable($loop_callback)
                        || (\is_array($loop_callback)
                            && !\method_exists(...$loop_callback));',
                'error_message' => 'UndefinedGlobalVariable',
            ],
            'parseUrlPossiblyUndefined' => [
                'code' => '<?php
                    function bar(string $s) : string {
                        $parsed = parse_url($s);

                        return $parsed["host"];
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'parseUrlPossiblyUndefined2' => [
                'code' => '<?php
                    function bag(string $s) : string {
                        $parsed = parse_url($s);

                        if (is_string($parsed["host"] ?? false)) {
                            return $parsed["host"];
                        }

                        return "";
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'pathinfoDirnamePossiblyUndefined' => [
                'code' => '<?php
                    function dir_of(string $s) : string {
                        return pathinfo($s)["dirname"];
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'strposNoSetFirstParam' => [
                'code' => '<?php
                    function sayHello(string $format): void {
                        if (strpos("u", $format)) {}
                    }',
                'error_message' => 'InvalidLiteralArgument',
            ],
            'strposMultibyteLiteralFirstParam' => [
                'code' => '<?php
                    function sayHello(string $format): void {
                        if (strpos("здравствуй мир", $format)) {}
                    }',
                'error_message' => 'InvalidLiteralArgument',
            ],
            'curlInitIsResourceFailsIn8x' => [
                'code' => '<?php
                    $ch = curl_init();
                    if (!is_resource($ch)) {}',
                'error_message' => 'RedundantCondition',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'maxCallWithArray' => [
                'code' => '<?php
                    function foo(array $a) {
                        max($a);
                    }',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'pregSplitNoEmpty' => [
                'code' => '<?php
                    /** @return non-empty-list */
                    function foo(string $s) {
                        return preg_split("/ /", $s, -1, PREG_SPLIT_NO_EMPTY);
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'maxWithMixed' => [
                'code' => '<?php
                    /** @var mixed $b */;
                    /** @var mixed $c */;
                    $a = max($b, $c);',
                'error_message' => 'MixedAssignment',
            ],
            'literalFalseArgument' => [
                'code' => '<?php
                    function takesAString(string $s): void{
                        echo $s;
                    }

                    takesAString(false);',
                'error_message' => 'InvalidArgument',
            ],
            'getClassWithoutArgsOutsideClass' => [
                'code' => '<?php

                    echo get_class();',
                'error_message' => 'TooFewArguments',
            ],
            'count_charsWithInvalidMode' => [
                'code' => '<?php
                    function scope(int $mode){
                        $a = count_chars("foo", $mode);
                    }',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'getObjectVarsOfAStdClassIsAMixedCoercion' => [
                'code' => '<?php
                    /** @param array{i: int} $array */
                    function takesAnArray(array $array): void {
                        echo $array["i"];
                    }

                    $obj = new stdClass();

                    takesAnArray(get_object_vars($obj));',
                'error_message' => 'MixedArgumentTypeCoercion',
            ],
            'strReplaceWithFewerReplacementsThanSearches' => [
                'code' => '<?php
                    $subject = "Hello";
                    $search = ["a", "b", "c"];
                    $replace = ["x", "y"];
                    echo str_replace($search, $replace, $subject);',
                'error_message' => 'InvalidArgument',
            ],
            'strIreplaceWithFewerReplacementsThanSearches' => [
                'code' => '<?php
                    echo str_ireplace(["a", "b"], ["x"], "Hello");',
                'error_message' => 'InvalidArgument',
            ],
            'array_is_list_literal_array' => [
                'code' => '<?php
                    $list = [1 => 0, 0 => 1];
                    assert(array_is_list($list));',
                'error_message' => 'TypeDoesNotContainType',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'arrayIsListOfALiteralArrayOutOfOrderWithAnOptionalKey' => [
                'code' => '<?php
                    $list = [1 => 0, 0 => 1];
                    if (rand(0, 1)) {
                        $list[2] = 2;
                    }
                    assert(array_is_list($list));',
                'error_message' => 'TypeDoesNotContainType',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'array_is_list_on_list_shape' => [
                'code' => '<?php
                    /** @param list{string, int} $arr */
                    function f(array $arr): void {
                        assert(array_is_list($arr));
                    }',
                'error_message' => 'RedundantCondition',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'array_is_list_keyed_array_with_string_keys' => [
                'code' => '<?php
                    /** @param array{a: string, b: int} $arr */
                    function f(array $arr): void {
                        assert(array_is_list($arr));
                    }',
                'error_message' => 'TypeDoesNotContainType',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'array_is_list_keyed_array_without_first_key' => [
                'code' => '<?php
                    /** @param array{1: string, 2: int} $arr */
                    function f(array $arr): void {
                        assert(array_is_list($arr));
                    }',
                'error_message' => 'TypeDoesNotContainType',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'array_is_list_keyed_array_with_required_key_after_gap' => [
                'code' => '<?php
                    /** @param array{0: string, 2: int} $arr */
                    function f(array $arr): void {
                        assert(array_is_list($arr));
                    }',
                'error_message' => 'TypeDoesNotContainType',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'keyed_array_asserted_as_list_of_other_values' => [
                'code' => '<?php
                    /**
                     * @param mixed $value
                     * @psalm-assert list<int> $value
                     */
                    function assertIntList($value): void {}

                    /** @param array{0: string} $arr */
                    function f(array $arr): void {
                        assertIntList($arr);
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'passingObjectToStringableObjectParam' => [
                'code' => '<?php
                    /** @param stringable-object $o */
                    function acceptsStringableObject(object $o): void {}

                    acceptsStringableObject((object)[]);
                ',
                'error_message' => 'InvalidArgument',
            ],
            'passingNonStringableObjectToStringableObjectParam' => [
                'code' => '<?php
                    /** @param stringable-object $o */
                    function acceptsStringableObject(object $o): void {}

                    class C {}
                    acceptsStringableObject(new C);
                ',
                'error_message' => 'InvalidArgument',
            ],
            'passingStdClassToStringableObjectParam' => [
                'code' => '<?php
                    /** @param stringable-object $o */
                    function acceptsStringableObject(object $o): void {}

                    acceptsStringableObject(new stdClass);
                ',
                'error_message' => 'InvalidArgument',
            ],
            'shouldReturnNeverNotString' => [
                'code' => '<?php
                    /**
                     * @return string
                     */
                    function finalFunc() {
                        exit;
                    }

                    finalFunc();',
                'error_message' => 'InvalidReturnType',
            ],
            'shouldReturnNeverNotStringCaller' => [
                'code' => '<?php
                    /**
                     * @return string
                     */
                    function foo() {
                       finalFunc();
                    }

                    /**
                     * @return never
                     */
                    function finalFunc() {
                        exit;
                    }

                    foo();',
                'error_message' => 'InvalidReturnType',
            ],
            'shouldReturnNeverNotStringNoDocblockCaller' => [
                'code' => '<?php
                    /**
                     * @return string
                     */
                    function foo() {
                       finalFunc();
                    }

                    function finalFunc() {
                        exit;
                    }

                    foo();',
                'error_message' => 'InvalidReturnType',
            ],
            'DontAcceptArrayWithShapesNotContained' => [
                'code' => '<?php

                    /** @param array{bar: 0|positive-int} $foo */
                    function takesArrayShapeWithZeroOrPositiveInt(array $foo): void
                    {
                    }

                    /** @var int $mayBeInt */
                    $mayBeInt = -1;

                    takesArrayShapeWithZeroOrPositiveInt(["bar" => $mayBeInt]);
                ',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'implicitToStringCastOfAnExtraEntryOfAnUnsealedShape' => [
                'code' => '<?php
                    final class S {
                        public function __toString(): string {
                            return "s";
                        }
                    }

                    /** @param array{id: int, ...<string, string>} $a */
                    function takesShape(array $a): void {}

                    takesShape(["id" => 1, "name" => new S()]);
                ',
                'error_message' => 'ImplicitToStringCast',
            ],
            'implicitToStringCastOfTheFallbackOfAnUnsealedShape' => [
                'code' => '<?php
                    final class S {
                        public function __toString(): string {
                            return "s";
                        }
                    }

                    /** @param array{id: int, ...<string, string>} $a */
                    function takesShape(array $a): void {}

                    /** @param array{id: int, ...<string, S>} $b */
                    function passesShape(array $b): void {
                        takesShape($b);
                    }
                ',
                'error_message' => 'ImplicitToStringCast',
            ],
            'implicitToStringCastOfAnExtraEntryOfAnUnsealedList' => [
                'code' => '<?php
                    final class S {
                        public function __toString(): string {
                            return "s";
                        }
                    }

                    /** @param list{int, ...<string>} $a */
                    function takesList(array $a): void {}

                    takesList([1, new S()]);
                ',
                'error_message' => 'ImplicitToStringCast',
            ],
            'additionalFieldsOfANestedShapeInAnExtraEntry' => [
                'code' => '<?php
                    /** @param array{id: int, ...<string, array{name: string}>} $a */
                    function takesShape(array $a): void {}

                    takesShape(["id" => 1, "child" => ["name" => 1, "age" => 2]]);
                ',
                'error_message' => 'with additional array shape fields (age) was provided',
            ],
            'parentTypeOfAnEntryDoesNotHideAnInvalidEntry' => [
                'code' => '<?php
                    class P {}
                    final class C extends P {}

                    /** @param array{a: int, b: C} $a */
                    function takesShape(array $a): void {}

                    function passesParent(P $p): void {
                        takesShape(["a" => "str", "b" => $p]);
                    }
                ',
                'error_message' => 'InvalidArgument',
            ],
            'parentTypeOfAnExtraEntryDoesNotHideAnInvalidEntry' => [
                'code' => '<?php
                    class P {}
                    final class C extends P {}

                    /** @param array{a: int, ...<string, C>} $a */
                    function takesShape(array $a): void {}

                    function passesParent(P $p): void {
                        takesShape(["a" => "str", "x" => $p]);
                    }
                ',
                'error_message' => 'InvalidArgument',
            ],
            'parentTypeAndInvalidTypeArgument' => [
                'code' => '<?php
                    final class Foo {}
                    function takesFoo(Foo $_f): void {}

                    function passes(object|int $o): void {
                        takesFoo($o);
                    }
                ',
                'error_message' => 'PossiblyInvalidArgument',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'invalidTypeAndParentTypeArgument' => [
                'code' => '<?php
                    final class Foo {}
                    function takesFoo(Foo $_f): void {}

                    function passes(int|object $o): void {
                        takesFoo($o);
                    }
                ',
                'error_message' => 'PossiblyInvalidArgument',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'parentScalarTypeAndInvalidTypeArgument' => [
                'code' => '<?php
                    /** @param non-empty-string $_s */
                    function takesNonEmptyString(string $_s): void {}

                    function passes(string|int $s): void {
                        takesNonEmptyString($s);
                    }
                ',
                'error_message' => 'PossiblyInvalidArgument',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'curlHandleArgumentGivenObjectOrResource' => [
                'code' => '<?php
                    /** @param object|resource $h */
                    function g($h): void {
                        curl_getinfo($h, CURLINFO_HTTP_CODE);
                    }
                ',
                'error_message' => 'PossiblyInvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:38'
                    . ' - Argument 1 of curl_getinfo expects CurlHandle, but possibly different type'
                    . ' object|resource provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'sealedShapeWithAMixedValueMissingARequiredKeyArgument' => [
                'code' => '<?php
                    /** @param array{a: int, b: int} $_a */
                    function takesShape(array $_a): void {}

                    /** @param array{a: mixed} $a */
                    function passes(array $a): void {
                        takesShape($a);
                    }
                ',
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'sealedShapeMissingARequiredKeyArgument' => [
                'code' => '<?php
                    /** @param array{a: non-empty-string, b: int} $_a */
                    function takesShape(array $_a): void {}

                    /** @param array{a: string} $a */
                    function passes(array $a): void {
                        takesShape($a);
                    }
                ',
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'sealedShapeWithAPossiblyUndefinedRequiredKeyArgument' => [
                'code' => '<?php
                    /** @param array{a: non-empty-string, b: int} $_a */
                    function takesShape(array $_a): void {}

                    /** @param array{a: string, b?: int} $a */
                    function passes(array $a): void {
                        takesShape($a);
                    }
                ',
                'error_message' => 'ArgumentTypeCoercion',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'shapeMissingARequiredKeyAndInvalidTypeArgument' => [
                'code' => '<?php
                    /** @param array{a: non-empty-string, b: int} $_a */
                    function takesShape(array $_a): void {}

                    /** @param array{a: string}|int $a */
                    function passes(array|int $a): void {
                        takesShape($a);
                    }
                ',
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'noMemberFitsArgument' => [
                'code' => '<?php
                    final class Foo {}
                    final class Bar {}
                    function takesFoo(Foo $_f): void {}

                    function passes(Bar|int $o): void {
                        takesFoo($o);
                    }
                ',
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'implementedInterfaceAndInvalidTypeArgument' => [
                'code' => '<?php
                    interface I {}
                    final class Foo implements I {}
                    function takesFoo(Foo $_f): void {}

                    function passes(I|int $o): void {
                        takesFoo($o);
                    }
                ',
                'error_message' => 'PossiblyInvalidArgument',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'unimplementedInterfaceAndInvalidTypeArgument' => [
                'code' => '<?php
                    interface I {}
                    final class Foo {}
                    function takesFoo(Foo $_f): void {}

                    function passes(I|int $o): void {
                        takesFoo($o);
                    }
                ',
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'possiblyInvalidEntryOfAList' => [
                'code' => '<?php
                    /** @param scalar|list<scalar> $value */
                    function takesValue(string|int|float|bool|array $value): void {}

                    /** @param list{0: list{0: string, ...<string>}|string, 1?: string, ...<string>}|string $v */
                    function passesValue(array|string $v): void {
                        takesValue($v);
                    }
                ',
                'error_message' => 'PossiblyInvalidArgument',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'extraKeyOutsideTheFallbackKeyType' => [
                'code' => '<?php
                    /** @param array{a: int, ...<non-empty-string, int>} $a */
                    function takesShape(array $a): void {}

                    takesShape(["a" => 1, "" => 2]);
                ',
                'error_message' => 'InvalidArgument',
            ],
            'extraEntryOfASealedShape' => [
                'code' => '<?php
                    /** @param array{id: int} $a */
                    function takesShape(array $a): void {}

                    takesShape(["id" => 1, "name" => "a"]);
                ',
                'error_message' => 'InvalidArgument',
            ],
            'strtolowerOfALowercaseString' => [
                'code' => '<?php
                    echo strtolower("abc");',
                'error_message' => 'The call to strtolower is unnecessary',
            ],
            'is_a_withAStringAndNoThirdArg' => [
                'code' => '<?php
                    is_a("Foo", Exception::class);
                ',
                'error_message' => 'RedundantFunctionCall',
                'error_levels' => ['DeprecatedFunction'],
            ],
            'is_a_withAStringAndFalseThirdArg' => [
                'code' => '<?php
                    is_a("Foo", Exception::class, false);
                ',
                'error_message' => 'RedundantFunctionCall',
                'error_levels' => ['DeprecatedFunction'],
            ],
            'is_a_withAUnionOfStringsAndNoThirdArg' => [
                'code' => '<?php
                    is_a(rand(0, 1) ? "Foo" : "Bar", Exception::class);
                ',
                'error_message' => 'RedundantFunctionCall',
                'error_levels' => ['DeprecatedFunction'],
            ],
            'is_a_withAUnionOfStringsAndFalseThirdArg' => [
                'code' => '<?php
                    is_a(rand(0, 1) ? "Foo" : "Bar", Exception::class, false);
                ',
                'error_message' => 'RedundantFunctionCall',
                'error_levels' => ['DeprecatedFunction'],
            ],
            'is_a_withAClassStringAndNoThirdArg' => [
                'code' => '<?php
                    is_a(InvalidArgumentException::class, Exception::class);
                ',
                'error_message' => 'RedundantFunctionCall',
                'error_levels' => ['DeprecatedFunction'],
            ],
            'is_a_withAClassStringAndFalseThirdArg' => [
                'code' => '<?php
                    is_a(InvalidArgumentException::class, Exception::class, false);
                ',
                'error_message' => 'RedundantFunctionCall',
                'error_levels' => ['DeprecatedFunction'],
            ],
            'is_a_withAUnionOfClassStringsAndNoThirdArg' => [
                'code' => '<?php
                    is_a(rand(0, 1) ? InvalidArgumentException::class : RuntimeException::class, Exception::class);
                ',
                'error_message' => 'RedundantFunctionCall',
                'error_levels' => ['DeprecatedFunction'],
            ],
            'is_a_withAUnionOfClassStringsAndFalseThirdArg' => [
                'code' => '<?php
                    is_a(rand(0, 1) ? InvalidArgumentException::class : RuntimeException::class, Exception::class, false);
                ',
                'error_message' => 'RedundantFunctionCall',
                'error_levels' => ['DeprecatedFunction'],
            ],
            'incorrectCallableParamDefault' => [
                'code' => '<?php
                    function foo(callable $_a = "strlen"): void {}
                ',
                'error_message' => 'InvalidParamDefault',
            ],
            'disallowStrposIntSecondParam' => [
                'code' => '<?php
                    function hasZeroByteOffset(string $s) : bool {
                        return strpos($s, 0) !== false;
                    }',
                'error_message' => 'InvalidScalarArgument',
            ],
            'disallowNeverTypeForParam' => [
                'code' => '<?php
                    function foo(never $_): void
                    {
                        return;
                    }
                    ',
                'error_message' => 'ParadoxicalCondition',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'functionExistsOfANumericString' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param numeric-string $function */
                    function check(string $function): bool {
                        if (function_exists($function)) {
                            return true;
                        }

                        return false;
                    }
                    PHP,
                'error_message' => 'DocblockTypeContradiction',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'extractVarCheckInvalid' => [
                'code' => '<?php
                    function takesInt(int $i): void {}

                    $foo = "123hello";
                    $a = [$foo => 15];
                    extract($a);
                    takesInt($foo);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'extractForgetsTheOffsetsOfAVariableItOverwrites' => [
                'code' => '<?php
                    /** @param array{a?: int} $arr */
                    function f(array $arr): int {
                        $x = $arr;
                        if (!isset($x["a"])) {
                            return 0;
                        }
                        extract(["x" => []]);
                        return $x["a"];
                    }',
                'error_message' => 'EmptyArrayAccess',
            ],
            'unserializeWithInvalidMaxDepthType' => [
                'code' => '<?php
                    unserialize("", ["max_depth" => "foo"]);',
                'error_message' => 'InvalidScalarArgument',
                'error_levels' => [],
                'php_version' => '7.4',
            ],
            'languageConstructUnsetCalledAsAQualifiedFunction' => [
                'code' => '<?php
                    $what = 5;
                    \unset($what);',
                'error_message' => 'UndefinedFunction',
            ],
            'languageConstructIssetCalledAsAQualifiedFunction' => [
                'code' => '<?php
                    $what = 5;
                    if (\isset($what)) {
                    }',
                'error_message' => 'UndefinedFunction',
            ],
            'languageConstructEmptyCalledAsAQualifiedFunction' => [
                'code' => '<?php
                    if (\empty("")) {
                    }',
                'error_message' => 'UndefinedFunction',
            ],
            'languageConstructPrintCalledAsAQualifiedFunction' => [
                'code' => '<?php
                    \print("word");',
                'error_message' => 'UndefinedFunction',
            ],
            'languageConstructEvalCalledAsAQualifiedFunction' => [
                'code' => '<?php
                    \eval("return 1;");',
                'error_message' => 'UndefinedFunction - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:21',
            ],
            'languageConstructsCalledWithoutALeadingBackslashAreNotUndefinedFunctions' => [
                'code' => '<?php
                    $what = 5;
                    unset($what);
                    print("word");
                    $other = [];
                    if (isset($other["a"]) || empty($other)) {
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'pregReplaceCallbackWithoutUnmatchedAsNullFlag' => [
                'code' => '<?php
                    preg_replace_callback(
                        "#^([a-z]+)$|^([0-9]+)$#",
                        fn ($m) => null === $m[1] ? "1" : "a",
                        "1234567890",
                        1,
                        $count,
                        0,
                    );',
                'error_message' => 'DocblockTypeContradiction',
                'error_levels' => ['PossiblyUndefinedIntArrayOffset'],
            ],
            'pregReplaceCallbackWithUnmatchedAsNullFlagMayGiveNull' => [
                'code' => '<?php
                    preg_replace_callback(
                        "#^([a-z]+)$|^([0-9]+)$#",
                        fn ($m) => strtoupper($m[1]),
                        "1234567890",
                        1,
                        $count,
                        PREG_UNMATCHED_AS_NULL,
                    );',
                'error_message' => 'PossiblyNullArgument',
                'error_levels' => ['PossiblyUndefinedIntArrayOffset'],
            ],
            'arrayIsListOfAShapeWithARequiredKeyAfterAGapInItsListKeys' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param array{0?: string, 2: int} $a */
                    function f(array $a): void {
                        if (array_is_list($a)) {}
                    }
                    PHP,
                'error_message' => 'TypeDoesNotContainType - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:9 - Type array{0?: string, 2: int} for $a is never list',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'arrayIsListOfAShapeWithARequiredStringKey' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param array{a: string} $a */
                    function f(array $a): void {
                        if (array_is_list($a)) {}
                    }
                    PHP,
                'error_message' => 'TypeDoesNotContainType',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'streamGetMetaDataUriIsNotAlwaysSet' => [
                'code' => <<<'PHP'
                    <?php
                    $stream = popen('echo x', 'r');
                    if ($stream === false) {
                        exit;
                    }
                    $uri = stream_get_meta_data($stream)['uri'];
                    PHP,
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'streamGetMetaDataTimedOutIsNotAlwaysSet' => [
                'code' => <<<'PHP'
                    <?php
                    $stream = fopen('data:,hi', 'r');
                    if ($stream === false) {
                        exit;
                    }
                    $timed_out = stream_get_meta_data($stream)['timed_out'];
                    PHP,
                'error_message' => 'PossiblyUndefinedArrayOffset',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'streamGetMetaDataUnknownKey' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param resource $stream */
                    function f($stream): void {
                        $uri = stream_get_meta_data($stream)['uir'];
                    }
                    PHP,
                'error_message' => 'InvalidArrayOffset',
            ],
        ];
    }

    public function testTriggerErrorDefault(): void
    {
        $config = Config::getInstance();
        $config->trigger_error_exits = 'default';

        // E_USER_ERROR is deprecated, but still exits
        $config->setCustomErrorLevel('DeprecatedFunction', Config::REPORT_SUPPRESS);

        $this->addFile(
            'somefile.php',
            '<?php
                /** @return true */
                function returnsTrue(): bool {
                    return trigger_error("", E_USER_NOTICE);
                }
                /** @return never */
                function returnsNever(): void {
                    trigger_error("", E_USER_ERROR);
                }
                /**
                 * @psalm-suppress ArgumentTypeCoercion
                 * @return mixed
                 */
                function returnsNeverOrBool(int $i) {
                    return trigger_error("", $i);
                }',
        );

        //will only pass if no exception is thrown
        $this->assertTrue(true);

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testTriggerErrorAlways(): void
    {
        $config = Config::getInstance();
        $config->trigger_error_exits = 'always';

        // E_USER_ERROR is deprecated, but still exits
        $config->setCustomErrorLevel('DeprecatedFunction', Config::REPORT_SUPPRESS);

        $this->addFile(
            'somefile.php',
            '<?php
                /** @return never */
                function returnsNever1(): void {
                    trigger_error("", E_USER_NOTICE);
                }
                /** @return never */
                function returnsNever2(): void {
                    trigger_error("", E_USER_ERROR);
                }',
        );

        //will only pass if no exception is thrown
        $this->assertTrue(true);

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testTriggerErrorNever(): void
    {
        $config = Config::getInstance();
        $config->trigger_error_exits = 'never';

        // E_USER_ERROR is deprecated, but still exits
        $config->setCustomErrorLevel('DeprecatedFunction', Config::REPORT_SUPPRESS);

        $this->addFile(
            'somefile.php',
            '<?php
                /** @return true */
                function returnsTrue1(): bool {
                    return trigger_error("", E_USER_NOTICE);
                }
                /** @return true */
                function returnsTrue2(): bool {
                    return trigger_error("", E_USER_ERROR);
                }',
        );

        //will only pass if no exception is thrown
        $this->assertTrue(true);

        $this->analyzeFile('somefile.php', new Context());
    }
}
