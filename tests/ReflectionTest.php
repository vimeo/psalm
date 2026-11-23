<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Exception;
use Override;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Codebase\Reflection;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;
use ReflectionClass;

use const PHP_VERSION_ID;

final class ReflectionTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

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
