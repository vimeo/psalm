<?php

declare(strict_types=1);

namespace Psalm\Tests\FileManipulation;

use Override;

/**
 * @psalm-immutable
 */
final class ThrowsBlockAdditionTest extends FileManipulationTestCase
{
    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): array
    {
        return [
            'inheritedConstructorOfAnotherFileDoesNotCrashTheManipulation' => [
                'input' => '<?php
                    class Child extends Base {
                        private float $start;

                        public function start(): void {
                            $this->start = microtime(true);
                        }
                    }',
                'output' => '<?php
                    class Child extends Base {
                        private float $start;

                        public function start(): void {
                            $this->start = microtime(true);
                        }
                    }',
                'php_version' => '8.1',
                'issues_to_fix' => ['MissingThrowsDocblock'],
                'safe_types' => true,
                'allow_backwards_incompatible_changes' => true,
                'other_files' => [
                    'base.php' => '<?php
                    class Base {
                        // padding 0
                        // padding 1
                        // padding 2
                        // padding 3
                        // padding 4
                        // padding 5
                        // padding 6
                        // padding 7
                        // padding 8
                        // padding 9
                        // padding 10
                        // padding 11
                        // padding 12
                        // padding 13
                        // padding 14
                        // padding 15
                        // padding 16
                        // padding 17
                        // padding 18
                        // padding 19
                        // padding 20
                        // padding 21
                        // padding 22
                        // padding 23
                        // padding 24
                        // padding 25
                        // padding 26
                        // padding 27
                        // padding 28
                        // padding 29
                        public function __construct($config = []) {
                            echo json_encode($config);
                        }
                    }',
                ],
            ],
            'addThrowsAnnotationToFunction' => [
                'input' => '<?php
                    function foo(string $s): string {
                        if("" === $s) {
                            throw new \InvalidArgumentException();
                        }
                        return $s;
                    }',
                'output' => '<?php
                    /**
                     * @throws InvalidArgumentException
                     */
                    function foo(string $s): string {
                        if("" === $s) {
                            throw new \InvalidArgumentException();
                        }
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingThrowsDocblock'],
                'safe_types' => true,
            ],
            'addMultipleThrowsAnnotationToFunction' => [
                'input' => '<?php
                    function foo(string $s): string {
                        if("" === $s) {
                            throw new \InvalidArgumentException();
                        }
                        if("" === \trim($s)) {
                            throw new \DomainException();
                        }
                        return $s;
                    }',
                'output' => '<?php
                    /**
                     * @throws InvalidArgumentException|DomainException
                     */
                    function foo(string $s): string {
                        if("" === $s) {
                            throw new \InvalidArgumentException();
                        }
                        if("" === \trim($s)) {
                            throw new \DomainException();
                        }
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingThrowsDocblock'],
                'safe_types' => true,
            ],
            'preservesExistingThrowsAnnotationToFunction' => [
                'input' => '<?php
                    /**
                     * @throws InvalidArgumentException|DomainException
                     */
                    function foo(string $s): string {
                        if("" === $s) {
                            throw new \Exception();
                        }
                        return $s;
                    }',
                'output' => '<?php
                    /**
                     * @throws InvalidArgumentException|DomainException
                     * @throws Exception
                     */
                    function foo(string $s): string {
                        if("" === $s) {
                            throw new \Exception();
                        }
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingThrowsDocblock'],
                'safe_types' => true,
            ],
            'doesNotAddDuplicateThrows' => [
                'input' => '<?php
                    /**
                     * @throws InvalidArgumentException
                     */
                    function foo(string $s): string {
                        if("" === $s) {
                            throw new \InvalidArgumentException();
                        }
                        if("" === \trim($s)) {
                            throw new \DomainException();
                        }
                        return $s;
                    }',
                'output' => '<?php
                    /**
                     * @throws InvalidArgumentException
                     * @throws DomainException
                     */
                    function foo(string $s): string {
                        if("" === $s) {
                            throw new \InvalidArgumentException();
                        }
                        if("" === \trim($s)) {
                            throw new \DomainException();
                        }
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingThrowsDocblock'],
                'safe_types' => true,
            ],
            'addThrowsAnnotationToFunctionInNamespace' => [
                'input' => '<?php
                    namespace Foo;
                    function foo(string $s): string {
                        if("" === $s) {
                            throw new \InvalidArgumentException();
                        }
                        return $s;
                    }',
                'output' => '<?php
                    namespace Foo;
                    /**
                     * @throws \InvalidArgumentException
                     */
                    function foo(string $s): string {
                        if("" === $s) {
                            throw new \InvalidArgumentException();
                        }
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingThrowsDocblock'],
                'safe_types' => true,
            ],
            'addThrowsAnnotationToFunctionFromFunctionFromOtherNamespace' => [
                'input' => '<?php
                    namespace Foo {
                        function foo(): void {
                            \Bar\bar();
                        }
                    }
                    namespace Bar {
                        class BarException extends \DomainException {}
                        /**
                         * @throws BarException
                         */
                        function bar(): void {
                            throw new BarException();
                        }
                    }',
                'output' => '<?php
                    namespace Foo {
                        /**
                         * @throws \Bar\BarException
                         */
                        function foo(): void {
                            \Bar\bar();
                        }
                    }
                    namespace Bar {
                        class BarException extends \DomainException {}
                        /**
                         * @throws BarException
                         */
                        function bar(): void {
                            throw new BarException();
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingThrowsDocblock'],
                'safe_types' => true,
            ],
            'addThrowsAnnotationAccountsForUseStatements' => [
                'input' => '<?php
                    namespace Foo {
                        use Bar\BarException;
                        function foo(): void {
                            bar();
                        }
                        /**
                         * @throws BarException
                         */
                        function bar(): void {
                            throw new BarException();
                        }
                    }
                    namespace Bar {
                        class BarException extends \DomainException {}
                    }',
                'output' => '<?php
                    namespace Foo {
                        use Bar\BarException;
                        /**
                         * @throws BarException
                         */
                        function foo(): void {
                            bar();
                        }
                        /**
                         * @throws BarException
                         */
                        function bar(): void {
                            throw new BarException();
                        }
                    }
                    namespace Bar {
                        class BarException extends \DomainException {}
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingThrowsDocblock'],
                'safe_types' => true,
            ],
            'doesNotCrashOnBodylessMethodsWithReturnType' => [
                'input' => '<?php
                    interface Contract {
                        public function name(): string;
                    }
                    abstract class Base {
                        abstract protected function id(): int;
                    }',
                'output' => '<?php
                    interface Contract {
                        public function name(): string;
                    }
                    abstract class Base {
                        abstract protected function id(): int;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingThrowsDocblock'],
                'safe_types' => true,
            ],
        ];
    }
}
