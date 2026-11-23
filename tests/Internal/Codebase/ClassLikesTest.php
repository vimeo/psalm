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
}
