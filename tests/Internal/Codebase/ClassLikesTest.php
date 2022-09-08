<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use Psalm\Internal\Codebase\ClassLikes;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Tests\TestCase;

final class ClassLikesTest extends TestCase
{
    /**
     * @var ClassLikes
     */
    private $classlikes;

    /**
     * @var ClassLikeStorageProvider
     */
    private $storage_provider;

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
}
