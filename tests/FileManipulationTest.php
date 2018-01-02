<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;
use Psalm\Context;

class FileManipulationTest extends TestCase
{
    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;

    /**
     * @return void
     */
    public function setUp()
    {
        FileChecker::clearCache();
        \Psalm\FileManipulation\FunctionDocblockManipulator::clearCache();

        $this->file_provider = new Provider\FakeFileProvider();

        $this->project_checker = new \Psalm\Checker\ProjectChecker(
            $this->file_provider,
            new Provider\FakeParserCacheProvider()
        );

        if (!self::$config) {
            self::$config = new TestConfig();
            self::$config->addPluginPath('examples/ClassUnqualifier.php');
        }

        $this->project_checker->setConfig(self::$config);

        $this->project_checker->update_docblocks = true;
    }

    /**
     * @dataProvider providerFileCheckerValidCodeParse
     *
     * @param string $input_code
     * @param string $output_code
     *
     * @return void
     */
    public function testValidCode($input_code, $output_code)
    {
        $test_name = $this->getName();
        if (strpos($test_name, 'PHP7-') !== false) {
            if (version_compare(PHP_VERSION, '7.0.0dev', '<')) {
                $this->markTestSkipped('Test case requires PHP 7.');

                return;
            }
        } elseif (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        $context = new Context();

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            $input_code
        );

        $file_checker = new FileChecker($file_path, $this->project_checker);
        $file_checker->visitAndAnalyzeMethods($context);
        $this->project_checker->updateFile($file_path);
        $this->assertSame($output_code, $this->project_checker->getFileContents($file_path));
    }

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'doesNothing' => [
                '<?php
                    function foo() { }',
                '<?php
                    /**
                     * @return void
                     */
                    function foo() { }',
            ],
            'returnsString' => [
                '<?php
                    function foo() {
                        return "hello";
                    }',
                '<?php
                    /**
                     * @return string
                     */
                    function foo() {
                        return "hello";
                    }',
            ],
            'returnsStringNotInt' => [
                '<?php
                    /**
                     * @return int
                     */
                    function foo() {
                        return "hello";
                    }',
                '<?php
                    /**
                     * @return string
                     */
                    function foo() {
                        return "hello";
                    }',
            ],
            'returnStringArray' => [
                '<?php
                    function foo() {
                        return ["hello"];
                    }',
                '<?php
                    /**
                     * @return       string[]
                     * @psalm-return array{0:string}
                     */
                    function foo() {
                        return ["hello"];
                    }',
            ],
            'useUnqualifierPlugin' => [
                '<?php
                    namespace A\B\C {
                        class D {}
                    }
                    namespace Foo\Bar {
                        use A\B\C\D;

                        new \A\B\C\D();
                    }',
                '<?php
                    namespace A\B\C {
                        class D {}
                    }
                    namespace Foo\Bar {
                        use A\B\C\D;

                        new D();
                    }',
            ],
        ];
    }
}
