<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

/**
 * What PHP deprecates is reported whatever PHP version is analysed
 */
final class PhpDeprecationTest extends TestCase
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
            'arrayObjectSortingIsNotDeprecated' => [
                'code' => '<?php
                    $o = new ArrayObject(["b" => 2, "a" => 1]);
                    $o->asort();
                    $o->setFlags(ArrayObject::ARRAY_AS_PROPS);',
            ],
            'overrideOfADeprecatedBuiltinMethodIsNotDeprecated' => [
                'code' => '<?php
                    /**
                     * @extends ArrayIterator<array-key, mixed>
                     * @psalm-capabilities read-props|write-this-props
                     */
                    final class SortedIterator extends ArrayIterator
                    {
                        #[\Override]
                        public function asort(int $flags = SORT_REGULAR): true
                        {
                            return true;
                        }
                    }

                    (new SortedIterator([]))->asort();',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'replacementsAreNotDeprecated' => [
                'code' => '<?php
                    function f(mixed $value, string $class): void {
                        $a = is_float($value);
                        $b = is_int($value);
                        $c = floatval($value);
                        $d = spl_object_id(new stdClass());
                        $e = is_a($class, stdClass::class, true);
                        $f = is_a(new stdClass(), stdClass::class);
                        $g = is_subclass_of($class, stdClass::class);
                        $h = http_build_query(["a" => 1]);
                        $array = ["a" => 1];
                        array_walk($array, function (int $v): void {});
                    }

                    define("SOME_CONSTANT", 1);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'sessionHandlersWithIdMethods' => [
                'code' => '<?php
                    final class Handler extends SessionHandler {}

                    session_set_save_handler(new Handler());',
            ],
            'phpFilterWithFewFilters' => [
                'code' => '<?php
                    $f = fopen("php://filter/read=string.rot13|string.toupper/resource=data.txt", "r");',
            ],
            'returnInAClosureInAFinallyBlock' => [
                'code' => '<?php
                    function f(): int {
                        try {
                            return 1;
                        } finally {
                            $g = function (): int {
                                return 2;
                            };
                        }
                    }',
            ],
            'bareReturnInAConstructor' => [
                'code' => '<?php
                    final class A
                    {
                        public function __construct()
                        {
                            return;
                        }
                    }',
            ],
            'arrayDebugInfo' => [
                'code' => '<?php
                    final class A
                    {
                        /** @return array<string, int> */
                        public function __debugInfo(): array
                        {
                            return [];
                        }
                    }',
            ],
            'namesThatAreNotReserved' => [
                'code' => '<?php
                    final class Letter {}
                    function isValid(): bool { return true; }
                    const LETTER = 1;
                    final class A
                    {
                        const NAMESPACE_NAME = 1;
                    }',
            ],
            'returnInAClosureDefinedInAFinallyBlock' => [
                'code' => '<?php
                    function f(): void {
                        try {
                            echo "a";
                        } finally {
                            $f = function (): int {
                                return 1;
                            };
                            echo $f();
                        }
                    }',
            ],
            'returnAfterAFinallyBlock' => [
                'code' => '<?php
                    function f(): int {
                        try {
                            echo "a";
                        } finally {
                            echo "b";
                        }

                        return 1;
                    }',
            ],
            'valueReturnedFromAClosureInAConstructor' => [
                'code' => '<?php
                    final class A
                    {
                        public function __construct()
                        {
                            $f = function (): int {
                                return 1;
                            };
                            echo $f();
                        }
                    }',
            ],
            'canonicalCastsAndInterpolation' => [
                'code' => '<?php
                    function f(string $var): string {
                        $a = (int) "1";
                        $b = (bool) $a;
                        return "{$var} $var {${\'var\'}}" . (string) $b . (float) $a;
                    }',
            ],
            'caseEndingWithAColon' => [
                'code' => '<?php
                    function f(string $s): int {
                        switch ($s) {
                            case "a": /* ; */
                                return 1;
                            default:
                                return 2;
                        }
                    }',
            ],
            'explicitlyNullableParametersAndOptionalOnesLast' => [
                'code' => '<?php
                    function f(int $a, ?stdClass $b = null, stdClass|null $c = null, mixed $d = null, int ...$e): void {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'csvFunctionsGivenTheEscapeArgument' => [
                'code' => '<?php
                    /** @param resource $stream */
                    function f($stream): void {
                        fputcsv($stream, ["a"], ",", "\"", "");
                        fgetcsv($stream, null, ",", "\"", escape: "");
                        str_getcsv("a", ",", "\"", "");
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'callablesNotRelativeToTheCallingClass' => [
                'code' => '<?php
                    class A {
                        /** @param list<int> $l */
                        public function sort(array $l): void {
                            usort($l, [self::class, "compare"]);
                            usort($l, self::compare(...));
                        }

                        public static function compare(int $a, int $b): int {
                            return $a <=> $b;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'functionsGivenValuesTheyStillAccept' => [
                'code' => '<?php
                    $a = chr(65) . ord("a") . pow(2, -1) . 0 ** 2;',
            ],
            'argumentCountsThatAreNotDeprecated' => [
                'code' => '<?php
                    $a = mb_check_encoding("a");
                    $b = stream_context_set_option(stream_context_create(), "http", "method", "POST");
                    $c = finfo_buffer(finfo_open(), "a", 0);',
            ],
            'staticMemberOfAClassUsingATrait' => [
                'code' => '<?php
                    trait T {
                        public static function make(): int {
                            return 1;
                        }
                    }
                    final class A {
                        use T;
                    }
                    $a = A::make();',
                'assertions' => [
                    '$a===' => 'int',
                ],
            ],
            'serializableWithTheMagicMethods' => [
                'code' => '<?php
                    final class A implements Serializable {
                        public function serialize(): string { return ""; }
                        public function unserialize(string $data): void {}
                        /** @return array<string, int> */
                        public function __serialize(): array { return []; }
                        /** @param array<string, int> $data */
                        public function __unserialize(array $data): void {}
                    }',
                'assertions' => [],
                'ignored_issues' => ['MissingOverrideAttribute'],
            ],
            'sleepAndWakeupBesideTheirReplacements' => [
                'code' => '<?php
                    final class A {
                        public function __serialize(): array { return []; }
                        public function __unserialize(array $data): void {}
                        public function __sleep(): array { return []; }
                        public function __wakeup(): void {}
                    }',
            ],
            'wakeupBesideAnInheritedUnserialize' => [
                'code' => '<?php
                    abstract class A {
                        public function __unserialize(array $data): void {}
                    }

                    final class B extends A {
                        public function __wakeup(): void {}
                    }',
            ],
            'deprecatedSyntaxSuppressedOnTheClass' => [
                'code' => '<?php
                    /** @psalm-suppress DeprecatedSyntax */
                    final class A
                    {
                        public function __destruct()
                        {
                            return 1;
                        }
                    }',
            ],
            'streamLastErrorsReturnsStreamErrors' => [
                'code' => '<?php
                    $errors = stream_last_errors();',
                'assertions' => [
                    '$errors===' => 'list<StreamError>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
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
            'deprecatedFunction' => [
                'code' => '<?php
                    $a = is_double(1.0);',
                'error_message' => 'DeprecatedFunction - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:26 - The function is_double is deprecated since PHP 8.6, use is_float() instead',
            ],
            'deprecatedFunctionOnPhp86' => [
                'code' => '<?php
                    $a = spl_object_hash(new stdClass());',
                'error_message' => 'The function spl_object_hash is deprecated since PHP 8.6, use spl_object_id() instead',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'deprecatedMbRegexFunction' => [
                'code' => '<?php
                    $parts = mb_split(",", "a,b");',
                'error_message' => 'The function mb_split is deprecated since PHP 8.6',
            ],
            'deprecatedFunctionWithoutReplacement' => [
                'code' => '<?php
                    $m = metaphone("Thompson");',
                'error_message' => 'The function metaphone is deprecated since PHP 8.6',
            ],
            'deprecatedMethod' => [
                'code' => '<?php
                    function f(ArrayIterator $i): void {
                        $i->asort();
                    }',
                'error_message' => 'DeprecatedMethod - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:29 - The method ArrayIterator::asort is deprecated since PHP 8.6',
            ],
            'deprecatedMethodOfASubclass' => [
                'code' => '<?php
                    /** @extends ArrayIterator<array-key, mixed> */
                    final class Items extends ArrayIterator {}

                    (new Items([]))->setFlags(0);',
                'error_message' => 'The method ArrayIterator::setFlags is deprecated since PHP 8.6',
            ],
            'deprecatedMethodCalledThroughParent' => [
                'code' => '<?php
                    /**
                     * @extends ArrayIterator<array-key, mixed>
                     * @psalm-capabilities read-props|write-this-props
                     */
                    final class SortedIterator extends ArrayIterator
                    {
                        #[\Override]
                        public function ksort(int $flags = SORT_REGULAR): true
                        {
                            return parent::ksort($flags);
                        }
                    }',
                'error_message' => 'The method ArrayIterator::ksort is deprecated since PHP 8.6',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'deprecatedSplFileObjectCsvMethod' => [
                'code' => '<?php
                    function f(SplFileObject $file): void {
                        $file->fgetcsv();
                    }',
                'error_message' => 'The method SplFileObject::fgetcsv is deprecated since PHP 8.6, use fgetcsv() on a stream instead',
            ],
            'deprecatedConstant' => [
                'code' => '<?php
                    $list = ["b", "a"];
                    sort($list, SORT_LOCALE_STRING);',
                'error_message' => 'DeprecatedConstant - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:33 - The constant SORT_LOCALE_STRING is deprecated since PHP 8.6',
            ],
            'deprecatedConstantInANamespace' => [
                'code' => '<?php
                    namespace App;

                    $list = ["b", "a"];
                    sort($list, SORT_LOCALE_STRING);',
                'error_message' => 'The constant SORT_LOCALE_STRING is deprecated since PHP 8.6',
            ],
            'objectPassedToArrayWalk' => [
                'code' => '<?php
                    $object = new stdClass();
                    array_walk($object, function (mixed $v): void {});',
                'error_message' => 'Passing an object as the array argument of array_walk() is deprecated since PHP 8.6, use get_object_vars() instead',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'objectPassedToHttpBuildQuery' => [
                'code' => '<?php
                    $q = http_build_query(new stdClass());',
                'error_message' => 'Passing an object as the data argument of http_build_query() is deprecated since PHP 8.6',
            ],
            'objectPassedToDeflateInit' => [
                'code' => '<?php
                    $c = deflate_init(ZLIB_ENCODING_DEFLATE, new stdClass());',
                'error_message' => 'Passing an object as the options argument of deflate_init() is deprecated',
                'error_levels' => ['InvalidArgument'],
            ],
            'caseInsensitiveArgumentOfDefine' => [
                'code' => '<?php
                    define("SOME_CONSTANT", 1, false);',
                'error_message' => 'The case_insensitive argument of define() is deprecated since PHP 8.6, leave it out',
            ],
            'mbCheckEncodingWithoutArguments' => [
                'code' => '<?php
                    $a = mb_check_encoding();',
                'error_message' => 'Calling mb_check_encoding() without arguments is deprecated since PHP 8.1',
            ],
            'streamContextSetOptionWithAnArrayOfOptions' => [
                'code' => '<?php
                    $a = stream_context_set_option(stream_context_create(), ["http" => ["method" => "POST"]]);',
                'error_message' => 'Calling stream_context_set_option() with an array of options is deprecated since PHP 8.4, '
                    . 'use stream_context_set_options() instead',
            ],
            'contextArgumentOfFinfoBuffer' => [
                'code' => '<?php
                    $a = finfo_buffer(finfo_open(), "a", 0, stream_context_create());',
                'error_message' => 'The context argument of finfo_buffer() is deprecated since PHP 8.5, leave it out',
            ],
            'isAWithAClassNameAndNoAllowString' => [
                'code' => '<?php
                    $a = is_a("stdClass", stdClass::class);',
                'error_message' => 'Calling is_a() with a class name when allow_string is false is deprecated since PHP 8.6',
                'error_levels' => ['RedundantFunctionCall'],
            ],
            'isSubclassOfWithAClassNameAndAllowStringFalse' => [
                'code' => '<?php
                    $a = is_subclass_of("stdClass", stdClass::class, false);',
                'error_message' => 'Calling is_subclass_of() with a class name when allow_string is false',
            ],
            'sessionHandlerWithoutIdMethods' => [
                'code' => '<?php
                    final class Handler implements SessionHandlerInterface
                    {
                        public function close(): bool { return true; }
                        public function destroy(string $id): bool { return true; }
                        public function gc(int $max_lifetime): int|false { return 0; }
                        public function open(string $path, string $name): bool { return true; }
                        public function read(string $id): string|false { return ""; }
                        public function write(string $id, string $data): bool { return true; }
                    }

                    session_set_save_handler(new Handler());',
                'error_message' => 'A session handler without the create_sid() and validateId() methods is deprecated since PHP 8.6',
                'error_levels' => ['MissingOverrideAttribute'],
                'php_version' => '8.0',
            ],
            'phpFilterWithTooManyFilters' => [
                'code' => '<?php
                    $f = fopen("php://filter/read=a|a|a|a|a|a|a|a|a|a|a|a|a|a|a|a|a/resource=data.txt", "r");',
                'error_message' => 'A php://filter URL chaining more than 16 filters is deprecated since PHP 8.6',
            ],
            'mysqliStmtWithoutAQuery' => [
                'code' => '<?php
                    function f(mysqli $mysqli): mysqli_stmt {
                        return new mysqli_stmt($mysqli);
                    }',
                'error_message' => 'Calling mysqli_stmt::__construct() without the query argument is deprecated since PHP 8.6, use mysqli::prepare() instead',
            ],
            'returnInAFinallyBlock' => [
                'code' => '<?php
                    function f(): int {
                        try {
                            return 1;
                        } finally {
                            return 2;
                        }
                    }',
                'error_message' => 'DeprecatedSyntax - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:29 - A return in a finally block is deprecated since PHP 8.6',
            ],
            'returnInAFinallyBlockOutsideOfAFunction' => [
                'code' => '<?php
                    try {
                        echo "a";
                    } finally {
                        return;
                    }',
                'error_message' => 'A return in a finally block is deprecated since PHP 8.6',
            ],
            'valueReturnedFromADestructor' => [
                'code' => '<?php
                    final class A
                    {
                        public function __destruct()
                        {
                            return 1;
                        }
                    }',
                'error_message' => 'Returning a value from __destruct() is deprecated since PHP 8.6',
            ],
            'generatorConstructor' => [
                'code' => '<?php
                    final class A
                    {
                        public function __construct()
                        {
                            yield 1;
                        }
                    }',
                'error_message' => 'Making __construct() a generator is deprecated since PHP 8.6',
            ],
            'nullableDebugInfo' => [
                'code' => '<?php
                    final class A
                    {
                        /** @return array<string, int>|null */
                        public function __debugInfo(): ?array
                        {
                            return null;
                        }
                    }',
                'error_message' => 'A nullable return type of __debugInfo() is deprecated since PHP 8.6, declare array',
            ],
            'classConstantNamedNamespace' => [
                'code' => '<?php
                    final class A
                    {
                        const namespace = 1;
                    }',
                'error_message' => 'A class constant named namespace is deprecated since PHP 8.6',
            ],
            'classNamedLet' => [
                'code' => '<?php
                    final class let {}',
                'error_message' => 'ReservedWord - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:33 - Naming a class let is deprecated since PHP 8.6, as let becomes a keyword',
            ],
            'interfaceNamedIs' => [
                'code' => '<?php
                    interface is {}',
                'error_message' => 'Naming an interface is is deprecated since PHP 8.6',
            ],
            'functionNamedIs' => [
                'code' => '<?php
                    function is(): void {}',
                'error_message' => 'Naming a function is is deprecated since PHP 8.6',
            ],
            'functionNamedReadonly' => [
                'code' => '<?php
                    function readonly(): void {}',
                'error_message' => 'Naming a function readonly is deprecated since PHP 8.6',
            ],
            'constantNamedUnderscore' => [
                'code' => '<?php
                    const _ = 1;',
                'error_message' => 'Naming a constant _ is deprecated since PHP 8.6',
            ],
            'constantDefinedWithAReservedName' => [
                'code' => '<?php
                    define("App\\\\let", 1);',
                'error_message' => 'Naming a constant let is deprecated since PHP 8.6',
            ],
            'classAliasedToAReservedName' => [
                'code' => '<?php
                    final class A {}
                    class_alias(A::class, "is");',
                'error_message' => 'Naming an alias is is deprecated since PHP 8.6',
            ],
            'returnInATryNestedInAFinallyBlock' => [
                'code' => '<?php
                    function f(): int {
                        try {
                            echo "a";
                        } finally {
                            try {
                                return 2;
                            } catch (Exception $e) {
                            }
                        }
                    }',
                'error_message' => 'A return in a finally block is deprecated since PHP 8.6',
            ],
            'constructorDelegatingToAGenerator' => [
                'code' => '<?php
                    final class A
                    {
                        public function __construct()
                        {
                            yield from [1];
                        }
                    }',
                'error_message' => 'Making __construct() a generator is deprecated since PHP 8.6',
            ],
            'traitNamedLet' => [
                'code' => '<?php
                    trait let {}',
                'error_message' => 'Naming a trait let is deprecated since PHP 8.6',
            ],
            'enumNamedIs' => [
                'code' => '<?php
                    enum is {}',
                'error_message' => 'Naming an enum is is deprecated since PHP 8.6',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'interfaceConstantNamedNamespace' => [
                'code' => '<?php
                    interface A
                    {
                        const namespace = 1;
                    }',
                'error_message' => 'A class constant named namespace is deprecated since PHP 8.6',
            ],
            'fullyQualifiedDeprecatedConstant' => [
                'code' => '<?php
                    namespace App;

                    $list = ["b", "a"];
                    sort($list, \\SORT_LOCALE_STRING);',
                'error_message' => 'DeprecatedConstant - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:33 - The constant SORT_LOCALE_STRING is deprecated since PHP 8.6',
            ],
            'importedFunctionAliasedToAReservedName' => [
                'code' => '<?php
                    namespace App;

                    use function strlen as is;',
                'error_message' => 'Naming an alias is is deprecated since PHP 8.6',
            ],
            'functionDeprecatedInPhp81' => [
                'code' => '<?php
                    $a = date_sunrise(1);',
                'error_message' => 'The function date_sunrise is deprecated since PHP 8.1, use date_sun_info',
            ],
            'functionDeprecatedInPhp82' => [
                'code' => '<?php
                    $a = utf8_encode("a");',
                'error_message' => 'The function utf8_encode is deprecated since PHP 8.2, use mb_convert_encoding',
            ],
            'functionDeprecatedInPhp84' => [
                'code' => '<?php
                    $a = lcg_value();',
                'error_message' => 'The function lcg_value is deprecated since PHP 8.4',
            ],
            'methodDeprecatedInPhp85' => [
                'code' => '<?php
                    function f(SplObjectStorage $s): void {
                        $s->attach(new stdClass());
                    }',
                'error_message' => 'The method SplObjectStorage::attach is deprecated since PHP 8.5, use SplObjectStorage::offsetSet',
            ],
            'constantDeprecatedInPhp81' => [
                'code' => '<?php
                    $a = filter_var("a", FILTER_SANITIZE_STRING);',
                'error_message' => 'The constant FILTER_SANITIZE_STRING is deprecated since PHP 8.1',
            ],
            'constantDeprecatedInPhp84' => [
                'code' => '<?php
                    error_reporting(E_ALL & ~E_STRICT);',
                'error_message' => 'The constant E_STRICT is deprecated since PHP 8.4',
            ],
            'classConstantDeprecatedInPhp85' => [
                'code' => '<?php
                    $a = DateTime::RFC7231;',
                'error_message' => 'The constant DateTimeInterface::RFC7231 is deprecated since PHP 8.5',
            ],
            'sessionIdConstant' => [
                'code' => '<?php
                    $a = SID;',
                'error_message' => 'The constant SID is deprecated since PHP 8.4',
            ],
            'getClassWithoutArguments' => [
                'code' => '<?php
                    final class A {
                        public function name(): string {
                            return get_class();
                        }
                    }',
                'error_message' => 'Calling get_class() without arguments is deprecated since PHP 8.3',
            ],
            'triggerErrorWithUserError' => [
                'code' => '<?php
                    trigger_error("failed", E_USER_ERROR);',
                'error_message' => 'Calling trigger_error() with E_USER_ERROR is deprecated since PHP 8.4',
            ],
            'csvFunctionRelyingOnTheDefaultEscape' => [
                'code' => '<?php
                    $a = str_getcsv("a,b");',
                'error_message' => 'Relying on the default escape argument of str_getcsv() is deprecated since PHP 8.4',
            ],
            'chrOutsideOfAByte' => [
                'code' => '<?php
                    $a = chr(300);',
                'error_message' => 'Passing an integer outside of 0..255 to chr() is deprecated since PHP 8.5',
            ],
            'ordOfSeveralBytes' => [
                'code' => '<?php
                    $a = ord("ab");',
                'error_message' => 'Passing a string that is not one byte to ord() is deprecated since PHP 8.5',
            ],
            'changingADeprecatedIniSetting' => [
                'code' => '<?php
                    ini_set("assert.active", "1");',
                'error_message' => 'Changing the ini setting assert.active is deprecated since PHP 8.3, use zend.assertions',
            ],
            'reflectionMethodWithOneArgument' => [
                'code' => '<?php
                    $a = new ReflectionMethod("ArrayObject::count");',
                'error_message' => 'Constructing ReflectionMethod with one argument is deprecated since PHP 8.4, use ReflectionMethod::createFromMethodName',
            ],
            'arrayObjectWrappingAnObject' => [
                'code' => '<?php
                    $a = new ArrayObject(new stdClass());',
                'error_message' => 'Wrapping an object in ArrayObject or ArrayIterator is deprecated since PHP 8.5, use get_object_vars',
            ],
            'nonCanonicalCast' => [
                'code' => '<?php
                    $a = (integer) "1";',
                'error_message' => 'DeprecatedSyntax - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:26 - The (integer) cast is deprecated since PHP 8.5, use (int) instead',
            ],
            'backtickOperator' => [
                'code' => '<?php
                    $a = `ls`;',
                'error_message' => 'The backtick operator is deprecated since PHP 8.5, use shell_exec',
            ],
            'dollarBraceInterpolation' => [
                'code' => '<?php
                    function f(string $name): string {
                        return "Hello ${name}";
                    }',
                'error_message' => 'Interpolating "${var}" in a string is deprecated since PHP 8.2',
            ],
            'caseEndingWithASemicolon' => [
                'code' => '<?php
                    function f(string $s): int {
                        switch ($s) {
                            case "a";
                                return 1;
                        }

                        return 2;
                    }',
                'error_message' => 'Ending a case with a semicolon is deprecated since PHP 8.5, use a colon instead',
            ],
            'httpResponseHeaderVariable' => [
                'code' => '<?php
                    function f(): void {
                        file_get_contents("https://example.com");
                        var_dump($http_response_header);
                    }',
                'error_message' => 'The $http_response_header variable is deprecated since PHP 8.5, use http_get_last_response_headers',
            ],
            'nullReturnedFromDebugInfo' => [
                'code' => '<?php
                    final class A {
                        /** @return array<string, int>|null */
                        public function __debugInfo() {
                            return null;
                        }
                    }',
                'error_message' => 'Returning null from __debugInfo() is deprecated since PHP 8.5',
            ],
            'referenceReturnedFromAVoidFunction' => [
                'code' => '<?php
                    function &f(): void {}',
                'error_message' => 'Returning by reference from a void function is deprecated since PHP 8.1',
            ],
            'optionalParameterBeforeARequiredOne' => [
                'code' => '<?php
                    function f(int $a = 1, int $b): void {}',
                'error_message' => 'Declaring the optional parameter $a before the required parameter $b is deprecated since PHP 8.0',
            ],
            'implicitlyNullableParameter' => [
                'code' => '<?php
                    function f(stdClass $a = null): void {}',
                'error_message' => 'Making the type of $a nullable with its null default is deprecated since PHP 8.4, declare the type nullable',
            ],
            'declaringSleep' => [
                'code' => '<?php
                    final class A {
                        public function __sleep(): array { return []; }
                    }',
                'error_message' => 'DeprecatedSyntax - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:25 - Declaring __sleep()'
                    . ' is deprecated since PHP 8.5, use __serialize() instead',
            ],
            'declaringWakeup' => [
                'code' => '<?php
                    final class A {
                        public function __wakeup(): void {}
                    }',
                'error_message' => 'Declaring __wakeup() is deprecated since PHP 8.5, use __unserialize() instead',
            ],
            'declaringWakeupBesideOnlySerialize' => [
                'code' => '<?php
                    final class A {
                        public function __serialize(): array { return []; }
                        public function __wakeup(): void {}
                    }',
                'error_message' => 'Declaring __wakeup() is deprecated since PHP 8.5, use __unserialize() instead',
            ],
            'sleepNotReturningAnArray' => [
                'code' => '<?php
                    final class A {
                        public function __sleep(): string {
                            return "";
                        }
                    }',
                'error_message' => 'A __sleep() not returning an array is deprecated since PHP 8.1',
            ],
            'zeroRaisedToANegativePower' => [
                'code' => '<?php
                    $a = 0 ** -1;',
                'error_message' => 'Raising 0 to a negative power is deprecated since PHP 8.4, use fpow',
            ],
            'zeroRaisedToANegativePowerWithPow' => [
                'code' => '<?php
                    $a = pow(0, -1);',
                'error_message' => 'Raising 0 to a negative power is deprecated since PHP 8.4, use fpow',
            ],
            'classNamedUnderscore' => [
                'code' => '<?php
                    final class _ {}',
                'error_message' => 'Naming a class _ is deprecated since PHP 8.4',
            ],
            'serializableWithoutTheMagicMethods' => [
                'code' => '<?php
                    final class A implements Serializable {
                        public function serialize(): string { return ""; }
                        public function unserialize(string $data): void {}
                    }',
                'error_message' => 'Implementing Serializable without __serialize() and __unserialize() is deprecated since PHP 8.1',
                'error_levels' => ['MissingOverrideAttribute'],
            ],
            'staticMethodCalledOnATrait' => [
                'code' => '<?php
                    trait T {
                        public static function make(): int {
                            return 1;
                        }
                    }
                    $a = T::make();',
                'error_message' => 'Accessing a static member directly on the trait T is deprecated since PHP 8.1',
            ],
            'callableRelativeToTheCallingClass' => [
                'code' => '<?php
                    class A {
                        /** @param list<int> $l */
                        public function sort(array $l): void {
                            usort($l, "self::compare");
                        }

                        public static function compare(int $a, int $b): int {
                            return $a <=> $b;
                        }
                    }',
                'error_message' => 'A callable relative to the calling class is deprecated since PHP 8.2',
            ],
            'deprecatedMbstringEncoding' => [
                'code' => '<?php
                    function f(string $s): string {
                        return mb_convert_encoding($s, "HTML-ENTITIES", "UTF-8");
                    }',
                'error_message' => 'The mbstring encoding HTML-ENTITIES is deprecated since PHP 8.2',
            ],
            'useAliasedToAReservedName' => [
                'code' => '<?php
                    namespace App;

                    use ArrayObject as let;',
                'error_message' => 'Naming an alias let is deprecated since PHP 8.6',
            ],
        ];
    }
}
