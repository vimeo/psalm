<?php
namespace Psalm\Tests;

use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Config;
use Psalm\Context;
use Psalm\Tests\Internal\Provider;

class StubTest extends TestCase
{
    /** @var TestConfig */
    protected static $config;

    /**
     * @return void
     */
    public static function setUpBeforeClass()
    {
        self::$config = new TestConfig();

        if (!defined('PSALM_VERSION')) {
            define('PSALM_VERSION', '2.0.0');
        }

        if (!defined('PHP_PARSER_VERSION')) {
            define('PHP_PARSER_VERSION', '4.0.0');
        }
    }

    /**
     * @return void
     */
    public function setUp()
    {
        FileAnalyzer::clearCache();
        $this->file_provider = new Provider\FakeFileProvider();
    }

    /**
     * @param  Config $config
     *
     * @return \Psalm\Internal\Analyzer\ProjectAnalyzer
     */
    private function getProjectAnalyzerWithConfig(Config $config)
    {
        $project_analyzer = new \Psalm\Internal\Analyzer\ProjectAnalyzer(
            $config,
            new \Psalm\Internal\Provider\Providers(
                $this->file_provider,
                new Provider\FakeParserCacheProvider()
            )
        );
        $project_analyzer->setPhpVersion('7.3');

        $config->visitComposerAutoloadFiles($project_analyzer, false);

        return $project_analyzer;
    }

    /**
     * @expectedException        \Psalm\Exception\ConfigException
     * @expectedExceptionMessage Cannot resolve stubfile path
     *
     * @return                   void
     */
    public function testNonexistentStubFile()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>

                    <stubs>
                        <file name="stubs/invalidfile.php" />
                    </stubs>
                </psalm>'
            )
        );
    }

    /**
     * @return void
     */
    public function testStubFile()
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
                        <file name="tests/stubs/systemclass.php" />
                    </stubs>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                $a = new SystemClass();
                echo SystemClass::HELLO;

                $b = $a->foo(5, "hello");
                $c = SystemClass::bar(5, "hello");'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return void
     */
    public function testNamespacedStubClass()
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
                        <file name="tests/stubs/namespaced_class.php" />
                    </stubs>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                $a = new Foo\SystemClass();
                echo Foo\SystemClass::HELLO;

                $b = $a->foo(5, "hello");
                $c = Foo\SystemClass::bar(5, "hello");

                echo Foo\BAR;'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return void
     */
    public function testStubRegularFunction()
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
                        <file name="tests/stubs/custom_functions.php" />
                    </stubs>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                echo barBar("hello");'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return void
     */
    public function testStubVariadicFunction()
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
                        <file name="tests/stubs/custom_functions.php" />
                    </stubs>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                variadic("bat", "bam");'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidScalarArgument
     *
     * @return                   void
     */
    public function testStubVariadicFunctionWrongArgType()
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
                        <file name="tests/stubs/custom_functions.php" />
                    </stubs>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                variadic("bat", 5);'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage TooManyArguments
     *
     * @return                   void
     */
    public function testUserVariadicWithFalseVariadic()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                /**
                 * @param string ...$bar
                 */
                function variadic() : void {}
                variadic("hello");'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return void
     */
    public function testPolyfilledFunction()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    autoloader="tests/stubs/polyfill.php"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                $a = random_bytes(16);
                $b = new_random_bytes(16);'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return void
     */
    public function testClassAlias()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm
                    autoloader="tests/stubs/class_alias.php"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

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

                class E implements IAlias {}'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return void
     */
    public function testStubFunctionWithFunctionExists()
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
                        <file name="tests/stubs/custom_functions.php" />
                    </stubs>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                function_exists("fooBar");
                echo barBar("hello");'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return void
     */
    public function testNamespacedStubFunctionWithFunctionExists()
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
                        <file name="tests/stubs/custom_functions.php" />
                    </stubs>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                namespace A;
                function_exists("fooBar");
                echo barBar("hello");'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedFunction - /src/somefile.php:2 - Function barBar does not exist
     *
     * @return                   void
     */
    public function testNoStubFunction()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                echo barBar("hello");'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return void
     */
    public function testNamespacedStubFunction()
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
                        <file name="tests/stubs/namespaced_functions.php" />
                    </stubs>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                echo Foo\barBar("hello");'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return void
     */
    public function testConditionalNamespacedStubFunction()
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
                        <file name="tests/stubs/conditional_namespaced_functions.php" />
                    </stubs>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                echo Foo\barBar("hello");'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return void
     */
    public function testStubFileWithExistingClassDefinition()
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
                        <file name="tests/stubs/logicexception.php" />
                    </stubs>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                $a = new LogicException(5);'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return void
     */
    public function testStubFileWithPartialClassDefinitionWithMoreMethods()
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
                        <file name="tests/stubs/partial_class.php" />
                    </stubs>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

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
                (new PartiallyStubbedClass())->bar(5);'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage TypeCoercion
     *
     * @return                   void
     */
    public function testStubFileWithPartialClassDefinitionWithCoercion()
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
                        <file name="tests/stubs/partial_class.php" />
                    </stubs>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

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

                (new PartiallyStubbedClass())->foo("dasda");'
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidReturnStatement
     *
     * @return                   void
     */
    public function testStubFileWithPartialClassDefinitionGeneralReturnType()
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
                        <file name="tests/stubs/partial_class.php" />
                    </stubs>
                </psalm>'
            )
        );

        $file_path = getcwd() . '/src/somefile.php';

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
                }'
        );

        $this->analyzeFile($file_path, new Context());
    }
}
