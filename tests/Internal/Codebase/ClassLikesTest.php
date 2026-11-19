<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use Override;
use Psalm\Internal\Codebase\ClassLikes;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Tests\TestCase;

final class ClassLikesTest extends TestCase
{
    private ClassLikes $classlikes;

    private ClassLikeStorageProvider $storage_provider;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->classlikes = $this->project_analyzer->getCodebase()->classlikes;
        $this->storage_provider = $this->project_analyzer->getCodebase()->classlike_storage_provider;
    }

    public function testWillDetectClassImplementingAliasedInterface(): void
    {
        $this->classlikes->addClassAlias('Foo', 'Bar');

        $classStorage = new ClassLikeStorage('Baz');
        $classStorage->class_implements['bar'] = 'Bar';

        $this->storage_provider->addMore(['baz' => $classStorage]);

        self::assertTrue($this->classlikes->classImplements('Baz', 'Foo'));
    }

    public function testWillResolveAliasedAliases(): void
    {
        $this->classlikes->addClassAlias('Foo', 'Bar');
        $this->classlikes->addClassAlias('Bar', 'Baz');
        $this->classlikes->addClassAlias('Baz', 'Qoo');

        self::assertSame('Foo', $this->classlikes->getUnAliasedName('Qoo'));
    }

    public function testThreadDataCarriesAliases(): void
    {
        $codebase = $this->project_analyzer->getCodebase();
        $worker_classlikes = new ClassLikes(
            $codebase->config,
            $this->storage_provider,
            $codebase->file_reference_provider,
            $codebase->scanner,
        );
        $worker_classlikes->addClassAlias('Foo', 'Bar');

        $this->classlikes->addThreadData($worker_classlikes->getThreadData());

        self::assertSame('Foo', $this->classlikes->getUnAliasedName('Bar'));
    }

    public function testEnumRegisteredAgainAsTraitIsNoLongerAnEnum(): void
    {
        $this->classlikes->addFullyQualifiedEnumName('Foo');
        $this->classlikes->addFullyQualifiedTraitName('Foo');

        self::assertFalse($this->classlikes->enumExists('Foo'));
        self::assertTrue($this->classlikes->traitExists('Foo'));
    }

    public function testTraitDoesNotHaveEnumCasing(): void
    {
        $this->classlikes->addFullyQualifiedTraitName('Foo');

        self::assertFalse($this->classlikes->enumHasCorrectCasing('Foo'));
        self::assertTrue($this->classlikes->traitHasCorrectCasing('Foo'));
        self::assertFalse($this->classlikes->traitHasCorrectCasing('FOO'));
    }

    public function testLastRegisteredKindWins(): void
    {
        $this->classlikes->addFullyQualifiedClassName('Foo');
        $this->classlikes->addFullyQualifiedInterfaceName('Foo');

        self::assertFalse($this->classlikes->classExists('Foo'));
        self::assertTrue($this->classlikes->interfaceExists('Foo'));
        // every spelling keeps the kinds it was registered with
        self::assertTrue($this->classlikes->classHasCorrectCasing('Foo'));
        self::assertTrue($this->classlikes->interfaceHasCorrectCasing('Foo'));
    }

    public function testUnknownClassLikeDoesNotExist(): void
    {
        self::assertFalse($this->classlikes->classExists('Unknown'));
        self::assertFalse($this->classlikes->classExists('Unknown'));
        self::assertFalse($this->classlikes->interfaceExists('Unknown'));
        self::assertFalse($this->classlikes->enumExists('Unknown'));
        self::assertFalse($this->classlikes->traitExists('Unknown'));
    }

    public function testClassLikeWithoutStorageExists(): void
    {
        $this->classlikes->addFullyQualifiedClassName('NoStorage');

        self::assertTrue($this->classlikes->classExists('nostorage'));
        self::assertFalse($this->classlikes->interfaceExists('NoStorage'));
    }

    public function testRemovedClassLikeNoLongerExists(): void
    {
        $this->classlikes->addFullyQualifiedEnumName('Foo');
        $this->classlikes->removeClassLike('Foo');

        self::assertFalse($this->classlikes->enumExists('Foo'));
        self::assertFalse($this->classlikes->enumHasCorrectCasing('Foo'));
        self::assertFalse($this->classlikes->doesClassLikeExist('foo'));
    }

    public function testThreadDataKeepsTheKindsOfEveryWorker(): void
    {
        $codebase = $this->project_analyzer->getCodebase();
        $worker_classlikes = new ClassLikes(
            $codebase->config,
            $this->storage_provider,
            $codebase->file_reference_provider,
            $codebase->scanner,
        );
        $worker_classlikes->addFullyQualifiedClassName('Foo');
        $this->classlikes->addFullyQualifiedInterfaceName('Foo');
        $this->classlikes->addFullyQualifiedTraitName('Bar');

        $this->classlikes->addThreadData($worker_classlikes->getThreadData());

        self::assertTrue($this->classlikes->classExists('Foo'));
        self::assertTrue($this->classlikes->interfaceExists('Foo'));
        self::assertTrue($this->classlikes->classHasCorrectCasing('Foo'));
        self::assertTrue($this->classlikes->interfaceHasCorrectCasing('Foo'));
        self::assertTrue($this->classlikes->traitExists('Bar'));
        self::assertFalse($this->classlikes->classExists('Bar'));
    }

    public function testMatchingClassLikeNamesListsClassesThenInterfaces(): void
    {
        $this->classlikes->addFullyQualifiedInterfaceName('Ns\FooInterface');
        $this->classlikes->addFullyQualifiedClassName('Ns\FooClass');
        $this->classlikes->addFullyQualifiedTraitName('Ns\FooTrait');
        $this->classlikes->addFullyQualifiedEnumName('Ns\FooEnum');

        self::assertSame(
            ['Ns\FooClass', 'Ns\FooInterface'],
            $this->classlikes->getMatchingClassLikeNames('\Ns\Foo'),
        );
    }
}
