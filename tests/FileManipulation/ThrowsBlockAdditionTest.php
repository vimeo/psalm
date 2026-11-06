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
                        // padding 0\n                        // padding 1\n                        // padding 2\n                        // padding 3\n                        // padding 4\n                        // padding 5\n                        // padding 6\n                        // padding 7\n                        // padding 8\n                        // padding 9\n                        // padding 10\n                        // padding 11\n                        // padding 12\n                        // padding 13\n                        // padding 14\n                        // padding 15\n                        // padding 16\n                        // padding 17\n                        // padding 18\n                        // padding 19\n                        // padding 20\n                        // padding 21\n                        // padding 22\n                        // padding 23\n                        // padding 24\n                        // padding 25\n                        // padding 26\n                        // padding 27\n                        // padding 28\n                        // padding 29
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
