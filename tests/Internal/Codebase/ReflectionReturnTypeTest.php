<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use DateTimeImmutable;
use Psalm\Internal\Codebase\Reflection;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Tests\Fixtures\NativeReturnTypeFixture;
use Psalm\Tests\TestCase;
use ReflectionClass;

use function function_exists;

use const PHP_MAJOR_VERSION;
use const PHP_MINOR_VERSION;

final class ReflectionReturnTypeTest extends TestCase
{
    public function testUnmappedMethodNativeReturnsArePreserved(): void
    {
        $storage = $this->reflectFixture(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION);

        self::assertSame('int', $storage->methods['nativereturn']->signature_return_type?->getId());
        self::assertSame('int', $storage->methods['nativereturn']->return_type?->getId());
        self::assertSame('mixed', $storage->methods['mixedreturn']->signature_return_type?->getId());
        self::assertSame('int|null', $storage->methods['nullablereturn']->signature_return_type?->getId());
        self::assertSame('null', $storage->methods['nullreturn']->signature_return_type?->getId());
        self::assertSame('void', $storage->methods['noreturn']->signature_return_type?->getId());
        self::assertNull($storage->methods['documentaryreturn']->signature_return_type);
    }

    public function testUnmappedMethodReturnsAreNotAppliedToAnotherPhpVersion(): void
    {
        $storage = $this->reflectFixture('8.0');

        self::assertNull($storage->methods['nativereturn']->signature_return_type);
        self::assertNull($storage->methods['mixedreturn']->signature_return_type);
    }

    public function testUnmappedFunctionNativeReturnIsPreserved(): void
    {
        $this->project_analyzer->setPhpVersion(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION, 'tests');
        $codebase = $this->project_analyzer->getCodebase();
        $reflection = new Reflection($codebase->classlike_storage_provider, $codebase);
        $function_id = 'PHPUnit\\Framework\\assertTrue';

        self::assertNull($reflection->registerFunction($function_id));
        self::assertSame('void', $reflection->getFunctionStorage($function_id)->signature_return_type?->getId());
    }

    public function testVersionedCallmapTakesPriorityOverRuntimeReflection(): void
    {
        $this->project_analyzer->setPhpVersion('8.1', 'tests');
        $codebase = $this->project_analyzer->getCodebase();
        $reflection = new Reflection($codebase->classlike_storage_provider, $codebase);
        $reflection->registerClass(new ReflectionClass(DateTimeImmutable::class));
        $storage = $codebase->classlike_storage_provider->get(DateTimeImmutable::class);

        self::assertSame('DateTimeImmutable|false', $storage->methods['modify']->signature_return_type?->getId());
    }

    public function testCallmapSignatureWithoutRuntimeReflection(): void
    {
        if (function_exists('mysqli_get_client_version')) {
            self::markTestSkipped('This case requires mysqli to be absent from the running PHP process.');
        }

        $this->project_analyzer->setPhpVersion('8.1', 'tests');
        $codebase = $this->project_analyzer->getCodebase();
        $reflection = new Reflection($codebase->classlike_storage_provider, $codebase);
        $storage = $reflection->createFunctionStorage('mysqli_get_client_version');

        self::assertNotNull($storage);
        self::assertSame('int', $storage->signature_return_type?->getId());
        self::assertSame('int', $storage->return_type?->getId());
        self::assertTrue($storage->builtin);
        self::assertFalse($reflection->hasFunction('mysqli_get_client_version'));
    }

    private function reflectFixture(string $php_version): ClassLikeStorage
    {
        require_once __DIR__ . '/../../fixtures/NativeReturnTypeFixture.php';

        $this->project_analyzer->setPhpVersion($php_version, 'tests');
        $codebase = $this->project_analyzer->getCodebase();
        $reflection = new Reflection($codebase->classlike_storage_provider, $codebase);
        $reflection->registerClass(new ReflectionClass(NativeReturnTypeFixture::class));

        return $codebase->classlike_storage_provider->get(NativeReturnTypeFixture::class);
    }
}
