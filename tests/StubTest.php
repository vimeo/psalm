<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Exception\ConfigException;
use Psalm\Exception\InvalidClasslikeOverrideException;
use Psalm\Exception\InvalidMethodOverrideException;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;

use function assert;
use function define;
use function defined;
use function dirname;
use function explode;
use function getcwd;
use function implode;
use function reset;
use function strlen;
use function strpos;
use function substr;

use const DIRECTORY_SEPARATOR;

class StubTest extends TestCase
{
    protected static TestConfig $config;

    public static function setUpBeforeClass(): void
    {
        self::$config = new TestConfig();

        if (!defined('PSALM_VERSION')) {
            define('PSALM_VERSION', '4.0.0');
        }

        if (!defined('PHP_PARSER_VERSION')) {
            define('PHP_PARSER_VERSION', '4.0.0');
        }
    }

    public function setUp(): void
    {
        RuntimeCaches::clearAll();
        $this->file_provider = new FakeFileProvider();
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
        $project_analyzer->setPhpVersion('7.4', 'tests');

        $config->setIncludeCollector(new IncludeCollector());
        $config->visitComposerAutoloadFiles($project_analyzer, null);

        return $project_analyzer;
    }

    public function testNonexistentStubFile(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Cannot resolve stubfile path');
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="stubs/invalidfile.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );
    }

    public function testStubFileClass(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/fixtures/stubs/systemclass.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                namespace A\B\C;

                $a = new \SystemClass();
                $b = $a->foo(5, "hello");
                $c = \SystemClass::bar(5, "hello");
                echo \SystemClass::HELLO;',
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @psalm-pure
     */
    private function getOperatingSystemStyledPath(string $file): string
    {
        return implode(DIRECTORY_SEPARATOR, explode('/', $file));
    }

    public function testLoadStubFileWithRelativePath(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <stubs>
                        <file name="./tests/../tests/fixtures/stubs/systemclass.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );

        $path = $this->getOperatingSystemStyledPath('tests/fixtures/stubs/systemclass.phpstub');
        $stub_files = $this->project_analyzer->getConfig()->getStubFiles();
        assert(!empty($stub_files));
        $this->assertStringContainsString($path, reset($stub_files));
    }

    public function testLoadStubFileWithAbsolutePath(): void
    {
        $runDir = dirname(__DIR__);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                $runDir,
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <stubs>
                        <file name="' . $runDir . '/tests/fixtures/stubs/systemclass.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );

        $path = $this->getOperatingSystemStyledPath('tests/fixtures/stubs/systemclass.phpstub');
        $stub_files = $this->project_analyzer->getConfig()->getStubFiles();
        assert(!empty($stub_files));
        $this->assertStringContainsString($path, reset($stub_files));
    }

    public function testStubFileConstant(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/fixtures/stubs/systemclass.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                namespace A\B\C;

                $d = ROOT_CONST_CONSTANT;
                $e = \ROOT_CONST_CONSTANT;
                $f = ROOT_DEFINE_CONSTANT;
                $g = \ROOT_DEFINE_CONSTANT;',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testStubFileParentClass(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('ImplementedParamTypeMismatch');
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/fixtures/stubs/systemclass.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                namespace A\B\C;

                class Foo extends \SystemClass
                {
                    public function foo(string $a, string $b): string
                    {
                        return $a . $b;
                    }
                }
            ',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testStubFileCircularReference(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('CircularReference');
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/fixtures/stubs/CircularReference.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                class Foo extends Baz {}
            ',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testPhpStormMetaParsingFile(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/fixtures/stubs/phpstorm.meta.php" />
                    </stubs>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                namespace Ns {
                    class MyClass {

                        public const OBJECT = "object";
                        private const EXCEPTION = "exception";

                        /**
                         * @return mixed
                         * @psalm-suppress InvalidReturnType
                         */
                        public function create(string $s) {}

                        /**
                         * @return mixed
                         * @psalm-suppress InvalidReturnType
                         */
                        public function create2(string $s) {}

                        /**
                         * @return mixed
                         * @psalm-suppress InvalidReturnType
                         */
                        public function create3(string $s) {}

                        /**
                         * @param mixed $s
                         * @return mixed
                         * @psalm-suppress InvalidReturnType
                         */
                        public function foo($s) {}

                        /**
                         * @return mixed
                         * @psalm-suppress InvalidReturnType
                         */
                        public function bar(array $a) {}
                    }
                }
                namespace {
                    /**
                     * @return mixed
                     * @psalm-suppress InvalidReturnType
                     */
                    function create(string $s) {}

                    /**
                     * @return mixed
                     * @psalm-suppress InvalidReturnType
                     */
                    function create2(string $s) {}

                    /**
                     * @param mixed $s
                     * @return mixed
                     * @psalm-suppress InvalidReturnType
                     */
                    function foo($s) {}

                    /**
                     * @return mixed
                     * @psalm-suppress InvalidReturnType
                     */
                    function bar(array $a) {}

                    $a1 = (new \Ns\MyClass)->creAte("object");
                    $a2 = (new \Ns\MyClass)->creaTe("exception");

                    $y1 = (new \Ns\MyClass)->creAte2("object");
                    $y2 = (new \Ns\MyClass)->creaTe2("exception");

                    $const1 = (new \Ns\MyClass)->creAte3(\Ns\MyClass::OBJECT);
                    $const2 = (new \Ns\MyClass)->creaTe3("exception");

                    $b1 = \Create("object");
                    $b2 = \cReate("exception");

                    $e2 = \creAte(\LogicException::class);

                    $z1 = \Create2("object");
                    $z2 = \cReate2("exception");

                    $x2 = \creAte2(\LogicException::class);

                    $c1 = (new \Ns\MyClass)->foo(5);
                    $c2 = (new \Ns\MyClass)->bar(["hello"]);

                    $d1 = \foO(5);
                    $d2 = \baR(["hello"]);
                }',
        );

        $context = new Context();
        $this->analyzeFile($file_path, $context);

        $this->assertContextVars(
            [
                '$a1===' => 'stdClass',
                '$a2===' => 'Exception',

                '$y1===' => 'stdClass',
                '$y2===' => 'Exception',

                '$const1===' => 'stdClass',
                '$const2===' => 'Exception',

                '$b1===' => 'stdClass',
                '$b2===' => 'Exception',

                '$e2===' => 'LogicException',

                '$z1===' => 'stdClass',
                '$z2===' => 'Exception',

                '$x2===' => 'LogicException',

                '$c1===' => "5",
                '$c2===' => "'hello'",

                '$d1===' => "5",
                '$d2===' => "'hello'",
            ],
            $context,
        );
    }

    /** @param array<string, string> $assertions */
    private function assertContextVars(array $assertions, Context $context): void
    {
        $actual_vars = [];
        foreach ($assertions as $var => $_) {
            $exact = false;

            if ($var && strpos($var, '===') === strlen($var) - 3) {
                $var = substr($var, 0, -3);
                $exact = true;
            }

            if (isset($context->vars_in_scope[$var])) {
                $value = $context->vars_in_scope[$var]->getId($exact);
                if ($exact) {
                    $actual_vars[$var . '==='] = $value;
                } else {
                    $actual_vars[$var] = $value;
                }
            }
        }
        $this->assertSame($assertions, $actual_vars);
    }

    public function testNamespacedStubClass(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/fixtures/stubs/namespaced_class.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                $a = new Foo\SystemClass();
                echo Foo\SystemClass::HELLO;

                $b = $a->foo(5, "hello");
                $c = Foo\SystemClass::bar(5, "hello");

                echo Foo\BAR;',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testStubRegularFunction(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/fixtures/stubs/custom_functions.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                echo barBar("hello");',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testStubVariadicFunction(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/fixtures/stubs/custom_functions.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                variadic("bat", "bam");',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testStubVariadicFunctionWrongArgType(): void
    {
        $this->expectExceptionMessage('InvalidScalarArgument');
        $this->expectException(CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/fixtures/stubs/custom_functions.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                variadic("bat", 5);',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testUserVariadicWithFalseVariadic(): void
    {
        $this->expectExceptionMessage('TooManyArguments');
        $this->expectException(CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
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
                /**
                 * @param string ...$bar
                 */
                function variadic() : void {}
                variadic("hello");',
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @runInSeparateProcess
     */
    public function testPolyfilledFunction(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                    autoloader="tests/fixtures/stubs/polyfill.phpstub"
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
                $a = random_bytes(16);
                $b = new_random_bytes(16);',
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @runInSeparateProcess
     */
    public function testConditionalConstantDefined(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                    autoloader="tests/fixtures/stubs/conditional_constant_define_inferred.phpstub"
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
                /** @psalm-suppress MixedArgument */
                echo CODE_DIR;',
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @runInSeparateProcess
     */
    public function testStubbedConstantVarCommentType(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/fixtures/stubs/constant_var_comment.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                /**
                 * @param non-empty-string $arg
                 * @return void
                 */
                function hello($arg) {
                    echo $arg;
                }

                hello(FOO_BAR);',
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @runInSeparateProcess
     */
    public function testClassAlias(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                    autoloader="tests/fixtures/stubs/class_alias.phpstub"
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
                namespace ClassAliasStubTest;

                function foo(A $a) : void {}

                foo(new B());
                foo(new C());

                function bar(B $b) : void {}

                bar(new A());

                $a = new B();

                echo $a->foo;

                echo $a->bar("hello");

                function f(): A {
                    return new A;
                }

                function getAliased(): B {
                    return f();
                }

                $d = new D();

                D::bat();
                $d::bat();

                class E implements IAlias {}',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testStubFunctionWithFunctionExists(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/fixtures/stubs/custom_functions.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                function_exists("fooBar");
                echo barBar("hello");',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testNamespacedStubFunctionWithFunctionExists(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/fixtures/stubs/custom_functions.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                namespace A;
                function_exists("fooBar");
                echo barBar("hello");',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testNoStubFunction(): void
    {
        $this->expectExceptionMessage('UndefinedFunction - /src/somefile.php:2:22 - Function barBar does not exist');
        $this->expectException(CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
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
                echo barBar("hello");',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testNamespacedStubFunction(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/fixtures/stubs/namespaced_functions.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                echo Foo\barBar("hello");',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testConditionalNamespacedStubFunction(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/fixtures/stubs/conditional_namespaced_functions.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                echo Foo\barBar("hello");',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testConditionallyExtendingInterface(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/fixtures/stubs/conditional_interface.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                class C implements I1, I2, I3, I4 {}

                function foo(I5 $d) : void {
                    $d->getMessage();
                }

                function bar(I6 $d) : void {
                    $d->getMessage();
                }

                function bat(I7 $d) : void {
                    $d->getMessage();
                }

                function baz(I8 $d) : void {
                    $d->getMessage();
                }',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testStubFileWithExistingClassDefinition(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/fixtures/stubs/DomainException.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                $a = new DomainException(5);',
        );

        $this->analyzeFile($file_path, new Context());
    }

    /** @return iterable<string, array{string,string}> */
    public function versionDependentStubsProvider(): iterable
    {
        yield '7.0' => [
            '7.0',
            '<?php
                $a = new SomeClass;
                $a->something("zzz");',
        ];
        yield '8.0' => [
            '8.0',
            '<?php
                $a = new SomeClass;
                $a->something();',
        ];
    }

    /** @dataProvider versionDependentStubsProvider */
    public function testVersionDependentStubs(string $php_version, string $code): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/fixtures/stubs/VersionDependentMethods.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );
        $this->project_analyzer->setPhpVersion($php_version, 'tests');

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile($file_path, $code);

        $this->analyzeFile($file_path, new Context());
    }

    public function testStubFileWithPartialClassDefinitionWithMoreMethods(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/fixtures/stubs/partial_class.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                namespace Foo;

                class PartiallyStubbedClass  {
                    /**
                     * @param string $a
                     * @return object
                     */
                    public function foo(string $a) {
                        return new self;
                    }

                    public function bar(int $i) : void {}
                }

                class A {}

                (new PartiallyStubbedClass())->foo(A::class);
                (new PartiallyStubbedClass())->bar(5);',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testExtendOnlyStubbedClass(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/fixtures/stubs/partial_class.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                namespace Foo;

                class A extends PartiallyStubbedClass {}

                (new A)->foo(A::class);',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testStubFileWithExtendedStubbedClass(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/fixtures/stubs/partial_class.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                namespace Foo;

                class Bar extends PartiallyStubbedClass  {}

                new Bar();',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testStubFileWithPartialClassDefinitionWithCoercion(): void
    {
        $this->expectExceptionMessage('TypeCoercion');
        $this->expectException(CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/fixtures/stubs/partial_class.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                namespace Foo;

                class PartiallyStubbedClass  {
                    /**
                     * @param string $a
                     * @return object
                     */
                    public function foo(string $a) {
                        return new self;
                    }
                }

                (new PartiallyStubbedClass())->foo("dasda");',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testStubFileWithPartialClassDefinitionGeneralReturnType(): void
    {
        $this->expectExceptionMessage('InvalidReturnStatement');
        $this->expectException(CodeException::class);
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/fixtures/stubs/partial_class.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                namespace Foo;

                class PartiallyStubbedClass  {
                    /**
                     * @param string $a
                     * @return object
                     */
                    public function foo(string $a) {
                        return new \stdClass;
                    }
                }',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testStubFileWithTemplatedClassDefinitionAndMagicMethodOverride(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/fixtures/stubs/templated_class.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                class A {
                    /**
                     * @param int $id
                     * @param ?int $lockMode
                     * @param ?int $lockVersion
                     * @return mixed
                     */
                    public function find($id, $lockMode = null, $lockVersion = null) {
                        return null;
                    }
                }

                /**
                 * @psalm-suppress MissingTemplateParam
                 */
                class B extends A {}

                class Obj {}

                /**
                 * @method ?Obj find(int $id, $lockMode = null, $lockVersion = null)
                 */
                class C extends B {}',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testInheritedMethodUsedInStub(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    findUnusedCode="true"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                </psalm>',
            ),
        );

        $this->project_analyzer->getCodebase()->reportUnusedCode();

        $vendor_file_path = (string) getcwd() . '/vendor/vendor_class.php';

        $this->addFile(
            $vendor_file_path,
            '<?php
                namespace SomeVendor;

                class VendorClass {
                    abstract public function foo() : void;

                    public static function vendorFunction(VendorClass $v) : void {
                        $v->foo();
                    }
                }',
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                class MyClass extends \SomeVendor\VendorClass {
                    public function foo() : void {}
                }

                \SomeVendor\VendorClass::vendorFunction(new MyClass);',
        );

        $this->analyzeFile($file_path, new Context(), false);

        $this->project_analyzer->consolidateAnalyzedData();
    }

    public function testStubOverridingMissingClass(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <stubs>
                        <file name="tests/fixtures/stubs/MissingClass.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php

                echo "hello";',
        );

        $this->expectException(InvalidClasslikeOverrideException::class);

        $this->analyzeFile($file_path, new Context());
    }

    public function testStubOverridingMissingMethod(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <stubs>
                        <file name="tests/fixtures/stubs/MissingMethod.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php

                echo "hello";',
        );

        $this->expectException(InvalidMethodOverrideException::class);

        $this->analyzeFile($file_path, new Context());
    }

    public function testStubReplacingInterfaceDocblock(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <stubs>
                        <file name="tests/fixtures/stubs/Doctrine.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );

        $this->addFile(
            (string) getcwd() . '/vendor/doctrine/import.php',
            '<?php
                namespace Doctrine\ORM;

                interface EntityManagerInterface
                {
                    /**
                     * @param string $entityName The name of the entity type.
                     * @param mixed  $id         The entity identifier.
                     *
                     * @return object|null The entity reference.
                     */
                    public function getReference($entityName, $id);
                }

                class EntityManager implements EntityManagerInterface
                {
                    /**
                     * @psalm-suppress InvalidReturnType
                     */
                    public function getReference($entityName, $id) {
                        /**
                         * @psalm-suppress InvalidReturnStatement
                         */
                        return new \stdClass;
                    }
                }',
        );

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                use Doctrine\ORM\EntityManager;

                class A {}

                function em(EntityManager $em) : void {
                    echo $em->getReference(A::class, 1);
                }',
        );

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('A|null');

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * This covers the following case encountered by mmcev106:
     * - A function was defined without a docblock
     * - The autoloader defined a global containing the path to that file
     * - The code being scanned required the path specified by the autoloader defined global
     * - A docblock was added via a stub that marked the function as a taint source
     * - The stub docblock was incorrectly ignored, causing the the taint source to be ignored
     */
    public function testAutoloadDefinedRequirePath(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                    autoloader="tests/fixtures/stubs/define_custom_require_path.php"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="tests/fixtures/stubs/custom_taint_source.phpstub" />
                    </stubs>
                </psalm>',
            ),
        );

        $this->project_analyzer->trackTaintedInputs();

        $file_path = (string) getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                require_once CUSTOM_REQUIRE_PATH;
                echo custom_taint_source();',
        );

        $this->expectExceptionMessage('TaintedHtml - /src/somefile.php');
        $this->analyzeFile($file_path, new Context());
    }
}
