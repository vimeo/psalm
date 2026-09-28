<?php

declare(strict_types=1);

namespace Psalm\Tests\Template;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class TemplateDefaultTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'classTemplateDefaultBasic' => [
                'code' => '<?php
                    /**
                     * @template T = string
                     */
                    class Foo {
                        /** @return T */
                        public function get() {
                            throw new \RuntimeException();
                        }
                    }

                    /** @param Foo $foo */
                    function test(Foo $foo): string {
                        return $foo->get();
                    }',
            ],
            'classTemplateDefaultWithBound' => [
                'code' => '<?php
                    /**
                     * @template T of string = "hello"
                     */
                    class Foo {
                        /** @return T */
                        public function get(): string {
                            throw new \RuntimeException();
                        }
                    }

                    /** @param Foo $foo */
                    function test(Foo $foo): string {
                        return $foo->get();
                    }',
            ],
            'classTemplateDefaultExplicitOverride' => [
                'code' => '<?php
                    /**
                     * @template T = string
                     */
                    class Foo {
                        /** @return T */
                        public function get() {
                            throw new \RuntimeException();
                        }
                    }

                    /** @param Foo<int> $foo */
                    function test(Foo $foo): int {
                        return $foo->get();
                    }',
            ],
            'methodTemplateDefaultReferencingClassTemplate' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    interface I {
                        /**
                         * @template TResult = T
                         * @param (callable(T): TResult)|null $a
                         * @return I<TResult>
                         */
                        public function work(?callable $a = null): self;
                    }

                    /**
                     * @param I<string> $i
                     * @return I<string>
                     */
                    function test(I $i): I {
                        return $i->work(null);
                    }',
            ],
            'methodTemplateDefaultWithCallable' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    interface I {
                        /**
                         * @template TResult = T
                         * @param (callable(T): TResult)|null $a
                         * @return I<TResult>
                         */
                        public function work(?callable $a = null): self;
                    }

                    /**
                     * @param I<string> $i
                     */
                    function test(I $i): void {
                        /** @var I<int> */
                        $result = $i->work(
                            /** @param string $s @return int */
                            function (string $s): int { return 1; },
                        );
                    }',
            ],
            'multipleTemplateDefaults' => [
                'code' => '<?php
                    /**
                     * @template T = string
                     * @template U = int
                     */
                    class Pair {
                        /** @return T */
                        public function first() {
                            throw new \RuntimeException();
                        }
                        /** @return U */
                        public function second() {
                            throw new \RuntimeException();
                        }
                    }

                    /** @param Pair $p */
                    function testFirst(Pair $p): string {
                        return $p->first();
                    }

                    /** @param Pair $p */
                    function testSecond(Pair $p): int {
                        return $p->second();
                    }',
            ],
            'templateDefaultNever' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    interface Promise {
                        /**
                         * @template TResult1 = T
                         * @template TResult2 = never
                         * @param (callable(T): TResult1)|null $onFulfilled
                         * @param (callable(mixed): TResult2)|null $onRejected
                         * @return Promise<TResult1|TResult2>
                         */
                        public function then(
                            ?callable $onFulfilled = null,
                            ?callable $onRejected = null
                        ): self;
                    }

                    /**
                     * @param Promise<int> $promise
                     * @return Promise<int>
                     */
                    function testNulls(Promise $promise): Promise {
                        return $promise->then(null, null);
                    }',
            ],
            'classTemplateDefaultCovariant' => [
                'code' => '<?php
                    /**
                     * @template-covariant T = string
                     */
                    class Box {
                        /** @return T */
                        public function get() {
                            throw new \RuntimeException();
                        }
                    }

                    /** @param Box $b */
                    function test(Box $b): string {
                        return $b->get();
                    }',
            ],
            'classTemplateDefaultOnNew' => [
                'code' => '<?php
                    /**
                     * @template T = int
                     */
                    class Container {
                        /** @var T */
                        public $value;

                        /** @param T $value */
                        public function __construct($value) {
                            $this->value = $value;
                        }
                    }

                    $c = new Container(42);',
                'assertions' => [
                    '$c===' => 'Container<42>',
                ],
            ],
            'phpstanTemplateSyntax' => [
                'code' => '<?php
                    /**
                     * @phpstan-template T = string
                     */
                    class Foo {
                        /** @return T */
                        public function get() {
                            throw new \RuntimeException();
                        }
                    }

                    /** @param Foo $foo */
                    function test(Foo $foo): string {
                        return $foo->get();
                    }',
            ],
            'functionTemplateDefault' => [
                'code' => '<?php
                    /**
                     * @template TResult = string
                     * @param (callable(): TResult)|null $callback
                     * @return TResult
                     */
                    function resolve(?callable $callback = null) {
                        throw new \RuntimeException();
                    }

                    function test(): string {
                        return resolve(null);
                    }',
            ],
            'templateDefaultWithAsKeyword' => [
                'code' => '<?php
                    /**
                     * @template T as object = stdClass
                     */
                    class Foo {
                        /** @return T */
                        public function get(): object {
                            throw new \RuntimeException();
                        }
                    }

                    /** @param Foo $foo */
                    function test(Foo $foo): object {
                        return $foo->get();
                    }',
            ],
            'inferredNeverPreservedOverDefault' => [
                'code' => '<?php
                    /**
                     * @template T = string
                     * @param list<T> $items
                     * @return list<T>
                     */
                    function passThrough(array $items): array {
                        return $items;
                    }

                    /** @var list<never> $empty */
                    $empty = [];
                    $result = passThrough($empty);',
                'assertions' => [
                    '$result===' => 'list<never>',
                ],
            ],
            'classTemplateDefaultEqualsBound' => [
                'code' => '<?php
                    /**
                     * @template T of stdClass = stdClass
                     */
                    class Foo {
                        /** @return T */
                        public function get(): stdClass {
                            throw new \RuntimeException();
                        }
                    }',
            ],
            'classTemplateDefaultReferencingClassNotYetLoaded' => [
                'code' => '<?php
                    /**
                     * @template T of \DateTimeInterface = \DateTimeImmutable
                     */
                    class Foo {
                        /** @return T */
                        public function get(): \DateTimeInterface {
                            throw new \RuntimeException();
                        }
                    }

                    /** @param Foo $foo */
                    function test(Foo $foo): \DateTimeInterface {
                        return $foo->get();
                    }',
            ],
            'classTemplateDefaultWithTemplateBound' => [
                'code' => '<?php
                    /**
                     * @template TKey of array-key
                     * @template T of TKey = string
                     */
                    class Foo {}',
            ],
            'classTemplateDefaultWithNestedTemplateInBound' => [
                'code' => '<?php
                    /**
                     * @template TKey of string
                     * @template T of array<TKey, mixed> = array<string, mixed>
                     */
                    class Foo {}',
            ],
            'classTemplateDefaultTransitiveInheritance' => [
                'code' => '<?php
                    class Z {}
                    class A extends Z {}
                    class Child extends A {}

                    /**
                     * @template T of Z = Child
                     */
                    class Foo {
                        /** @return T */
                        public function get(): Z {
                            throw new \RuntimeException();
                        }
                    }

                    /** @param Foo $foo */
                    function test(Foo $foo): Z {
                        return $foo->get();
                    }',
            ],
            'classTemplateDefaultArrayWithTransitiveInheritance' => [
                'code' => '<?php
                    class Z {}
                    class A extends Z {}
                    class Child extends A {}

                    /**
                     * @template T of array<int, Z> = array<int, Child>
                     */
                    class Foo {}',
            ],
            'classTemplateDefaultClassStringWithTransitiveInheritance' => [
                'code' => '<?php
                    class Z {}
                    class A extends Z {}
                    class Child extends A {}

                    /**
                     * @template T of class-string<Z> = class-string<Child>
                     */
                    class Foo {}',
            ],
            'classTemplateDefaultIterableWithTransitiveInheritance' => [
                'code' => '<?php
                    class Z {}
                    class A extends Z {}
                    class Child extends A {}

                    /**
                     * @template T of iterable<Z> = array<Child>
                     */
                    class Foo {}',
            ],
            'classTemplateDefaultAppliedThroughFunctionReturn' => [
                'code' => '<?php
                    /**
                     * @template T of string = "hello"
                     */
                    class Foo {
                        /** @return T */
                        public function get(): string { throw new \RuntimeException(); }
                    }

                    /** @return Foo */
                    function makeFoo(): Foo { throw new \RuntimeException(); }

                    $r = makeFoo()->get();',
                'assertions' => [
                    "\$r===" => "'hello'",
                ],
            ],
            'classDefaultReferencingAnotherDefaultResolves' => [
                'code' => '<?php
                    /**
                     * @template T = string
                     * @template U = T
                     */
                    class Pair {}

                    $p = new Pair();',
                'assertions' => [
                    '$p===' => 'Pair<string, string>',
                ],
            ],
            'classChainedDefaultsAppliedThroughFunctionReturn' => [
                'code' => '<?php
                    /**
                     * @template T = string
                     * @template U = T
                     */
                    class Pair {}

                    /** @return Pair */
                    function makePair(): Pair { throw new \RuntimeException(); }

                    $p = makePair();',
                'assertions' => [
                    '$p===' => 'Pair<string, string>',
                ],
            ],
            // @template tags are resolved in declaration order at scan time, so when T's
            // default (`= U`) is parsed, U isn't a known template yet: it's parsed as an
            // ordinary (here: undefined) class-name reference, not a template reference.
            // By the time U's default (`= T`) is parsed, T is already registered, so U
            // correctly resolves to "whatever T resolved to" - which is that same phantom
            // class reference. Neither side ever re-enters getTemplateDefault() for the
            // same template, so this never was a cycle that reaches the infinite-loop
            // guard; see classSelfReferentialTemplateDefaultDoesNotInfiniteLoop below for
            // a default that actually does.
            'classForwardReferencedTemplateDefaultIsTreatedAsClassName' => [
                'code' => '<?php
                    /**
                     * @template T = U
                     * @template U = T
                     */
                    class Cycle {
                        /** @return T */
                        public function getT() {
                            throw new RuntimeException("empty");
                        }

                        /** @return U */
                        public function getU() {
                            throw new RuntimeException("empty");
                        }
                    }

                    $c = new Cycle();
                    $t = $c->getT();
                    $u = $c->getU();',
                'assertions' => [
                    '$t===' => 'U',
                    '$u===' => 'U',
                ],
            ],
            // unlike the forward-reference case above, T here is already registered as a
            // template by the time its own default is parsed, so `= T` is a genuine
            // self-reference. getTemplateDefault() pre-resolves a self-reference to the
            // template's own bound (here: mixed, since T is unbounded) before expanding
            // the rest of the default, so this bottoms out at mixed instead of recursing;
            // the visiting_defaults cycle guard remains in place as a backstop for longer
            // indirect cycles (T = U, U = V, V = T) that this pre-substitution doesn't
            // catch, since it only rewrites T referring to itself, not a chain.
            'classSelfReferentialTemplateDefaultDoesNotInfiniteLoop' => [
                'code' => '<?php
                    /**
                     * @template T = T
                     */
                    class SelfCycle {
                        /** @return T */
                        public function get() {
                            throw new RuntimeException("empty");
                        }
                    }

                    /** @psalm-suppress MixedAssignment */
                    $r = (new SelfCycle())->get();',
                'assertions' => [
                    '$r===' => 'mixed',
                ],
            ],
            // the self-reference here is nested inside a generic type param rather than
            // being the whole default, so resolving it recurses into replace() through
            // GenericTrait::replaceTypeParamsTemplateTypesWithArgTypes(), which doesn't
            // thread the visiting_defaults cycle guard. Without the guard surviving that
            // hop, this used to recurse without ever terminating. The fix pre-resolves a
            // self-reference to the template's bound (here: mixed, since T is unbounded)
            // before expanding the rest of the default. `array<int, T>` gets unrolled
            // through two independent code paths that each apply this once (TypeExpander's
            // own bare-class default expansion, then the generic template-param fallback
            // it recurses into), so the pathological cycle bottoms out two levels deep
            // instead of one — still finite and still terminates quickly, which is what
            // actually matters here.
            'classSelfReferentialNestedTemplateDefaultDoesNotInfiniteLoop' => [
                'code' => '<?php
                    /**
                     * @template T = array<int, T>
                     */
                    class NestedCycle {
                        /** @return T */
                        public function get() {
                            throw new RuntimeException("empty");
                        }
                    }

                    $r = new NestedCycle();',
                'assertions' => [
                    '$r===' => 'NestedCycle<array<int, array<int, mixed>>>',
                ],
            ],
            'functionTemplateDefaultAppliedWhenNoArguments' => [
                'code' => '<?php
                    /**
                     * @template T = string
                     * @return T
                     */
                    function defaultNoParam() {
                        throw new \RuntimeException();
                    }

                    $r = defaultNoParam();',
                'assertions' => [
                    '$r===' => 'string',
                ],
            ],
            'functionTemplateChainedDefaultsAppliedWhenNoArguments' => [
                'code' => '<?php
                    /**
                     * @template T = string
                     * @template U = T
                     * @return U
                     */
                    function defaultChainNoParam() {
                        throw new \RuntimeException();
                    }

                    $r = defaultChainNoParam();',
                'assertions' => [
                    '$r===' => 'string',
                ],
            ],
            // pins the fix for a regression where a legitimately inferred `mixed`
            // lower bound was mistaken for "nothing inferred" and overridden by
            // the declared default; companion to functionTemplateDefaultAppliedWhenNoArguments
            // above, which pins the no-argument case still falling back to the default
            'functionTemplateDefaultNotAppliedWhenMixedInferred' => [
                'code' => '<?php
                    /**
                     * @template T = string
                     * @param T $x
                     * @return T
                     */
                    function identity($x) {
                        return $x;
                    }

                    /** @var mixed $m */
                    $m = null;

                    /** @psalm-suppress MixedAssignment */
                    $r = identity($m);',
                'assertions' => [
                    '$r===' => 'mixed',
                ],
            ],
            'instanceMethodTemplateDefaultAppliedWhenNoArguments' => [
                'code' => '<?php
                    class Box {
                        /**
                         * @template T = int
                         * @return T
                         */
                        public function get() {
                            throw new RuntimeException("empty");
                        }
                    }

                    $r = (new Box())->get();',
                'assertions' => [
                    '$r===' => 'int',
                ],
            ],
            'staticMethodTemplateDefaultAppliedWhenNoArguments' => [
                'code' => '<?php
                    class Box {
                        /**
                         * @template T = int
                         * @return T
                         */
                        public static function make() {
                            throw new RuntimeException("empty");
                        }
                    }

                    $r = Box::make();',
                'assertions' => [
                    '$r===' => 'int',
                ],
            ],
            'instanceMethodTemplateInferredFromArgOverridesDefault' => [
                'code' => '<?php
                    class Box {
                        /**
                         * @template T = int
                         * @param T $x
                         * @return T
                         */
                        public function identity($x) {
                            return $x;
                        }
                    }

                    $r = (new Box())->identity("hello");',
                'assertions' => [
                    '$r===' => "'hello'",
                ],
            ],
            'classExtendsWithoutTypeArgsUsesParentDefault' => [
                'code' => '<?php
                    /**
                     * @template T = string
                     */
                    class Foo {
                        /** @return T */
                        public function get() {
                            throw new RuntimeException("empty");
                        }
                    }

                    class Sub extends Foo {}

                    $r = (new Sub())->get();',
                'assertions' => [
                    '$r===' => 'string',
                ],
                'ignored_issues' => ['MissingTemplateParam'],
            ],
            'classImplementsWithoutTypeArgsUsesInterfaceDefault' => [
                'code' => '<?php
                    /**
                     * @template T = int
                     */
                    interface IFoo {
                        /** @return T */
                        public function get();
                    }

                    class Impl implements IFoo {
                        public function get() {
                            throw new RuntimeException("empty");
                        }
                    }

                    $r = (new Impl())->get();',
                'assertions' => [
                    '$r===' => 'int',
                ],
                'ignored_issues' => ['MissingTemplateParam'],
            ],
            'traitUseWithoutTypeArgsUsesTraitDefault' => [
                'code' => '<?php
                    /**
                     * @template T = float
                     */
                    trait Tr {
                        /** @return T */
                        public function get() {
                            throw new RuntimeException("empty");
                        }
                    }

                    class UsesTr {
                        use Tr;
                    }

                    $r = (new UsesTr())->get();',
                'assertions' => [
                    '$r===' => 'float',
                ],
                'ignored_issues' => ['MissingTemplateParam'],
            ],
            'classExtendsWithoutTypeArgsResolvesChainedDefault' => [
                'code' => '<?php
                    /**
                     * @template T = string
                     * @template U = T
                     */
                    class Foo {
                        /** @return U */
                        public function get() {
                            throw new RuntimeException("empty");
                        }
                    }

                    class Sub extends Foo {}

                    $r = (new Sub())->get();',
                'assertions' => [
                    '$r===' => 'string',
                ],
                'ignored_issues' => ['MissingTemplateParam'],
            ],
            'chainedDefaultResolvesOnUnparameterizedClassInstance' => [
                'code' => '<?php
                    /**
                     * @template T = string
                     * @template U = T
                     */
                    class Pair {
                        /** @return U */
                        public function second() {
                            throw new RuntimeException("empty");
                        }
                    }

                    class Holder {
                        /** @var Pair */
                        public Pair $p;

                        public function __construct() {
                            $this->p = new Pair();
                        }
                    }

                    /** @param Pair<string, string> $p */
                    function takesTyped(Pair $p): void {}

                    $h = new Holder();
                    $r = $h->p->second();

                    takesTyped($h->p);',
                'assertions' => [
                    '$r===' => 'string',
                ],
            ],
            'templateDefaultNotGluedToTrailingDescription' => [
                'code' => '<?php
                    /**
                     * @template T of int|string = int the id type
                     */
                    class Foo {
                        /** @return T */
                        public function get() {
                            throw new RuntimeException("empty");
                        }
                    }

                    $r = (new Foo())->get();',
                'assertions' => [
                    '$r===' => 'int',
                ],
            ],
            'functionTemplateDefaultNotGluedToTrailingDescription' => [
                'code' => '<?php
                    /**
                     * @template T of int|string = int the id type
                     * @return T
                     */
                    function makeDefault() {
                        throw new RuntimeException("empty");
                    }

                    $r = makeDefault();',
                'assertions' => [
                    '$r===' => 'int',
                ],
            ],
            'templateDefaultWithoutSpacesAroundEquals' => [
                'code' => '<?php
                    /**
                     * @template T=string
                     */
                    class Foo {
                        /** @return T */
                        public function get() {
                            throw new RuntimeException("empty");
                        }
                    }

                    $r = (new Foo())->get();',
                'assertions' => [
                    '$r===' => 'string',
                ],
            ],
            'templateDefaultBoundNotFlaggedForUnresolvedClassConstant' => [
                'code' => '<?php
                    class K {
                        const MAP = ["a" => 1, "b" => 2];
                    }

                    /**
                     * @template T of int = value-of<K::MAP>
                     */
                    class Foo {
                        /** @return T */
                        public function get() {
                            throw new RuntimeException("empty");
                        }
                    }

                    $r = (new Foo())->get();',
                'assertions' => [
                    '$r===' => '1|2',
                ],
            ],
            // `me()` returns `static`, resolved against the receiver `Foo<42>`. That
            // receiver already carries a real inferred type arg, so the class-level
            // default for T must not be re-applied when expanding `static` — the
            // receiver's args win.
            'staticReturnPreservesReceiverArgsOverClassDefault' => [
                'code' => '<?php
                    /**
                     * @template T = string
                     */
                    class Foo {
                        /** @var T */
                        private $v;
                        /** @param T $v */
                        public function __construct($v) {
                            $this->v = $v;
                        }
                        /** @return T */
                        public function get() {
                            return $this->v;
                        }
                        public function me(): static {
                            return $this;
                        }
                    }

                    $r = (new Foo(42))->me()->get();',
                'assertions' => [
                    '$r===' => '42',
                ],
            ],
            // T appears only in the callable parameter's input position, so it's
            // inferred as an upper bound (see TemplateResult doc comment), not a lower
            // bound. That's still real inferred content from the passed closure, not an
            // unmatched placeholder, so it must not be flagged from_unbound_template_fallback
            // — doing so would make the declared default override a real inference.
            'templateDefaultNotAppliedOverRealCallableParamInference' => [
                'code' => '<?php
                    /**
                     * @template T = string
                     * @param callable(T): void $c
                     * @return T
                     */
                    function process(callable $c) {
                        throw new RuntimeException("empty");
                    }

                    $r = process(function (int $v): void {});',
                'assertions' => [
                    '$r===' => 'int',
                ],
            ],
            'templateDefaultNotAppliedOverRealCallableParamInferenceMixed' => [
                'code' => '<?php
                    /**
                     * @template T = string
                     * @param callable(T): void $c
                     * @return T
                     */
                    function process(callable $c) {
                        throw new RuntimeException("empty");
                    }

                    /** @psalm-suppress MixedAssignment */
                    $r = process(function ($v): void {});',
                'assertions' => [
                    '$r===' => 'mixed',
                ],
            ],
            // the default is declared on A::make(), not on B. Looking the default up
            // from the called class's method storage (B's) instead of the declaring
            // method's storage (A's) loses it, since B inherits the method without
            // re-declaring the docblock.
            // T is a *method-level* template declared on A::make(), not a class-level
            // template on A. B inherits make() without redeclaring it, so looking the
            // default up from the called class's (B's) method storage instead of the
            // declaring method's (A's) storage loses it entirely.
            'inheritedMethodTemplateDefaultAppliedThroughSubclass' => [
                'code' => '<?php
                    class A {
                        /**
                         * @template T = int
                         * @return T
                         */
                        public function make() {
                            throw new RuntimeException("empty");
                        }
                    }

                    class B extends A {}

                    $r = (new B())->make();',
                'assertions' => [
                    '$r===' => 'int',
                ],
            ],
            'inheritedStaticMethodTemplateDefaultAppliedThroughSubclass' => [
                'code' => '<?php
                    class A {
                        /**
                         * @template T = int
                         * @return T
                         */
                        public static function make() {
                            throw new RuntimeException("empty");
                        }
                    }

                    class B extends A {}

                    $r = B::make();',
                'assertions' => [
                    '$r===' => 'int',
                ],
            ],
            'traitMethodTemplateDefaultAppliedThroughUsingClass' => [
                'code' => '<?php
                    trait Tr {
                        /**
                         * @template T = int
                         * @return T
                         */
                        public function make() {
                            throw new RuntimeException("empty");
                        }
                    }

                    class C {
                        use Tr;
                    }

                    $r = (new C())->make();',
                'assertions' => [
                    '$r===' => 'int',
                ],
            ],
            // passing `null` for `(callable(): T)|null $f` gives T a lower bound that's
            // only a placeholder (from_unbound_template_fallback), not real inferred
            // content — the constructor argument never actually names T. NewAnalyzer
            // must still fall through to the declared default here, the same as it does
            // when the argument is omitted entirely.
            'constructorFallbackPlaceholderDoesNotBlockDefault' => [
                'code' => '<?php
                    /**
                     * @template T = string
                     */
                    class Box {
                        /** @param (callable(): T)|null $f */
                        public function __construct($f = null) {}
                    }

                    $a = new Box();
                    $b = new Box(null);',
                'assertions' => [
                    '$a===' => 'Box<string>',
                    '$b===' => 'Box<string>',
                ],
            ],
        ];
    }

    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'classTemplateDefaultMismatch' => [
                'code' => '<?php
                    /**
                     * @template T = string
                     */
                    class Foo {
                        /** @return T */
                        public function get() {
                            throw new \RuntimeException();
                        }
                    }

                    /** @param Foo $foo */
                    function test(Foo $foo): int {
                        return $foo->get();
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'classTemplateDefaultViolatesBound' => [
                'code' => '<?php
                    /**
                     * @template T of object = int
                     */
                    class Foo {}',
                'error_message' => 'is not within bound',
            ],
            'classTemplateDefaultViolatesAsBound' => [
                'code' => '<?php
                    /**
                     * @template T as string = 42
                     */
                    class Foo {}',
                'error_message' => 'is not within bound',
            ],
            'functionTemplateDefaultViolatesBound' => [
                'code' => '<?php
                    /**
                     * @template T of object = int
                     * @return T
                     */
                    function foo() {
                        throw new \RuntimeException();
                    }',
                'error_message' => 'is not within bound',
            ],
            'classTemplateDefaultScalarViolatesNamedClassBound' => [
                'code' => '<?php
                    /**
                     * @template T of \DateTimeInterface = int
                     */
                    class Foo {}',
                'error_message' => 'is not within bound',
            ],
        ];
    }
}
