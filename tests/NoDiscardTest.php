<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Context;
use Psalm\IssueBuffer;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function array_filter;

final class NoDiscardTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * A function that is both pure and `#[\NoDiscard]` is matched by the pure-call check and
     * the NoDiscard check. Both emit an identical UnusedFunctionCall at the same location, so
     * IssueBuffer must deduplicate them into a single report.
     */
    public function testPureNoDiscardFunctionIsReportedOnce(): void
    {
        $this->project_analyzer->setPhpVersion('8.5', 'tests');
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->find_unused_variables = true;
        $codebase->config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @psalm-pure */
                #[\NoDiscard]
                function f(): int { return 1; }

                f();
            ',
        );

        $this->analyzeFile('somefile.php', new Context());

        $unused_calls = array_filter(
            IssueBuffer::getIssuesData()['somefile.php'] ?? [],
            static fn($issue): bool => $issue->type === 'UnusedFunctionCall',
        );

        $this->assertCount(1, $unused_calls);
    }

    /**
     * A mutation-free `#[\NoDiscard]` method is matched by the mutation-free unused-call check
     * and the NoDiscard check. Both emit an identical UnusedMethodCall, so IssueBuffer must
     * deduplicate them into a single report.
     */
    public function testMutationFreeNoDiscardMethodIsReportedOnce(): void
    {
        $this->project_analyzer->setPhpVersion('8.5', 'tests');
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->find_unused_variables = true;
        $codebase->config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class Foo {
                    /** @psalm-mutation-free */
                    #[\NoDiscard]
                    public function bar(): int { return 1; }
                }

                (new Foo())->bar();
            ',
        );

        $this->analyzeFile('somefile.php', new Context());

        $unused_calls = array_filter(
            IssueBuffer::getIssuesData()['somefile.php'] ?? [],
            static fn($issue): bool => $issue->type === 'UnusedMethodCall',
        );

        $this->assertCount(1, $unused_calls);
    }

    #[Override]
    public function providerValidCodeParse(): iterable
    {
        yield 'noDiscardFunctionReturnValueAssigned' => [
            'code' => '<?php
                #[\NoDiscard]
                function f(): int { return 1; }

                $x = f();
                echo $x;
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];

        yield 'noDiscardFunctionReturnValueReturned' => [
            'code' => '<?php
                #[\NoDiscard]
                function f(): int { return 1; }

                function g(): int { return f(); }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];

        yield 'noDiscardFunctionReturnValueUsedAsCondition' => [
            'code' => '<?php
                #[\NoDiscard]
                function f(): int { return 1; }

                if (f()) {}
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];

        yield 'noDiscardFunctionReturnValuePassedAsArgument' => [
            'code' => '<?php
                #[\NoDiscard]
                function f(): int { return 1; }

                function takesInt(int $i): void {}

                takesInt(f());
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];

        yield 'noDiscardFunctionResultDiscardedViaVoidCast' => [
            'code' => '<?php
                #[\NoDiscard]
                function f(): int { return 1; }

                (void) f();
                echo "still reachable";
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];

        yield 'noDiscardWithMessageArgumentStillResolves' => [
            'code' => '<?php
                #[\NoDiscard("use the handle")]
                function f(): int { return 1; }

                $x = f();
                echo $x;
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];

        yield 'noDiscardMethodReturnValueUsed' => [
            'code' => '<?php
                class Foo {
                    #[\NoDiscard]
                    public function bar(): int { return 1; }

                    #[\NoDiscard]
                    public static function baz(): int { return 1; }
                }

                $x = (new Foo())->bar();
                $y = Foo::baz();
                echo $x + $y;
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];

        yield 'noDiscardOnInterfaceMethodNotInherited' => [
            'code' => '<?php
                interface I {
                    #[\NoDiscard]
                    public function bar(): int;
                }

                // PHP does not inherit #[\NoDiscard] onto implementations, so a call resolved
                // to the interface declaration must not report.
                function takesI(I $i): void {
                    $i->bar();
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];

        yield 'noDiscardOnAbstractMethodNotInherited' => [
            'code' => '<?php
                abstract class A {
                    #[\NoDiscard]
                    abstract public function bar(): int;
                }

                function takesA(A $a): void {
                    $a->bar();
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];

        yield 'noDiscardResultDiscardedViaVoidCastOnMethods' => [
            'code' => '<?php
                class Foo {
                    #[\NoDiscard]
                    public function bar(): int { return 1; }

                    #[\NoDiscard]
                    public static function baz(): int { return 1; }
                }

                (void) (new Foo())->bar();
                (void) Foo::baz();
                echo "still reachable";
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];

        yield 'noDiscardSuppressedViaAnnotation' => [
            'code' => '<?php
                #[\NoDiscard]
                function f(): int { return 1; }

                /** @psalm-suppress UnusedFunctionCall */
                f();
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];

        yield 'noDiscardResultUsedInMatchAndTernaryAndBinaryOp' => [
            'code' => '<?php
                #[\NoDiscard]
                function f(): int { return 1; }

                function takesBool(bool $b): void {
                    $x = $b ? f() : 0;
                    $y = match (true) { default => f() };
                    echo $x + $y + f();
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];

        yield 'noDiscardNamedMessageArgumentUsed' => [
            'code' => '<?php
                #[\NoDiscard(message: "use the handle")]
                function f(): int { return 1; }

                $x = f();
                echo $x;
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];

        yield 'noDiscardAttributeNotEnforcedBelowPhp85' => [
            'code' => '<?php
                #[\Attribute(\Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD)]
                final class NoDiscard {
                    public function __construct(public ?string $message = null) {}
                }

                #[\NoDiscard]
                function f(): int { return 1; }

                f();
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.4',
        ];

        yield 'noDiscardOverrideThatDropsAttribute' => [
            'code' => '<?php
                class A {
                    #[\NoDiscard]
                    public function foo(): int { return 1; }
                }

                class B extends A {
                    public function foo(): int { return 2; }
                }

                (new B())->foo();
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];

        yield 'noDiscardFirstClassCallableCreation' => [
            'code' => '<?php
                #[\NoDiscard]
                function f(): int { return 1; }

                $c = f(...);
                echo $c();
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];

        yield 'noDiscardNullsafeMethodReturnValueUsed' => [
            'code' => '<?php
                class Foo {
                    #[\NoDiscard]
                    public function bar(): int { return 1; }
                }

                function takesFoo(?Foo $foo): void {
                    $x = $foo?->bar();
                    echo $x ?? 0;
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];
    }

    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        yield 'noDiscardFunctionReturnValueDiscarded' => [
            'code' => '<?php
                #[\NoDiscard]
                function f(): int { return 1; }

                f();
            ',
            'error_message' => 'UnusedFunctionCall',
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];

        yield 'noDiscardFunctionWithMessageDiscarded' => [
            'code' => '<?php
                #[\NoDiscard("use the handle")]
                function f(): int { return 1; }

                f();
            ',
            'error_message' => 'UnusedFunctionCall',
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];

        yield 'noDiscardInstanceMethodReturnValueDiscarded' => [
            'code' => '<?php
                class Foo {
                    #[\NoDiscard]
                    public function bar(): int { return 1; }
                }

                (new Foo())->bar();
            ',
            'error_message' => 'UnusedMethodCall',
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];

        yield 'noDiscardStaticMethodReturnValueDiscarded' => [
            'code' => '<?php
                class Foo {
                    #[\NoDiscard]
                    public static function baz(): int { return 1; }
                }

                Foo::baz();
            ',
            'error_message' => 'UnusedMethodCall',
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];

        yield 'noDiscardInheritedMethodReturnValueDiscarded' => [
            'code' => '<?php
                class A {
                    #[\NoDiscard]
                    public function foo(): int { return 1; }
                }

                class C extends A {}

                (new C())->foo();
            ',
            'error_message' => 'UnusedMethodCall',
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];

        yield 'noDiscardNullsafeMethodReturnValueDiscarded' => [
            'code' => '<?php
                class Foo {
                    #[\NoDiscard]
                    public function bar(): int { return 1; }
                }

                function takesFoo(?Foo $foo): void {
                    $foo?->bar();
                }
            ',
            'error_message' => 'UnusedMethodCall',
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];

        yield 'noDiscardTraitMethodReturnValueDiscarded' => [
            'code' => '<?php
                trait T {
                    #[\NoDiscard]
                    public function bar(): int { return 1; }
                }

                class Foo {
                    use T;
                }

                (new Foo())->bar();
            ',
            'error_message' => 'UnusedMethodCall',
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];

        yield 'noDiscardOnVoidReturnIsInvalidDeclaration' => [
            'code' => '<?php
                #[\NoDiscard]
                function f(): void {}
            ',
            'error_message' => 'InvalidAttribute',
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];

        yield 'noDiscardOnNeverReturnIsInvalidDeclaration' => [
            'code' => '<?php
                #[\NoDiscard]
                function f(): never { exit; }
            ',
            'error_message' => 'InvalidAttribute',
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];

        yield 'voidCastInValuePositionIsInvalid' => [
            'code' => '<?php
                #[\NoDiscard]
                function f(): int { return 1; }

                $x = (void) f();
            ',
            'error_message' => 'InvalidCast',
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];

        yield 'noDiscardNamedMessageArgumentDiscarded' => [
            'code' => '<?php
                #[\NoDiscard(message: "use the handle")]
                function f(): int { return 1; }

                f();
            ',
            'error_message' => 'UnusedFunctionCall',
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];
    }
}
