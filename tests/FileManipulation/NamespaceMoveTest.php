<?php

declare(strict_types=1);

namespace Psalm\Tests\FileManipulation;

use Psalm\Context;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;

use function strpos;

class NamespaceMoveTest extends TestCase
{
    protected ProjectAnalyzer $project_analyzer;

    public function setUp(): void
    {
        RuntimeCaches::clearAll();

        $this->file_provider = new FakeFileProvider();
    }

    /**
     * @dataProvider providerValidCodeParse
     * @param array<string, string> $namespaces_to_move
     */
    public function testValidCode(
        string $input_code,
        string $output_code,
        array $namespaces_to_move,
    ): void {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        $config = new TestConfig();

        $this->project_analyzer = new ProjectAnalyzer(
            $config,
            new Providers(
                $this->file_provider,
                new FakeParserCacheProvider(),
            ),
        );

        $context = new Context();

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            $input_code,
        );

        $codebase = $this->project_analyzer->getCodebase();

        $this->project_analyzer->refactorCodeAfterCompletion($namespaces_to_move);

        $this->analyzeFile($file_path, $context);

        $this->project_analyzer->prepareMigration();

        $codebase->analyzer->updateFile($file_path, false);

        $this->project_analyzer->migrateCode();

        $this->assertSame($output_code, $codebase->getFileContents($file_path));
    }

    /**
     * @return array<string,array{input:string,output:string,migrations:array<string, string>}>
     */
    public function providerValidCodeParse(): array
    {
        return [
            'moveClassesIntoNamespace' => [
                'input' => '<?php
                    namespace Foo {
                        class A {
                            /** @var ?B */
                            public $x = null;
                            /** @var ?A */
                            public $y = null;
                            /** @var A|B|C|null */
                            public $z = null;
                        }
                    }

                    namespace Foo {
                        class B {
                            /** @var ?A */
                            public $x = null;
                            /** @var ?B */
                            public $y = null;
                            /** @var A|B|C|null */
                            public $z = null;
                        }
                    }

                    namespace Bar {
                        use Foo\A;
                        use Foo\B;

                        class C {
                            /** @var ?A */
                            public $x = null;
                            /** @var ?B */
                            public $y = null;
                            /** @var null|A|B */
                            public $z = null;
                        }
                    }',
                'output' => '<?php
                    namespace Bar\Baz {
                        class A {
                            /** @var B|null */
                            public $x = null;
                            /** @var null|self */
                            public $y = null;
                            /** @var B|\Foo\C|null|self */
                            public $z = null;
                        }
                    }

                    namespace Bar\Baz {
                        class B {
                            /** @var A|null */
                            public $x = null;
                            /** @var null|self */
                            public $y = null;
                            /** @var A|\Foo\C|null|self */
                            public $z = null;
                        }
                    }

                    namespace Bar {
                        use Bar\Baz\A;
                        use Bar\Baz\B;

                        class C {
                            /** @var A|null */
                            public $x = null;
                            /** @var B|null */
                            public $y = null;
                            /** @var A|B|null */
                            public $z = null;
                        }
                    }',
                'migrations' => [
                    'Foo\*' => 'Bar\Baz\*',
                ],
            ],
        ];
    }
}
