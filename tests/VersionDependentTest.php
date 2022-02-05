<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class VersionDependentTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->addStubFile(
            'versionDependentCode.php',
            '<?php

                class VersionDependent
                {
                    /** @since PHP-8.0 */
                    public const EXISTS_AFTER_8 = 1;

                    /** @since PHP-7.0 */
                    public const STRING_7_INT_8 = "";

                    /** @since PHP-8.0 */
                    public const STRING_7_INT_8 = 1;

                    /**
                     * @since PHP-8.0
                     * @var int
                     */
                    public $exists_after_8 = 1;

                    /**
                     * @since PHP-7.0
                     * @var string
                     */
                    public $string_7_int_8 = "";

                    /**
                     * @since PHP-8.0
                     * @var int
                     */
                    public $string_7_int_8 = 1;

                    /**
                     * @since PHP-8.0
                     * @var int
                     */
                    public static $static_exists_after_8 = 1;

                    /**
                     * @since PHP-7.0
                     */
                    public function takesStringUntil8(string $param) {}

                    /**
                     * @since PHP-8.0
                     */
                    public function takesStringUntil8() {}

                    /**
                     * @since PHP-8.0
                     */
                    public function existsAfter8() {}
                }
            '
        );
    }

    /**
     * @return iterable<string,array{code:string,assertions?:array<string,string>,ignored_issues?:list<string>,php_version?:string}>
     */
    public function providerValidCodeParse(): iterable
    {
        yield 'methodChangedOld' => [
            'code' => '<?php
                (new VersionDependent())->takesStringUntil8("");
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];
        yield 'methodChangedNew' => [
            'code' => '<?php
                (new VersionDependent())->takesStringUntil8();
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];
        yield 'methodAdded' => [
            'code' => '<?php
                (new VersionDependent())->existsAfter8();
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];
        yield 'constantChangedOld' => [
            'code' => '<?php
                $foo = VersionDependent::STRING_7_INT_8;
            ',
            'assertions' => ['$foo' => 'string'],
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];
        yield 'constantChangedNew' => [
            'code' => '<?php
                $foo = VersionDependent::STRING_7_INT_8;
            ',
            'assertions' => ['$foo' => 'int'],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];
        yield 'constantAdded' => [
            'code' => '<?php
                $foo = VersionDependent::EXISTS_AFTER_8;
            ',
            'assertions' => ['$foo' => 'int'],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];
        yield 'propertyChangedOld' => [
            'code' => '<?php
                $foo = (new VersionDependent())->string_7_int_8;
            ',
            'assertions' => ['$foo' => 'string'],
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];
        yield 'propertyChangedNew' => [
            'code' => '<?php
                $foo = (new VersionDependent())->string_7_int_8;
            ',
            'assertions' => ['$foo' => 'int'],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];
        yield 'propertyAdded' => [
            'code' => '<?php
                $foo = (new VersionDependent())->exists_after_8;
            ',
            'assertions' => ['$foo' => 'int'],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];
        yield 'staticPropertyAdded' => [
            'code' => '<?php
                $foo = VersionDependent::$static_exists_after_8;
            ',
            'assertions' => ['$foo' => 'int'],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];
    }

    /**
     * @return iterable<string,array{code:string,error_message:string,ignored_issues?:list<string>,php_version?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        yield 'methodChangedOld' => [
            'code' => '<?php
                (new VersionDependent())->takesStringUntil8();
            ',
            'error_message' => 'TooFewArguments',
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];
        yield 'methodChangedNew' => [
            'code' => '<?php
                (new VersionDependent())->takesStringUntil8("");
            ',
            'error_message' => 'TooManyArguments',
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];
        yield 'methodAdded' => [
            'code' => '<?php
                (new VersionDependent())->existsAfter8();
            ',
            'error_message' => 'UndefinedMethod',
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];
        yield 'constantAdded' => [
            'code' => '<?php
                $foo = VersionDependent::EXISTS_AFTER_8;
            ',
            'error_message' => 'UndefinedConstant',
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];
        yield 'propertyAdded' => [
            'code' => '<?php
                $foo = (new VersionDependent())->exists_after_8;
            ',
            'error_message' => 'UndefinedPropertyFetch',
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];
        yield 'staticPropertyAdded' => [
            'code' => '<?php
                $foo = VersionDependent::$static_exists_after_8;
            ',
            'error_message' => 'UndefinedPropertyFetch',
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];
    }
}
