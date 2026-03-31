<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class ReflectionTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    #[Override]
    public function providerValidCodeParse(): iterable
    {
        yield 'ReflectionClass::isSubclassOf' => [
            'code' => <<<'PHP'
                <?php
                $a = new ReflectionClass(stdClass::class);
                if (!$a->isSubclassOf(Iterator::class)) {
                    throw new Exception();
                }
                PHP,
            'assertions' => ['$a===' => 'ReflectionClass<stdClass&Iterator>'],
        ];
        yield 'ReflectionClass::implementsInterface' => [
            'code' => <<<'PHP'
                <?php
                $a = new ReflectionClass(stdClass::class);
                if (!$a->implementsInterface(Iterator::class)) {
                    throw new Exception();
                }
                PHP,
            'assertions' => ['$a===' => 'ReflectionClass<stdClass&Iterator>'],
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
        yield 'ReflectionObject infers generic type from object' => [
            'code' => <<<'PHP'
                <?php
                $r = new ReflectionObject(new stdClass());
                PHP,
            'assertions' => ['$r===' => 'ReflectionObject<stdClass>'],
        ];
        yield 'ReflectionObject::getName returns class-string' => [
            'code' => <<<'PHP'
                <?php
                $r = new ReflectionObject(new stdClass());
                $name = $r->getName();
                PHP,
            'assertions' => ['$name===' => 'class-string<stdClass>'],
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
            'assertions' => ['$a===' => 'ReflectionObject<stdClass&Iterator>'],
        ];
        yield 'ReflectionObject::implementsInterface' => [
            'code' => <<<'PHP'
                <?php
                $a = new ReflectionObject(new stdClass());
                if (!$a->implementsInterface(Iterator::class)) {
                    throw new Exception();
                }
                PHP,
            'assertions' => ['$a===' => 'ReflectionObject<stdClass&Iterator>'],
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
    }

    #[Override]
    public function providerInvalidCodeParse(): iterable
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
