<?php

namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\Providers;
use Psalm\Tests\Internal\Provider;

use function dirname;
use function getcwd;

class ForbiddenCodeTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'varDump' => [
                '<?php
                    var_dump("hello");',
                'error_message' => 'ForbiddenCode',
            ],
            'varDumpCased' => [
                '<?php
                    vAr_dUMp("hello");',
                'error_message' => 'ForbiddenCode',
            ],
            'execTicks' => [
                '<?php
                    `rm -rf`;',
                'error_message' => 'ForbiddenCode',
            ],
            'exec' => [
                '<?php
                    shell_exec("rm -rf");',
                'error_message' => 'ForbiddenCode',
            ],
            'execCased' => [
                '<?php
                    sHeLl_EXeC("rm -rf");',
                'error_message' => 'ForbiddenCode',
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'execWithSuppression' => [
                '<?php
                    @exec("pwd 2>&1", $output, $returnValue);
                    if ($returnValue === 0) {
                        echo "success";
                    }',
            ],
            'execWithoutSuppression' => [
                '<?php
                    exec("pwd 2>&1", $output, $returnValue);
                    if ($returnValue === 0) {
                        echo "success";
                    }',
            ],
        ];
    }

    public function testAllowedEchoFunction(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm></psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                echo "hello";'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testForbiddenEchoFunctionViaFunctions(): void
    {
        $this->expectExceptionMessage('ForbiddenCode');
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <forbiddenFunctions>
                        <function name="echo" />
                    </forbiddenFunctions>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                echo "hello";'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testForbiddenEchoFunctionViaFlag(): void
    {
        $this->expectExceptionMessage('ForbiddenEcho');
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm forbidEcho="true"></psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                echo "hello";'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testAllowedPrintFunction(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm></psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                print "hello";'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testForbiddenPrintFunction(): void
    {
        $this->expectExceptionMessage('ForbiddenCode');
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <forbiddenFunctions>
                        <function name="print" />
                    </forbiddenFunctions>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                print "hello";'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testAllowedVarExportFunction(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm></psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                $a = [1, 2, 3];
                var_export($a);'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testForbiddenVarExportFunction(): void
    {
        $this->expectExceptionMessage('ForbiddenCode');
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <forbiddenFunctions>
                        <function name="var_export" />
                    </forbiddenFunctions>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                $a = [1, 2, 3];
                var_export($a);'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testForbiddenEmptyFunction(): void
    {
        $this->expectExceptionMessage('ForbiddenCode');
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <forbiddenFunctions>
                        <function name="empty" />
                    </forbiddenFunctions>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                empty(false);'
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testForbiddenExitFunction(): void
    {
        $this->expectExceptionMessage('ForbiddenCode');
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <forbiddenFunctions>
                        <function name="exit" />
                    </forbiddenFunctions>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                exit(2);
            '
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testForbiddenDieFunction(): void
    {
        $this->expectExceptionMessage('ForbiddenCode');
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <forbiddenFunctions>
                        <function name="die" />
                    </forbiddenFunctions>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                die(2);
            '
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testForbiddenEvalExpression(): void
    {
        $this->expectExceptionMessage('ForbiddenCode');
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <forbiddenFunctions>
                        <function name="eval" />
                    </forbiddenFunctions>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                eval("foo bar");
            '
        );

        $this->analyzeFile($file_path, new Context());
    }

    private function getProjectAnalyzerWithConfig(Config $config): ProjectAnalyzer
    {
        $p = new ProjectAnalyzer(
            $config,
            new Providers(
                $this->file_provider,
                new Provider\FakeParserCacheProvider()
            )
        );

        $p->setPhpVersion('7.4', 'tests');

        return $p;
    }
}
