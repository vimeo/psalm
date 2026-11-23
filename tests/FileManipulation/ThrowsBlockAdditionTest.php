<?php

declare(strict_types=1);

namespace Psalm\Tests\FileManipulation;

use Override;
use Psalm\Tests\TestConfig;

final class ThrowsBlockAdditionTest extends FileManipulationTestCase
{
    #[Override]
    public function providerValidCodeParse(): array
    {
        return [
            'doesNotAddThrowsWhenSuppressedForDirectory' => [
                'input' => '<?php
                    function foo(bool $b): void {
                        if ($b) {
                            throw new \LogicException();
                        }
                        throw new \RuntimeException();
                    }',
                'output' => '<?php
                    function foo(bool $b): void {
                        if ($b) {
                            throw new \LogicException();
                        }
                        throw new \RuntimeException();
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingThrowsDocblock'],
                'safe_types' => true,
                'allow_backwards_incompatible_changes' => true,
                'configure' => static function (TestConfig $config): void {
                    $config->setAdvancedErrorLevel('MissingThrowsDocblock', [
                        [
                            'type' => TestConfig::REPORT_SUPPRESS,
                            'directory' => [['name' => 'src']],
                        ],
                    ]);
                },
            ],
            'onlyAddsThrowsForUnsuppressedException' => [
                'input' => '<?php
                    function foo(bool $b): void {
                        if ($b) {
                            throw new \LogicException();
                        }
                        throw new \RuntimeException();
                    }',
                'output' => '<?php
                    /**
                     * @throws RuntimeException
                     */
                    function foo(bool $b): void {
                        if ($b) {
                            throw new \LogicException();
                        }
                        throw new \RuntimeException();
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingThrowsDocblock'],
                'safe_types' => true,
                'allow_backwards_incompatible_changes' => true,
                'configure' => static function (TestConfig $config): void {
                    $config->setAdvancedErrorLevel('MissingThrowsDocblock', [
                        [
                            'type' => TestConfig::REPORT_SUPPRESS,
                            'referencedClass' => [['name' => 'LogicException']],
                        ],
                    ]);
                },
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
