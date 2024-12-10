<?php

declare(strict_types=1);

namespace Psalm\Tests\LanguageServer;

use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Tests\Internal\Provider\FakeFileReferenceCacheProvider;
use Psalm\Tests\Internal\Provider\ParserInstanceCacheProvider;
use Psalm\Tests\Internal\Provider\ProjectCacheProvider;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;

use function count;

class FileMapTest extends TestCase
{
    protected Codebase $codebase;

    public function setUp(): void
    {
        parent::setUp();

        $this->file_provider = new FakeFileProvider();

        $config = new TestConfig();

        $providers = new Providers(
            $this->file_provider,
            new ParserInstanceCacheProvider(),
            null,
            null,
            new FakeFileReferenceCacheProvider(),
            new ProjectCacheProvider(),
        );

        $this->codebase = new Codebase($config, $providers);

        $this->project_analyzer = new ProjectAnalyzer(
            $config,
            $providers,
            null,
            [],
            1,
            null,
            $this->codebase,
        );

        $this->project_analyzer->setPhpVersion('7.3', 'tests');
        $this->project_analyzer->getCodebase()->store_node_types = true;
    }

    public function testMapIsUpdatedOnReloadFiles(): void
    {
        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    function __construct( string $var ) {
                    }
                }
                $a = new A( "foo" );',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());
        [ $type_map ] = $codebase->analyzer->getMapsForFile('somefile.php');

        $this->assertTrue(!empty($type_map));

        $codebase->file_provider->setOpenContents('somefile.php', '');
        $codebase->reloadFiles($this->project_analyzer, ['somefile.php']);
        [ $type_map ] = $codebase->analyzer->getMapsForFile('somefile.php');

        $this->assertSame([], $type_map);
    }

    public function testGetTypeMap(): void
    {
        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    function __construct( string $var ) {
                    }
                }
                $a = new A( "foo" );',
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
            $type_map,
        );
    }

    public function testMapIsUpdatedAfterEditingMethod(): void
    {
        $codebase = $this->codebase;
        $codebase->diff_methods = true;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace Foo;

                class A {
                    public function first(\DateTimeImmutable $d) : void {
                        echo $d->format("Y");
                        echo "\n";
                    }

                    public function second(\DateTimeImmutable $d) : void {
                        echo $d->format("Y");
                    }
                }',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->addFilesToAnalyze(['somefile.php' => 'somefile.php']);
        $codebase->scanFiles();
        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);
        [$before] = $codebase->analyzer->getMapsForFile('somefile.php');

        $codebase->addTemporaryFileChanges(
            'somefile.php',
            '<?php
                namespace Foo;

                class A {
                    public function first(\DateTimeImmutable $d) : void {
                        echo $d->format("Y");

                        echo "\n";
                    }

                    public function second(\DateTimeImmutable $d) : void {
                        echo $d->format("Y");
                    }
                }',
        );
        $codebase->reloadFiles($this->project_analyzer, ['somefile.php']);
        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);
        [$after] = $codebase->analyzer->getMapsForFile('somefile.php');

        $this->assertCount(count($before), $after);
    }

    public function testMapIsUpdatedAfterDeletingFirstMethod(): void
    {
        $codebase = $this->codebase;
        $codebase->diff_methods = true;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace Foo;

                class A {
                    public function first(\DateTimeImmutable $d) : void {
                        echo $d->format("Y");
                        echo "\n";
                    }

                    public function second_method(\DateTimeImmutable $d) : void {
                        new \DateTimeImmutable("2010-01-01");
                        echo $d->format("Y");
                    }
                }',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->addFilesToAnalyze(['somefile.php' => 'somefile.php']);
        $codebase->scanFiles();
        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);
        $this->assertCount(9, $codebase->analyzer->getMapsForFile('somefile.php')[0]);

        $codebase->addTemporaryFileChanges(
            'somefile.php',
            '<?php
                namespace Foo;

                class A {
                    public function second_method(\DateTimeImmutable $d) : void {
                        new \DateTimeImmutable("2010-01-01");
                        echo $d->format("Y");
                    }
                }',
        );
        $codebase->reloadFiles($this->project_analyzer, ['somefile.php']);
        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);
        $this->assertCount(5, $codebase->analyzer->getMapsForFile('somefile.php')[0]);
    }

    public function testMapIsUpdatedAfterDeletingSecondMethod(): void
    {
        $codebase = $this->codebase;
        $codebase->diff_methods = true;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace Foo;

                class A {
                    public function first(\DateTimeImmutable $d) : void {
                        echo $d->format("Y");
                        echo "\n";
                    }

                    public function second(\DateTimeImmutable $d) : void {
                       echo $d->format("Y");
                    }
                }',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->addFilesToAnalyze(['somefile.php' => 'somefile.php']);
        $codebase->scanFiles();
        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);
        $this->assertCount(8, $codebase->analyzer->getMapsForFile('somefile.php')[0]);

        $codebase->addTemporaryFileChanges(
            'somefile.php',
            '<?php
                namespace Foo;

                class A {
                    public function second(\DateTimeImmutable $d) : void {
                        echo $d->format("Y");
                    }
                }',
        );
        $codebase->reloadFiles($this->project_analyzer, ['somefile.php']);
        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);
        $this->assertCount(4, $codebase->analyzer->getMapsForFile('somefile.php')[0]);
    }

    public function testMapIsUpdatedAfterAddingMethod(): void
    {
        $codebase = $this->codebase;
        $codebase->diff_methods = true;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace Foo;

                class A {
                    public function first(\DateTimeImmutable $d) : void {
                        echo $d->format("Y");
                        echo "\n";
                    }

                    public function second(\DateTimeImmutable $d) : void {
                        echo $d->format("Y");
                    }
                }',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->addFilesToAnalyze(['somefile.php' => 'somefile.php']);
        $codebase->scanFiles();
        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);
        $this->assertCount(8, $codebase->analyzer->getMapsForFile('somefile.php')[0]);

        $codebase->addTemporaryFileChanges(
            'somefile.php',
            '<?php
                namespace Foo;

                class A {
                    public function first(\DateTimeImmutable $d) : void {
                        echo $d->format("Y");
                        echo "\n";
                    }

                    public function third(\DateTimeImmutable $d) : void {
                        echo $d->format("Y");
                    }

                    public function second(\DateTimeImmutable $d) : void {
                        echo $d->format("Y");
                    }
                }',
        );
        $codebase->reloadFiles($this->project_analyzer, ['somefile.php']);
        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);
        $this->assertCount(12, $codebase->analyzer->getMapsForFile('somefile.php')[0]);
    }
}
