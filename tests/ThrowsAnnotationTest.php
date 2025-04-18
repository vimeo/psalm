<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;

class ThrowsAnnotationTest extends TestCase
{
    public function testUndefinedClassAsThrows(): void
    {
        $this->expectExceptionMessage('UndefinedDocblockClass - somefile.php:3:28');
        $this->expectException(CodeException::class);

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @throws Foo
                 */
                function bar() : void {}',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testNonThrowableClassAsThrows(): void
    {
        $this->expectExceptionMessage('InvalidThrow');
        $this->expectException(CodeException::class);

        $this->addFile(
            'somefile.php',
            '<?php
                class Foo {}

                /**
                 * @throws Foo
                 */
                function bar() : void {}',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testInheritedThrowableClassAsThrows(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
                class MyException extends Exception {}

                class Foo {
                    /**
                     * @throws MyException|Throwable
                     */
                    public function bar() : void {}
                }',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testUndocumentedThrow(): void
    {
        $this->expectExceptionMessage('MissingThrowsDocblock');
        $this->expectException(CodeException::class);
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function foo(int $x, int $y) : int {
                    if ($y === 0) {
                        throw new \RangeException("Cannot divide by zero");
                    }

                    if ($y < 0) {
                        throw new \InvalidArgumentException("This is also bad");
                    }

                    return intdiv($x, $y);
                }',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testDocumentedThrow(): void
    {
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @throws RangeException
                 * @throws InvalidArgumentException
                 */
                function foo(int $x, int $y) : int {
                    if ($y === 0) {
                        throw new \RangeException("Cannot divide by zero");
                    }

                    if ($y < 0) {
                        throw new \InvalidArgumentException("This is also bad");
                    }

                    return intdiv($x, $y);
                }',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testDocumentedParentThrow(): void
    {
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @throws Exception
                 */
                function foo(int $x, int $y) : int {
                    if ($y === 0) {
                        throw new \RangeException("Cannot divide by zero");
                    }

                    if ($y < 0) {
                        throw new \InvalidArgumentException("This is also bad");
                    }

                    return intdiv($x, $y);
                }',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testThrowableInherited(): void
    {
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @throws Throwable
                 */
                function foo(int $x, int $y) : int {
                    if ($y < 0) {
                        throw new \InvalidArgumentException("This is also bad");
                    }

                    return intdiv($x, $y);
                }',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testUndocumentedThrowInFunctionCall(): void
    {
        $this->expectExceptionMessage('MissingThrowsDocblock');
        $this->expectException(CodeException::class);
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @throws RangeException
                 * @throws InvalidArgumentException
                 */
                function foo(int $x, int $y) : int {
                    if ($y === 0) {
                        throw new \RangeException("Cannot divide by zero");
                    }

                    if ($y < 0) {
                        throw new \InvalidArgumentException("This is also bad");
                    }

                    return intdiv($x, $y);
                }

                function bar(int $x, int $y) : void {
                    foo($x, $y);
                }',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testDocumentedThrowInFunctionCallWithThrow(): void
    {
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @throws RangeException
                 * @throws InvalidArgumentException
                 */
                function foo(int $x, int $y) : int {
                    if ($y === 0) {
                        throw new \RangeException("Cannot divide by zero");
                    }

                    if ($y < 0) {
                        throw new \InvalidArgumentException("This is also bad");
                    }

                    return intdiv($x, $y);
                }

                /**
                 * @throws RangeException
                 * @throws InvalidArgumentException
                 */
                function bar(int $x, int $y) : void {
                    foo($x, $y);
                }',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testDocumentedThrowInFunctionCallWithoutThrow(): void
    {
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                class Foo
                {
                    /**
                     * @throws \TypeError
                     */
                    public static function notReallyThrowing(int $a): string
                    {
                        if ($a > 0) {
                            return "";
                        }

                        return (string) $a;
                    }

                    public function test(): string
                    {
                        try {
                            return self::notReallyThrowing(2);
                        } catch (\Throwable $E) {
                            return "";
                        }
                    }
                }',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testCaughtThrowInFunctionCall(): void
    {
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @throws RangeException
                 * @throws InvalidArgumentException
                 */
                function foo(int $x, int $y) : int {
                    if ($y === 0) {
                        throw new \RangeException("Cannot divide by zero");
                    }

                    if ($y < 0) {
                        throw new \InvalidArgumentException("This is also bad");
                    }

                    return intdiv($x, $y);
                }

                function bar(int $x, int $y) : void {
                    try {
                        foo($x, $y);
                    } catch (RangeException $e) {

                    } catch (InvalidArgumentException $e) {}
                }',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testUncaughtThrowInFunctionCall(): void
    {
        $this->expectExceptionMessage('MissingThrowsDocblock');
        $this->expectException(CodeException::class);
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @throws RangeException
                 * @throws InvalidArgumentException
                 */
                function foo(int $x, int $y) : int {
                    if ($y === 0) {
                        throw new \RangeException("Cannot divide by zero");
                    }

                    if ($y < 0) {
                        throw new \InvalidArgumentException("This is also bad");
                    }

                    return intdiv($x, $y);
                }

                function bar(int $x, int $y) : void {
                    try {
                        foo($x, $y);
                    } catch (\RangeException $e) {

                    }
                }',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testEmptyThrows(): void
    {
        $this->expectExceptionMessage('MissingDocblockType');
        $this->expectException(CodeException::class);
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @throws
                 */
                function foo(int $x, int $y) : int {}',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testCaughtAllThrowInFunctionCall(): void
    {
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @throws RangeException
                 * @throws InvalidArgumentException
                 */
                function foo(int $x, int $y) : int {
                    if ($y === 0) {
                        throw new \RangeException("Cannot divide by zero");
                    }

                    if ($y < 0) {
                        throw new \InvalidArgumentException("This is also bad");
                    }

                    return intdiv($x, $y);
                }

                function bar(int $x, int $y) : void {
                    try {
                        foo($x, $y);
                    } catch (Exception $e) {}
                }',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testDocumentedThrowInInterfaceWithInheritDocblock(): void
    {
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                interface Foo
                {
                    /**
                     * @throws \InvalidArgumentException
                     */
                    public function test(): void;
                }

                class Bar implements Foo
                {
                    /**
                     * {@inheritdoc}
                     */
                    public function test(): void
                    {
                        throw new \InvalidArgumentException();
                    }
                }
                ',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testDocumentedThrowInInterfaceWithoutInheritDocblock(): void
    {
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                interface Foo
                {
                    /**
                     * @throws \InvalidArgumentException
                     */
                    public function test(): void;
                }

                class Bar implements Foo
                {
                    public function test(): void
                    {
                        throw new \InvalidArgumentException();
                    }
                }
                ',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testDocumentedThrowInSubclassWithExtendedInheritDocblock(): void
    {
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                interface Foo
                {
                    /**
                     * @throws \InvalidArgumentException
                     */
                    public function test(): void;
                }

                class Bar implements Foo
                {
                    /**
                     * {@inheritdoc}
                     * @throws \OutOfBoundsException
                     */
                    public function test(): void
                    {
                        throw new \OutOfBoundsException();
                    }
                }
                ',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testDocumentedThrowInInterfaceWithExtendedInheritDocblock(): void
    {
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                interface Foo
                {
                    /**
                     * @throws \InvalidArgumentException
                     */
                    public function test(): void;
                }

                class Bar implements Foo
                {
                    /**
                     * {@inheritdoc}
                     * @throws \OutOfBoundsException
                     */
                    public function test(): void
                    {
                        throw new \InvalidArgumentException();
                    }
                }
                ',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testDocumentedThrowInInterfaceWithOverriddenDocblock(): void
    {
        $this->expectExceptionMessage('MissingThrowsDocblock');
        $this->expectException(CodeException::class);
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                interface Foo
                {
                    /**
                     * @throws \InvalidArgumentException
                     */
                    public function test(): void;
                }

                class Bar implements Foo
                {
                    /**
                     * @throws \OutOfBoundsException
                     */
                    public function test(): void
                    {
                        throw new \InvalidArgumentException();
                    }
                }
                ',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testDocumentedThrowInsideCatch(): void
    {
        $this->expectExceptionMessage('MissingThrowsDocblock');
        $this->expectException(CodeException::class);
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @return void
                 */
                function foo() : void {
                    try {
                        throw new Exception("foo");
                    } catch (Exception $e) {
                        throw new RuntimeException("bar");
                    }
                }',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testNextCatchShouldIgnoreExceptionsCaughtByPreviousCatch(): void
    {
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @throws \RuntimeException
                 */
                function method(): void
                {
                    try {
                        throw new \LogicException();
                    } catch (\LogicException $e) {
                        throw new \RuntimeException();
                    } catch (\Exception $e) {
                        throw new \RuntimeException();
                    }
                }',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testUnknownExceptionInThrowsOfACalledMethod(): void
    {
        $this->expectExceptionMessage('MissingThrowsDocblock');
        $this->expectException(CodeException::class);
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                final class Monkey {
                    /** @throws InvalidArgumentException */
                    public function spendsItsDay(): void {
                        $this->havingFun();
                    }
                    /** @throws \Monkey\Shit */
                    private function havingFun(): void {}
                }
            ',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testDocumentedThrowInterfaceWithFunctionCallWithImplementedExceptionThrow(): void
    {
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                interface TestExceptionInterface extends Throwable
                {
                }

                class TestException extends Exception implements TestExceptionInterface
                {
                }

                class Example
                {
                    /**
                     * @throws Throwable
                     */
                    private function methodOne(): void {
                        $this->methodTwo();
                    }

                    /**
                     * @throws TestExceptionInterface
                     */
                    private function methodTwo(): void {}
                }
            ',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }
}
