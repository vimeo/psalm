<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class DocumentationTest extends TestCase
{
    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;

    /**
     * @return array<string, array<int, string>>
     */
    private static function getCodeBlocksFromDocs()
    {
        $issue_file = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'issues.md';

        if (!file_exists($issue_file)) {
            throw new \UnexpectedValueException('docs not found');
        }

        $file_contents = file_get_contents($issue_file);

        if (!$file_contents) {
            throw new \UnexpectedValueException('Docs are empty');
        }

        $file_lines = explode(PHP_EOL, $file_contents);

        $issue_code = [];

        $current_issue = null;

        for ($i = 0, $j = count($file_lines); $i < $j; ++$i) {
            $current_line = $file_lines[$i];

            if (substr($current_line, 0, 4) === '### ') {
                $current_issue = trim(substr($current_line, 4));
                ++$i;
                continue;
            }

            if (substr($current_line, 0, 6) === '```php' && $current_issue) {
                $current_block = '';
                ++$i;

                do {
                    $current_block .= $file_lines[$i] . PHP_EOL;
                    ++$i;
                } while (substr($file_lines[$i], 0, 3) !== '```' && $i < $j);

                $issue_code[$current_issue][] = trim($current_block);
            }
        }

        return $issue_code;
    }

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
            new Provider\FakeParserCacheProvider()
        );
    }

    /**
     * @return void
     */
    public function testAllIssuesCovered()
    {
        $all_issues = ConfigTest::getAllIssues();
        sort($all_issues);

        $code_blocks = self::getCodeBlocksFromDocs();

        // these cannot have code
        $code_blocks['UnrecognizedExpression'] = true;
        $code_blocks['UnrecognizedStatement'] = true;

        $documented_issues = array_keys($code_blocks);
        sort($documented_issues);

        $this->assertSame(implode(PHP_EOL, $all_issues), implode(PHP_EOL, $documented_issues));
    }

    /**
     * @dataProvider providerFileCheckerInvalidCodeParse
     * @small
     *
     * @param string $code
     * @param string $error_message
     * @param array<string> $error_levels
     * @param bool $check_references
     *
     * @return void
     */
    public function testInvalidCode($code, $error_message, $error_levels = [], $check_references = false)
    {
        if (strpos($this->getName(), 'SKIPPED-') !== false) {
            $this->markTestSkipped();
        }

        $this->project_checker->getCodebase()->collect_references = $check_references;

        foreach ($error_levels as $error_level) {
            $this->project_checker->config->setCustomErrorLevel($error_level, Config::REPORT_SUPPRESS);
        }

        $this->expectException('\Psalm\Exception\CodeException');
        $this->expectExceptionMessageRegexp('/\b' . preg_quote($error_message, '/') . '\b/');

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile($file_path, $code);

        $context = new Context();
        $context->collect_references = $check_references;

        $this->analyzeFile($file_path, $context);

        if ($check_references) {
            $this->project_checker->getCodebase()->checkClassReferences();
        }
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        $invalid_code_data = [];

        foreach (self::getCodeBlocksFromDocs() as $issue_name => $blocks) {
            switch ($issue_name) {
                case 'InvalidFalsableReturnType':
                    $ignored_issues = ['FalsableReturnStatement'];
                    break;

                case 'InvalidNullableReturnType':
                    $ignored_issues = ['NullableReturnStatement'];
                    break;

                case 'InvalidReturnType':
                    $ignored_issues = ['InvalidReturnStatement'];
                    break;

                case 'MixedInferredReturnType':
                    $ignored_issues = ['MixedReturnStatement'];
                    break;

                case 'MixedStringOffsetAssignment':
                    $ignored_issues = ['MixedAssignment'];
                    break;

                case 'UnusedClass':
                case 'UnusedMethod':
                    $ignored_issues = ['UnusedVariable'];
                    break;

                default:
                    $ignored_issues = [];
            }

            $invalid_code_data[$issue_name] = [
                '<?php' . PHP_EOL . $blocks[0],
                $issue_name,
                $ignored_issues,
                strpos($issue_name, 'Unused') !== false || strpos($issue_name, 'Unevaluated') !== false,
            ];
        }

        return $invalid_code_data;
    }
}
