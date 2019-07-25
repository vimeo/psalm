<?php
namespace Psalm\Tests\LanguageServer;

use LanguageServerProtocol\Position;
use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\Providers;
use Psalm\Tests\Internal\Provider;
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
            $providers
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
                }

                function baz(int $a) : int {
                    return $a;
                }

                function qux(int $a, int $b) : int {
                    return $a + $b;
                }'
        );

        new FileAnalyzer($this->project_analyzer, 'somefile.php', 'somefile.php');

        $codebase = $this->project_analyzer->getCodebase();

        $this->analyzeFile('somefile.php', new Context());

        $this->assertSame('<?php public function foo() : void', $codebase->getSymbolInformation('somefile.php', 'B\A::foo()'));
        $this->assertSame('<?php protected int|null $a', $codebase->getSymbolInformation('somefile.php', 'B\A::$a'));
        $this->assertSame('<?php function B\bar() : int', $codebase->getSymbolInformation('somefile.php', 'B\bar()'));
        $this->assertSame('<?php BANANA', $codebase->getSymbolInformation('somefile.php', 'B\A::BANANA'));
        $this->assertSame("<?php function B\baz(\n    int \$a\n) : int", $codebase->getSymbolInformation('somefile.php', 'B\baz()'));
        $this->assertSame("<?php function B\qux(\n    int \$a,\n    int \$b\n) : int", $codebase->getSymbolInformation('somefile.php', 'B\qux()'));
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

                    const BANANA = "nana";

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
        $this->assertSame(16, $function_symbol_location->getLineNumber());
        $this->assertSame(26, $function_symbol_location->getColumn());

        $function_symbol_location = $codebase->getSymbolLocation('somefile.php', '257-259');

        $this->assertNotNull($function_symbol_location);
        $this->assertSame(11, $function_symbol_location->getLineNumber());
        $this->assertSame(25, $function_symbol_location->getColumn());
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

        $this->assertSame('245-246:int|null', $symbol_at_position[0]);

        $symbol_at_position = $codebase->getReferenceAtPosition('somefile.php', new Position(12, 30));

        $this->assertNotNull($symbol_at_position);

        $this->assertSame('213-214:int(1)', $symbol_at_position[0]);

        $symbol_at_position = $codebase->getReferenceAtPosition('somefile.php', new Position(17, 30));

        $this->assertNotNull($symbol_at_position);

        $this->assertSame('425-426:int(2)', $symbol_at_position[0]);
    }

    /**
     * @return void
     */
    public function testGetSymbolPositionMissingArg()
    {
        $codebase = $this->project_analyzer->getCodebase();
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace B;

                class A {
                    public function foo(int $i) : string {
                        return "hello";
                    }

                    public function bar() : void {
                        $this->foo();
                    }
                }'
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $symbol_at_position = $codebase->getReferenceAtPosition('somefile.php', new Position(9, 33));

        $this->assertNotNull($symbol_at_position);

        $this->assertSame('B\A::foo()', $symbol_at_position[0]);
    }

    /**
     * @return void
     */
    public function testGetTypeInDocblock()
    {
        $codebase = $this->project_analyzer->getCodebase();
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace B;

                class A {
                    /** @var \Exception|null */
                    public $prop;
                }'
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $symbol_at_position = $codebase->getReferenceAtPosition('somefile.php', new Position(4, 35));

        $this->assertNotNull($symbol_at_position);

        $this->assertSame('Exception', $symbol_at_position[0]);
    }

    /**
     * @return array<int, array{0: Position, 1: ?string, 2: ?int, 3: ?int}>
     */
    public function providerGetSignatureHelp(): array
    {
        return [
            [new Position(5, 34), null, null, null],
            [new Position(5, 35), 'B\A::foo', 0, 2],
            [new Position(5, 36), null, null, null],
            [new Position(6, 34), null, null, null],
            [new Position(6, 35), 'B\A::foo', 0, 2],
            [new Position(6, 40), 'B\A::foo', 0, 2],
            [new Position(6, 41), 'B\A::foo', 1, 2],
            [new Position(6, 47), 'B\A::foo', 1, 2],
            [new Position(6, 48), null, null, null],
            [new Position(7, 40), 'B\A::foo', 0, 2],
            [new Position(7, 41), 'B\A::foo', 1, 2],
            [new Position(7, 42), 'B\A::foo', 1, 2],
            [new Position(8, 40), 'B\A::foo', 0, 2],
            [new Position(8, 46), 'B\A::bar', 0, 1],
            [new Position(8, 47), 'B\A::foo', 0, 2],
            [new Position(10, 40), 'B\A::staticfoo', 0, 1],
            #[new Position(12, 28), 'B\foo', 0, 1],
            [new Position(14, 30), 'B\A::__construct', 0, 0],
            [new Position(16, 31), 'strlen', 0, 1],
        ];
    }

    /**
     * @dataProvider providerGetSignatureHelp
     */
    public function testGetSignatureHelp(
        Position $position,
        ?string $expected_symbol,
        ?int $expected_argument_number,
        ?int $expected_param_count
    ): void {
        $codebase = $this->project_analyzer->getCodebase();
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace B;

                class A {
                    public function foo(string $a, array $b) {
                        $this->foo();
                        $this->foo("Foo", "Bar");
                        $this->foo("Foo", );
                        $this->foo($this->bar());

                        self::staticFoo();

                        foo();

                        new A();

                        strlen();
                    }

                    public function bar(string $a) {}

                    public static function staticFoo(string $a) {}

                    public function __construct() {}
                }

                function foo(string $a) {
                }'
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $reference_location = $codebase->getFunctionArgumentAtPosition('somefile.php', $position);

        if ($expected_symbol !== null) {
            $this->assertNotNull($reference_location);
            list($symbol, $argument_number) = $reference_location;
            $this->assertSame($expected_symbol, $symbol);
            $this->assertSame($expected_argument_number, $argument_number);

            $symbol_information = $codebase->getSignatureInformation($reference_location[0]);

            if ($expected_param_count === null) {
                $this->assertNull($symbol_information);
            } else {
                $this->assertNotNull($symbol_information);
                $this->assertNotNull($symbol_information->parameters);
                $this->assertCount($expected_param_count, $symbol_information->parameters);
            }
        } else {
            $this->assertNull($reference_location);
        }
    }
}
