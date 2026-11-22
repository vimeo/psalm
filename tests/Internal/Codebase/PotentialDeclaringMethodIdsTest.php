<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Context;
use Psalm\Tests\TestCase;

use function array_filter;
use function array_values;

/**
 * A call of an inherited method references every method it could reach down the inheritance chain
 */
final class PotentialDeclaringMethodIdsTest extends TestCase
{
    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->project_analyzer->getCodebase()->collectLocations();
        $this->project_analyzer->getConfig()->setCustomErrorLevel('UndefinedTrait', 'suppress');
        $this->project_analyzer->getConfig()->setCustomErrorLevel('CircularReference', 'suppress');
    }

    /**
     * @param list<string> $candidates
     * @param list<string> $expected_referenced
     */
    #[DataProvider('providerCalls')]
    public function testCallReferencesEveryPotentialDeclaringMethod(
        string $code,
        array $candidates,
        array $expected_referenced,
    ): void {
        $this->addFile('somefile.php', $code);
        $this->analyzeFile('somefile.php', new Context());

        $codebase = $this->project_analyzer->getCodebase();

        self::assertSame(
            $expected_referenced,
            array_values(array_filter(
                $candidates,
                static fn(string $method_id): bool => $codebase->findReferencesToMethod($method_id) !== [],
            )),
        );
    }

    /**
     * @return iterable<string, array{string, list<string>, list<string>}>
     * @psalm-pure
     */
    public static function providerCalls(): iterable
    {
        yield 'insteadofWithTheExcludedTraitFirst' => [
            '<?php
                trait T { public function foo(): void {} }
                trait U { public function foo(): void {} }
                final class C {
                    use U, T { T::foo insteadof U; }
                }

                (new C())->foo();',
            ['C::foo', 'T::foo', 'U::foo'],
            ['C::foo', 'T::foo'],
        ];

        yield 'aliasOfTheExcludedMethod' => [
            '<?php
                trait T { public function foo(): void {} }
                trait U { public function foo(): void {} }
                final class C {
                    use T, U { T::foo insteadof U; U::foo as uFoo; }
                }

                (new C())->uFoo();',
            ['C::foo', 'C::ufoo', 'T::foo', 'U::foo'],
            ['C::foo', 'U::foo'],
        ];

        yield 'traitBeforeParent' => [
            '<?php
                trait T { public function foo(): void {} }
                class P { public function foo(): void {} }
                final class C extends P { use T; }

                (new C())->foo();',
            ['C::foo', 'T::foo', 'P::foo'],
            ['C::foo', 'T::foo'],
        ];

        yield 'interfaceChain' => [
            '<?php
                interface I { public function i(): void; }
                interface J extends I {}
                interface K extends J {}

                function f(K $k): void {
                    $k->i();
                }',
            ['K::i', 'J::i', 'I::i'],
            ['K::i', 'J::i', 'I::i'],
        ];

        yield 'missingTraitIsSkipped' => [
            '<?php
                class P { public function foo(): void {} }
                final class C extends P { use Missing; }

                (new C())->foo();',
            ['C::foo', 'P::foo'],
            ['C::foo', 'P::foo'],
        ];

        yield 'overriddenInTheMiddleOfTheChain' => [
            '<?php
                class P { public function foo(): void {} }
                class C extends P {}
                class D extends C { public function foo(): void {} }
                final class E extends D {}

                (new E())->foo();',
            ['E::foo', 'D::foo', 'C::foo', 'P::foo'],
            ['E::foo', 'D::foo', 'P::foo'],
        ];

        yield 'aliasedTrait' => [
            '<?php
                trait T { public function foo(): void {} }
                class_alias(T::class, "TAlias");
                final class C { use TAlias; }

                (new C())->foo();',
            ['C::foo', 'T::foo', 'TAlias::foo'],
            ['C::foo', 'T::foo'],
        ];

        yield 'aliasAndTheTraitMethodOfThatNameTheAliasedMethodFirst' => [
            '<?php
                trait T {
                    public function foo(): void {}
                    public function bar(): void {}
                }
                final class C { use T { foo as bar; } }

                (new C())->bar();',
            ['C::foo', 'C::bar', 'T::foo', 'T::bar'],
            ['C::foo', 'T::foo'],
        ];

        yield 'aliasAndTheTraitMethodOfThatNameTheTraitMethodFirst' => [
            '<?php
                trait T {
                    public function bar(): void {}
                    public function foo(): void {}
                }
                final class C { use T { foo as bar; } }

                (new C())->bar();',
            ['C::foo', 'C::bar', 'T::foo', 'T::bar'],
            ['C::bar', 'T::bar'],
        ];

        yield 'aliasOfAMethodOfTheSecondTrait' => [
            '<?php
                trait T {}
                trait U { public function foo(): void {} }
                final class C { use T, U { foo as bar; } }

                (new C())->bar();',
            ['C::foo', 'C::bar', 'T::foo', 'U::foo'],
            ['C::foo', 'U::foo'],
        ];

        yield 'methodOfTheSecondInterfaceParent' => [
            '<?php
                interface A {}
                interface B { public function foo(): void; }
                interface K extends A, B {}

                function f(K $k): void {
                    $k->foo();
                }',
            ['K::foo', 'A::foo', 'B::foo'],
            ['K::foo', 'B::foo'],
        ];

        yield 'circularInterfaceInheritanceWithAnOutsideInterface' => [
            '<?php
                interface K { public function k(): int; }
                interface J2 extends I2 {}
                interface I2 extends J2, K {}

                function g(I2 $i, J2 $j): int {
                    return $i->k() + $j->k();
                }',
            ['I2::k', 'J2::k', 'K::k'],
            ['I2::k', 'J2::k', 'K::k'],
        ];

        yield 'reflectedParentClassMethod' => [
            '<?php
                final class MyException extends RuntimeException {}

                (new MyException())->getMessage();',
            ['MyException::getmessage', 'RuntimeException::getmessage', 'Exception::getmessage'],
            ['MyException::getmessage', 'RuntimeException::getmessage'],
        ];
    }
}
