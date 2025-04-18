<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\Providers;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function dirname;
use function getcwd;

class ForbiddenCodeTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'varDump' => [
                'code' => '<?php
                    var_dump("hello");',
                'error_message' => 'ForbiddenCode',
            ],
            'varDumpCased' => [
                'code' => '<?php
                    vAr_dUMp("hello");',
                'error_message' => 'ForbiddenCode',
            ],
            'execTicks' => [
                'code' => '<?php
                    `rm -rf`;',
                'error_message' => 'ForbiddenCode',
            ],
            'exec' => [
                'code' => '<?php
                    shell_exec("rm -rf");',
                'error_message' => 'ForbiddenCode',
            ],
            'execCased' => [
                'code' => '<?php
                    sHeLl_EXeC("rm -rf");',
                'error_message' => 'ForbiddenCode',
            ],
        ];
    }

    public function providerValidCodeParse(): iterable
    {
        return [
            'execWithSuppression' => [
                'code' => '<?php
                    @exec("pwd 2>&1", $output, $returnValue);
                    if ($returnValue === 0) {
                        echo "success";
                    }',
            ],
            'execWithoutSuppression' => [
                'code' => '<?php
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
                <psalm></psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                echo "hello";',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testForbiddenCodeFunctionViaFunctions(): void
    {
        $this->expectExceptionMessage('ForbiddenCode');
        $this->expectException(CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <forbiddenFunctions>
                        <function name="echo" />
                    </forbiddenFunctions>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                echo "hello";',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testAllowedPrintFunction(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm></psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                print "hello";',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testForbiddenPrintFunction(): void
    {
        $this->expectExceptionMessage('ForbiddenCode');
        $this->expectException(CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <forbiddenFunctions>
                        <function name="print" />
                    </forbiddenFunctions>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                print "hello";',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testAllowedVarExportFunction(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm></psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                $a = [1, 2, 3];
                var_export($a);',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testForbiddenVarExportFunction(): void
    {
        $this->expectExceptionMessage('ForbiddenCode');
        $this->expectException(CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <forbiddenFunctions>
                        <function name="var_export" />
                    </forbiddenFunctions>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                $a = [1, 2, 3];
                var_export($a);',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testNoExceptionWithMatchingNameButDifferentNamespace(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                <<<'XML'
                    <?xml version="1.0"?>
                    <psalm>
                        <forbiddenFunctions>
                            <function name="strlen"/>
                        </forbiddenFunctions>
                    </psalm>
                    XML,
            ),
        );
        $file_path = (string) getcwd() . '/src/somefile.php';
        $this->addFile(
            $file_path,
            <<<'PHP'
                <?php
                namespace Foo {
                    function strlen(): int {
                        return 0;
                    }
                }
                namespace {
                    use function Foo\strlen;
                    strlen();
                }
                PHP,
        );
        $this->analyzeFile($file_path, new Context());
    }

    public function testForbiddenEmptyFunction(): void
    {
        $this->expectExceptionMessage('ForbiddenCode');
        $this->expectException(CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <forbiddenFunctions>
                        <function name="empty" />
                    </forbiddenFunctions>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                empty(false);',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testForbiddenExitFunction(): void
    {
        $this->expectExceptionMessage('ForbiddenCode');
        $this->expectException(CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <forbiddenFunctions>
                        <function name="exit" />
                    </forbiddenFunctions>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                exit(2);
            ',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testForbiddenDieFunction(): void
    {
        $this->expectExceptionMessage('ForbiddenCode');
        $this->expectException(CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <forbiddenFunctions>
                        <function name="die" />
                    </forbiddenFunctions>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                die(2);
            ',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testForbiddenEvalExpression(): void
    {
        $this->expectExceptionMessage('ForbiddenCode');
        $this->expectException(CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 2),
                '<?xml version="1.0"?>
                <psalm>
                    <forbiddenFunctions>
                        <function name="eval" />
                    </forbiddenFunctions>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                eval("foo bar");
            ',
        );

        $this->analyzeFile($file_path, new Context());
    }

    private function getProjectAnalyzerWithConfig(Config $config): ProjectAnalyzer
    {
        $p = new ProjectAnalyzer(
            $config,
            new Providers(
                $this->file_provider,
                new FakeParserCacheProvider(),
            ),
        );

        $p->setPhpVersion('7.4', 'tests');

        return $p;
    }
}
