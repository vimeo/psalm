<?php
namespace Psalm\Tests\LanguageServer;

use LanguageServerProtocol\Position;
use Psalm\Context;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\Providers;
use Psalm\Tests\Internal\Provider;
use Psalm\Tests\TestConfig;

class MapTest extends \Psalm\Tests\TestCase
{
    public function setUp() : void
    {
        parent::setUp();

        $this->file_provider = new \Psalm\Tests\Internal\Provider\FakeFileProvider();

        $config = new TestConfig();

        $providers = new Providers(
            $this->file_provider,
            new \Psalm\Tests\Internal\Provider\ParserInstanceCacheProvider(),
            null,
            null,
            new Provider\FakeFileReferenceCacheProvider(),
            new \Psalm\Tests\Internal\Provider\ProjectCacheProvider()
        );

        $this->project_analyzer = new ProjectAnalyzer(
            $config,
            $providers
        );
        $this->project_analyzer->setPhpVersion('7.3');
        $this->project_analyzer->getCodebase()->store_node_types = true;
    }



    public function testMapIsUpdatedOnReloadFiles(): void
    {
        $codebase = $this->project_analyzer->getCodebase();
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    function __construct( string $var ) {
                    }
                }
                $a = new A( "foo" );'
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());
        [ $type_map ] = $codebase->analyzer->getMapsForFile('somefile.php');

        $this->assertTrue( ! empty( $type_map ) );

        $codebase->file_provider->setOpenContents('somefile.php', '');
        $codebase->reloadFiles( $this->project_analyzer, [ 'somefile.php'] );
        $map = $codebase->analyzer->getMapsForFile('somefile.php');
        var_dump( $map );
    }

    public function testGetTypeMap(): void
    {
        $codebase = $this->project_analyzer->getCodebase();
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    function __construct( string $var ) {
                    }
                }
                $a = new A( "foo" );'
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());
        [ $type_map ] = $codebase->analyzer->getMapsForFile('somefile.php');

        $this->assertSame(
            [
                155 => [
                    156,
                    'A',
                ],
                146 => [
                    148,
                    '146-147:A',
                ],
            ],
            $type_map
        );
    }
}
