<?php
namespace Psalm\Tests\FileManipulation;

use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Tests\Internal\Provider;
use Psalm\Tests\TestConfig;

class MethodRenameTest extends \Psalm\Tests\TestCase
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
     * @param array<string, string> $methods_to_rename
     * @param array<string, string> $call_transforms
     *
     * @return void
     */
    public function testValidCode(
        string $input_code,
        string $output_code,
        array $methods_to_rename,
        array $call_transforms
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

        $codebase->call_transforms = $call_transforms;
        $codebase->methods_to_rename = $methods_to_rename;

        $this->project_analyzer->refactorCodeAfterCompletion();

        $this->analyzeFile($file_path, $context);

        $this->project_analyzer->prepareMigration();

        $codebase->analyzer->updateFile($file_path, false);

        $this->project_analyzer->migrateCode();

        $this->assertSame($output_code, $codebase->getFileContents($file_path));
    }

    /**
     * @return array<string,array{string,string,array<string, string>,array<string, string>}>
     */
    public function providerValidCodeParse()
    {
        return [
            'renameMethod' => [
                '<?php
                    namespace Ns;

                    use ArrayObject;

                    class A {
                        /**
                         * @return ArrayObject<int, int>
                         */
                        public function Foo() {
                            return new ArrayObject([self::C]);
                        }

                        public function bat() {
                            $this->foo();
                        }
                    }

                    class B extends A {
                        public static function bar(A $a) : void {
                            $a->Foo();

                            $this->foo();
                            parent::foo();

                            foreach ($a->Foo() as $f) {}
                        }
                    }',
                '<?php
                    namespace Ns;

                    use ArrayObject;

                    class A {
                        /**
                         * @return ArrayObject<int, int>
                         */
                        public function Fedcba() {
                            return new ArrayObject([self::C]);
                        }

                        public function bat() {
                            $this->Fedcba();
                        }
                    }

                    class B extends A {
                        public static function bar(A $a) : void {
                            $a->Fedcba();

                            $this->Fedcba();
                            parent::Fedcba();

                            foreach ($a->Fedcba() as $f) {}
                        }
                    }',
                [
                    'ns\a::foo' => 'Fedcba',
                ],
                [
                    'ns\a::foo\((.*\))' => 'Ns\A::Fedcba($1)',
                ]
            ],
        ];
    }
}
