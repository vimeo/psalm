<?php

declare(strict_types=1);

namespace Psalm\Tests\Config\Plugin\EventHandler\RemoveTaints;

use Override;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\Report\ReportOptions;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;

use function define;
use function defined;
use function dirname;
use function getcwd;

use const DIRECTORY_SEPARATOR;

final class RemoveTaintsInterfaceTest extends TestCase
{
    #[Override]
    public static function setUpBeforeClass(): void
    {
        new TestConfig();

        if (!defined('PSALM_VERSION')) {
            define('PSALM_VERSION', '4.0.0');
        }

        if (!defined('PHP_PARSER_VERSION')) {
            define('PHP_PARSER_VERSION', '4.0.0');
        }
    }

    private function getProjectAnalyzerWithConfig(Config $config): ProjectAnalyzer
    {
        $config->setIncludeCollector(new IncludeCollector());
        $p = new ProjectAnalyzer(
            $config,
            new Providers(
                $this->file_provider,
                new FakeParserCacheProvider(),
            ),
            new ReportOptions(),
        );
        $p->initExtraFiles();
        $p->initProjectFiles();
        return $p;
    }

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    public function setUp(): void
    {
        RuntimeCaches::clearAll();
        $this->file_provider = new FakeFileProvider();
    }


    public function testRemoveAllTaints(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 5) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="6"
                    runTaintAnalysis="true"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <plugin filename="tests/Config/Plugin/EventHandler/RemoveTaints/RemoveAllTaintsPlugin.php" />
                    </plugins>
                </psalm>',
            ),
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php // --taint-analysis

            /**
             * @psalm-mutation-free
             * @psalm-taint-sink html $string
             */
            function output($string) {}

            echo $_POST["username"];',
        );

        $this->project_analyzer->trackTaintedInputs();

        $this->analyzeFile($file_path, new Context());
    }

    public function testRemoveTaintsSafeArrayKeyChecker(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 5) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="6"
                    runTaintAnalysis="true"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <plugin filename="examples/plugins/SafeArrayKeyChecker.php" />
                    </plugins>
                </psalm>',
            ),
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php // --taint-analysis

            /**
             * @psalm-mutation-free
             * 
             * @psalm-taint-sink html $build
             */
            function output(array $build) {}

            $build = [
                "nested" => [
                    "safe_key" => $_GET["input"],
                ],
            ];
            output($build);',
        );

        $this->project_analyzer->trackTaintedInputs();

        $this->analyzeFile($file_path, new Context());

        $this->addFile(
            $file_path,
            '<?php // --taint-analysis

            /**
             * @psalm-mutation-free
             * @psalm-taint-sink html $build
             */
            function output(array $build) {}

            $build = [
                "nested" => [
                    "safe_key" => $_GET["input"],
                    "a" => $_GET["input"],
                ],
            ];
            output($build);',
        );

        $this->project_analyzer->trackTaintedInputs();

        $this->expectException(CodeException::class);
        $this->expectExceptionMessageMatches('/TaintedHtml/');

        $this->analyzeFile($file_path, new Context());
    }

    private function analyzeWithSafeVariablePlugin(string $code): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__, 5) . DIRECTORY_SEPARATOR,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="6"
                    runTaintAnalysis="true"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <plugin filename="tests/Config/Plugin/EventHandler/RemoveTaints/RemoveTaintsOfSafeVariablePlugin.php" />
                    </plugins>
                </psalm>',
            ),
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile($file_path, $code);

        $this->project_analyzer->trackTaintedInputs();

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * What a plugin removes from the result of a call of an inherited method isn't removed from those of the
     * other calls, which share the path from the body of the method.
     */
    public function testRemoveTaintsOfOneCallOfInheritedMethod(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessageMatches('/TaintedHtml/');

        $this->analyzeWithSafeVariablePlugin(
            '<?php // --taint-analysis
            class A {
                public function get(): string {
                    return (string) $_GET["x"];
                }
            }
            class B extends A {}

            $tainted = new B();
            $safe = new B();
            echo $tainted->get();
            echo $safe->get();',
        );
    }

    public function testRemoveTaintsOfCallOfDeclaredMethod(): void
    {
        $this->analyzeWithSafeVariablePlugin(
            '<?php // --taint-analysis
            class A {
                public function get(): string {
                    return (string) $_GET["x"];
                }
            }

            $safe = new A();
            echo $safe->get();',
        );
    }

    public function testRemoveTaintsOfCallOfInheritedMethod(): void
    {
        $this->analyzeWithSafeVariablePlugin(
            '<?php // --taint-analysis
            class A {
                public function get(): string {
                    return (string) $_GET["x"];
                }
            }
            class B extends A {}

            $safe = new B();
            echo $safe->get();',
        );
    }

    public function testRemoveTaintsOfSpecializedCallOfDeclaredMethod(): void
    {
        $this->analyzeWithSafeVariablePlugin(
            '<?php // --taint-analysis
            class A {
                /** @psalm-taint-specialize */
                public function get(): string {
                    return (string) $_GET["x"];
                }
            }

            $safe = new A();
            echo $safe->get();',
        );
    }

    /**
     * What a plugin removes from the result of a call is removed from what the overrides the call may run return
     */
    public function testRemoveTaintsOfWhatOverridesReturn(): void
    {
        $this->analyzeWithSafeVariablePlugin(
            '<?php // --taint-analysis
            class A {
                public function get(): string {
                    return (string) $_GET["x"];
                }
            }
            class C extends A {
                public function get(): string {
                    return (string) $_GET["y"];
                }
            }

            function show(A $safe): void {
                echo $safe->get();
            }',
        );
    }

    /**
     * What a plugin removes from the result of a conditionally escaped call isn't removed from those of the other
     * calls, which share the return node of the function
     */
    public function testRemoveTaintsOfOneConditionallyEscapedFunctionCall(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessageMatches('/TaintedShell/');

        $this->analyzeWithSafeVariablePlugin(
            '<?php // --taint-analysis
            /**
             * @psalm-impure
             * @psalm-taint-escape ($escape is true ? "html" : null)
             */
            function escape(string $s, bool $escape): string {
                return $s;
            }

            $tainted = (string) $_GET["x"];
            $safe = (string) $_GET["y"];
            $result = escape($tainted, true);
            escape($safe, true);
            shell_exec($result);',
        );
    }

    public function testRemoveTaintsOfOneConditionallyEscapedStaticCall(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessageMatches('/TaintedShell/');

        $this->analyzeWithSafeVariablePlugin(
            '<?php // --taint-analysis
            class A {
                public static int $calls = 0;

                /** @psalm-taint-escape ($escape is true ? "html" : null) */
                public static function escape(string $s, bool $escape): string {
                    self::$calls++;
                    return $s;
                }
            }

            $tainted = (string) $_GET["x"];
            $safe = (string) $_GET["y"];
            $result = A::escape($tainted, true);
            A::escape($safe, true);
            shell_exec($result);',
        );
    }
}
