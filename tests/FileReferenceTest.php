<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;
use Psalm\Context;

class FileReferenceTest extends TestCase
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
            new TestConfig(),
            $this->file_provider,
            new Provider\FakeParserCacheProvider(),
            new \Psalm\Provider\NoCache\NoFileStorageCacheProvider(),
            new \Psalm\Provider\NoCache\NoClassLikeStorageCacheProvider()
        );

        $this->project_checker->getCodebase()->collectReferences();
    }

    /**
     * @dataProvider providerFileCheckerValidCodeParse
     *
     * @param string $input_code
     * @param string $symbol
     * @param array<int, string> $expected_locations
     *
     * @return void
     */
    public function testValidCode($input_code, $symbol, $expected_locations)
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

        $this->addFile($file_path, $input_code);

        $this->analyzeFile($file_path, $context);

        $found_references = $this->project_checker->getCodebase()->findReferencesToSymbol($symbol);

        if (!isset($found_references[$file_path])) {
            throw new \UnexpectedValueException('No file references found in this file');
        }

        $file_references = $found_references[$file_path];

        $this->assertSame(count($file_references), count($expected_locations));

        foreach ($expected_locations as $i => $expected_location) {
            $actual_location = $file_references[$i];

            $this->assertSame(
                $expected_location,
                $actual_location->getLineNumber() . ':' . $actual_location->getColumn()
                    . ':' . $actual_location->getSelectedText()
            );
        }
    }

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'getClassLocation' => [
                '<?php
                    class A {}

                    new A();',
                'A',
                ['4:25:A'],
            ],
            'getMethodLocation' => [
                '<?php
                    class A {
                        public function foo(): void {}
                    }

                    (new A())->foo();',
                'A::foo',
                ['6:21:(new A())->foo()'],
            ],
        ];
    }
}
