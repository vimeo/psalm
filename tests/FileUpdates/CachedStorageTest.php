<?php

declare(strict_types=1);

namespace Psalm\Tests\FileUpdates;

use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Tests\Internal\Provider\ClassLikeStorageInstanceCacheProvider;
use Psalm\Tests\Internal\Provider\FakeFileReferenceCacheProvider;
use Psalm\Tests\Internal\Provider\FileStorageInstanceCacheProvider;
use Psalm\Tests\Internal\Provider\ParserInstanceCacheProvider;
use Psalm\Tests\Internal\Provider\ProjectCacheProvider;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;

use function array_keys;
use function getcwd;
use function strpos;

use const DIRECTORY_SEPARATOR;

class CachedStorageTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->file_provider = new FakeFileProvider();

        $config = new TestConfig();

        $providers = new Providers(
            $this->file_provider,
            new ParserInstanceCacheProvider(),
            new FileStorageInstanceCacheProvider(),
            new ClassLikeStorageInstanceCacheProvider(),
            new FakeFileReferenceCacheProvider(),
            new ProjectCacheProvider(),
        );

        $this->project_analyzer = new ProjectAnalyzer(
            $config,
            $providers,
        );
        $this->project_analyzer->setPhpVersion('7.3', 'tests');
    }

    public function testValidInclude(): void
    {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        $this->project_analyzer->getCodebase()->diff_methods = true;

        $codebase = $this->project_analyzer->getCodebase();

        $vendor_files = [
            (string) getcwd() . DIRECTORY_SEPARATOR . 'V1.php' => '<?php
                namespace AnotherPackage;
                interface StorageInterface {
                   public function getRecord(): OperationInterface;
                }',
            (string) getcwd() . DIRECTORY_SEPARATOR . 'V2.php' => '<?php
                namespace AnotherPackage;
                interface OperationInterface {
                   public function getResult(): ResultInterface;
                }',
            (string) getcwd() . DIRECTORY_SEPARATOR . 'V3.php' => '<?php
                namespace AnotherPackage;
                interface ResultInterface {}',
        ];

        $analyzable_files = [
            (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                use AnotherPackage\StorageInterface;
                class C {
                    /** @var ?StorageInterface */
                    private $storage;
                    public function zugzug() : void {
                        if (!$this->storage) {
                            return;
                        }
                        $result = $this->storage->getRecord()->getResult();
                    }
                }',
        ];

        foreach ($vendor_files as $file_path => $contents) {
            $this->file_provider->registerFile($file_path, $contents);
            $codebase->scanner->addFilesToShallowScan([$file_path => $file_path]);
        }

        foreach ($analyzable_files as $file_path => $contents) {
            $this->file_provider->registerFile($file_path, $contents);
            $codebase->addFilesToAnalyze([$file_path => $file_path]);
        }

        $codebase->scanFiles();

        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

        $codebase->reloadFiles($this->project_analyzer, array_keys($analyzable_files + $vendor_files));

        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);
    }
}
