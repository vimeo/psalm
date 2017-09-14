<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;

class ParamTypeAdditionTest extends TestCase
{
    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;

    /**
     * @return void
     */
    public function setUp()
    {
        FileChecker::clearCache();

        $this->file_provider = new Provider\FakeFileProvider();

        $this->project_checker = new \Psalm\Checker\ProjectChecker(
            $this->file_provider,
            new Provider\FakeCacheProvider()
        );

        $this->project_checker->setConfig(new TestConfig());

        $this->project_checker->collect_references = true;
    }

    /**
     * @dataProvider providerTestJsonOutputErrors
     *
     * @param string $input
     * @param string $output
     * @param int $line_number
     * @param string $error
     *
     * @return void
     */
    public function testParamTypeAdditions($input, $output)
    {
        $this->addFile('somefile.php', $input);

        $file_checker = new FileChecker('somefile.php', $this->project_checker);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return array
     */
    public function providerTestParamTypeAdditions()
    {
        return [
            'addSimpleReturnType' => [
                'input' => '<?php
                    function takesString(string $s) : void {}

                    function shouldTakeString($s) : void {
                      takesString($s);
                    }',
                'output' => '<?php
                    function takesString(string $s) : void {}

                    function shouldTakeString(string $s) : void {
                      takesString($s);
                    }',
            ],
        ];
    }
}
