<?php

declare(strict_types=1);

namespace Psalm\Tests\LanguageServer;

use Amp\DeferredFuture;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\LanguageServer\ClientConfiguration;
use Psalm\Internal\LanguageServer\LanguageServer;
use Psalm\Internal\LanguageServer\Message;
use Psalm\Internal\LanguageServer\PathMapper;
use Psalm\Internal\LanguageServer\Progress;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\IssueBuffer;
use Psalm\Tests\AsyncTestCase;
use Psalm\Tests\Internal\Provider\FakeFileReferenceCacheProvider;
use Psalm\Tests\Internal\Provider\ParserInstanceCacheProvider;
use Psalm\Tests\Internal\Provider\ProjectCacheProvider;
use Psalm\Tests\LanguageServer\Message as MessageBody;
use Psalm\Tests\LanguageServer\MockProtocolStream;
use Psalm\Tests\TestConfig;

use function getcwd;
use function rand;

class DiagnosticTest extends AsyncTestCase
{
    protected Codebase $codebase;
    private int $increment = 0;

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

        $this->project_analyzer->setPhpVersion('7.4', 'tests');
        $this->project_analyzer->getCodebase()->store_node_types = true;
    }

    public function testSnippetSupportDisabled(): void
    {
        // Create a new promisor
        $deferred = new DeferredFuture;

        $this->setTimeout(5000);
        $clientConfiguration = new ClientConfiguration();

        $read = new MockProtocolStream();
        $write = new MockProtocolStream();

        $array = $this->generateInitializeRequest();
        /**  @psalm-suppress MixedArrayAssignment */
        $array['params']['capabilities']['textDocument']['completion']['completionItem']['snippetSupport'] = false;
        $read->write(new Message(MessageBody::parseArray($array)));

        $server = new LanguageServer(
            $read,
            $write,
            $this->project_analyzer,
            $this->codebase,
            $clientConfiguration,
            new Progress,
            new PathMapper((string) getcwd(), (string) getcwd()),
        );

        $write->on('message', function (Message $message) use ($deferred, $server): void {
            /** @psalm-suppress NullPropertyFetch,PossiblyNullPropertyFetch,UndefinedPropertyFetch */
            if ($message->body->method === 'telemetry/event' && ($message->body->params->message ?? null) === 'initialized') {
                $this->assertFalse($server->clientCapabilities->textDocument->completion->completionItem->snippetSupport);
                $deferred->complete(null);
                return;
            }

            /** @psalm-suppress NullPropertyFetch,PossiblyNullPropertyFetch */
            if ($message->body->method === '$/progress'
                && ($message->body->params->value->kind ?? null) === 'end'
                && ($message->body->params->value->message ?? null) === 'initialized'
            ) {
                $this->assertFalse($server->clientCapabilities->textDocument->completion->completionItem->snippetSupport);
                $deferred->complete(null);
                return;
            }
        });

        $deferred->getFuture()->await();
    }

    /**
     * @psalm-suppress UnusedMethod
     */
    public function jestRun(): void
    {
        $config = $this->codebase->config;
        $config->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '',
        );

        $issues = $this->changeFile(
            'somefile.php',
            '<?php
            class SomeController
            {
                public function __construct()
                {
                }
            }',
        );

        $this->assertEmpty($issues);

        $issues = $this->changeFile(
            'somefile.php',
            '<?php
            class SomeController
            {
                public function __construct()
                {
                    strpos("", "");
                }
            }',
        );

        $this->assertArrayHasKey('somefile.php', $issues);
        $this->assertSame('Argument 1 of strpos expects a non-literal value, "" provided', $issues['somefile.php'][0]->message);

        $issues = $this->changeFile(
            'somefile.php',
            '<?php
            class SomeController
            {
                public function __construct()
                {
                    strpos("", "");

                    strpos("", "");
                }

                public function foobar(): void {}
            }',
        );

        $this->assertArrayHasKey('somefile.php', $issues);
        $this->assertSame('Argument 1 of strpos expects a non-literal value, "" provided', $issues['somefile.php'][0]->message);
        $this->assertSame('Argument 1 of strpos expects a non-literal value, "" provided', $issues['somefile.php'][1]->message);

        $issues = $this->changeFile(
            'somefile.php',
            '<?php
            class SomeController
            {
                public function __construct()
                {
                    $str = __DIR__;

                    strpos("", "");

                    strpos("", "");

                }

                public function foobar(): void {}
            }',
        );

        $this->assertArrayHasKey('somefile.php', $issues);

        $issues = $this->changeFile(
            'somefile.php',
            '<?php
            class SomeController
            {
                public function __construct()
                {
                    $str = __DIR__;

                    strpos($str, "");

                    strpos("", "");

                }

                public function foobar(): void {}
            }',
        );

        $this->assertArrayHasKey('somefile.php', $issues);

        $issues = $this->changeFile(
            'somefile.php',
            '<?php
            class SomeController
            {
                public function __construct()
                {
                    $str = __DIR__;
                    echo strpos($str, "");
                    strpos("", "");
                }

                public function foobar(): void {}
            }',
        );

        $this->assertArrayHasKey('somefile.php', $issues);

        $issues = $this->changeFile(
            'somefile.php',
            '<?php
            class SomeController
            {
                public function __construct()
                {





                    echo __DIR__;
                }

                public function foobar(): void {}
            }',
        );

        $this->assertEmpty($issues);
    }

     /**
     * @return array<string, list<IssueData>>
     */
    private function changeFile(string $file_path, string $contents): array
    {
        $this->codebase->addTemporaryFileChanges(
            $file_path,
            $contents,
            $this->increment,
        );

        $this->codebase->reloadFiles(
            $this->project_analyzer,
            [$file_path],
        );
        $this->codebase->analyzer->addFilesToAnalyze(
            [$file_path => $file_path],
        );
        $this->codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

        $this->increment++;

        return IssueBuffer::clear();
    }

    private function generateInitializeRequest(): array
    {
        return [
          'method' => 'initialize',
          'params' => [
              'processId' => rand(),
              'locale' => 'en-us',
              'capabilities' => [
                'workspace' => [
                  'applyEdit' => true,
                  'workspaceEdit' => [
                    'documentChanges' => true,
                    'resourceOperations' => [
                      0 => 'create',
                      1 => 'rename',
                      2 => 'delete',
                    ],
                    'failureHandling' => 'textOnlyTransactional',
                    'normalizesLineEndings' => true,
                    'changeAnnotationSupport' => [
                      'groupsOnLabel' => true,
                    ],
                  ],
                  'didChangeConfiguration' => [
                    'dynamicRegistration' => true,
                  ],
                  'didChangeWatchedFiles' => [
                    'dynamicRegistration' => true,
                  ],
                  'symbol' => [
                    'dynamicRegistration' => true,
                    'symbolKind' => [
                      'valueSet' => [
                        0 => 1,
                        1 => 2,
                        2 => 3,
                        3 => 4,
                        4 => 5,
                        5 => 6,
                        6 => 7,
                        7 => 8,
                        8 => 9,
                        9 => 10,
                        10 => 11,
                        11 => 12,
                        12 => 13,
                        13 => 14,
                        14 => 15,
                        15 => 16,
                        16 => 17,
                        17 => 18,
                        18 => 19,
                        19 => 20,
                        20 => 21,
                        21 => 22,
                        22 => 23,
                        23 => 24,
                        24 => 25,
                        25 => 26,
                      ],
                    ],
                    'tagSupport' => [
                      'valueSet' => [
                        0 => 1,
                      ],
                    ],
                  ],
                  'codeLens' => [
                    'refreshSupport' => true,
                  ],
                  'executeCommand' => [
                    'dynamicRegistration' => true,
                  ],
                  'configuration' => true,
                  'workspaceFolders' => true,
                  'semanticTokens' => [
                    'refreshSupport' => true,
                  ],
                  'fileOperations' => [
                    'dynamicRegistration' => true,
                    'didCreate' => true,
                    'didRename' => true,
                    'didDelete' => true,
                    'willCreate' => true,
                    'willRename' => true,
                    'willDelete' => true,
                  ],
                ],
                'textDocument' => [
                  'publishDiagnostics' => [
                    'relatedInformation' => true,
                    'versionSupport' => false,
                    'tagSupport' => [
                      'valueSet' => [
                        0 => 1,
                        1 => 2,
                      ],
                    ],
                    'codeDescriptionSupport' => true,
                    'dataSupport' => true,
                  ],
                  'synchronization' => [
                    'dynamicRegistration' => true,
                    'willSave' => true,
                    'willSaveWaitUntil' => true,
                    'didSave' => true,
                  ],
                  'completion' => [
                    'dynamicRegistration' => true,
                    'contextSupport' => true,
                    'completionItem' => [
                      'snippetSupport' => true,
                      'commitCharactersSupport' => true,
                      'documentationFormat' => [
                        0 => 'markdown',
                        1 => 'plaintext',
                      ],
                      'deprecatedSupport' => true,
                      'preselectSupport' => true,
                      'tagSupport' => [
                        'valueSet' => [
                          0 => 1,
                        ],
                      ],
                      'insertReplaceSupport' => true,
                      'resolveSupport' => [
                        'properties' => [
                          0 => 'documentation',
                          1 => 'detail',
                          2 => 'additionalTextEdits',
                        ],
                      ],
                      'insertTextModeSupport' => [
                        'valueSet' => [
                          0 => 1,
                          1 => 2,
                        ],
                      ],
                    ],
                    'completionItemKind' => [
                      'valueSet' => [
                        0 => 1,
                        1 => 2,
                        2 => 3,
                        3 => 4,
                        4 => 5,
                        5 => 6,
                        6 => 7,
                        7 => 8,
                        8 => 9,
                        9 => 10,
                        10 => 11,
                        11 => 12,
                        12 => 13,
                        13 => 14,
                        14 => 15,
                        15 => 16,
                        16 => 17,
                        17 => 18,
                        18 => 19,
                        19 => 20,
                        20 => 21,
                        21 => 22,
                        22 => 23,
                        23 => 24,
                        24 => 25,
                      ],
                    ],
                  ],
                  'hover' => [
                    'dynamicRegistration' => true,
                    'contentFormat' => [
                      0 => 'markdown',
                      1 => 'plaintext',
                    ],
                  ],
                  'signatureHelp' => [
                    'dynamicRegistration' => true,
                    'signatureInformation' => [
                      'documentationFormat' => [
                        0 => 'markdown',
                        1 => 'plaintext',
                      ],
                      'parameterInformation' => [
                        'labelOffsetSupport' => true,
                      ],
                      'activeParameterSupport' => true,
                    ],
                    'contextSupport' => true,
                  ],
                  'definition' => [
                    'dynamicRegistration' => true,
                    'linkSupport' => true,
                  ],
                  'references' => [
                    'dynamicRegistration' => true,
                  ],
                  'documentHighlight' => [
                    'dynamicRegistration' => true,
                  ],
                  'documentSymbol' => [
                    'dynamicRegistration' => true,
                    'symbolKind' => [
                      'valueSet' => [
                        0 => 1,
                        1 => 2,
                        2 => 3,
                        3 => 4,
                        4 => 5,
                        5 => 6,
                        6 => 7,
                        7 => 8,
                        8 => 9,
                        9 => 10,
                        10 => 11,
                        11 => 12,
                        12 => 13,
                        13 => 14,
                        14 => 15,
                        15 => 16,
                        16 => 17,
                        17 => 18,
                        18 => 19,
                        19 => 20,
                        20 => 21,
                        21 => 22,
                        22 => 23,
                        23 => 24,
                        24 => 25,
                        25 => 26,
                      ],
                    ],
                    'hierarchicalDocumentSymbolSupport' => true,
                    'tagSupport' => [
                      'valueSet' => [
                        0 => 1,
                      ],
                    ],
                    'labelSupport' => true,
                  ],
                  'codeAction' => [
                    'dynamicRegistration' => true,
                    'isPreferredSupport' => true,
                    'disabledSupport' => true,
                    'dataSupport' => true,
                    'resolveSupport' => [
                      'properties' => [
                        0 => 'edit',
                      ],
                    ],
                    'codeActionLiteralSupport' => [
                      'codeActionKind' => [
                        'valueSet' => [
                          0 => '',
                          1 => 'quickfix',
                          2 => 'refactor',
                          3 => 'refactor.extract',
                          4 => 'refactor.inline',
                          5 => 'refactor.rewrite',
                          6 => 'source',
                          7 => 'source.organizeImports',
                        ],
                      ],
                    ],
                    'honorsChangeAnnotations' => false,
                  ],
                  'codeLens' => [
                    'dynamicRegistration' => true,
                  ],
                  'formatting' => [
                    'dynamicRegistration' => true,
                  ],
                  'rangeFormatting' => [
                    'dynamicRegistration' => true,
                  ],
                  'onTypeFormatting' => [
                    'dynamicRegistration' => true,
                  ],
                  'rename' => [
                    'dynamicRegistration' => true,
                    'prepareSupport' => true,
                    'prepareSupportDefaultBehavior' => 1,
                    'honorsChangeAnnotations' => true,
                  ],
                  'documentLink' => [
                    'dynamicRegistration' => true,
                    'tooltipSupport' => true,
                  ],
                  'typeDefinition' => [
                    'dynamicRegistration' => true,
                    'linkSupport' => true,
                  ],
                  'implementation' => [
                    'dynamicRegistration' => true,
                    'linkSupport' => true,
                  ],
                  'colorProvider' => [
                    'dynamicRegistration' => true,
                  ],
                  'foldingRange' => [
                    'dynamicRegistration' => true,
                    'rangeLimit' => 5000,
                    'lineFoldingOnly' => true,
                  ],
                  'declaration' => [
                    'dynamicRegistration' => true,
                    'linkSupport' => true,
                  ],
                  'selectionRange' => [
                    'dynamicRegistration' => true,
                  ],
                  'callHierarchy' => [
                    'dynamicRegistration' => true,
                  ],
                  'semanticTokens' => [
                    'dynamicRegistration' => true,
                    'tokenTypes' => [
                      0 => 'namespace',
                      1 => 'type',
                      2 => 'class',
                      3 => 'enum',
                      4 => 'interface',
                      5 => 'struct',
                      6 => 'typeParameter',
                      7 => 'parameter',
                      8 => 'variable',
                      9 => 'property',
                      10 => 'enumMember',
                      11 => 'event',
                      12 => 'function',
                      13 => 'method',
                      14 => 'macro',
                      15 => 'keyword',
                      16 => 'modifier',
                      17 => 'comment',
                      18 => 'string',
                      19 => 'number',
                      20 => 'regexp',
                      21 => 'operator',
                    ],
                    'tokenModifiers' => [
                      0 => 'declaration',
                      1 => 'definition',
                      2 => 'readonly',
                      3 => 'static',
                      4 => 'deprecated',
                      5 => 'abstract',
                      6 => 'async',
                      7 => 'modification',
                      8 => 'documentation',
                      9 => 'defaultLibrary',
                    ],
                    'formats' => [
                      0 => 'relative',
                    ],
                    'requests' => [
                      'range' => true,
                      'full' => [
                        'delta' => true,
                      ],
                    ],
                    'multilineTokenSupport' => false,
                    'overlappingTokenSupport' => false,
                  ],
                  'linkedEditingRange' => [
                    'dynamicRegistration' => true,
                  ],
                ],
                'window' => [
                  'showMessage' => [
                    'messageActionItem' => [
                      'additionalPropertiesSupport' => true,
                    ],
                  ],
                  'showDocument' => [
                    'support' => true,
                  ],
                  'workDoneProgress' => true,
                ],
                'general' => [
                  'regularExpressions' => [
                    'engine' => 'ECMAScript',
                    'version' => 'ES2020',
                  ],
                  'markdown' => [
                    'parser' => 'marked',
                    'version' => '1.1.0',
                  ],
                ],
              ],
              'trace' => 'off',
            ],
        ];
    }
}
