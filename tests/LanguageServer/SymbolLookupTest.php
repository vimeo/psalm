<?php
namespace Psalm\Tests\LanguageServer;

use LanguageServerProtocol\Position;
use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\Providers;
use Psalm\Tests\Internal\Provider;
use Psalm\Tests\Progress\VoidProgress;
use Psalm\Tests\TestConfig;

class SymbolLookupTest extends \Psalm\Tests\TestCase
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
            $providers,
            false,
            true,
            ProjectAnalyzer::TYPE_CONSOLE,
            1,
            new VoidProgress()
        );

        $this->project_analyzer->setPhpVersion('7.3');
        $this->project_analyzer->getCodebase()->store_node_types = true;
    }

    /**
     * @return void
     */
    public function testSimpleSymbolLookup()
    {
        $this->addFile(
            'somefile.php',
            '<?php
                namespace B;

                class A {
                    /** @var int|null */
                    protected $a;

                    const BANANA = "🍌";

                    public function foo() : void {
                        $a = 1;
                        echo $a;
                    }
                }

                function bar() : int {
                    return 5;
                }'
        );

        new FileAnalyzer($this->project_analyzer, 'somefile.php', 'somefile.php');

        $codebase = $this->project_analyzer->getCodebase();

        $this->analyzeFile('somefile.php', new Context());

        $this->assertSame('<?php public function foo() : void', $codebase->getSymbolInformation('somefile.php', 'B\A::foo()'));
        $this->assertSame('<?php protected int|null $a', $codebase->getSymbolInformation('somefile.php', 'B\A::$a'));
        $this->assertSame('<?php function B\bar() : int', $codebase->getSymbolInformation('somefile.php', 'B\bar()'));
        $this->assertSame('<?php BANANA', $codebase->getSymbolInformation('somefile.php', 'B\A::BANANA'));
    }

    /**
     * @return void
     */
    public function testSimpleSymbolLocation()
    {
        $this->addFile(
            'somefile.php',
            '<?php
                namespace B;

                class A {
                    /** @var int|null */
                    protected $a;

                    const BANANA = "🍌";

                    public function foo() : void {}
                }

                function bar() : int {
                    return 5;
                }'
        );

        new FileAnalyzer($this->project_analyzer, 'somefile.php', 'somefile.php');

        $codebase = $this->project_analyzer->getCodebase();

        $this->analyzeFile('somefile.php', new Context());

        $method_symbol_location = $codebase->getSymbolLocation('somefile.php', 'B\A::foo()');

        $this->assertNotNull($method_symbol_location);
        $this->assertSame(10, $method_symbol_location->getLineNumber());
        $this->assertSame(37, $method_symbol_location->getColumn());

        $property_symbol_location = $codebase->getSymbolLocation('somefile.php', 'B\A::$a');

        $this->assertNotNull($property_symbol_location);
        $this->assertSame(6, $property_symbol_location->getLineNumber());
        $this->assertSame(31, $property_symbol_location->getColumn());

        $constant_symbol_location = $codebase->getSymbolLocation('somefile.php', 'B\A::BANANA');

        $this->assertNotNull($constant_symbol_location);
        $this->assertSame(8, $constant_symbol_location->getLineNumber());
        $this->assertSame(27, $constant_symbol_location->getColumn());

        $function_symbol_location = $codebase->getSymbolLocation('somefile.php', 'B\bar()');

        $this->assertNotNull($function_symbol_location);
        $this->assertSame(13, $function_symbol_location->getLineNumber());
        $this->assertSame(26, $function_symbol_location->getColumn());
    }

    /**
     * @return void
     */
    public function testSymbolLookupAfterAlteration()
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

                    public function foo() : voi {
                        $a = 1;
                        $b = $this->a;
                        $c = $b;

                        echo $a;
                    }

                    public function bar() : void {
                        $a = 2;
                        echo $a;
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

                    public function foo() : void {
                        $a = 1;
                        $b = $this->a;
                        $c = $b;

                        echo $a;
                    }

                    public function bar() : void {
                        $a = 2;
                        echo $a;
                    }
                }'
        );

        $codebase->reloadFiles($this->project_analyzer, ['somefile.php']);

        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

        $symbol_at_position = $codebase->getReferenceAtPosition('somefile.php', new Position(10, 30));

        $this->assertNotNull($symbol_at_position);

        $this->assertSame('type: int|null', $symbol_at_position[0]);

        $symbol_at_position = $codebase->getReferenceAtPosition('somefile.php', new Position(12, 30));

        $this->assertNotNull($symbol_at_position);

        $this->assertSame('type: int', $symbol_at_position[0]);

        $symbol_at_position = $codebase->getReferenceAtPosition('somefile.php', new Position(17, 30));

        $this->assertNotNull($symbol_at_position);

        $this->assertSame('type: int', $symbol_at_position[0]);
    }
}
