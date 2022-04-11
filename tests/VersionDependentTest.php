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
                /** @since PHP-7.0 */
                function takesStringUntil8(string $param): void {}

                /** @since PHP-8.0 */
                function takesStringUntil8(): void {}

                /** @since PHP-8.0 */
                function existsAfter8(): void {}

                class VersionDependent
                {
                    /** @since PHP-8.0 */
                    public const EXISTS_AFTER_8 = 1;

                    /** @since PHP-7.0 */
                    public const STRING_7_INT_8 = "";

                    /** @since PHP-8.0 */
                    public const STRING_7_INT_8 = 1;

                    /**
                     * @var int
                     * @since 3.0 (library version)
                     * @since PHP-8.0 Some extra comment about something
                     */
                    public $exists_after_8 = 1;

                    /**
                     * @var string
                     * @since PHP-7.0
                     */
                    public $string_7_int_8 = "";

                    /**
                     * @var int
                     * @since PHP-8.0
                     */
                    public $string_7_int_8 = 1;

                    /**
                     * @var int
                     * @since PHP-8.0
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

                /** @since PHP-8.0 */
                class ExistsAfter8 {}

                interface Foo {}

                /** @since 3.0 (library version) */
                class ClassImplementsFooAfter8 {}

                /** @since PHP-8.0 */
                class ClassImplementsFooAfter8 implements Foo {}

                class ClassImplementsFooUntil8 implements Foo {}

                /** @since PHP-8.0 */
                class ClassImplementsFooUntil8 {}

                interface InterfaceExtendsFooAfter8 {}

                /** @since PHP-8.0 */
                interface InterfaceExtendsFooAfter8 extends Foo {}

                class ImplementsInterfaceExtendsFooAfter8 implements InterfaceExtendsFooAfter8 {}

                interface InterfaceExtendsFooUntil8 extends Foo {}

                /** @since PHP-8.0 */
                interface InterfaceExtendsFooUntil8 {}

                class ImplementsInterfaceExtendsFooUntil8 implements InterfaceExtendsFooUntil8 {}

                function takesFoo(Foo $foo): void {}
            '
        );
    }

    /**
     * @return iterable<string,array{code:string,assertions?:array<string,string>,ignored_issues?:list<string>,php_version?:string}>
     */
    public function providerValidCodeParse(): iterable
    {
        yield 'functionChangedOld' => [
            'code' => '<?php
                takesStringUntil8("");
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];
        yield 'functionChangedNew' => [
            'code' => '<?php
                takesStringUntil8();
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];
        yield 'functionAdded' => [
            'code' => '<?php
                existsAfter8();
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];
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
        yield 'classAdded' => [
            'code' => '<?php
                $foo = new ExistsAfter8();
            ',
            'assertions' => ['$foo' => 'ExistsAfter8'],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];
        yield 'classAddsInterfaceNew' => [
            'code' => '<?php
                $foo = new ClassImplementsFooAfter8();
                takesFoo($foo);
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];
        yield 'classRemovesInterfaceOld' => [
            'code' => '<?php
                $foo = new ClassImplementsFooUntil8();
                takesFoo($foo);
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];
        yield 'interfaceAddsParentNew' => [
            'code' => '<?php
                $foo = new ImplementsInterfaceExtendsFooAfter8();
                takesFoo($foo);
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];
        yield 'interfaceAddsParentOld' => [
            'code' => '<?php
                $foo = new ImplementsInterfaceExtendsFooUntil8();
                takesFoo($foo);
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];
    }

    /**
     * @return iterable<string,array{code:string,error_message:string,ignored_issues?:list<string>,php_version?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        yield 'functionChangedOld' => [
            'code' => '<?php
                takesStringUntil8();
            ',
            'error_message' => 'TooFewArguments',
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];
        yield 'functionChangedNew' => [
            'code' => '<?php
                takesStringUntil8("");
            ',
            'error_message' => 'TooManyArguments',
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];
        yield 'functionAdded' => [
            'code' => '<?php
                existsAfter8();
            ',
            'error_message' => 'UndefinedFunction',
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];
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
        yield 'classAdded' => [
            'code' => '<?php
                $foo = new ExistsAfter8();
            ',
            'error_message' => 'UndefinedClass',
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];
        yield 'classAddsInterfaceOld' => [
            'code' => '<?php
                $foo = new ClassImplementsFooAfter8();
                takesFoo($foo);
            ',
            'error_message' => 'InvalidArgument',
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];
        yield 'classRemovesInterfaceNew' => [
            'code' => '<?php
                $foo = new ClassImplementsFooUntil8();
                takesFoo($foo);
            ',
            'error_message' => 'InvalidArgument',
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];
        yield 'interfaceAddsParentOld' => [
            'code' => '<?php
                $foo = new ImplementsInterfaceExtendsFooAfter8();
                takesFoo($foo);
            ',
            'error_message' => 'InvalidArgument',
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];
        yield 'interfaceAddsParentNew' => [
            'code' => '<?php
                $foo = new ImplementsInterfaceExtendsFooUntil8();
                takesFoo($foo);
            ',
            'error_message' => 'InvalidArgument',
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];
    }
}
