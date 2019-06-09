<?php
namespace Psalm\Tests\LanguageServer;

use LanguageServerProtocol\Position;
use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\Providers;
use Psalm\Tests\Internal\Provider;
use Psalm\Tests\TestConfig;

class CompletionTest extends \Psalm\Tests\TestCase
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
            new \Psalm\Tests\Internal\Provider\ParserInstanceCacheProvider(),
            null,
            null,
            new Provider\FakeFileReferenceCacheProvider()
        );

        $this->project_analyzer = new ProjectAnalyzer(
            $config,
            $providers
        );
        $this->project_analyzer->setPhpVersion('7.3');
        $this->project_analyzer->getCodebase()->store_node_types = true;
    }

    /**
     * @return void
     */
    public function testCompletionOnThisWithNoAssignment()
    {
        $codebase = $this->project_analyzer->getCodebase();
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

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $this->assertSame(['B\A', '->'], $codebase->getCompletionDataAtPosition('somefile.php', new Position(8, 31)));
    }

    /**
     * @return void
     */
    public function testCompletionOnThisWithAssignmentBelow()
    {
        $codebase = $this->project_analyzer->getCodebase();
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

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $this->assertSame(['B\A', '->'], $codebase->getCompletionDataAtPosition('somefile.php', new Position(8, 31)));
    }

    /**
     * @return void
     */
    public function testCompletionOnThisWithIfBelow()
    {
        $codebase = $this->project_analyzer->getCodebase();
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

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $codebase->addTemporaryFileChanges(
            'somefile.php',
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
        );
        $codebase->reloadFiles($this->project_analyzer, ['somefile.php']);
        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

        $this->assertSame(['B\A', '->'], $codebase->getCompletionDataAtPosition('somefile.php', new Position(8, 31)));
    }

    /**
     * @return void
     */
    public function testCompletionOnThisProperty()
    {
        $codebase = $this->project_analyzer->getCodebase();
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

        $codebase = $this->project_analyzer->getCodebase();

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $this->assertSame(['B\C', '->'], $codebase->getCompletionDataAtPosition('somefile.php', new Position(16, 39)));
    }

    /**
     * @return void
     */
    public function testCompletionOnThisPropertyWithCharacter()
    {
        $codebase = $this->project_analyzer->getCodebase();
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
                        $this->cee_me->o
                    }
                }'
        );

        $codebase = $this->project_analyzer->getCodebase();

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $this->assertSame(['B\C', '->'], $codebase->getCompletionDataAtPosition('somefile.php', new Position(16, 40)));
    }

    /**
     * @return void
     */
    public function testCompletionOnThisPropertyWithAnotherCharacter()
    {
        $codebase = $this->project_analyzer->getCodebase();
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
                        $this->cee_me->ot
                    }
                }'
        );

        $codebase = $this->project_analyzer->getCodebase();

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $this->assertSame(null, $codebase->getCompletionDataAtPosition('somefile.php', new Position(16, 41)));
    }

    /**
     * @return void
     */
    public function testCompletionOnTemplatedThisProperty()
    {
        $codebase = $this->project_analyzer->getCodebase();
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace B;

                /** @template T */
                class C {
                    /** @var T */
                    private $t;

                    /** @param T $t */
                    public function __construct($t) {
                        $this->t = $t;
                    }

                    public function otherFunction() : void
                }

                class A {
                    /** @var C<string> */
                    protected $cee_me;

                    public function __construct() {
                        $this->cee_me = new C("hello");
                    }

                    public function foo() : void {
                        $this->cee_me->
                    }
                }'
        );

        $codebase = $this->project_analyzer->getCodebase();

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $completion_data = $codebase->getCompletionDataAtPosition('somefile.php', new Position(25, 39));

        $this->assertSame(['B\C<string>', '->'], $completion_data);

        $completion_items = $codebase->getCompletionItemsForClassishThing($completion_data[0], $completion_data[1]);

        $this->assertCount(3, $completion_items);
    }

    /**
     * @return void
     */
    public function testCompletionOnMethodReturnValue()
    {
        $codebase = $this->project_analyzer->getCodebase();
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace B;
                class A {
                    public function foo() : self {
                        return $this;
                    }
                }

                function (A $a) {
                    $a->foo()->
                }
                '
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();

        $this->analyzeFile('somefile.php', new Context());
        $this->assertSame(['B\A', '->'], $codebase->getCompletionDataAtPosition('somefile.php', new Position(9, 31)));
    }

    /**
     * @return void
     */
    public function testCompletionOnMethodArgument()
    {
        $codebase = $this->project_analyzer->getCodebase();
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace B;
                class A {
                    public function foo(A $a) : self {
                        return $this;
                    }
                }

                class C {}

                function (A $a, C $c) {
                    $a->foo($c->)
                }
                '
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();

        $this->analyzeFile('somefile.php', new Context());
        $this->assertSame(['B\C', '->'], $codebase->getCompletionDataAtPosition('somefile.php', new Position(11, 32)));
    }

    /**
     * @return void
     */
    public function testCompletionOnMethodReturnValueWithArgument()
    {
        $codebase = $this->project_analyzer->getCodebase();
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace B;
                class A {
                    public function foo(A $a) : self {
                        return $this;
                    }
                }

                class C {}

                function (A $a, C $c) {
                    $a->foo($c)->
                }
                '
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();

        $this->analyzeFile('somefile.php', new Context());
        $this->assertSame(['B\A', '->'], $codebase->getCompletionDataAtPosition('somefile.php', new Position(11, 33)));
    }
}
