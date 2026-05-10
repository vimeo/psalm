<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\IssueBuffer;
use RuntimeException;

final class ThrowsAnnotationTest extends TestCase
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

    /**
     * @see https://github.com/vimeo/psalm/issues/11842
     */
    public function testPseudoMethodThrowsPropagatedViaMagicCall(): void
    {
        $this->expectExceptionMessage('MissingThrowsDocblock');
        $this->expectException(CodeException::class);
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @method void doIt()
                 */
                class Foo {
                    public function __call(string $name, array $args): void {}
                }

                class Caller {
                    public function trigger(): void {
                        (new Foo())->doIt();
                    }
                }',
        );

        $this->analyzeWithInjectedPseudoMethodThrows('somefile.php', 'foo', 'doit', false);
    }

    /**
     * @see https://github.com/vimeo/psalm/issues/11842
     */
    public function testPseudoMethodThrowsPropagatedWithoutMagicCall(): void
    {
        $this->expectExceptionMessage('MissingThrowsDocblock');
        $this->expectException(CodeException::class);
        Config::getInstance()->check_for_throws_docblock = true;
        Config::getInstance()->use_phpdoc_method_without_magic_or_parent = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @method void doIt()
                 */
                class Foo {}

                class Caller {
                    public function trigger(): void {
                        (new Foo())->doIt();
                    }
                }',
        );

        $this->analyzeWithInjectedPseudoMethodThrows('somefile.php', 'foo', 'doit', false);
    }

    /**
     * @see https://github.com/vimeo/psalm/issues/11842
     */
    public function testPseudoStaticMethodThrowsPropagatedViaMagicCallStatic(): void
    {
        $this->expectExceptionMessage('MissingThrowsDocblock');
        $this->expectException(CodeException::class);
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @method static void doIt()
                 */
                class Foo {
                    public static function __callStatic(string $name, array $args): void {}
                }

                class Caller {
                    public function trigger(): void {
                        Foo::doIt();
                    }
                }',
        );

        $this->analyzeWithInjectedPseudoMethodThrows('somefile.php', 'foo', 'doit', true);
    }

    /**
     * @see https://github.com/vimeo/psalm/issues/11842
     */
    public function testPseudoStaticMethodThrowsPropagatedWithoutMagicCallStatic(): void
    {
        $this->expectExceptionMessage('MissingThrowsDocblock');
        $this->expectException(CodeException::class);
        Config::getInstance()->check_for_throws_docblock = true;
        Config::getInstance()->use_phpdoc_method_without_magic_or_parent = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @method static void doIt()
                 */
                class Foo {}

                class Caller {
                    public function trigger(): void {
                        Foo::doIt();
                    }
                }',
        );

        $this->analyzeWithInjectedPseudoMethodThrows('somefile.php', 'foo', 'doit', true);
    }

    /**
     * @see https://github.com/vimeo/psalm/issues/11842
     */
    public function testPseudoMethodThrowsSuppressedAtCallSite(): void
    {
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @method void doIt()
                 */
                class Foo {
                    public function __call(string $name, array $args): void {}
                }

                class Caller {
                    public function trigger(): void {
                        /** @psalm-suppress MissingThrowsDocblock */
                        (new Foo())->doIt();
                    }
                }',
        );

        $this->analyzeWithInjectedPseudoMethodThrows('somefile.php', 'foo', 'doit', false);
    }

    /**
     * @see https://github.com/vimeo/psalm/issues/11842
     */
    public function testPseudoMethodThrowsDocumentedByCaller(): void
    {
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @method void doIt()
                 */
                class Foo {
                    public function __call(string $name, array $args): void {}
                }

                class Caller {
                    /**
                     * @throws \RuntimeException
                     */
                    public function trigger(): void {
                        (new Foo())->doIt();
                    }
                }',
        );

        $this->analyzeWithInjectedPseudoMethodThrows('somefile.php', 'foo', 'doit', false);
    }

    /**
     * @see https://github.com/vimeo/psalm/issues/11842
     *
     * First-class callable creates a closure without invoking the method, so throws
     * must not propagate at the creation site. Regression guard for the early-return
     * before the merge in MissingMethodCallHandler::handleMagicMethod.
     */
    public function testPseudoMethodThrowsNotPropagatedForFirstClassCallable(): void
    {
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @method void doIt()
                 */
                class Foo {
                    public function __call(string $name, array $args): void {}
                }

                class Caller {
                    public function trigger(): Closure {
                        return (new Foo())->doIt(...);
                    }
                }',
        );

        $this->analyzeWithInjectedPseudoMethodThrows('somefile.php', 'foo', 'doit', false);
    }

    /**
     * @see https://github.com/vimeo/psalm/issues/11842
     *
     * Regression guard for the FCC early-return in
     * MissingMethodCallHandler::handleMissingOrMagicMethod (use_phpdoc_method_without_magic_or_parent path).
     */
    public function testPseudoMethodThrowsNotPropagatedForFirstClassCallableWithoutMagicCall(): void
    {
        Config::getInstance()->check_for_throws_docblock = true;
        Config::getInstance()->use_phpdoc_method_without_magic_or_parent = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @method void doIt()
                 */
                class Foo {}

                class Caller {
                    public function trigger(): Closure {
                        return (new Foo())->doIt(...);
                    }
                }',
        );

        $this->analyzeWithInjectedPseudoMethodThrows('somefile.php', 'foo', 'doit', false);
    }

    /**
     * @see https://github.com/vimeo/psalm/issues/11842
     */
    public function testPseudoMethodThrowsCaughtByTryCatch(): void
    {
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @method void doIt()
                 */
                class Foo {
                    public function __call(string $name, array $args): void {}
                }

                class Caller {
                    public function trigger(): void {
                        try {
                            (new Foo())->doIt();
                        } catch (\RuntimeException $e) {
                        }
                    }
                }',
        );

        $this->analyzeWithInjectedPseudoMethodThrows('somefile.php', 'foo', 'doit', false);
    }

    /**
     * @param lowercase-string $fq_class_name_lc
     * @param lowercase-string $method_name_lc
     */
    private function analyzeWithInjectedPseudoMethodThrows(
        string $file_path,
        string $fq_class_name_lc,
        string $method_name_lc,
        bool $is_static,
    ): void {
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->addFilesToAnalyze([$file_path => $file_path]);
        $codebase->scanFiles();

        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name_lc);
        $method_storage = $is_static
            ? $class_storage->pseudo_static_methods[$method_name_lc]
            : $class_storage->pseudo_methods[$method_name_lc];
        $method_storage->throws[RuntimeException::class] = true;

        $codebase->config->visitStubFiles($codebase);

        $file_analyzer = new FileAnalyzer(
            $this->project_analyzer,
            $file_path,
            $codebase->config->shortenFileName($file_path),
        );
        $file_analyzer->analyze(new Context());

        IssueBuffer::processUnusedSuppressions($codebase->file_provider);
    }
}
