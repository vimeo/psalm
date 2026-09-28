<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

/**
 * Native symbols stubbed with an `@since x.y` tag are reported as undefined when analysing an
 * older PHP version without a polyfill, while analysis still uses their stubbed shape.
 */
final class NativeSymbolAvailabilityTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    #[Override]
    public function providerValidCodeParse(): iterable
    {
        yield 'classAvailableFromItsVersion' => [
            'code' => '<?php
                $m = new WeakMap();
                $c = $m->count();
            ',
            'assertions' => [
                '$c===' => 'int',
            ],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];

        yield 'classPolyfilledBelowItsVersion' => [
            'code' => '<?php
                final class WeakMap implements Countable {
                    public function count(): int { return 0; }
                }

                $m = new WeakMap();
                $c = $m->count();
            ',
            'assertions' => [
                '$c===' => 'int',
            ],
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];

        yield 'classPolyfilledByProjectBelowItsVersion' => [
            'code' => '<?php
                interface Stringable {
                    public function __toString(): string;
                }

                final class A implements Stringable {
                    public function __toString(): string { return ""; }
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];

        yield 'stubShapeKeptBelowItsVersion' => [
            'code' => '<?php
                /** @psalm-suppress UndefinedClass, UndefinedPropertyFetch */
                function f(UnitEnum $e): string {
                    return $e->name;
                }
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];
    }

    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        yield 'classBelowItsVersion' => [
            'code' => '<?php
                $m = new WeakMap();
            ',
            'error_message' => 'UndefinedClass - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:26 - WeakMap is not'
                . ' defined for the analysed PHP version 7.4 (it was introduced in PHP 8.0);'
                . ' install symfony/polyfill-php80',
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];

        yield 'docblockClassBelowItsVersion' => [
            'code' => '<?php
                /** @param WeakMap<object, int> $m */
                function f($m): void {}
            ',
            'error_message' => 'UndefinedDocblockClass',
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];

        yield 'interfaceBelowItsVersion' => [
            'code' => '<?php
                final class A implements Stringable {
                    public function __toString(): string { return ""; }
                }
            ',
            'error_message' => 'UndefinedClass',
            'ignored_issues' => [],
            'php_version' => '7.4',
        ];

        yield 'propertyInheritsItsClassVersion' => [
            'code' => '<?php
                /** @psalm-suppress UndefinedClass */
                function f(UnitEnum $e): string {
                    return $e->name;
                }
            ',
            'error_message' => 'UndefinedPropertyFetch',
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];

        yield 'attributeBelowItsVersion' => [
            'code' => '<?php
                // No polyfill and analysing below 8.5, so #[\NoDiscard] is not available: Psalm
                // reports it as an undefined attribute class (enforcement is orthogonal).
                #[\NoDiscard]
                function f(): int { return 1; }

                $x = f();
                echo $x;
            ',
            'error_message' => 'UndefinedAttributeClass',
            'ignored_issues' => [],
            'php_version' => '8.0',
        ];
    }
}
