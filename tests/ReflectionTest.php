<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class ReflectionTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

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
    }
}
