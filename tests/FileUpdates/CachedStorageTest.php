<?php
namespace Psalm\Tests\FileUpdates;

use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\Providers;
use Psalm\Tests\TestConfig;
use Psalm\Tests\Provider;

class CachedStorageTest extends \Psalm\Tests\TestCase
{
    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        FileAnalyzer::clearCache();

        $this->file_provider = new \Psalm\Tests\Provider\FakeFileProvider();

        $config = new TestConfig();

        $providers = new Providers(
            $this->file_provider,
            new Provider\ParserInstanceCacheProvider(),
            new Provider\FileStorageInstanceCacheProvider(),
            new Provider\ClassLikeStorageInstanceCacheProvider(),
            new Provider\FakeFileReferenceCacheProvider()
        );

        $this->project_checker = new ProjectAnalyzer(
            $config,
            $providers,
            false,
            true,
            ProjectAnalyzer::TYPE_CONSOLE,
            1,
            false
        );

        $this->project_checker->getCodebase()->infer_types_from_usage = true;
    }

    /**
     * @return void
     */
    public function testValidInclude()
    {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        $this->project_checker->getCodebase()->diff_methods = true;

        $codebase = $this->project_checker->getCodebase();

        $vendor_files = [
            getcwd() . DIRECTORY_SEPARATOR . 'V1.php' => '<?php
                namespace AnotherPackage;
                interface StorageInterface {
                   public function getRecord(): OperationInterface;
                }',
            getcwd() . DIRECTORY_SEPARATOR . 'V2.php' => '<?php
                namespace AnotherPackage;
                interface OperationInterface {
                   public function getResult(): ResultInterface;
                }',
            getcwd() . DIRECTORY_SEPARATOR . 'V3.php' => '<?php
                namespace AnotherPackage;
                interface ResultInterface {}',
        ];

        $analyzable_files = [
            getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
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

        $codebase->analyzer->analyzeFiles($this->project_checker, 1, false);

        $codebase->reloadFiles($this->project_checker, array_keys($analyzable_files + $vendor_files));

        foreach ($analyzable_files as $file_path => $_) {
            $codebase->addFilesToAnalyze([$file_path => $file_path]);
        }

        $codebase->scanFiles();

        $codebase->analyzer->analyzeFiles($this->project_checker, 1, false);
    }
}
