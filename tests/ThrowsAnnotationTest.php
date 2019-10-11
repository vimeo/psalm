<?php
namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;

class ThrowsAnnotationTest extends TestCase
{
    public function testUndefinedClassAsThrows() : void
    {
        $this->expectExceptionMessage('UndefinedDocblockClass - somefile.php:3:28');
        $this->expectException(\Psalm\Exception\CodeException::class);

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @throws Foo
                 */
                function bar() : void {}'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testNonThrowableClassAsThrows() : void
    {
        $this->expectExceptionMessage('InvalidThrow');
        $this->expectException(\Psalm\Exception\CodeException::class);

        $this->addFile(
            'somefile.php',
            '<?php
                class Foo {}

                /**
                 * @throws Foo
                 */
                function bar() : void {}'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testInheritedThrowableClassAsThrows() : void
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
                }'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    /**
     * @return void
     */
    public function testUndocumentedThrow()
    {
        $this->expectExceptionMessage('MissingThrowsDocblock');
        $this->expectException(\Psalm\Exception\CodeException::class);
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
                }'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    /**
     * @return void
     */
    public function testDocumentedThrow()
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
                }'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    /**
     * @return void
     */
    public function testDocumentedParentThrow()
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
                }'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    /**
     * @return void
     */
    public function testThrowableInherited()
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
                }'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    /**
     * @return void
     */
    public function testUndocumentedThrowInFunctionCall()
    {
        $this->expectExceptionMessage('MissingThrowsDocblock');
        $this->expectException(\Psalm\Exception\CodeException::class);
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
                }'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    /**
     * @return void
     */
    public function testDocumentedThrowInFunctionCallWithThrow()
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
                }'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    /**
     * @return void
     */
    public function testDocumentedThrowInFunctionCallWithoutThrow()
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
                }'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    /**
     * @return void
     */
    public function testCaughtThrowInFunctionCall()
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
                }'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    /**
     * @return void
     */
    public function testUncaughtThrowInFunctionCall()
    {
        $this->expectExceptionMessage('MissingThrowsDocblock');
        $this->expectException(\Psalm\Exception\CodeException::class);
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
                }'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    /**
     * @return void
     */
    public function testEmptyThrows()
    {
        $this->expectExceptionMessage('MissingDocblockType');
        $this->expectException(\Psalm\Exception\CodeException::class);
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @throws
                 */
                function foo(int $x, int $y) : int {}'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    /**
     * @return void
     */
    public function testCaughtAllThrowInFunctionCall()
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
                }'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    /**
     * @return void
     */
    public function testDocumentedThrowInInterfaceWithInheritDocblock()
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
                '
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    /**
     * @return void
     */
    public function testDocumentedThrowInInterfaceWithoutInheritDocblock()
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
                '
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    /**
     * @return void
     */
    public function testDocumentedThrowInSubclassWithExtendedInheritDocblock()
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
                '
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    /**
     * @return void
     */
    public function testDocumentedThrowInInterfaceWithExtendedInheritDocblock()
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
                '
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    /**
     * @return void
     */
    public function testDocumentedThrowInInterfaceWithOverriddenDocblock()
    {
        $this->expectExceptionMessage('MissingThrowsDocblock');
        $this->expectException(\Psalm\Exception\CodeException::class);
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
                '
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }
}
