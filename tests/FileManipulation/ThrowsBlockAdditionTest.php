<?php

declare(strict_types=1);

namespace Psalm\Tests\FileManipulation;

class ThrowsBlockAdditionTest extends FileManipulationTestCase
{
    public function providerValidCodeParse(): array
    {
        return [
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
        ];
    }
}
