<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;
use Psalm\Context;

class FileManipulationTest extends TestCase
{
    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;

    /** @var TestConfig|null */
    private static $config;

    /**
     * @return void
     */
    public function setUp()
    {
        FileChecker::clearCache();
        \Psalm\FileManipulation\FunctionDocblockManipulator::clearCache();

        $this->file_provider = new Provider\FakeFileProvider();

        if (!self::$config) {
            self::$config = new TestConfig();
            self::$config->addPluginPath('examples/ClassUnqualifier.php');
        }

        $this->project_checker = new \Psalm\Checker\ProjectChecker(
            self::$config,
            $this->file_provider,
            new Provider\FakeParserCacheProvider()
        );
    }

    /**
     * @dataProvider providerFileCheckerValidCodeParse
     *
     * @param string $input_code
     * @param string $output_code
     * @param string $php_version
     * @param string[] $issues_to_fix
     * @param bool $safe_types
     *
     * @return void
     */
    public function testValidCode($input_code, $output_code, $php_version, array $issues_to_fix, $safe_types)
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

        $this->project_checker->setIssuesToFix($keyed_issues_to_fix);
        $this->project_checker->alterCodeAfterCompletion(
            (int) $php_major_version,
            (int) $php_minor_version,
            false,
            $safe_types
        );

        $this->analyzeFile($file_path, $context);

        $this->project_checker->getCodebase()->updateFile($file_path, false);
        $this->assertSame($output_code, $this->project_checker->getCodebase()->getFileContents($file_path));
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
                true,
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
                true,
            ],
            'addMissingVoidReturnType71' => [
                '<?php
                    function foo() { }',
                '<?php
                    function foo(): void { }',
                '7.1',
                ['MissingReturnType'],
                true,
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
                true,
            ],
            'addMissingStringReturnType70' => [
                '<?php
                    function foo() {
                        return "hello";
                    }',
                '<?php
                    function foo(): string {
                        return "hello";
                    }',
                '7.0',
                ['MissingReturnType'],
                true,
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
                true,
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
                true,
            ],
            'addMissingNullableStringReturnType70' => [
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
                true,
            ],
            'addMissingStringReturnType71' => [
                '<?php
                    function foo() {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '<?php
                    function foo(): ?string {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '7.1',
                ['MissingReturnType'],
                true,
            ],
            'addMissingStringReturnTypeWithComment71' => [
                '<?php
                    function foo() /** : ?string */ {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '<?php
                    function foo(): ?string /** : ?string */ {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '7.1',
                ['MissingReturnType'],
                true,
            ],
            'addMissingStringReturnTypeWithSingleLineComment71' => [
                '<?php
                    function foo()// cool
                    {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '<?php
                    function foo(): ?string// cool
                    {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '7.1',
                ['MissingReturnType'],
                true,
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
                true,
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
                    function foo(): array {
                        return ["hello"];
                    }',
                '7.0',
                ['MissingReturnType'],
                true,
            ],
            'addMissingStringArrayReturnTypeFromCall71' => [
                '<?php
                    /** @return string[] */
                    function foo(): array {
                        return ["hello"];
                    }

                    function bar() {
                        return foo();
                    }',
                '<?php
                    /** @return string[] */
                    function foo(): array {
                        return ["hello"];
                    }

                    /**
                     * @return string[]
                     *
                     * @psalm-return array<mixed, string>
                     */
                    function bar(): array {
                        return foo();
                    }',
                '7.1',
                ['MissingReturnType'],
                true,
            ],
            'addMissingDocblockStringArrayReturnTypeFromCall71' => [
                '<?php
                    /** @return string[] */
                    function foo() {
                        return ["hello"];
                    }

                    function bar() {
                        return foo();
                    }',
                '<?php
                    /** @return string[] */
                    function foo() {
                        return ["hello"];
                    }

                    /**
                     * @return string[]
                     *
                     * @psalm-return array<mixed, string>
                     */
                    function bar() {
                        return foo();
                    }',
                '7.1',
                ['MissingReturnType'],
                true,
            ],
            'addMissingNullableStringReturnType71' => [
                '<?php
                    /** @return string[] */
                    function foo(): array {
                        return ["hello"];
                    }

                    function bar() {
                        foreach (foo() as $f) {
                            return $f;
                        }
                        return null;
                    }',
                '<?php
                    /** @return string[] */
                    function foo(): array {
                        return ["hello"];
                    }

                    /**
                     * @return null|string
                     */
                    function bar() {
                        foreach (foo() as $f) {
                            return $f;
                        }
                        return null;
                    }',
                '7.1',
                ['MissingReturnType'],
                true,
            ],
            'addMissingNullableStringReturnTypeWithMaybeReturn71' => [
                '<?php
                    function foo() {
                      if (rand(0, 1)) return new stdClass;
                    }',
                '<?php
                    /**
                     * @return stdClass|null
                     */
                    function foo() {
                      if (rand(0, 1)) return new stdClass;
                    }',
                '7.1',
                ['MissingReturnType'],
                true,
            ],
            'addMissingUnsafeNullableStringReturnType71' => [
                '<?php
                    /** @return string[] */
                    function foo(): array {
                        return ["hello"];
                    }

                    function bar() {
                        foreach (foo() as $f) {
                            return $f;
                        }
                        return null;
                    }',
                '<?php
                    /** @return string[] */
                    function foo(): array {
                        return ["hello"];
                    }

                    function bar(): ?string {
                        foreach (foo() as $f) {
                            return $f;
                        }
                        return null;
                    }',
                '7.1',
                ['MissingReturnType'],
                false,
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
                true,
            ],
            'fixInvalidIntReturnType70' => [
                '<?php
                    /**
                     * @return int
                     */
                    function foo(): int {
                        return "hello";
                    }',
                '<?php
                    /**
                     * @return string
                     */
                    function foo(): string {
                        return "hello";
                    }',
                '7.0',
                ['InvalidReturnType'],
                true,
            ],
            'fixInvalidIntReturnTypeJustInTypehint70' => [
                '<?php
                    function foo(): int {
                        return "hello";
                    }',
                '<?php
                    function foo(): string {
                        return "hello";
                    }',
                '7.0',
                ['InvalidReturnType'],
                true,
            ],
            'fixInvalidStringReturnTypeThatIsNotPhpCompatible70' => [
                '<?php
                    function foo(): string {
                        return rand(0, 1) ? "hello" : false;
                    }',
                '<?php
                    /**
                     * @return string|false
                     */
                    function foo() {
                        return rand(0, 1) ? "hello" : false;
                    }',
                '7.0',
                ['InvalidFalsableReturnType'],
                true,
            ],
            'fixInvalidIntReturnTypeThatIsNotPhpCompatible70' => [
                '<?php
                    function foo(): string {
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
                ['InvalidNullableReturnType'],
                true,
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
                true,
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
                true,
            ],
            'fixMismatchingDocblockReturnType70' => [
                '<?php
                    /**
                     * @return int
                     */
                    function foo(): string {
                        return "hello";
                    }',
                '<?php
                    /**
                     * @return string
                     */
                    function foo(): string {
                        return "hello";
                    }',
                '7.0',
                ['MismatchingDocblockReturnType'],
                true,
            ],
            'fixMismatchingDocblockParamType70' => [
                '<?php
                    /**
                     * @param int $s
                     */
                    function foo(string $s): string {
                        return "hello";
                    }',
                '<?php
                    /**
                     * @param string $s
                     */
                    function foo(string $s): string {
                        return "hello";
                    }',
                '7.0',
                ['MismatchingDocblockParamType'],
                true,
            ],
            'fixNamespacedMismatchingDocblockParamsType70' => [
                '<?php
                    namespace Foo\Bar {
                        class A {
                            /**
                             * @param \B $b
                             * @param \C $c
                             */
                            function foo(B $b, C $c): string {
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
                            function foo(B $b, C $c): string {
                                return "hello";
                            }
                        }
                        class B {}
                        class C {}
                    }',
                '7.0',
                ['MismatchingDocblockParamType'],
                true,
            ],
            'preserveFormat' => [
                '<?php
                    /**
                     * @other is
                     *    a friend of mine
                     *       + Members
                     *          - `google`
                     * @return int
                     */
                    function foo(): int {
                      return "hello";
                    }',
                '<?php
                    /**
                     * @other is
                     *    a friend of mine
                     *       + Members
                     *          - `google`
                     *
                     * @return string
                     */
                    function foo(): string {
                      return "hello";
                    }',
                '7.0',
                ['InvalidReturnType'],
                true,
            ],
            'possiblyUndefinedVariable' => [
                '<?php
                    $flag = rand(0, 1);
                    $otherflag = rand(0, 1);
                    $yetanotherflag = rand(0, 1);

                    if ($flag) {
                        if ($otherflag) {
                            $a = 5;
                        }

                        echo $a;
                    }

                    if ($flag) {
                        if ($yetanotherflag) {
                            $a = 5;
                        }

                        echo $a;
                    }',
                '<?php
                    $flag = rand(0, 1);
                    $otherflag = rand(0, 1);
                    $yetanotherflag = rand(0, 1);

                    $a = null;
                    if ($flag) {
                        if ($otherflag) {
                            $a = 5;
                        }

                        echo $a;
                    }

                    if ($flag) {
                        if ($yetanotherflag) {
                            $a = 5;
                        }

                        echo $a;
                    }',
                '5.6',
                ['PossiblyUndefinedVariable'],
                true,
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
                true,
            ],
        ];
    }
}
