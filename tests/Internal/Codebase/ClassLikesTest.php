<?php
declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use Psalm\Codebase;
use Psalm\Internal\Codebase\ClassLikes;
use Psalm\Internal\Codebase\Reflection;
use Psalm\Internal\Codebase\Scanner;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FileReferenceProvider;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\Provider\StatementsProvider;
use Psalm\Progress\VoidProgress;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Tests\TestCase;

final class ClassLikesTest extends TestCase
{
    /**
     * @var ClassLikes
     */
    private $classLikes;

    /**
     * @var ClassLikeStorageProvider
     */
    private $storageProvider;

    /**
     * @var FileReferenceProvider
     */
    private $fileReferenceProvider;

    /**
     * @var StatementsProvider
     */
    private $statementsProvider;

    /**
     * @var Scanner
     */
    private $codebaseScanner;

    /**
     * @var Codebase
     */
    private $codebase;

    /**
     * @var Providers
     */
    private $providers;

    /**
     * @var VoidProgress
     */
    private $progress;

    /**
     * @var FileStorageProvider
     */
    private $fileStorageProvider;

    /**
     * @var Reflection
     */
    private $reflection;

    public function setUp(): void
    {
        parent::setUp();
        $this->storageProvider = new ClassLikeStorageProvider();
        $this->fileReferenceProvider = new FileReferenceProvider();
        $this->statementsProvider = new StatementsProvider($this->file_provider);
        $this->providers = new Providers($this->file_provider);
        $this->progress = new VoidProgress();
        $this->codebase = new Codebase(
            $this->testConfig,
            $this->providers,
            $this->progress
        );
        $this->fileStorageProvider = new FileStorageProvider();
        $this->reflection = new Reflection(
            $this->storageProvider,
            $this->codebase
        );
        $this->codebaseScanner = new Scanner(
            $this->codebase,
            $this->testConfig,
            $this->fileStorageProvider,
            $this->file_provider,
            $this->reflection,
            $this->fileReferenceProvider,
            $this->progress
        );
        $this->classLikes = new ClassLikes(
            $this->testConfig,
            $this->storageProvider,
            $this->fileReferenceProvider,
            $this->statementsProvider,
            $this->codebaseScanner
        );
    }

    public function testWillDetectClassImplementingAliasedInterface(): void
    {
        $this->classLikes->addClassAlias('Foo', 'bar');

        $classStorage = new ClassLikeStorage('Baz');
        $classStorage->class_implements['bar'] = 'Bar';

        $this->storageProvider->addMore(['baz' => $classStorage]);

        self::assertTrue($this->classLikes->classImplements('Baz', 'Foo'));
    }
}
