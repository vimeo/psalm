<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;
use Psalm\Context;

class FileManipulationTest extends TestCase
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
            $this->file_provider,
            new Provider\FakeParserCacheProvider()
        );

        if (!self::$config) {
            self::$config = new TestConfig();
            self::$config->addPluginPath('examples/ClassUnqualifier.php');
        }

        $this->project_checker->setConfig(self::$config);
    }

    /**
     * @dataProvider providerFileCheckerValidCodeParse
     *
     * @param string $input_code
     * @param string $output_code
     * @param string $php_version
     * @param string[] $issues_to_fix
     *
     * @return void
     */
    public function testValidCode($input_code, $output_code, $php_version, array $issues_to_fix)
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

        $this->addFile(
            $file_path,
            $input_code
        );

        list($php_major_version, $php_minor_version) = explode('.', $php_version);

        $keyed_issues_to_fix = [];

        foreach ($issues_to_fix as $issue) {
            $keyed_issues_to_fix[$issue] = true;
        }

        $file_checker = new FileChecker($file_path, $this->project_checker);

        $this->project_checker->setIssuesToFix($keyed_issues_to_fix);
        $this->project_checker->alterCodeAfterCompletion((int) $php_major_version, (int) $php_minor_version);

        $file_checker->visitAndAnalyzeMethods($context);
        $this->project_checker->updateFile($file_path, false);
        $this->assertSame($output_code, $this->project_checker->getFileContents($file_path));
    }

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'addMissingVoidReturnType56' => [
                '<?php
                    function foo() { }',
                '<?php
                    /**
                     * @return void
                     */
                    function foo() { }',
                '5.6',
                ['MissingReturnType'],
            ],
            'addMissingVoidReturnType70' => [
                '<?php
                    function foo() { }',
                '<?php
                    /**
                     * @return void
                     */
                    function foo() { }',
                '7.0',
                ['MissingReturnType'],
            ],
            'addMissingVoidReturnType71' => [
                '<?php
                    function foo() { }',
                '<?php
                    function foo() : void { }',
                '7.1',
                ['MissingReturnType'],
            ],
            'addMissingStringReturnType56' => [
                '<?php
                    function foo() {
                        return "hello";
                    }',
                '<?php
                    /**
                     * @return string
                     */
                    function foo() {
                        return "hello";
                    }',
                '5.6',
                ['MissingReturnType'],
            ],
            'addMissingStringReturnType70' => [
                '<?php
                    function foo() {
                        return "hello";
                    }',
                '<?php
                    function foo() : string {
                        return "hello";
                    }',
                '7.0',
                ['MissingReturnType'],
            ],
            'addMissingClosureStringReturnType56' => [
                '<?php
                    $a = function() {
                        return "hello";
                    }',
                '<?php
                    $a = /**
                     * @return string
                     */
                    function() {
                        return "hello";
                    }',
                '5.6',
                ['MissingClosureReturnType'],
            ],
            'addMissingNullableStringReturnType56' => [
                '<?php
                    function foo() {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '<?php
                    /**
                     * @return string|null
                     */
                    function foo() {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '5.6',
                ['MissingReturnType'],
            ],
            'addMissingStringReturnType70' => [
                '<?php
                    function foo() {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '<?php
                    /**
                     * @return string|null
                     */
                    function foo() {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '7.0',
                ['MissingReturnType'],
            ],
            'addMissingStringReturnType71' => [
                '<?php
                    function foo() {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '<?php
                    function foo() : ?string {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '7.1',
                ['MissingReturnType'],
            ],
            'addMissingStringReturnTypeWithComment71' => [
                '<?php
                    function foo() /** : ?string */ {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '<?php
                    function foo() : ?string /** : ?string */ {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '7.1',
                ['MissingReturnType'],
            ],
            'addMissingStringReturnTypeWithSingleLineComment71' => [
                '<?php
                    function foo()// cool
                    {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '<?php
                    function foo() : ?string// cool
                    {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '7.1',
                ['MissingReturnType'],
            ],
            'addMissingStringArrayReturnType56' => [
                '<?php
                    function foo() {
                        return ["hello"];
                    }',
                '<?php
                    /**
                     * @return string[]
                     *
                     * @psalm-return array{0:string}
                     */
                    function foo() {
                        return ["hello"];
                    }',
                '5.6',
                ['MissingReturnType'],
            ],
            'addMissingStringArrayReturnType70' => [
                '<?php
                    function foo() {
                        return ["hello"];
                    }',
                '<?php
                    /**
                     * @return string[]
                     *
                     * @psalm-return array{0:string}
                     */
                    function foo() : array {
                        return ["hello"];
                    }',
                '7.0',
                ['MissingReturnType'],
            ],
            'fixInvalidIntReturnType56' => [
                '<?php
                    /**
                     * @return int
                     */
                    function foo() {
                        return "hello";
                    }',
                '<?php
                    /**
                     * @return string
                     */
                    function foo() {
                        return "hello";
                    }',
                '5.6',
                ['InvalidReturnType'],
            ],
            'fixInvalidIntReturnType70' => [
                '<?php
                    /**
                     * @return int
                     */
                    function foo() : int {
                        return "hello";
                    }',
                '<?php
                    /**
                     * @return string
                     */
                    function foo() : string {
                        return "hello";
                    }',
                '7.0',
                ['InvalidReturnType'],
            ],
            'fixInvalidIntReturnTypeJustInTypehint70' => [
                '<?php
                    function foo() : int {
                        return "hello";
                    }',
                '<?php
                    function foo() : string {
                        return "hello";
                    }',
                '7.0',
                ['InvalidReturnType'],
            ],
            'fixInvalidIntReturnTypeJustInTypehintWithComment70' => [
                '<?php
                    function foo() /** cool : beans */ : int /** cool : beans */ {
                        return "hello";
                    }',
                '<?php
                    function foo() /** cool : beans */ : string /** cool : beans */ {
                        return "hello";
                    }',
                '7.0',
                ['InvalidReturnType'],
            ],
            'fixInvalidIntReturnTypeJustInTypehintWithSingleLineComment70' => [
                '<?php
                    function foo() // hello
                    : int {
                        return "hello";
                    }',
                '<?php
                    function foo() // hello
                    : string {
                        return "hello";
                    }',
                '7.0',
                ['InvalidReturnType'],
            ],
            'fixMismatchingDocblockReturnType70' => [
                '<?php
                    /**
                     * @return int
                     */
                    function foo() : string {
                        return "hello";
                    }',
                '<?php
                    /**
                     * @return string
                     */
                    function foo() : string {
                        return "hello";
                    }',
                '7.0',
                ['MismatchingDocblockReturnType'],
            ],
            'fixMismatchingDocblockParamType70' => [
                '<?php
                    /**
                     * @param int $s
                     */
                    function foo(string $s) : string {
                        return "hello";
                    }',
                '<?php
                    /**
                     * @param string $s
                     */
                    function foo(string $s) : string {
                        return "hello";
                    }',
                '7.0',
                ['MismatchingDocblockParamType'],
            ],
            'fixNamespacedMismatchingDocblockParamsType70' => [
                '<?php
                    namespace Foo\Bar {
                        class A {
                            /**
                             * @param \B $b
                             * @param \C $c
                             */
                            function foo(B $b, C $c) : string {
                                return "hello";
                            }
                        }
                        class B {}
                        class C {}
                    }',
                '<?php
                    namespace Foo\Bar {
                        class A {
                            /**
                             * @param B $b
                             * @param C $c
                             */
                            function foo(B $b, C $c) : string {
                                return "hello";
                            }
                        }
                        class B {}
                        class C {}
                    }',
                '7.0',
                ['MismatchingDocblockParamType'],
            ],
            'useUnqualifierPlugin' => [
                '<?php
                    namespace A\B\C {
                        class D {}
                    }
                    namespace Foo\Bar {
                        use A\B\C\D;

                        new \A\B\C\D();
                    }',
                '<?php
                    namespace A\B\C {
                        class D {}
                    }
                    namespace Foo\Bar {
                        use A\B\C\D;

                        new D();
                    }',
                PHP_VERSION,
                [],
            ],
        ];
    }
}
