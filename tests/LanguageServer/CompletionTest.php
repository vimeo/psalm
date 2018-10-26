<?php
namespace Psalm\Tests\LanguageServer;

use LanguageServerProtocol\Position;
use Psalm\Checker\FileChecker;
use Psalm\Checker\ProjectChecker;
use Psalm\Context;
use Psalm\Tests\TestConfig;
use Psalm\Tests\Provider;
use Psalm\Provider\Providers;

class CompletionTest extends \Psalm\Tests\TestCase
{
    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        FileChecker::clearCache();

        $this->file_provider = new \Psalm\Tests\Provider\FakeFileProvider();

        $config = new TestConfig();

        $providers = new Providers(
            $this->file_provider,
            new \Psalm\Tests\Provider\ParserInstanceCacheProvider(),
            null,
            null,
            new Provider\FakeFileReferenceCacheProvider()
        );

        $this->project_checker = new ProjectChecker(
            $config,
            $providers,
            false,
            true,
            ProjectChecker::TYPE_CONSOLE,
            1,
            false
        );

        $this->project_checker->codebase->server_mode = true;
    }

    /**
     * @return void
     */
    public function testCompletionOnThisWithNoAssignment()
    {
        $codebase = $this->project_checker->getCodebase();
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace B;

                class A {
                    /** @var int|null */
                    protected $a;

                    public function foo() {
                        $this->
                    }
                }'
        );

        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $this->assertSame(['B\A', '->'], $codebase->getCompletionDataAtPosition('somefile.php', new Position(8, 31)));
    }

    /**
     * @return void
     */
    public function testCompletionOnThisWithAssignmentBelow()
    {
        $codebase = $this->project_checker->getCodebase();
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace B;

                class A {
                    /** @var int|null */
                    protected $a;

                    public function foo() : self {
                        $this->

                        $a = "foo";
                    }
                }'
        );

        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $this->assertSame(['B\A', '->'], $codebase->getCompletionDataAtPosition('somefile.php', new Position(8, 31)));
    }

    /**
     * @return void
     */
    public function testCompletionOnThisWithIfBelow()
    {
        $codebase = $this->project_checker->getCodebase();
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace B;

                class A {
                    /** @var int|null */
                    protected $a;

                    public function foo() : self {
                        $this

                        if(rand(0, 1)) {}
                    }
                }'
        );

        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $codebase->addTemporaryFileChanges(
            'somefile.php',
            [
                new \LanguageServerProtocol\TextDocumentContentChangeEvent(
                    null,
                    null,
                    '<?php
                namespace B;

                class A {
                    /** @var int|null */
                    protected $a;

                    public function foo() : self {
                        $this->

                        if(rand(0, 1)) {}
                    }
                }'
                )
            ]
        );

        $codebase->addFilesToAnalyze(['somefile.php' => 'somefile.php']);

        $codebase->analyzer->analyzeFiles($this->project_checker, 1, false);

        $this->assertSame(['B\A', '->'], $codebase->getCompletionDataAtPosition('somefile.php', new Position(8, 31)));
    }

    /**
     * @return void
     */
    public function testCompletionOnThisProperty()
    {
        $codebase = $this->project_checker->getCodebase();
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace B;

                class C {
                    public function otherFunction() : void
                }

                class A {
                    /** @var C */
                    protected $cee_me;

                    public function __construct() {
                        $this->cee_me = new C();
                    }

                    public function foo() : void {
                        $this->cee_me->
                    }
                }'
        );

        $codebase = $this->project_checker->getCodebase();

        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $this->assertSame(['B\C', '->'], $codebase->getCompletionDataAtPosition('somefile.php', new Position(16, 39)));
    }
}
