<?php

declare(strict_types=1);

namespace Psalm\Tests\LanguageServer;

use LanguageServerProtocol\Position;
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
use Psalm\Type;

use function count;

class CompletionTest extends TestCase
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

    public function testCompletionOnThisWithNoAssignment(): void
    {
        $codebase = $this->codebase;
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
                }',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $this->assertSame(['B\A&static', '->', 213], $codebase->getCompletionDataAtPosition('somefile.php', new Position(8, 31)));
    }

    public function testCompletionOnThisWithAssignmentBelow(): void
    {
        $codebase = $this->codebase;
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
                }',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $this->assertSame(['B\A&static', '->', 220], $codebase->getCompletionDataAtPosition('somefile.php', new Position(8, 31)));
    }

    public function testCompletionOnThisWithIfBelow(): void
    {
        $codebase = $this->codebase;
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
                }',
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
                }',
        );
        $codebase->reloadFiles($this->project_analyzer, ['somefile.php']);
        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

        $this->assertSame(['B\A&static', '->', 220], $codebase->getCompletionDataAtPosition('somefile.php', new Position(8, 31)));
    }

    public function testCompletionOnSelfWithIfBelow(): void
    {
        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace B;

                class A {
                    /** @var int|null */
                    protected static $a;

                    public function foo() : self {
                        A

                        if(rand(0, 1)) {}
                    }
                }',
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
                    protected static $a;

                    public function foo() : self {
                        A::

                        if(rand(0, 1)) {}
                    }
                }',
        );
        $codebase->reloadFiles($this->project_analyzer, ['somefile.php']);
        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

        $this->assertSame(['B\A', '::', 223], $codebase->getCompletionDataAtPosition('somefile.php', new Position(8, 27)));
    }

    public function testCompletionOnSelfWithListBelow(): void
    {
        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace B;

                class A {
                    /** @var int|null */
                    protected static $a;

                    public function foo() : self {
                        A

                        list($a, $b) = $c;
                    }
                }',
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
                    protected static $a;

                    public function foo() : self {
                        A::

                        list($a, $b) = $c;
                    }
                }',
        );
        $codebase->reloadFiles($this->project_analyzer, ['somefile.php']);
        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

        $this->assertSame(['B\A', '::', 223], $codebase->getCompletionDataAtPosition('somefile.php', new Position(8, 27)));
    }

    public function testCompletionOnThisProperty(): void
    {
        $codebase = $this->codebase;
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
                }',
        );

        $codebase = $this->codebase;

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $this->assertSame(['B\C', '->', 454], $codebase->getCompletionDataAtPosition('somefile.php', new Position(16, 39)));
    }

    public function testCompletionOnThisPropertyWithCharacter(): void
    {
        $codebase = $this->codebase;
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
                }',
        );

        $codebase = $this->codebase;

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $this->assertSame(['B\C', '->', 455], $codebase->getCompletionDataAtPosition('somefile.php', new Position(16, 40)));
    }

    public function testCompletionOnThisPropertyWithAnotherCharacter(): void
    {
        $codebase = $this->codebase;
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
                }',
        );

        $codebase = $this->codebase;

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $this->assertNull($codebase->getCompletionDataAtPosition('somefile.php', new Position(16, 41)));
    }

    public function testCompletionOnTemplatedThisProperty(): void
    {
        $codebase = $this->codebase;
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
                }',
        );

        $codebase = $this->codebase;

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $completion_data = $codebase->getCompletionDataAtPosition('somefile.php', new Position(25, 39));

        $this->assertSame(['B\C<string>', '->', 726], $completion_data);

        $completion_items = $codebase->getCompletionItemsForClassishThing($completion_data[0], $completion_data[1], true);

        $this->assertCount(3, $completion_items);
    }

    public function testCompletionOnMethodReturnValue(): void
    {
        $codebase = $this->codebase;
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
                ',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();

        $this->analyzeFile('somefile.php', new Context());
        $this->assertSame(['B\A', '->', 259], $codebase->getCompletionDataAtPosition('somefile.php', new Position(9, 31)));
    }

    public function testCompletionOnMethodArgument(): void
    {
        $codebase = $this->codebase;
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
                ',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();

        $this->analyzeFile('somefile.php', new Context());
        $this->assertSame(['B\C', '->', 298], $codebase->getCompletionDataAtPosition('somefile.php', new Position(11, 32)));
    }

    public function testCompletionOnMethodReturnValueWithArgument(): void
    {
        $codebase = $this->codebase;
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
                ',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();

        $this->analyzeFile('somefile.php', new Context());
        $this->assertSame(['B\A', '->', 299], $codebase->getCompletionDataAtPosition('somefile.php', new Position(11, 33)));
    }

    public function testCompletionOnVariableWithWhitespace(): void
    {
        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace B;

                class A {}

                function bar(A $a) {
                    $a ->
                }',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();

        $this->analyzeFile('somefile.php', new Context());
        $this->assertSame(['B\A', '->', 126], $codebase->getCompletionDataAtPosition('somefile.php', new Position(6, 25)));
    }

    public function testCompletionOnVariableWithWhitespaceAndReturn(): void
    {
        $codebase = $this->codebase;
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
                ',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();

        $this->analyzeFile('somefile.php', new Context());
        $this->assertSame(['B\A', '->', 150], $codebase->getCompletionDataAtPosition('somefile.php', new Position(7, 26)));
    }

    public function testCompletionOnMethodReturnValueWithWhitespace(): void
    {
        $codebase = $this->codebase;
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
                ',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();

        $this->analyzeFile('somefile.php', new Context());
        $this->assertSame(['B\A', '->', 261], $codebase->getCompletionDataAtPosition('somefile.php', new Position(10, 32)));
    }

    public function testCompletionOnMethodReturnValueWithWhitespaceAndReturn(): void
    {
        $codebase = $this->codebase;
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
                ',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();

        $this->analyzeFile('somefile.php', new Context());
        $this->assertSame(['B\A', '->', 285], $codebase->getCompletionDataAtPosition('somefile.php', new Position(11, 26)));
    }

    public function testCompletionOnMethodReturnValueWhereParamIsClosure(): void
    {
        $codebase = $this->codebase;
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
                }',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();

        $this->analyzeFile('somefile.php', new Context());
        $this->assertSame(['B\Collection', '->', 312], $codebase->getCompletionDataAtPosition('somefile.php', new Position(10, 49)));
    }

    public function testCompletionOnMethodReturnValueWhereParamIsClosureWithStmt(): void
    {
        $codebase = $this->codebase;
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
                }',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();

        $this->analyzeFile('somefile.php', new Context());
        $this->assertSame(['B\Collection', '->', 324], $codebase->getCompletionDataAtPosition('somefile.php', new Position(10, 61)));
    }

    public function testCursorPositionOnMethodCompletion(): void
    {
        $codebase = $this->codebase;
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
                }',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();

        $this->analyzeFile('somefile.php', new Context());

        $completion_data = $codebase->getCompletionDataAtPosition('somefile.php', new Position(5, 31));

        $this->assertSame(['B\A&static', '->', 146], $completion_data);

        $completion_items = $codebase->getCompletionItemsForClassishThing($completion_data[0], $completion_data[1], true);

        $this->assertCount(2, $completion_items);

        $this->assertSame('bar($0)', $completion_items[0]->insertText);
        $this->assertSame('baz()', $completion_items[1]->insertText);
    }

    public function testCompletionOnNewExceptionWithoutNamespace(): void
    {
        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                function foo() : void {
                    throw new Ex
                }',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $this->assertSame(['*-Ex', 'symbol', 78], $codebase->getCompletionDataAtPosition('somefile.php', new Position(2, 32)));
    }

    public function testCompletionOnNewExceptionWithNamespaceNoUse(): void
    {
        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace Bar;

                function foo() : void {
                    throw new Ex
                }',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $completion_data = $codebase->getCompletionDataAtPosition('somefile.php', new Position(4, 32));

        $this->assertSame(
            [
                '*Bar-Ex',
                'symbol',
                110,
            ],
            $completion_data,
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

    public function testCompletionOnNewExceptionWithNamespaceAndUse(): void
    {
        $codebase = $this->codebase;
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
                }',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $completion_data = $codebase->getCompletionDataAtPosition('somefile.php', new Position(9, 30));

        $this->assertSame(
            [
                '*Bar-ArrayO',
                'symbol',
                220,
            ],
            $completion_data,
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

    public function testCompletionOnNamespaceWithFullyQualified(): void
    {
        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace Bar\Baz\Bat;

                class B {
                    public function foo() : void {
                        \Ex
                    }
                }',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $completion_data = $codebase->getCompletionDataAtPosition('somefile.php', new Position(5, 27));

        $this->assertSame(
            [
                '*\Ex',
                'symbol',
                150,
            ],
            $completion_data,
        );

        $completion_items = $codebase->getCompletionItemsForPartialSymbol($completion_data[0], $completion_data[2], 'somefile.php');

        $this->assertNotEmpty($completion_items);

        $this->assertSame('Exception', $completion_items[0]->label);
        $this->assertSame('\Exception', $completion_items[0]->insertText);

        $this->assertEmpty($completion_items[0]->additionalTextEdits);
    }

    public function testCompletionOnExceptionWithNamespaceAndUseInClass(): void
    {
        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace Bar\Baz\Bat;

                class B {
                    public function foo() : void {
                        Ex
                    }
                }',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $completion_data = $codebase->getCompletionDataAtPosition('somefile.php', new Position(5, 26));

        $this->assertSame(
            [
                '*Bar\Baz\Bat-Ex',
                'symbol',
                149,
            ],
            $completion_data,
        );

        $completion_items = $codebase->getCompletionItemsForPartialSymbol($completion_data[0], $completion_data[2], 'somefile.php');

        $this->assertNotEmpty($completion_items);

        $this->assertSame('Exception', $completion_items[0]->label);
        $this->assertSame('Exception', $completion_items[0]->insertText);

        $this->assertNotNull($completion_items[0]->additionalTextEdits);
        $this->assertCount(1, $completion_items[0]->additionalTextEdits);
    }

    public function testCompletionForFunctionNames(): void
    {
        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace Bar;

                /**
                 * My Function in a Bar
                 *
                 * @return void
                 */
                function my_function_in_bar() : void {

                }

                my_function_in',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $completion_data = $codebase->getCompletionDataAtPosition('somefile.php', new Position(12, 30));
        $this->assertNotNull($completion_data);
        $this->assertSame('*Bar-my_function_in', $completion_data[0]);

        $completion_items = $codebase->getCompletionItemsForPartialSymbol($completion_data[0], $completion_data[2], 'somefile.php');
        $this->assertSame(1, count($completion_items));
        $this->assertEquals('My Function in a Bar', $completion_items[0]->documentation);
    }

    public function testCompletionForNamespacedOverriddenFunctionNames(): void
    {
        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace Bar;

                function strlen() : void {

                }

                strlen',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $completion_data = $codebase->getCompletionDataAtPosition('somefile.php', new Position(7, 22));
        $this->assertNotNull($completion_data);
        $this->assertSame('*Bar-strlen', $completion_data[0]);

        $completion_items = $codebase->getCompletionItemsForPartialSymbol($completion_data[0], $completion_data[2], 'somefile.php');
        $this->assertSame(2, count($completion_items));
    }

    public function testCompletionForFunctionNamesRespectUsedNamespaces(): void
    {
        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace Bar;
                use phpunit\framework as phpf;
                atleaston',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $completion_data = $codebase->getCompletionDataAtPosition('somefile.php', new Position(3, 25));
        $this->assertNotNull($completion_data);
        $this->assertSame('*Bar-atleaston', $completion_data[0]);

        $completion_items = $codebase->getCompletionItemsForPartialSymbol($completion_data[0], $completion_data[2], 'somefile.php');
        $this->assertSame(1, count($completion_items));
        $this->assertSame('phpf\\atLeastOnce', $completion_items[0]->label);
    }

    public function testCompletionForFunctionNamesRespectCase(): void
    {
        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace Bar;
                use phpunit\framework as phpf;
                Atleaston',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $completion_data = $codebase->getCompletionDataAtPosition('somefile.php', new Position(3, 25));
        $this->assertNotNull($completion_data);
        $this->assertSame('*Bar-Atleaston', $completion_data[0]);

        $completion_items = $codebase->getCompletionItemsForPartialSymbol($completion_data[0], $completion_data[2], 'somefile.php');
        $this->assertSame(0, count($completion_items));
    }

    public function testGetMatchingFunctionNames(): void
    {
        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php

            function my_function() {
            }',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $functions = $codebase->functions->getMatchingFunctionNames('*-array_su', 0, 'somefile.php', $codebase);
        $this->assertSame(1, count($functions));

        $functions = $codebase->functions->getMatchingFunctionNames('*-my_funct', 0, 'somefile.php', $codebase);
        $this->assertSame(1, count($functions));
    }

    public function testGetMatchingFunctionNamesFromPredefinedFunctions(): void
    {
        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $functions = $codebase->functions->getMatchingFunctionNames('*-urlencod', 0, 'somefile.php', $codebase);
        $this->assertSame(1, count($functions));
    }

    public function testGetMatchingFunctionNamesFromUsedFunction(): void
    {
        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php

            namespace Foo;
            use function phpunit\framework\atleastonce;
            ',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $functions = $codebase->functions->getMatchingFunctionNames('*Foo-atleaston', 81, 'somefile.php', $codebase);
        $this->assertSame(1, count($functions));
    }

    public function testGetMatchingFunctionNamesFromUsedNamespace(): void
    {
        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php

            namespace Foo;
            use phpunit\framework;
            ',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $functions = $codebase->functions->getMatchingFunctionNames('*Foo-atleaston', 81, 'somefile.php', $codebase);
        $this->assertSame(1, count($functions));
    }

    public function testGetMatchingFunctionNamesFromUsedNamespaceRespectFirstCharCase(): void
    {
        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php

            namespace Foo;
            use phpunit\framework;
            ',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $functions = $codebase->functions->getMatchingFunctionNames('*Foo-Atleaston', 81, 'somefile.php', $codebase);
        $this->assertSame(0, count($functions));
    }

    public function testGetMatchingFunctionNamesWithNamespace(): void
    {
        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
            namespace Foo;
            function my_function() {
            }',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $functions = $codebase->functions->getMatchingFunctionNames('*Foo-array_su', 45, 'somefile.php', $codebase);
        $this->assertSame(1, count($functions));

        $functions = $codebase->functions->getMatchingFunctionNames('Foo-my_funct', 45, 'somefile.php', $codebase);
        $this->assertSame(1, count($functions));
    }

    public function testCompletionOnInstanceofWithNamespaceAndUse(): void
    {
        $codebase = $this->codebase;
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
                }',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $completion_data = $codebase->getCompletionDataAtPosition('somefile.php', new Position(10, 41));

        $this->assertSame(
            [
                '*Bar-Ant',
                'symbol',
                267,
            ],
            $completion_data,
        );

        $completion_items = $codebase->getCompletionItemsForPartialSymbol($completion_data[0], $completion_data[2], 'somefile.php');

        $this->assertCount(2, $completion_items);
    }

    public function testCompletionOnClassReference(): void
    {

        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace Bar;

                class Alpha {
                    const FOO = "123";
                    static function add() : void {
                    }
                }
                Alpha::',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $completion_data = $codebase->getCompletionDataAtPosition('somefile.php', new Position(8, 23));

        $this->assertSame(['Bar\Alpha', '::', 221], $completion_data);

        $completion_items = $codebase->getCompletionItemsForClassishThing($completion_data[0], $completion_data[1], true);
        $this->assertCount(2, $completion_items);
    }

    public function testCompletionOnClassInstanceReferenceWithAssignmentAfter(): void
    {

        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace Bar;

                class Alpha {
                    public function add() : void {}
                }

                $alpha = new Alpha;

                $alpha->

                $a = 5;',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $completion_data = $codebase->getCompletionDataAtPosition('somefile.php', new Position(9, 24));

        $this->assertSame(['Bar\Alpha', '->', 200], $completion_data);

        $completion_items = $codebase->getCompletionItemsForClassishThing($completion_data[0], $completion_data[1], true);
        $this->assertCount(1, $completion_items);
    }

    public function testCompletionOnClassStaticReferenceWithAssignmentAfter(): void
    {

        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace Bar;

                class Alpha {
                    const FOO = "123";
                    static function add() : void {}
                }

                Alpha::

                $a = 5;',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $completion_data = $codebase->getCompletionDataAtPosition('somefile.php', new Position(8, 23));

        $this->assertSame(['Bar\Alpha', '::', 201], $completion_data);

        $completion_items = $codebase->getCompletionItemsForClassishThing($completion_data[0], $completion_data[1], true);
        $this->assertCount(2, $completion_items);
    }

    public function testNoCrashOnLoopId(): void
    {
        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                for ($x = 0; $x <= 10; $x++) {}',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());
    }

    public function testCompletionOnArrayKey(): void
    {

        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                $my_array = ["foo" => 1, "bar" => 2];
                $my_array[]
                ',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $completion_data = $codebase->getCompletionDataAtPosition('somefile.php', new Position(2, 26));
        $this->assertSame(
            [
                'array{bar: 2, foo: 1}',
                '[',
                86,
            ],
            $completion_data,
        );

        $completion_items = $codebase->getCompletionItemsForArrayKeys($completion_data[0]);

        $this->assertCount(2, $completion_items);
    }

    public function testCompletionOnNestedArrayKey(): void
    {
        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                $my_array = ["foo" => ["bar" => 1]];
                $my_array["foo"][]
                ',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $completion_data = $codebase->getCompletionDataAtPosition('somefile.php', new Position(2, 33));
        $this->assertSame(
            [
                'array{bar: 1}',
                '[',
                92,
            ],
            $completion_data,
        );

        $completion_items = $codebase->getCompletionItemsForArrayKeys($completion_data[0]);

        $this->assertCount(1, $completion_items);
    }

    public function testTypeContextForFunctionArgument(): void
    {

        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace Bar;
                function my_func(string $arg_a, bool $arg_b) : string {
                }

                my_func()',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $type = $codebase->getTypeContextAtPosition('somefile.php', new Position(5, 24));
        $this->assertSame('string', (string) $type);
    }

    public function testTypeContextForFunctionArgumentWithWhiteSpace(): void
    {
        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace Bar;
                function my_func(string $arg_a, bool $arg_b) : string {
                }

                my_func( "yes", )',
        );

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $type = $codebase->getTypeContextAtPosition('somefile.php', new Position(5, 32));
        $this->assertSame('bool', (string) $type);
    }

    public function testCallStaticInInstance(): void
    {
        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class Foo
                {
                    public function testFoo() {
                        $this->
                    }

                    public static function bar() : void {}

                    public function baz() : void {}
                }',
        );

        $codebase = $this->codebase;

        $codebase->file_provider->openFile('somefile.php');
        $codebase->scanFiles();
        $this->analyzeFile('somefile.php', new Context());

        $completion_data = $codebase->getCompletionDataAtPosition('somefile.php', new Position(4, 31));

        $this->assertSame(['Foo&static', '->', 129], $completion_data);

        $completion_items = $codebase->getCompletionItemsForClassishThing($completion_data[0], $completion_data[1], true);

        $this->assertCount(3, $completion_items);
    }

    public function testCompletionsForType(): void
    {
        $codebase = $this->codebase;
        $config = $codebase->config;
        $config->throw_exception = false;

        $completion_items = $codebase->getCompletionItemsForType(Type::parseString('bool'));
        $this->assertCount(2, $completion_items);

        $completion_items = $codebase->getCompletionItemsForType(Type::parseString('true'));
        $this->assertCount(1, $completion_items);

        $completion_items = $codebase->getCompletionItemsForType(Type::parseString("'yes'|'no'"));
        $this->assertCount(2, $completion_items);

        $completion_items = $codebase->getCompletionItemsForType(Type::parseString("1|2|3"));
        $this->assertCount(3, $completion_items);

        // Floats not supported.
        $completion_items = $codebase->getCompletionItemsForType(Type::parseString("1.0"));
        $this->assertCount(0, $completion_items);

        $completion_items = $codebase->getCompletionItemsForType(Type::parseString("DateTime::RFC3339"));
        $this->assertCount(1, $completion_items);
    }
}
