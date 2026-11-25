<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Exception;
use Override;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Codebase\Reflection;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;
use ReflectionClass;

use const PHP_VERSION_ID;

final class ReflectionTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    public function testReflectedPropertySetVisibilityDefaultsToReadVisibility(): void
    {
        $codebase = $this->project_analyzer->getCodebase();
        $reflection = new Reflection($codebase->classlike_storage_provider, $codebase);
        $reflection->registerClass(new ReflectionClass(Exception::class));
        $storage = $codebase->classlike_storage_provider->get(Exception::class);

        self::assertSame(ClassLikeAnalyzer::VISIBILITY_PROTECTED, $storage->properties['message']->set_visibility);
        self::assertSame(ClassLikeAnalyzer::VISIBILITY_PRIVATE, $storage->properties['trace']->set_visibility);
    }

    public function testReflectedAsymmetricPropertySetVisibility(): void
    {
        if (PHP_VERSION_ID < 8_04_00) {
            self::markTestSkipped('Asymmetric property visibility requires PHP 8.4.');
        }

        // Keep this test file parseable on supported runtimes before PHP 8.4.
        $instance = eval(<<<'PHP'
            return new class {
                public protected(set) string $protectedWrite = '';
                public private(set) string $privateWrite = '';
                public string $publicWrite = '';
            };
            PHP);
        self::assertIsObject($instance);

        $codebase = $this->project_analyzer->getCodebase();
        $reflection = new Reflection($codebase->classlike_storage_provider, $codebase);
        $class = new ReflectionClass($instance);
        $reflection->registerClass($class);
        $storage = $codebase->classlike_storage_provider->get($class->getName());

        self::assertSame(ClassLikeAnalyzer::VISIBILITY_PUBLIC, $storage->properties['protectedWrite']->visibility);
        self::assertSame(ClassLikeAnalyzer::VISIBILITY_PROTECTED, $storage->properties['protectedWrite']->set_visibility);
        self::assertSame(ClassLikeAnalyzer::VISIBILITY_PUBLIC, $storage->properties['privateWrite']->visibility);
        self::assertSame(ClassLikeAnalyzer::VISIBILITY_PRIVATE, $storage->properties['privateWrite']->set_visibility);
        self::assertSame(ClassLikeAnalyzer::VISIBILITY_PUBLIC, $storage->properties['publicWrite']->set_visibility);
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        yield 'ReflectionClass::newInstance' => [
            'code' => <<<'PHP'
                <?php
                $a = (new ReflectionClass(stdClass::class))->newInstance();
                PHP,
            'assertions' => ['$a===' => 'stdClass'],
        ];
        yield 'ReflectionClass::newInstanceArgs' => [
            'code' => <<<'PHP'
                <?php
                $a = (new ReflectionClass(stdClass::class))->newInstanceArgs();
                PHP,
            'assertions' => ['$a===' => 'stdClass'],
        ];
        yield 'PHP80-ReflectionClass::newInstanceArgs without arguments' => [
            'code' => <<<'PHP'
                <?php
                $a = (new ReflectionClass(stdClass::class))->newInstanceArgs();
                PHP,
            'assertions' => ['$a===' => 'stdClass'],
        ];
        yield 'ReflectionClass::newInstanceWithoutConstructor' => [
            'code' => <<<'PHP'
                <?php
                $a = (new ReflectionClass(stdClass::class))->newInstanceWithoutConstructor();
                PHP,
            'assertions' => ['$a===' => 'stdClass'],
        ];
        yield 'ReflectionClass::isSubclassOf' => [
            'code' => <<<'PHP'
                <?php
                $a = new ReflectionClass(stdClass::class);
                if (!$a->isSubclassOf(Iterator::class)) {
                    throw new Exception();
                }
                PHP,
            'assertions' => ['$a===' => 'ReflectionClass<stdClass&Iterator[impure]<mixed, mixed>>'],
        ];
        yield 'ReflectionClass::implementsInterface' => [
            'code' => <<<'PHP'
                <?php
                $a = new ReflectionClass(stdClass::class);
                if (!$a->implementsInterface(Iterator::class)) {
                    throw new Exception();
                }
                PHP,
            'assertions' => ['$a===' => 'ReflectionClass<stdClass&Iterator[impure]<mixed, mixed>>'],
        ];
        yield 'ReflectionClass::isInstance' => [
            'code' => <<<'PHP'
                <?php
                $a = new stdClass();
                $b = new ReflectionClass(Iterator::class);
                if (!$b->isInstance($a)) {
                    throw new Exception();
                }
                PHP,
            'assertions' => ['$a===' => 'Iterator&stdClass'],
        ];
        yield 'ReflectionClassStaysCovariantOnPhp84' => [
            'code' => <<<'PHP'
                <?php
                function inspect(ReflectionClass $reflectionClass): void
                {
                    echo $reflectionClass->getName();
                }

                /**
                 * @template T of object
                 * @param class-string<T> $class
                 * @return T
                 */
                function create(string $class): object
                {
                    $reflectionClass = new ReflectionClass($class);
                    inspect($reflectionClass);
                    return $reflectionClass->newInstance();
                }
                PHP,
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.4',
        ];
        yield 'ReflectionObject infers generic type from object' => [
            'code' => <<<'PHP'
                <?php
                $r = new ReflectionObject(new stdClass());
                PHP,
            'assertions' => ['$r===' => 'ReflectionObject<`_0:stdClass>'],
        ];
        yield 'ReflectionObject::getName returns class-string' => [
            'code' => <<<'PHP'
                <?php
                $r = new ReflectionObject(new stdClass());
                $name = $r->getName();
                PHP,
            'assertions' => ['$name===' => 'class-string<stdClass>'],
        ];
        yield 'ReflectionObject name property is class-string' => [
            'code' => <<<'PHP'
                <?php
                $r = new ReflectionObject(new stdClass());
                $name = $r->name;
                PHP,
            'assertions' => ['$name===' => 'class-string<stdClass>'],
        ];
        yield 'ReflectionObject of an object' => [
            'code' => <<<'PHP'
                <?php
                function makeObject(): object {
                    return new stdClass();
                }
                $r = new ReflectionObject(makeObject());
                PHP,
            'assertions' => ['$r===' => 'ReflectionObject<`_0:object>'],
        ];
        yield 'ReflectionObject keeps a template type' => [
            'code' => <<<'PHP'
                <?php
                /**
                 * @template T of object
                 * @param T $o
                 * @return T
                 */
                function cloneEmpty(object $o): object {
                    return (new ReflectionObject($o))->newInstanceWithoutConstructor();
                }
                $a = cloneEmpty(new stdClass());
                PHP,
            'assertions' => ['$a===' => 'stdClass'],
        ];
        yield 'ReflectionObject::newLazyGhost' => [
            'code' => <<<'PHP'
                <?php
                final class Foo {}
                $a = (new ReflectionObject(new Foo()))->newLazyGhost(function (Foo $foo): void {});
                PHP,
            'assertions' => ['$a===' => 'Foo'],
            'ignored_issues' => [],
            'php_version' => '8.4',
        ];
        yield 'ReflectionObject extends ReflectionClass' => [
            'code' => <<<'PHP'
                <?php
                function foo(ReflectionClass $r): void {}
                foo(new ReflectionObject(new stdClass()));
                PHP,
        ];
        yield 'ReflectionObject::isSubclassOf' => [
            'code' => <<<'PHP'
                <?php
                $a = new ReflectionObject(new stdClass());
                if (!$a->isSubclassOf(Iterator::class)) {
                    throw new Exception();
                }
                PHP,
            'assertions' => ['$a===' => 'ReflectionObject<stdClass&Iterator[impure]<mixed, mixed>>'],
        ];
        yield 'ReflectionObject::implementsInterface' => [
            'code' => <<<'PHP'
                <?php
                $a = new ReflectionObject(new stdClass());
                if (!$a->implementsInterface(Iterator::class)) {
                    throw new Exception();
                }
                PHP,
            'assertions' => ['$a===' => 'ReflectionObject<stdClass&Iterator[impure]<mixed, mixed>>'],
        ];
        yield 'ReflectionObject::isInstance' => [
            'code' => <<<'PHP'
                <?php
                class Foo {}
                $a = new stdClass();
                $b = new ReflectionObject(new Foo());
                if (!$b->isInstance($a)) {
                    throw new Exception();
                }
                PHP,
            'assertions' => ['$a===' => 'Foo&stdClass'],
        ];
        yield 'ReflectionObject::newInstance' => [
            'code' => <<<'PHP'
                <?php
                class Foo {}
                $a = new Foo();
                $b = (new ReflectionObject($a))->newInstance();
                PHP,
            'assertions' => ['$b===' => 'Foo'],
        ];
        yield 'ReflectionObject::newInstanceArgs' => [
            'code' => <<<'PHP'
                <?php
                class Foo {}
                $a = new Foo();
                $b = (new ReflectionObject($a))->newInstanceArgs([]);
                PHP,
            'assertions' => ['$b===' => 'Foo'],
        ];
        yield 'ReflectionObject::newInstanceWithoutConstructor' => [
            'code' => <<<'PHP'
                <?php
                class Foo {}
                $a = new Foo();
                $b = (new ReflectionObject($a))->newInstanceWithoutConstructor();
                PHP,
            'assertions' => ['$b===' => 'Foo'],
        ];
        yield 'PHP80-ReflectionObject::getAttributes' => [
            'code' => <<<'PHP'
                <?php
                $a = new stdClass();
                $b = (new ReflectionObject($a))->getAttributes();
                PHP,
            'assertions' => ['$b===' => 'list<ReflectionAttribute<object>>'],
        ];
        yield 'PHP80-ReflectionObject::getAttributes specific' => [
            'code' => <<<'PHP'
                <?php
                $a = new stdClass();
                $b = (new ReflectionObject($a))->getAttributes(Override::class);
                PHP,
            'assertions' => ['$b===' => 'list<ReflectionAttribute<Override>>'],
        ];
        yield 'ReflectionClass of an object names its class' => [
            'code' => <<<'PHP'
                <?php
                $a = new ReflectionClass(new stdClass());
                $name = $a->getName();
                $property = $a->name;
                if (!$a->isSubclassOf(Iterator::class)) {
                    throw new Exception();
                }
                PHP,
            'assertions' => [
                '$name===' => 'class-string<stdClass>',
                '$property===' => 'class-string<stdClass>',
                '$a===' => 'ReflectionClass<stdClass&Iterator[impure]<mixed, mixed>>',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        yield 'ReflectionObject rejects string argument' => [
            'code' => <<<'PHP'
                <?php
                new ReflectionObject('stdClass');
                PHP,
            'error_message' => 'InvalidArgument',
        ];
    }
}
