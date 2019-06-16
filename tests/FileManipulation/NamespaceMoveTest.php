<?php
namespace Psalm\Tests\FileManipulation;

use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Tests\Internal\Provider;
use Psalm\Tests\TestConfig;

class NamespaceMoveTest extends \Psalm\Tests\TestCase
{
    /** @var \Psalm\Internal\Analyzer\ProjectAnalyzer */
    protected $project_analyzer;

    public function setUp() : void
    {
        FileAnalyzer::clearCache();
        \Psalm\Internal\FileManipulation\FunctionDocblockManipulator::clearCache();

        $this->file_provider = new Provider\FakeFileProvider();
    }

    /**
     * @dataProvider providerValidCodeParse
     *
     * @param string $input_code
     * @param string $output_code
     * @param array<string, string> $namespaces_to_move
     * @param array<string, string> $call_transforms
     *
     * @return void
     */
    public function testValidCode(
        string $input_code,
        string $output_code,
        array $namespaces_to_move
    ) {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        $config = new TestConfig();

        $this->project_analyzer = new \Psalm\Internal\Analyzer\ProjectAnalyzer(
            $config,
            new \Psalm\Internal\Provider\Providers(
                $this->file_provider,
                new Provider\FakeParserCacheProvider()
            )
        );

        $context = new Context();

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            $input_code
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
     * @return array<string,array{string,string,array<string, string>}>
     */
    public function providerValidCodeParse()
    {
        return [
            'moveClassesIntoNamespace' => [
                '<?php
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
                '<?php
                    namespace Bar\Baz {
                        class A {
                            /** @var null|B */
                            public $x = null;
                            /** @var null|self */
                            public $y = null;
                            /** @var null|self|B|\Foo\C */
                            public $z = null;
                        }
                    }

                    namespace Bar\Baz {
                        class B {
                            /** @var null|A */
                            public $x = null;
                            /** @var null|self */
                            public $y = null;
                            /** @var null|A|self|\Foo\C */
                            public $z = null;
                        }
                    }

                    namespace Bar {
                        use Bar\Baz\A;
                        use Bar\Baz\B;

                        class C {
                            /** @var null|A */
                            public $x = null;
                            /** @var null|B */
                            public $y = null;
                            /** @var null|A|B */
                            public $z = null;
                        }
                    }',
                [
                    'Foo\*' => 'Bar\Baz\*',
                ]
            ],
        ];
    }
}
