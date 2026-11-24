<?php

declare(strict_types=1);

namespace Psalm\Tests\Template;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class ValueOfTemplateTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'scalarCanBeIdenticalToAKeyOrAValueOfATemplateBoundedByArray' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $a
                     * @param value-of<T> $v
                     */
                    function sameValue($a, $v, string $s): string {
                        if ($v === $s) {
                            return $s;
                        }
                        return "";
                    }

                    /**
                     * @template T of array
                     * @param T $a
                     * @param key-of<T> $k
                     */
                    function sameKey($a, $k, int $i): int {
                        if ($k === $i) {
                            return $i;
                        }
                        return 0;
                    }',
            ],
            'narrowedByIdentityToAValueOrAKeyOfATemplateKeepsTheLinkToTheTemplate' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $a
                     * @param value-of<T> $v
                     * @return value-of<T>
                     */
                    function sameValue($a, $v, string $s) {
                        if ($v === $s) {
                            return $s;
                        }
                        throw new RuntimeException();
                    }

                    /**
                     * @template T of array
                     * @param T $a
                     * @param key-of<T> $k
                     * @return key-of<T>
                     */
                    function sameKey($a, $k, int $i) {
                        if ($k === $i) {
                            return $i;
                        }
                        throw new RuntimeException();
                    }',
            ],
            'itemOfATemplateArrayAtItsKeyIsItsValue' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, int>
                     * @param T $arr
                     * @param key-of<T> $k
                     * @return value-of<T>
                     */
                    function item(array $arr, $k) {
                        return $arr[$k];
                    }

                    /**
                     * @template T of array{a: int, b: string}
                     * @param T $arr
                     * @param key-of<T> $k
                     * @return value-of<T>
                     */
                    function shapeItem(array $arr, $k) {
                        return $arr[$k];
                    }

                    /**
                     * @template T of array<string, int>
                     * @param key-of<T> $k
                     * @param value-of<T> $v
                     * @return array<key-of<T>, value-of<T>>
                     */
                    function pair($k, $v): array {
                        return [$k => $v];
                    }',
            ],
            'acceptsArrayValuesFn' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     * @return value-of<T>[]
                     */
                    function getValues($array) {
                        return array_values($array);
                    }
                ',
            ],
            'mixedValueOf' => [
                'code' => '<?php
                    /** @var array<int>|mixed */
                    $a = [];

                    /** @psalm-suppress MixedArgument */
                    $a = array_values($a);',
                'assertions' => [
                    '$a' => 'list<mixed>',
                ],
            ],
            'acceptsIfInArrayFn' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     * @return value-of<T>|null
                     */
                    function getValue(string $value, $array) {
                        if (in_array($value, $array, true)) {
                            return $value;
                        }
                        return null;
                    }
                ',
            ],
            'useAfterInArrayOnTemplate' => [
                'code' => '<?php
                    final class Foo {
                        public function m(): int {
                            return 1;
                        }
                    }

                    /**
                     * @template T of array
                     * @param T $array
                     */
                    function f(Foo $o, string $s, $array): int {
                        if (in_array($o, $array, true) && in_array($s, $array, true)) {
                            echo $s[0];
                            return $o->m();
                        }
                        return 0;
                    }
                ',
            ],
            'keyOfAndValueOfAFunctionTemplateBoundedByAShapeAreThoseOfTheBound' => [
                'code' => '<?php
                    /** @template TArr of array{foo: int, bar: string} */
                    final class Holder {
                        /** @return value-of<TArr> */
                        public function value(): mixed {
                            throw new Exception();
                        }

                        /** @return key-of<TArr> */
                        public function key(): string {
                            throw new Exception();
                        }
                    }

                    function useScalar(int|string $s): void {}

                    /**
                     * @template A of array{foo: int, bar: string}
                     * @param Holder<A> $h
                     */
                    function f(Holder $h): void {
                        useScalar($h->value());
                        echo $h->key() === "foo" ? 1 : 2;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'typeCheckAfterInArrayOnTemplate' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     */
                    function f(string $value, $array): void {
                        if (in_array($value, $array, true)) {
                            if ($value === "") {
                                echo "empty";
                            }
                        }
                    }
                ',
            ],
            'inArrayOnTemplateKeepsValueType' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     * @return value-of<T>|null
                     */
                    function getValue(string $value, $array) {
                        if (in_array($value, $array, true)) {
                            echo strlen($value);
                            return $value;
                        }
                        echo strlen($value);
                        return null;
                    }
                ',
            ],
            'inArrayOnTemplateNarrowsMixed' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, int>
                     * @param T $array
                     * @return value-of<T>|null
                     */
                    function getValue(mixed $value, $array) {
                        if (in_array($value, $array, true)) {
                            return $value;
                        }
                        return null;
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'inArrayOnTwoTemplates' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @template U of array
                     * @param T $a
                     * @param U $b
                     */
                    function f(string $value, $a, $b): void {
                        if (in_array($value, $a, true) && in_array($value, $b, true)) {
                            echo strlen($value);
                        }
                        if (in_array($value, $a, true) && !in_array($value, $b, true)) {
                            echo strlen($value);
                        }
                    }
                ',
            ],
            'valueOfUnreplacedTemplateParam' => [
                'code' => '<?php
                    /**
                     * @template T as array<bool>
                     */
                    abstract class Foo {
                        /**
                         * @return value-of<T>
                         */
                        abstract public function getRandomValue(): bool;
                    }
                ',
            ],
            'valueOfNestedTemplates' => [
                'code' => '<?php
                    /**
                     * @template TValue
                     * @template TArray of array<TValue>
                     * @param TArray $array
                     * @return list<TValue>
                     */
                    function toList(array $array): array {
                        return array_values($array);
                    }',
            ],
            'valueOfTemplatePassedToUnionOfItsBound' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, int|string>
                     * @param T $array
                     * @param value-of<T> $v
                     */
                    function f(array $array, $v): void {
                        takesIntOrString($v);
                    }

                    function takesIntOrString(int|string $v): void {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'valueOfTemplateNarrowedByIdentity' => [
                'code' => '<?php
                    /** @param 3 $i */
                    function takesThree(int $i): void {}

                    /**
                     * @template T of array<string, int>
                     * @param T $array
                     * @param value-of<T> $v
                     * @return value-of<T>
                     */
                    function f(array $array, $v) {
                        if ($v === 3) {
                            takesThree($v);
                        }

                        return $v;
                    }',
            ],
            'valueOfTemplateNarrowedByStrictInArray' => [
                'code' => '<?php
                    /** @param 1|2 $i */
                    function takesOneOrTwo(int $i): void {}

                    /** @param 2 $i */
                    function takesTwo(int $i): void {}

                    /**
                     * @template T of array<string, int>
                     * @param T $array
                     * @param value-of<T> $v
                     * @return value-of<T>
                     */
                    function f(array $array, $v) {
                        if (in_array($v, [1, 2], true)) {
                            takesOneOrTwo($v);

                            if ($v !== 1) {
                                takesTwo($v);
                            }
                        }

                        return $v;
                    }',
            ],
            'valueOfTemplateNarrowedByIsInt' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, int|string>
                     * @param T $array
                     * @param value-of<T> $v
                     * @return value-of<T>
                     */
                    function f(array $array, $v) {
                        if (is_int($v)) {
                            takesInt($v);
                        } else {
                            takesString($v);
                        }

                        return $v;
                    }

                    function takesInt(int $i): void {}
                    function takesString(string $s): void {}',
            ],
            'valueOfTemplateNarrowedByIsString' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, int|string>
                     * @param T $array
                     * @param value-of<T> $v
                     * @return value-of<T>
                     */
                    function f(array $array, $v) {
                        if (is_string($v)) {
                            takesString($v);
                        } else {
                            takesInt($v);
                        }

                        return $v;
                    }

                    function takesInt(int $i): void {}
                    function takesString(string $s): void {}',
            ],
            'valueOfTemplateNarrowedByIsNumeric' => [
                'code' => '<?php
                    /** @param int|numeric-string $n */
                    function takesNumeric($n): void {}

                    /**
                     * @template T of array<string, int|string|bool>
                     * @param T $array
                     * @param value-of<T> $v
                     * @return value-of<T>
                     */
                    function f(array $array, $v) {
                        if (is_numeric($v)) {
                            takesNumeric($v);
                        }

                        return $v;
                    }',
            ],
            'valueOfTemplateNarrowedByInstanceof' => [
                'code' => '<?php
                    final class A {}
                    final class B {}

                    /**
                     * @template T of array<string, A|B>
                     * @param T $array
                     * @param value-of<T> $v
                     * @return value-of<T>
                     */
                    function f(array $array, $v) {
                        if ($v instanceof A) {
                            takesA($v);
                        } else {
                            takesB($v);
                        }

                        return $v;
                    }

                    function takesA(A $a): void {}
                    function takesB(B $b): void {}',
            ],
            'valueOfTemplateNarrowedByNotNull' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, int|null>
                     * @param T $array
                     * @param value-of<T> $v
                     * @return value-of<T>
                     */
                    function f(array $array, $v) {
                        if ($v !== null) {
                            takesInt($v);
                        }

                        return $v;
                    }

                    function takesInt(int $i): void {}',
            ],
            'valueOfTemplateInArithmetic' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, int>
                     * @param T $array
                     * @param value-of<T> $v
                     */
                    function f(array $array, $v): int {
                        $r = $v % 3;
                        $v += 1;
                        $v++;
                        /** @psalm-check-type-exact $v = int */;
                        return $r + $v;
                    }',
            ],
            'valueOfTemplateConcatenated' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, "a"|"b">
                     * @param T $array
                     * @param value-of<T> $v
                     */
                    function f(array $array, $v): string {
                        $r = $v . "x";
                        /** @psalm-check-type-exact $r = "ax"|"bx" */;
                        return $r;
                    }',
            ],
            'valueOfTemplateCast' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, string>
                     * @param T $array
                     * @param value-of<T> $v
                     */
                    function f(array $array, $v): float {
                        return (int) $v + (float) $v;
                    }',
            ],
            'valueOfTemplateStringOffset' => [
                'code' => '<?php
                    /**
                     * @template T of array<int, string>
                     * @param T $array
                     * @param value-of<T> $v
                     */
                    function f(array $array, $v): string {
                        return $v[0];
                    }',
            ],
            'valueOfTemplateAsOffsetOfAnotherArray' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, int>
                     * @param T $array
                     * @param value-of<T> $v
                     * @param list<string> $other
                     */
                    function f(array $array, $v, array $other): string {
                        return $other[$v] ?? "";
                    }',
            ],
            'valueOfTemplateIterated' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, list<int>>
                     * @param T $array
                     * @param value-of<T> $v
                     */
                    function f(array $array, $v): int {
                        $sum = 0;
                        foreach ($v as $i) {
                            $sum += $i;
                        }
                        return $sum;
                    }',
            ],
            'valueOfTemplatePropertyFetchAndAssignment' => [
                'code' => '<?php
                    final class Foo {
                        public int $p = 0;
                    }

                    /**
                     * @template T of array<string, Foo>
                     * @param T $array
                     * @param value-of<T> $v
                     */
                    function f(array $array, $v): int {
                        $v->p = 3;
                        return $v->p;
                    }',
            ],
            'valueOfTemplateMethodCall' => [
                'code' => '<?php
                    final class Foo {
                        public function m(): int {
                            return 1;
                        }
                    }

                    /**
                     * @template T of array<string, Foo>
                     * @param T $array
                     * @param value-of<T> $v
                     * @return value-of<T>
                     */
                    function f(array $array, $v) {
                        echo $v->m();
                        /** @psalm-check-type-exact $v = value-of<T> */;
                        return $v;
                    }',
            ],
            'valueOfNullableTemplateNullsafeMethodCall' => [
                'code' => '<?php
                    final class Foo {
                        public function m(): int {
                            return 1;
                        }
                    }

                    /**
                     * @template T of array<string, ?Foo>
                     * @param T $array
                     * @param value-of<T> $v
                     */
                    function f(array $array, $v): ?int {
                        $r = $v?->m();
                        /** @psalm-check-type-exact $r = int|null */;
                        return $r;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'valueOfTemplateStaticCallAndClone' => [
                'code' => '<?php
                    final class Foo {
                        public static function s(): int {
                            return 1;
                        }
                    }

                    /**
                     * @template T of array<string, Foo>
                     * @param T $array
                     * @param value-of<T> $v
                     */
                    function f(array $array, $v): Foo {
                        echo $v::s();
                        return clone $v;
                    }',
            ],
            'valueOfTemplateClassFetched' => [
                'code' => '<?php
                    final class Foo {}

                    /**
                     * @template T of array<string, Foo>
                     * @param T $array
                     * @param value-of<T> $v
                     * @return class-string<Foo>
                     */
                    function f(array $array, $v): string {
                        $c = $v::class;
                        /** @psalm-check-type-exact $c = class-string<Foo> */;
                        return $c;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'valueOfTemplateCloned' => [
                'code' => '<?php
                    final class Foo {}

                    /**
                     * @template T of array<string, Foo>
                     * @param T $array
                     * @param value-of<T> $v
                     */
                    function f(array $array, $v): Foo {
                        return clone $v;
                    }',
            ],
            'valueOfTemplateCastToArray' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, list<int>|null>
                     * @param T $array
                     * @param value-of<T> $v
                     * @return list<int>
                     */
                    function f(array $array, $v): array {
                        $r = (array) $v;
                        /** @psalm-check-type-exact $r = list<int> */;
                        return $r;
                    }',
            ],
            'valueOfTemplateClassStringInstantiated' => [
                'code' => '<?php
                    final class Foo {
                        public static function s(): int {
                            return 1;
                        }
                    }

                    /**
                     * @template T of array<string, class-string<Foo>>
                     * @param T $array
                     * @param value-of<T> $v
                     */
                    function f(array $array, $v): Foo {
                        echo $v::s();
                        return new $v();
                    }',
                'assertions' => [],
                'ignored_issues' => ['UnsafeInstantiation'],
            ],
            'valueOfTemplateCalled' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, Closure(): int>
                     * @param T $array
                     * @param value-of<T> $v
                     */
                    function f(array $array, $v): int {
                        return $v();
                    }',
            ],
            'templateBoundedByValueOfTemplateMethodCall' => [
                'code' => '<?php
                    final class Foo {
                        public function m(): int {
                            return 1;
                        }
                    }

                    /**
                     * @template T of array<string, Foo>
                     * @template V of value-of<T>
                     * @param T $array
                     * @param V $v
                     * @return V
                     */
                    function f(array $array, $v) {
                        echo $v->m();
                        return $v;
                    }',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'intCannotBeIdenticalToAValueOfATemplateBoundedByAStringArray' => [
                'code' => '<?php
                    /**
                     * @template T of array<int, string>
                     * @param T $a
                     * @param value-of<T> $v
                     */
                    function value($a, $v, int $i): void {
                        if ($v === $i) {}
                    }',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'writeUnderTheKeyOfATemplateBoundedByAShapeWidensTheArray' => [
                'code' => '<?php
                    /**
                     * @template T of array{a: int, b: string}
                     * @param T $t
                     * @param key-of<T> $k
                     * @return T
                     */
                    function w(array $t, string $k): array {
                        $t[$k] = 1;

                        return $t;
                    }',
                'error_message' => 'InvalidReturnStatement - src' . DIRECTORY_SEPARATOR
                    . "somefile.php:11:32 - The inferred type 'array{a: int, b: 1|string, ...<key-of<T:fn-w as ",
                'error_levels' => [],
                'php_version' => '8.2',
            ],
            'itemOfATemplateArrayAtTheKeyOfAnotherTemplateIsNotItsValue' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, int>
                     * @template U of array<string, int>
                     * @param T $arr
                     * @param key-of<U> $k
                     * @return value-of<T>
                     */
                    function item(array $arr, $k) {
                        return $arr[$k];
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'valueOfTemplateIdenticalToImpossibleLiteral' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, int>
                     * @param T $array
                     * @param value-of<T> $v
                     */
                    function f(array $array, $v): void {
                        if ($v === "a") {}
                    }
                ',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'valueOfTemplateNotIncludesString' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     * @return value-of<T>
                     */
                    function getValue($array) {
                        return "foo";
                    }
                ',
                'error_message' => 'InvalidReturnStatement',
            ],
            'looseInArrayOnTemplateDoesNotNarrow' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     * @return value-of<T>|null
                     */
                    function getValue(string $value, $array) {
                        if (in_array($value, $array)) {
                            return $value;
                        }
                        return null;
                    }
                ',
                'error_message' => 'InvalidReturnStatement',
            ],
            'inArrayOnTemplateKeepsUnionNeedle' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     */
                    function getValue(int|string $value, $array): bool {
                        if (in_array($value, $array, true)) {
                            return $value;
                        }
                        return false;
                    }
                ',
                'error_message' => "The inferred type 'value-of<T:fn-getvalue as array<array-key, int|string",
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'valueOfTemplateNotIncludesInt' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $array
                     * @return value-of<T>
                     */
                    function getValue($array) {
                        return 0;
                    }
                ',
                'error_message' => 'InvalidReturnStatement',
            ],
            'valueOfUnresolvedTemplateParamIsStillChecked' => [
                'code' => '<?php
                    /**
                     * @template T as array<bool>
                     */
                    abstract class Foo {
                        /**
                         * @return value-of<T>
                         */
                        abstract public function getRandomValue(): string;
                    }
                ',
                'error_message' => 'MismatchingDocblockReturnType',
            ],
            'valueOfNullableTemplateMethodCall' => [
                'code' => '<?php
                    final class Foo {
                        public function m(): int {
                            return 1;
                        }
                    }

                    /**
                     * @template T of array<string, ?Foo>
                     * @param T $array
                     * @param value-of<T> $v
                     */
                    function f(array $array, $v): int {
                        return $v->m();
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
            'valueOfNullableTemplatePropertyAssignment' => [
                'code' => '<?php
                    final class Foo {
                        public int $p = 0;
                    }

                    /**
                     * @template T of array<string, ?Foo>
                     * @param T $array
                     * @param value-of<T> $v
                     */
                    function f(array $array, $v): void {
                        $v->p = 3;
                    }',
                'error_message' => 'PossiblyNullPropertyAssignment',
            ],
            'valueOfNonObjectTemplatePropertyFetch' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, int>
                     * @param T $array
                     * @param value-of<T> $v
                     */
                    function f(array $array, $v): void {
                        echo $v->p;
                    }',
                'error_message' => 'Cannot fetch property on non-object $v of type int',
            ],
        ];
    }
}
