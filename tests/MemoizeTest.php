<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class MemoizeTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->testConfig->memoize_method_calls = true;
    }

    /**
     * @return iterable<string,array{code:string,assertions?:array<string,string>,ignored_issues?:list<string>}>
     */
    public function providerValidCodeParse(): iterable
    {
        yield 'methodCallMemoize' => [
            'code' => '<?php
                class A {
                    function getFoo() : ?Foo {
                        return rand(0, 1) ? new Foo : null;
                    }
                }
                class Foo {
                    function getBar() : ?Bar {
                        return rand(0, 1) ? new Bar : null;
                    }
                }
                class Bar {
                    public function bat() : void {}
                };

                $a = new A();

                if ($a->getFoo()) {
                    if ($a->getFoo()->getBar()) {
                        $a->getFoo()->getBar()->bat();
                    }
                }
            ',
        ];
        yield 'propertyMethodCallMemoize' => [
            'code' => '<?php
                class Foo
                {
                    private ?string $bar;

                    public function __construct(?string $bar) {
                        $this->bar = $bar;
                    }

                    public function getBar(): ?string {
                        return $this->bar;
                    }
                }

                function doSomething(Foo $foo): string {
                    if ($foo->getBar() !== null){
                        return $foo->getBar();
                    }

                    return "hello";
                }
            ',
        ];
        yield 'propertyMethodCallMutationFreeMemoize' => [
            'code' => '<?php
                class Foo
                {
                    private ?string $bar;

                    public function __construct(?string $bar) {
                        $this->bar = $bar;
                    }

                    /**
                     * @psalm-mutation-free
                     */
                    public function getBar(): ?string {
                        return $this->bar;
                    }
                }

                function doSomething(Foo $foo): string {
                    if ($foo->getBar() !== null){
                        return $foo->getBar();
                    }

                    return "hello";
                }
            ',
        ];
        yield 'unchainedMethodCallMemoize' => [
            'code' => '<?php
                class SomeClass {
                    private ?int $int;

                    public function __construct() {
                        $this->int = 1;
                    }

                    final public function getInt(): ?int {
                        return $this->int;
                    }
                }

                function printInt(int $int): void {
                    echo $int;
                }

                $obj = new SomeClass();

                if ($obj->getInt()) {
                    printInt($obj->getInt());
                }
            ',
        ];
        yield 'unchainedMutationFreeMethodCallMemoize' => [
            'code' => '<?php
                class SomeClass {
                    private ?int $int;

                    public function __construct() {
                        $this->int = 1;
                    }

                    /**
                     * @psalm-mutation-free
                     */
                    public function getInt(): ?int {
                        return $this->int;
                    }
                }

                function printInt(int $int): void {
                    echo $int;
                }

                $obj = new SomeClass();

                if ($obj->getInt()) {
                    printInt($obj->getInt());
                }
            ',
        ];
        yield 'memoizedPropertyInCatchIsNotPossiblyUndefinedAfter' => [
            'code' => '<?php
                class SomeClass {
                    public int $int;

                    public function __construct() {
                        $this->int = 1;
                    }
                }

                $obj = new SomeClass();
                try {
                } catch (Exception $_) {
                    echo $obj->int;
                }
                $foo = $obj->int;
            ',
            'assertions' => [
                '$foo===' => 'int',
            ],
        ];
        yield 'memoizedMethodInCatchIsNotPossiblyUndefinedAfter' => [
            'code' => '<?php
                class SomeClass {
                    private int $int;

                    public function __construct() {
                        $this->int = 1;
                    }

                    /**
                     * @psalm-mutation-free
                     */
                    public function getInt(): int {
                        return $this->int;
                    }
                }

                $obj = new SomeClass();
                try {
                } catch (Exception $_) {
                    $obj->getInt();
                }
                $foo = $obj->getInt();
            ',
            'assertions' => [
                '$foo===' => 'int',
            ],
        ];
        yield 'tryCatchDoesntRemovePreviouslyMemoizedProperties' => [
            'code' => '<?php
                class SomeClass {
                    public ?int $int;

                    public function __construct() {
                        $this->int = 1;
                    }
                }

                $obj = new SomeClass();
                assert($obj->int !== null);
                try {
                } catch (Exception $_) {
                }
            ',
            'assertions' => [
                '$obj->int===' => 'int',
            ],
        ];
        yield 'tryCatchDoesntRemovePreviouslyMemoizedPropertiesWhenUsedInTryAndCatch' => [
            'code' => '<?php
                class SomeClass {
                    public ?object $obj;

                    public function __construct() {
                        $this->obj = new stdClass();
                    }
                }

                $obj = new SomeClass();
                assert($obj->obj !== null);
                try {
                    takesObj($obj->obj);
                } catch (Exception $_) {
                    takesObj($obj->obj);
                }
                takesObj($obj->obj);

                function takesObj(object $obj): void {}
            ',
        ];
    }
}
