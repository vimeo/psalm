<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class IntersectionTypeTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'callableObject' => [
                'code' => '<?php
                    /**
                     * @param object&callable():void $object
                     */
                    function takesCallableObject(object $object): void {
                        $object();
                    }
                ',
            ],
            'classStringOfCallableObject' => [
                'code' => '<?php
                    /**
                     * @param class-string<object&callable():void> $className
                     */
                    function takesCallableObject(string $className): void {
                        $object = new $className();
                        $object();
                    }',
                'assertions' => [],
                'ignored_issues' => ['UnsafeInstantiation', 'MixedMethodCall'],
            ],
            'callableObjectWithRequiredStringArgument' => [
                'code' => '<?php
                    /**
                     * @param object&callable(string):void $object
                     */
                    function takesCallableObject(object $object): void {
                        $object("foo");
                    }
                ',
            ],
            'classStringOfCallableObjectWithRequiredStringArgument' => [
                'code' => '<?php
                    /**
                     * @param class-string<object&callable(string):void> $className
                     */
                    function takesCallableObject(string $className): void {
                        $object = new $className();
                        $object("foo");
                    }',
                'assertions' => [],
                'ignored_issues' => ['UnsafeInstantiation', 'MixedMethodCall'],
            ],
            'callableObjectWithReturnType' => [
                'code' => '<?php
                    /**
                     * @param object&callable():int $object
                     */
                    function takesCallableObject(object $object): int {
                        return $object();
                    }
                ',
            ],
            'classStringOfCallableObjectWithReturnType' => [
                'code' => '<?php
                    /**
                     * @param class-string<object&callable():int> $className
                     */
                    function takesCallableObject(string $className): int {
                        $object = new $className();
                        return $object();
                    }

                    class Foo
                    {
                        public function __invoke(): int
                        {
                            return 0;
                        }
                    }

                    takesCallableObject(Foo::class);
                    ',
                'assertions' => [],
                'ignored_issues' => ['UnsafeInstantiation', 'MixedMethodCall'],
            ],
            'classStringOfCallableObjectEqualsObjectWithCallableIntersection' => [
                'code' => '<?php
                    /**
                     * @param class-string<callable-object> $className
                     */
                    function takesCallableObject(string $className): void {
                        $object = new $className();
                        $object();
                    }

                    class Foo
                    {
                        public function __invoke(): void
                        {
                        }
                    }

                    takesCallableObject(Foo::class);
                    ',
                'assertions' => [],
                'ignored_issues' => ['UnsafeInstantiation', 'MixedMethodCall'],
            ],
            'classStringOfImportedCallableTypeIntersection' => [
                'code' => '<?php
                    /** @psalm-type CallableType = callable */
                    class Bar
                    {

                    }

                    /** @psalm-import-type CallableType from Bar */
                    class Foo
                    {
                        /**
                         * @param class-string<object&CallableType> $className
                         */
                        function takesCallableObject(string $className): void {}
                    }
                    ',
                'assertions' => [],
                'ignored_issues' => [],
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'callableObjectWithMissingStringArgument' => [
                'code' => '<?php
                    /**
                     * @param object&callable(string):void $object
                     */
                    function takesCallableObject(object $object): void {
                        $object();
                    }
                ',
                'error_message' => 'TooFewArguments',
            ],
            'classStringOfCallableObjectWithMissingRequiredStringArgument' => [
                'code' => '<?php
                    /**
                     * @param class-string<object&callable(string):void> $className
                     */
                    function takesCallableObject(string $className): void {
                        $object = new $className();
                        $object();
                    }',
                'error_message' => 'TooFewArguments',
                'error_levels' => ['UnsafeInstantiation', 'MixedMethodCall'],
            ],
            'callableObjectWithInvalidStringArgument' => [
                'code' => '<?php
                    /**
                     * @param object&callable(string):void $object
                     */
                    function takesCallableObject(object $object): void {
                        $object(true);
                    }
                ',
                'error_message' => 'InvalidArgument',
                'error_levels' => ['UnsafeInstantiation', 'MixedMethodCall'],
            ],
            'classStringOfCallableObjectWithInvalidStringArgument' => [
                'code' => '<?php
                    /**
                     * @param class-string<object&callable(string):void> $className
                     */
                    function takesCallableObject(string $className): void {
                        $object = new $className();
                        $object(0);
                    }',
                'error_message' => 'InvalidArgument',
                'error_levels' => ['UnsafeInstantiation', 'MixedMethodCall'],
            ],
            'classStringOfCallableObjectWillTriggerMixedMethodCall' => [
                'code' => '<?php
                    /**
                     * @param class-string<object&callable> $className
                     */
                    function takesCallableObject(string $className): void {
                        new $className();
                    }

                    class Foo
                    {
                        public function __invoke(): int
                        {
                            return 0;
                        }
                    }

                    takesCallableObject(Foo::class);
                    ',
                'error_message' => 'MixedMethodCall',
            ],
            'classStringOfCallableIsNotAllowed' => [
                # Ref: https://github.com/phpstan/phpstan/issues/9148
                'code' => '<?php
                    /**
                     * @param class-string<callable():int> $className
                     */
                    function takesCallableObject(string $className): int {
                        $object = new $className();
                        return $object();
                    }

                    class Foo
                    {
                        public function __invoke(): int
                        {
                            return 0;
                        }
                    }

                    takesCallableObject(Foo::class);
                    ',
                'error_message' => 'class-string param can only target',
                'error_levels' => ['UnsafeInstantiation', 'MixedMethodCall'],
            ],
        ];
    }
}
