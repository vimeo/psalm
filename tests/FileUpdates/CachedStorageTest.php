<?php
namespace Psalm\Tests\FileUpdates;

use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\Providers;
use Psalm\Tests\Internal\Provider;
use Psalm\Tests\Progress\VoidProgress;
use Psalm\Tests\TestConfig;

class CachedStorageTest extends \Psalm\Tests\TestCase
{
    /**
     * @return void
     */
    public function setUp() : void
    {
        parent::setUp();

        FileAnalyzer::clearCache();

        $this->file_provider = new \Psalm\Tests\Internal\Provider\FakeFileProvider();

        $config = new TestConfig();

        $providers = new Providers(
            $this->file_provider,
            new Provider\ParserInstanceCacheProvider(),
            new Provider\FileStorageInstanceCacheProvider(),
            new Provider\ClassLikeStorageInstanceCacheProvider(),
            new Provider\FakeFileReferenceCacheProvider()
        );

        $this->project_analyzer = new ProjectAnalyzer(
            $config,
            $providers,
            false,
            true,
            ProjectAnalyzer::TYPE_CONSOLE,
            1,
            new VoidProgress()
        );
        $this->project_analyzer->setPhpVersion('7.3');
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

        $this->project_analyzer->getCodebase()->diff_methods = true;

        $codebase = $this->project_analyzer->getCodebase();

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

        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

        $codebase->reloadFiles($this->project_analyzer, array_keys($analyzable_files + $vendor_files));

        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);
    }
}
