<?php
namespace Psalm\Tests\LanguageServer;

use LanguageServerProtocol\Position;
use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
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

        $this->assertSame(['B\A&static', '->', 213], $codebase->getCompletionDataAtPosition('somefile.php', new Position(8, 31)));
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

        $this->assertSame(['B\A&static', '->', 220], $codebase->getCompletionDataAtPosition('somefile.php', new Position(8, 31)));
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

        $this->assertSame(['B\A&static', '->', 220], $codebase->getCompletionDataAtPosition('somefile.php', new Position(8, 31)));
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

        $this->assertSame(['B\C', '->', 454], $codebase->getCompletionDataAtPosition('somefile.php', new Position(16, 39)));
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

        $this->assertSame(['B\C', '->', 455], $codebase->getCompletionDataAtPosition('somefile.php', new Position(16, 40)));
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

        $this->assertNull($codebase->getCompletionDataAtPosition('somefile.php', new Position(16, 41)));
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

        $this->assertSame(['B\C<string>', '->', 726], $completion_data);

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

                function foo(A $a) {
                    $a->foo()->
                }
                '
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();

        $this->analyzeFile('somefile.php', new Context());
        $this->assertSame(['B\A', '->', 259], $codebase->getCompletionDataAtPosition('somefile.php', new Position(9, 31)));
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

                function bar(A $a, C $c) {
                    $a->foo($c->)
                }
                '
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();

        $this->analyzeFile('somefile.php', new Context());
        $this->assertSame(['B\C', '->', 298], $codebase->getCompletionDataAtPosition('somefile.php', new Position(11, 32)));
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

                function bar(A $a, C $c) {
                    $a->foo($c)->
                }
                '
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();

        $this->analyzeFile('somefile.php', new Context());
        $this->assertSame(['B\A', '->', 299], $codebase->getCompletionDataAtPosition('somefile.php', new Position(11, 33)));
    }

    /**
     * @return void
     */
    public function testCompletionOnVariableWithWhitespace()
    {
        $codebase = $this->project_analyzer->getCodebase();
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace B;

                class A {}

                function bar(A $a) {
                    $a ->
                }'
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();

        $this->analyzeFile('somefile.php', new Context());
        $this->assertSame(['B\A', '->', 126], $codebase->getCompletionDataAtPosition('somefile.php', new Position(6, 25)));
    }

    /**
     * @return void
     */
    public function testCompletionOnVariableWithWhitespaceAndReturn()
    {
        $codebase = $this->project_analyzer->getCodebase();
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace B;

                class A {}

                function baz(A $a) {
                    $a
                        ->
                }
                '
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();

        $this->analyzeFile('somefile.php', new Context());
        $this->assertSame(['B\A', '->', 150], $codebase->getCompletionDataAtPosition('somefile.php', new Position(7, 26)));
    }

    /**
     * @return void
     */
    public function testCompletionOnMethodReturnValueWithWhitespace()
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

                function bar(A $a) {
                    $a->foo() ->
                }
                '
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();

        $this->analyzeFile('somefile.php', new Context());
        $this->assertSame(['B\A', '->', 261], $codebase->getCompletionDataAtPosition('somefile.php', new Position(10, 32)));
    }

    /**
     * @return void
     */
    public function testCompletionOnMethodReturnValueWithWhitespaceAndReturn()
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

                function baz(A $a) {
                    $a->foo()
                        ->
                }
                '
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();

        $this->analyzeFile('somefile.php', new Context());
        $this->assertSame(['B\A', '->', 285], $codebase->getCompletionDataAtPosition('somefile.php', new Position(11, 26)));
    }

    /**
     * @return void
     */
    public function testCompletionOnMethodReturnValueWhereParamIsClosure()
    {
        $codebase = $this->project_analyzer->getCodebase();
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace B;

                class Collection {
                    public function map(callable $mapper) : self {
                        return $this;
                    }
                }

                function bar(Collection $a) {
                    $a->map(function ($foo) {})->
                }'
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();

        $this->analyzeFile('somefile.php', new Context());
        $this->assertSame(['B\Collection', '->', 312], $codebase->getCompletionDataAtPosition('somefile.php', new Position(10, 49)));
    }

    /**
     * @return void
     */
    public function testCompletionOnMethodReturnValueWhereParamIsClosureWithStmt()
    {
        $codebase = $this->project_analyzer->getCodebase();
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace B;

                class Collection {
                    public function map(callable $mapper) : self {
                        return $this;
                    }
                }

                function baz(Collection $a) {
                    $a->map(function ($foo) {return $foo;})->
                }'
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();

        $this->analyzeFile('somefile.php', new Context());
        $this->assertSame(['B\Collection', '->', 324], $codebase->getCompletionDataAtPosition('somefile.php', new Position(10, 61)));
    }

    public function testCursorPositionOnMethodCompletion(): void
    {
        $codebase = $this->project_analyzer->getCodebase();
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace B;

                class A {
                    public function bar(string $a) {
                        $this->
                    }

                    public function baz() {}
                }'
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();

        $this->analyzeFile('somefile.php', new Context());

        $completion_data = $codebase->getCompletionDataAtPosition('somefile.php', new Position(5, 31));

        $this->assertSame(['B\A&static', '->', 146], $completion_data);

        $completion_items = $codebase->getCompletionItemsForClassishThing($completion_data[0], $completion_data[1]);

        $this->assertCount(2, $completion_items);

        $this->assertSame('bar($0)', $completion_items[0]->insertText);
        $this->assertSame('baz()', $completion_items[1]->insertText);
    }

    /**
     * @return void
     */
    public function testCompletionOnNewExceptionWithoutNamespace()
    {
        $codebase = $this->project_analyzer->getCodebase();
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                function foo() : void {
                    throw new Ex
                }'
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $this->assertSame(['*Ex', 'symbol', 78], $codebase->getCompletionDataAtPosition('somefile.php', new Position(2, 32)));
    }

    /**
     * @return void
     */
    public function testCompletionOnNewExceptionWithNamespaceNoUse()
    {
        $codebase = $this->project_analyzer->getCodebase();
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace Bar;

                function foo() : void {
                    throw new Ex
                }'
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $completion_data = $codebase->getCompletionDataAtPosition('somefile.php', new Position(4, 32));

        $this->assertSame(
            [
                '*Ex',
                'symbol',
                110,
            ],
            $completion_data
        );

        $completion_items = $codebase->getCompletionItemsForPartialSymbol($completion_data[0], $completion_data[2], 'somefile.php');

        $this->assertNotEmpty($completion_items);

        $this->assertSame('Exception', $completion_items[0]->label);
        $this->assertSame('Exception', $completion_items[0]->insertText);

        $this->assertNotNull($completion_items[0]->additionalTextEdits);
        $this->assertCount(1, $completion_items[0]->additionalTextEdits);
        $this->assertSame('use Exception;' . "\n" . "\n", $completion_items[0]->additionalTextEdits[0]->newText);
        $this->assertSame(3, $completion_items[0]->additionalTextEdits[0]->range->start->line);
        $this->assertSame(16, $completion_items[0]->additionalTextEdits[0]->range->start->character);
        $this->assertSame(3, $completion_items[0]->additionalTextEdits[0]->range->end->line);
        $this->assertSame(16, $completion_items[0]->additionalTextEdits[0]->range->end->character);
    }

    /**
     * @return void
     */
    public function testCompletionOnNewExceptionWithNamespaceAndUse()
    {
        $codebase = $this->project_analyzer->getCodebase();
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace Bar;

                use LogicException as LogEx;

                class Alpha {}
                class Antelope {}

                function foo() : void {
                    new ArrayO
                }'
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $completion_data = $codebase->getCompletionDataAtPosition('somefile.php', new Position(9, 30));

        $this->assertSame(
            [
                '*ArrayO',
                'symbol',
                220,
            ],
            $completion_data
        );

        $completion_items = $codebase->getCompletionItemsForPartialSymbol($completion_data[0], $completion_data[2], 'somefile.php');

        $this->assertCount(1, $completion_items);

        $this->assertNotNull($completion_items[0]->additionalTextEdits);
        $this->assertCount(1, $completion_items[0]->additionalTextEdits);
        $this->assertSame("\n" . 'use ArrayObject;', $completion_items[0]->additionalTextEdits[0]->newText);
        $this->assertSame(3, $completion_items[0]->additionalTextEdits[0]->range->start->line);
        $this->assertSame(44, $completion_items[0]->additionalTextEdits[0]->range->start->character);
        $this->assertSame(3, $completion_items[0]->additionalTextEdits[0]->range->end->line);
        $this->assertSame(44, $completion_items[0]->additionalTextEdits[0]->range->end->character);
    }

    /**
     * @return void
     */
    public function testCompletionOnInstanceofWithNamespaceAndUse()
    {
        $codebase = $this->project_analyzer->getCodebase();
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace Bar;

                use LogicException as LogEx;

                class Alpha {}
                class Antelope {}
                class Anteater {}

                function foo($a) : void {
                    if ($a instanceof Ant) {}
                }'
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $completion_data = $codebase->getCompletionDataAtPosition('somefile.php', new Position(10, 41));

        $this->assertSame(
            [
                '*Ant',
                'symbol',
                267,
            ],
            $completion_data
        );

        $completion_items = $codebase->getCompletionItemsForPartialSymbol($completion_data[0], $completion_data[2], 'somefile.php');

        $this->assertCount(2, $completion_items);
    }
}
