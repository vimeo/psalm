<?php

declare(strict_types=1);

namespace Psalm\Tests\ReturnTypeProvider;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ArrayColumnTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        yield 'arrayColumnObjectWithProperties' => [
            'code' => '<?php
                /**
                 * @param object{id: int} $o
                 * @return non-empty-list<int>
                 */
                function f(object $o): array {
                    return array_column([$o], "id");
                }
            ',
        ];

        yield 'arrayColumnWithPrivatePropertiesExternal' => [
            'code' => '<?php
                class C {
                    /** @var int */
                    private $id = 42;
                }
                $r = array_column([new C], "id");
            ',
            // for inaccessible properties we cannot figure out neither type nor emptiness
            // in practice, array_column() omits inaccessible elements
            'assertions' => ['$r' => 'list<mixed>'],
        ];

        yield 'arrayColumnWithPrivatePropertiesInternal' => [
            'code' => '<?php
                class C {
                    /** @var int */
                    private $id = 42;

                    /** @return non-empty-list<int> */
                    public function f(): array {
                        return array_column([new self], "id");
                    }
                }
            ',
        ];

        yield 'arrayColumnWithShapes' => [
            'code' => '<?php
                /**
                 * @param array{id:int} $shape
                 * @return non-empty-list<int>
                 */
                function f(array $shape): array {
                    return array_column([$shape], "id");
                }
            ',
        ];

        yield 'arrayColumnWithObjectsAndColumnNameNull' => [
            'code' => '<?php
                class C {
                    /** @var string */
                    public $name = "";
                    public function foo(): void {}
                }

                foreach (array_column([new C, new C], null, "name") as $instance) {
                    $instance->foo();
                }
            ',
        ];

        yield 'arrayColumnWithIntersectionAndColumnNameNull' => [
            'code' => '<?php
                interface I {
                    public function foo(): void;
                }
                abstract class A {
                    /** @var string */
                    public $name = "";
                    abstract public function bar(): void;
                }
                class C extends A implements I {
                    public function foo(): void {}
                    public function bar(): void {}
                }

                /** @var (A&I)[] $instances */
                $instances = [];
                foreach (array_column($instances, null, "name") as $instance) {
                    $instance->foo();
                    $instance->bar();
                }
            ',
        ];

        yield 'arrayColumnWithArrayAndColumnNameNull' => [
            'code' => '<?php
                class C {
                    /** @var string */
                    public $name = "";
                    public function foo(): void {}
                }

                foreach (array_column([["name" => "", "instance" => new C]], null, "name") as $array) {
                    $array["instance"]->foo();
                }
            ',
        ];

        yield 'arrayColumnWithListOfObject' => [
            'code' => '<?php
                function foo(object $object): void {}

                /** @var list<object> $instances */
                $instances = [];
                foreach (array_column($instances, null, "name") as $instance) {
                    foo($instance);
                }
            ',
        ];

        yield 'arrayColumnWithListOfArrays' => [
            'code' => '<?php
                function foo(array $array): void {}

                /** @var list<array> $arrays */
                $arrays = [];
                foreach (array_column($arrays, null, "name") as $array) {
                    foo($array);
                }
            ',
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        yield 'arrayColumnWithArrayAndColumnNameNull' => [
            'code' => '<?php
                /** @var list<array{name: string, instance: object}> $arrays */
                $arrays = [];
                foreach (array_column($arrays, null, "name") as $array) {
                    $array["instance"]->foo();
                }
            ',
            'error_message' => 'MixedMethodCall',
        ];
    }
}
