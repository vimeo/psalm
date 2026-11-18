<?php

declare(strict_types=1);

namespace Psalm\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;

/** Regression matrix for allowStringToStandInForClass, including both policy values. */
final class ClassStringLiteralConfigTest extends TestCase
{
    #[DataProvider('provideClassNameContexts')]
    public function testClassNameContexts(bool $enabled, string $code, string $expected_issue): void
    {
        Config::getInstance()->allow_string_standin_for_class = $enabled;

        if ($enabled) {
            $this->expectNotToPerformAssertions();
        } else {
            $this->expectException(CodeException::class);
            if ($expected_issue !== '') {
                $this->expectExceptionMessage($expected_issue);
            }
        }

        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    public const VALUE = 1;
                    public static int $value = 1;
                    public static function ping(): void {}
                }
                class B extends A {}
                ' . $code,
        );
        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return iterable<string, array{bool, string, string}>
     * @psalm-pure
     */
    public static function provideClassNameContexts(): iterable
    {
        $cases = [
            'new' => ['$name = "A"; new $name();', 'InvalidStringClass'],
            'staticMethod' => ['$name = "A"; $name::ping();', 'InvalidStringClass'],
            'staticProperty' => ['$name = "A"; $name::$value;', 'InvalidStringClass'],
            'classConstant' => ['$name = "A"; $name::VALUE;', 'InvalidStringClass'],
            'param' => ['/** @param class-string<A> $name */
                function useName(string $name): void {} useName("A");', 'ArgumentTypeCoercion'],
            'exactParam' => ['/** @param A::class $name */
                function useName(string $name): void {} useName("A");', 'ArgumentTypeCoercion'],
            'return' => ['/** @return class-string<A> */
                function makeName(): string { return "A"; }', ''],
            'strictComparison' => ['/** @param class-string<A> $name */
                function eq(string $name): bool { return $name === "A"; }', 'InvalidStringClass'],
            'reversedComparison' => ['/** @param class-string<A> $name */
                function eq(string $name): bool { return "A" !== $name; }', 'InvalidStringClass'],
            'looseComparison' => ['/** @param class-string<A> $name */
                function eq(string $name): bool { return $name == "A"; }', 'InvalidStringClass'],
            'getClass' => ['function eq(A $a): bool { return get_class($a) === "A"; }', 'InvalidStringClass'],
        ];

        foreach ($cases as $name => [$code, $issue]) {
            yield $name . '/false' => [false, $code, $issue];
            yield $name . '/true' => [true, $code, $issue];
        }
    }

    #[DataProvider('provideLegitimateCode')]
    public function testLegitimateCodeInEitherMode(bool $enabled, string $code): void
    {
        Config::getInstance()->allow_string_standin_for_class = $enabled;
        $this->expectNotToPerformAssertions();
        $this->addFile('somefile.php', '<?php class A {} ' . $code);
        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return iterable<string, array{bool, string}>
     * @psalm-pure
     */
    public static function provideLegitimateCode(): iterable
    {
        $cases = [
            'ordinaryText' => 'function label(): string { return "A"; }',
            'classLiteralArgument' => '/** @param class-string<A> $name */
                function consume(string $name): void {} consume(A::class);',
            'classLiteralComparison' => 'function eq(A $a): bool { return get_class($a) === A::class; }',
            'classLiteralConstant' => '$a = new A(); echo $a::class;',
            'classLiteralConstFetch' => 'class WithConstant { public const X = 1; } $name = WithConstant::class; echo $name::X;',
            'classLiteralStaticProperty' => 'class WithProperty { public static int $x = 1; } $name = WithProperty::class; echo $name::$x;',
            'classLiteralStaticCall' => 'class WithMethod { public static function foo(): void {} } $name = WithMethod::class; $name::foo();',
            'classLiteralNew' => '$name = A::class; new $name();',
            'objectClassName' => 'function getName(A $a): string { return $a::class; }',
            'ordinaryStringComparison' => 'function eq(string $value): bool { return $value === "A"; }',
            'variableContainingLiteralComparison' => '/** @param class-string $name */
                function eq(string $name, bool $flag, string $other): bool {
                    if ($flag) { $name = "A"; }
                    return $other === $name;
                }',
        ];
        foreach ($cases as $name => $code) {
            yield $name . '/false' => [false, $code];
            yield $name . '/true' => [true, $code];
        }
    }

    #[DataProvider('provideInvalidClassLiterals')]
    public function testEnabledDoesNotAcceptInvalidClassNames(string $code, string $error): void
    {
        Config::getInstance()->allow_string_standin_for_class = true;
        $this->expectException(CodeException::class);
        if ($error !== '') {
            $this->expectExceptionMessage($error);
        }
        $this->addFile('somefile.php', '<?php class A {} class B {} ' . $code);
        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return iterable<string, array{string, string}>
     * @psalm-pure
     */
    public static function provideInvalidClassLiterals(): iterable
    {
        // A literal is fully known, so a name that cannot satisfy the bound is invalid, not a coercion.
        yield 'wrongBound' => [
            '/** @param class-string<A> $name */ function f(string $name): void {} f("B");',
            'InvalidArgument',
        ];
        yield 'unrelatedExactLiteral' => [
            '/** @param A::class $name */ function f(string $name): void {} f("B");',
            '',
        ];
        yield 'unknownClass' => [
            '/** @param class-string $name */ function f(string $name): void {} f("DefinitelyMissing");',
            'InvalidArgument',
        ];
        yield 'notCallable' => [
            '/** @param class-string<callable-object> $name */
                function f(string $name): void {} f("stdClass");',
            'InvalidArgument',
        ];
        yield 'invalidComparison' => [
            '/** @param class-string<callable-object> $name */
                function f(string $name): bool { return $name === "stdClass"; }',
            'DocblockTypeContradiction',
        ];
        yield 'impossibleGetClass' => [
            'function f(Countable $obj): bool { return get_class($obj) === "stdClass"; }',
            'TypeDoesNotContainType',
        ];
    }

    public function testEnabledAcceptsCallableClassName(): void
    {
        Config::getInstance()->allow_string_standin_for_class = true;
        $this->expectNotToPerformAssertions();
        $this->addFile('somefile.php', '<?php
            /** @param class-string<callable-object> $name */
            function f(string $name): void {} f("Closure");');
        $this->analyzeFile('somefile.php', new Context());
    }
}
