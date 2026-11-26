<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use Psalm\Context;
use Psalm\Internal\MethodIdentifier;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Tests\TestCase;

use function array_map;
use function strval;

final class InheritedMethodIdsTest extends TestCase
{
    public function testClassesBelowTheDeclaringOneOverrideIt(): void
    {
        $this->analyzeCode(
            '<?php
                class A {
                    public function foo(): void {}
                }
                class B extends A {}
                class C extends B {}
                class D extends C {
                    public function foo(): void {}
                }
                class E extends D {}',
        );

        self::assertSame(['A' => 'A::foo'], $this->getOverriddenMethodIds('b', 'foo'));
        self::assertSame(['A' => 'A::foo'], $this->getOverriddenMethodIds('c', 'foo'));
        self::assertSame(['A' => 'A::foo'], $this->getOverriddenMethodIds('d', 'foo'));
        self::assertSame(['D' => 'D::foo', 'A' => 'A::foo'], $this->getOverriddenMethodIds('e', 'foo'));
    }

    public function testInterfaceOfAChildDoesNotReachItsParent(): void
    {
        $this->analyzeCode(
            '<?php
                interface I {
                    public function foo(): void;
                }
                class A {
                    public function foo(): void {}
                }
                class B extends A {}
                class C extends B implements I {}
                class D extends C {}',
        );

        self::assertSame(['A' => 'A::foo'], $this->getOverriddenMethodIds('b', 'foo'));
        self::assertSame(['A' => 'A::foo', 'I' => 'I::foo'], $this->getOverriddenMethodIds('c', 'foo'));
        self::assertSame(['A' => 'A::foo', 'I' => 'I::foo'], $this->getOverriddenMethodIds('d', 'foo'));
    }

    public function testInterfaceOfAParentReachesItsChildren(): void
    {
        $this->analyzeCode(
            '<?php
                interface I {
                    public function foo(): void;
                }
                class A implements I {
                    public function foo(): void {}
                }
                class B extends A {}
                class C extends B {}',
        );

        self::assertSame(['I' => 'I::foo'], $this->getOverriddenMethodIds('a', 'foo'));
        self::assertSame(['A' => 'A::foo', 'I' => 'I::foo'], $this->getOverriddenMethodIds('b', 'foo'));
        self::assertSame(['A' => 'A::foo', 'I' => 'I::foo'], $this->getOverriddenMethodIds('c', 'foo'));
    }

    public function testPrivateMethodIsNotInheritable(): void
    {
        $this->analyzeCode(
            '<?php
                class A {
                    public function foo(): void {}
                    private function bar(): void {}
                }
                class B extends A {}',
        );

        $a_storage = $this->getStorage('a');
        $b_storage = $this->getStorage('b');

        self::assertSame(['foo' => 'A::foo', 'bar' => 'A::bar'], self::toStrings($a_storage->declaring_method_ids));
        self::assertSame(['foo' => 'A::foo'], self::toStrings($a_storage->inheritable_method_ids));
        self::assertSame(['foo' => 'A::foo'], self::toStrings($b_storage->declaring_method_ids));
        self::assertSame(['foo' => 'A::foo'], self::toStrings($b_storage->inheritable_method_ids));
        self::assertSame(
            ['foo' => 'A::foo', 'bar' => 'A::bar'],
            self::toStrings($b_storage->appearing_method_ids),
        );
    }

    public function testTraitMethodAppearsInTheClassUsingIt(): void
    {
        $this->analyzeCode(
            '<?php
                trait T {
                    public function foo(): void {}
                }
                class A {
                    use T;
                }
                class B extends A {}',
        );

        $b_storage = $this->getStorage('b');

        self::assertSame(['foo' => 'T::foo'], self::toStrings($b_storage->declaring_method_ids));
        self::assertSame(['foo' => 'T::foo'], self::toStrings($b_storage->inheritable_method_ids));
        self::assertSame(['foo' => 'A::foo'], self::toStrings($b_storage->appearing_method_ids));
    }

    private function analyzeCode(string $code): void
    {
        $file_path = 'somefile.php';

        $this->addFile($file_path, $code);
        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @param lowercase-string $fq_class_name_lc
     * @psalm-capabilities read-props
     */
    private function getStorage(string $fq_class_name_lc): ClassLikeStorage
    {
        return $this->project_analyzer->getCodebase()->classlike_storage_provider->get($fq_class_name_lc);
    }

    /**
     * @param lowercase-string $fq_class_name_lc
     * @param lowercase-string $method_name_lc
     * @return array<string, string>
     */
    private function getOverriddenMethodIds(string $fq_class_name_lc, string $method_name_lc): array
    {
        return self::toStrings($this->getStorage($fq_class_name_lc)->overridden_method_ids[$method_name_lc] ?? []);
    }

    /**
     * @param array<string, MethodIdentifier> $method_ids
     * @return array<string, string>
     * @psalm-pure
     */
    private static function toStrings(array $method_ids): array
    {
        return array_map(strval(...), $method_ids);
    }
}
