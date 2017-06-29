<?php
namespace Psalm\Tests\Traits;

use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

trait FileCheckerValidCodeParseTestTrait
{
    /**
     * @return array
     */
    abstract public function providerFileCheckerValidCodeParse();

    /**
     * @dataProvider providerFileCheckerValidCodeParse
     *
     * @param string $code
     * @param array<array<string,string>> $assertions
     * @param array<string> $error_levels
     * @param array<string,\Psalm\Type\Union> $scope_vars
     *
     * @return void
     */
    public function testValidCode($code, $assertions = [], $error_levels = [], $scope_vars = [])
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

        foreach ($error_levels as $error_level) {
            Config::getInstance()->setCustomErrorLevel($error_level, Config::REPORT_SUPPRESS);
        }

        $stmts = self::$parser->parse($code);

        $context = new Context();
        foreach ($scope_vars as $var => $value) {
            $context->vars_in_scope[$var] = $value;
        }

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods($context);

        foreach ($assertions as $var => $expected) {
            $this->assertSame($expected, (string)$context->vars_in_scope[$var]);
        }
    }
}
