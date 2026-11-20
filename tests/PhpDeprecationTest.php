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
                'error_message' => 'A function named readonly is deprecated since PHP 8.6',
            ],
            'constantNamedUnderscore' => [
                'code' => '<?php
                    const _ = 1;',
                'error_message' => 'A constant named _ is deprecated since PHP 8.6',
            ],
            'constantDefinedWithAReservedName' => [
                'code' => '<?php
                    define("App\\\\let", 1);',
                'error_message' => 'Defining a constant named let is deprecated since PHP 8.6',
            ],
            'classAliasedToAReservedName' => [
                'code' => '<?php
                    final class A {}
                    class_alias(A::class, "is");',
                'error_message' => 'Aliasing a class to is is deprecated since PHP 8.6',
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
