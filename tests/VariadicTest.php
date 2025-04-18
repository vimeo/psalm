<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Exception\ConfigException;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\Provider\Providers;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function dirname;
use function getcwd;

class VariadicTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    public function testVariadicArrayBadParam(): void
    {
        $this->expectExceptionMessage('InvalidScalarArgument');
        $this->expectException(CodeException::class);
        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @param int ...$a_list
                 * @return void
                 */
                function f(int ...$a_list) {
                }
                f(1, 2, "3");',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @throws ConfigException
     * @runInSeparateProcess
     */
    public function testVariadicFunctionFromAutoloadFile(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    autoloader="tests/fixtures/stubs/custom_functions.phpstub"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                variadic2(16, 30);
            ',
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return iterable<string,array{code: string}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'variadic' => [
                'code' => '<?php
                    /**
                     * @param mixed $req
                     * @param mixed $opt
                     * @param mixed ...$params
                     * @return array<mixed>
                     */
                    function f($req, $opt = null, ...$params) {
                        return $params;
                    }

                    f(1);
                    f(1, 2);
                    f(1, 2, 3);
                    f(1, 2, 3, 4);
                    f(1, 2, 3, 4, 5);',
            ],
            'funcNumArgsVariadic' => [
                'code' => '<?php
                    function test(): array {
                        return func_get_args();
                    }
                    var_export(test(2));',
            ],
            'variadicArray' => [
                'code' => '<?php
                    /**
                     * @param int ...$a_list
                     * @return array<array-key, int>
                     */
                    function f(int ...$a_list) {
                        return array_map(
                            /**
                             * @return int
                             */
                            function (int $a) {
                                return $a + 1;
                            },
                            $a_list
                        );
                    }

                    f(1);
                    f(1, 2);
                    f(1, 2, 3);

                    /**
                     * @param string ...$a_list
                     * @return void
                     */
                    function g(string ...$a_list) {
                    }',
            ],
        ];
    }

    private function getProjectAnalyzerWithConfig(Config $config): ProjectAnalyzer
    {
        $project_analyzer = new ProjectAnalyzer(
            $config,
            new Providers(
                $this->file_provider,
                new FakeParserCacheProvider(),
            ),
        );
        $project_analyzer->setPhpVersion('7.3', 'tests');

        $config->setIncludeCollector(new IncludeCollector());
        $config->visitComposerAutoloadFiles($project_analyzer, null);

        return $project_analyzer;
    }
}
