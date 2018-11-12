<?php
namespace Psalm\Tests;

use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Context;
use Psalm\Tests\Internal\Provider;

class FileManipulationTest extends TestCase
{
    /** @var \Psalm\Internal\Analyzer\ProjectAnalyzer */
    protected $project_analyzer;

    /**
     * @return void
     */
    public function setUp()
    {
        FileAnalyzer::clearCache();
        \Psalm\Internal\FileManipulation\FunctionDocblockManipulator::clearCache();

        $this->file_provider = new Provider\FakeFileProvider();
    }

    /**
     * @dataProvider providerValidCodeParse
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
        $test_name = $this->getTestName();
        if (strpos($test_name, 'PHP7-') !== false) {
            if (version_compare(PHP_VERSION, '7.0.0dev', '<')) {
                $this->markTestSkipped('Test case requires PHP 7.');

                return;
            }
        } elseif (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        $config = new TestConfig();

        $this->project_analyzer = new \Psalm\Internal\Analyzer\ProjectAnalyzer(
            $config,
            new \Psalm\Internal\Provider\Providers(
                $this->file_provider,
                new Provider\FakeParserCacheProvider()
            )
        );

        if (empty($issues_to_fix)) {
            $config->addPluginPath('examples/plugins/ClassUnqualifier.php');
            $config->initializePlugins($this->project_analyzer);
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

        $this->project_analyzer->setIssuesToFix($keyed_issues_to_fix);
        $this->project_analyzer->alterCodeAfterCompletion(
            (int) $php_major_version,
            (int) $php_minor_version,
            false,
            $safe_types
        );

        $this->analyzeFile($file_path, $context);

        $this->project_analyzer->getCodebase()->analyzer->updateFile($file_path, false);
        $this->assertSame($output_code, $this->project_analyzer->getCodebase()->getFileContents($file_path));
    }

    /**
     * @return array
     */
    public function providerValidCodeParse()
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
                    };',
                '<?php
                    $a = /**
                     * @return string
                     */
                    function() {
                        return "hello";
                    };',
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
            'addMissingObjectLikeReturnType70' => [
                '<?php
                    function foo() {
                        return rand(0, 1) ? ["a" => "hello"] : ["a" => "goodbye", "b" => "hello again"];
                    }',
                '<?php
                    /**
                     * @return string[]
                     *
                     * @psalm-return array{a:string, b?:string}
                     */
                    function foo(): array {
                        return rand(0, 1) ? ["a" => "hello"] : ["a" => "goodbye", "b" => "hello again"];
                    }',
                '7.0',
                ['MissingReturnType'],
                true,
            ],
            'addMissingObjectLikeReturnTypeSeparateStatements70' => [
                '<?php
                    function foo() {
                        if (rand(0, 1)) {
                            return ["a" => "hello", "b" => "hello again"];
                        }

                        if (rand(0, 1)) {
                            return ["a" => "hello", "b" => "hello again"];
                        }

                        return ["a" => "goodbye"];
                    }',
                '<?php
                    /**
                     * @return string[]
                     *
                     * @psalm-return array{a:string, b?:string}
                     */
                    function foo(): array {
                        if (rand(0, 1)) {
                            return ["a" => "hello", "b" => "hello again"];
                        }

                        if (rand(0, 1)) {
                            return ["a" => "hello", "b" => "hello again"];
                        }

                        return ["a" => "goodbye"];
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
            'addSelfReturnType' => [
                '<?php
                    class A {
                        public function foo() {
                            return $this;
                        }
                    }',
                '<?php
                    class A {
                        public function foo(): self {
                            return $this;
                        }
                    }',
                '7.1',
                ['MissingReturnType'],
                false,
            ],
            'addMissingNullableReturnTypeInDocblockOnly71' => [
                '<?php
                    function foo() {
                      if (rand(0, 1)) {
                        return;
                      }

                      return "hello";
                    }

                    function bar() {
                      if (rand(0, 1)) {
                        return;
                      }

                      if (rand(0, 1)) {
                        return null;
                      }

                      return "hello";
                    }',
                '<?php
                    /**
                     * @return string|null
                     */
                    function foo() {
                      if (rand(0, 1)) {
                        return;
                      }

                      return "hello";
                    }

                    /**
                     * @return string|null
                     */
                    function bar() {
                      if (rand(0, 1)) {
                        return;
                      }

                      if (rand(0, 1)) {
                        return null;
                      }

                      return "hello";
                    }',
                '7.1',
                ['MissingReturnType'],
                false,
            ],
            'addMissingVoidReturnTypeToOldArray71' => [
                '<?php
                    function foo(array $a = array()) {}
                    function bar(array $a = array() )  {}',
                '<?php
                    function foo(array $a = array()): void {}
                    function bar(array $a = array() ): void  {}',
                '7.1',
                ['MissingReturnType'],
                false,
            ],
            'addMissingVoidReturnTypeClosureUse71' => [
                '<?php
                    $a = "foo";
                    $b = function() use ($a) {};',
                '<?php
                    $a = "foo";
                    $b = function() use ($a): void {};',
                '7.1',
                ['MissingClosureReturnType'],
                false,
            ],
            'dontAddMissingVoidReturnType56' => [
                '<?php
                    /** @return void */
                    function foo() { }

                    function bar() {
                        return foo();
                    }',
                '<?php
                    /** @return void */
                    function foo() { }

                    function bar() {
                        return foo();
                    }',
                '5.6',
                ['MissingReturnType'],
                true,
            ],
            'dontAddMissingVoidReturnTypehintForSubclass71' => [
                '<?php
                    class A {
                        public function foo() {}
                    }

                    class B extends A {
                        public function foo() {}
                    }',
                '<?php
                    class A {
                        /**
                         * @return void
                         */
                        public function foo() {}
                    }

                    class B extends A {
                        /**
                         * @return void
                         */
                        public function foo() {}
                    }',
                '7.1',
                ['MissingReturnType'],
                true,
            ],
            'dontAddMissingVoidReturnTypehintForPrivateMethodInSubclass71' => [
                '<?php
                    class A {
                        private function foo() {}
                    }

                    class B extends A {
                        private function foo() {}
                    }',
                '<?php
                    class A {
                        private function foo(): void {}
                    }

                    class B extends A {
                        private function foo(): void {}
                    }',
                '7.1',
                ['MissingReturnType'],
                true,
            ],
            'dontAddMissingClassReturnTypehintForSubclass71' => [
                '<?php
                    class A {
                        public function foo() {
                            return $this;
                        }
                    }

                    class B extends A {
                        public function foo() {
                            return $this;
                        }
                    }',
                '<?php
                    class A {
                        /**
                         * @return self
                         */
                        public function foo() {
                            return $this;
                        }
                    }

                    class B extends A {
                        /**
                         * @return self
                         */
                        public function foo() {
                            return $this;
                        }
                    }',
                '7.1',
                ['MissingReturnType'],
                true,
            ],
            'dontAddMissingClassReturnTypehintForSubSubclass71' => [
                '<?php
                    class A {
                        public function foo() {
                            return $this;
                        }
                    }

                    class B extends A {}

                    class C extends B {
                        public function foo() {
                            return $this;
                        }
                    }',
                '<?php
                    class A {
                        /**
                         * @return self
                         */
                        public function foo() {
                            return $this;
                        }
                    }

                    class B extends A {}

                    class C extends B {
                        /**
                         * @return self
                         */
                        public function foo() {
                            return $this;
                        }
                    }',
                '7.1',
                ['MissingReturnType'],
                true,
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
                     * Here is a paragraph
                     *
                     * And another one
                     *
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
                     * Here is a paragraph
                     *
                     * And another one
                     *
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
                ['PossiblyUndefinedGlobalVariable'],
                true,
            ],
            'twoPossiblyUndefinedVariables' => [
                '<?php
                    if (rand(0, 1)) {
                      $a = 1;
                      $b = 2;
                    }

                    echo $a;
                    echo $b;',
                '<?php
                    $a = null;
                    $b = null;
                    if (rand(0, 1)) {
                      $a = 1;
                      $b = 2;
                    }

                    echo $a;
                    echo $b;',
                '5.6',
                ['PossiblyUndefinedGlobalVariable'],
                true,
            ],
            'possiblyUndefinedVariableInElse' => [
                '<?php
                    if (rand(0, 1)) {
                      // do nothing
                    } else {
                        $a = 5;
                    }

                    echo $a;',
                '<?php
                    $a = null;
                    if (rand(0, 1)) {
                      // do nothing
                    } else {
                        $a = 5;
                    }

                    echo $a;',
                '5.6',
                ['PossiblyUndefinedGlobalVariable'],
                true,
            ],
            'unsetPossiblyUndefinedVariable' => [
                '<?php
                    if (rand(0, 1)) {
                      $a = "bar";
                    }
                    unset($a);',
                '<?php
                    if (rand(0, 1)) {
                      $a = "bar";
                    }
                    unset($a);',
                '5.6',
                ['PossiblyUndefinedGlobalVariable'],
                true,
            ],
            'addLessSpecificArrayReturnType71' => [
                '<?php
                    namespace A\B {
                        class C {}
                    }

                    namespace C {
                        use A\B;

                        class D {
                            public function getArrayOfC(): array {
                                return [new \A\B\C];
                            }
                        }
                    }',
                '<?php
                    namespace A\B {
                        class C {}
                    }

                    namespace C {
                        use A\B;

                        class D {
                            /**
                             * @return \A\B\C[]
                             *
                             * @psalm-return array{0:\A\B\C}
                             */
                            public function getArrayOfC(): array {
                                return [new \A\B\C];
                            }
                        }
                    }',
                '7.1',
                ['LessSpecificReturnType'],
                true,
            ],
            'fixLessSpecificClosureReturnType' => [
                '<?php
                    function foo(string $name) : string {
                        return $name . " hello";
                    }

                    function bar() : callable {
                        return function(string $name) {
                            return foo($name);
                        };
                    }',
                '<?php
                    function foo(string $name) : string {
                        return $name . " hello";
                    }

                    /**
                     * @return Closure
                     *
                     * @psalm-return Closure(string):string
                     */
                    function bar() : Closure {
                        return function(string $name) {
                            return foo($name);
                        };
                    }',
                '7.1',
                ['LessSpecificReturnType'],
                false,
            ],
            'fixLessSpecificReturnTypePreserveNotes' => [
                '<?php
                    namespace Foo;

                    /**
                     * @return object some description
                     */
                    function foo() {
                        return new \stdClass();
                    }',
                '<?php
                    namespace Foo;

                    /**
                     * @return \stdClass some description
                     */
                    function foo() {
                        return new \stdClass();
                    }',
                '5.6',
                ['LessSpecificReturnType'],
                false,
            ],
            'fixInvalidReturnTypePreserveNotes' => [
                '<?php
                    namespace Foo;

                    class A {
                        /**
                         * @return string some description
                         */
                        function foo() {
                            return new \stdClass();
                        }
                    }',
                '<?php
                    namespace Foo;

                    class A {
                        /**
                         * @return \stdClass some description
                         */
                        function foo() {
                            return new \stdClass();
                        }
                    }',
                '5.6',
                ['InvalidReturnType'],
                false,
            ],
            'fixInvalidNullableReturnTypePreserveNotes' => [
                '<?php
                    namespace Foo;

                    class A {
                        /**
                         * @return string|null some notes
                         */
                        function foo() : ?string {
                            return "hello";
                        }
                    }',
                '<?php
                    namespace Foo;

                    class A {
                        /**
                         * @return string some notes
                         */
                        function foo() : string {
                            return "hello";
                        }
                    }',
                '7.1',
                ['LessSpecificReturnType'],
                false,
            ],
            'fixLessSpecificReturnType' => [
                '<?php
                    class A {}
                    class B extends A {}

                    class C extends B {
                        public function getB(): ?\A {
                            return new B;
                        }
                        public function getC(): ?\A {
                            return new C;
                        }
                    }',
                '<?php
                    class A {}
                    class B extends A {}

                    class C extends B {
                        public function getB(): B {
                            return new B;
                        }
                        public function getC(): self {
                            return new C;
                        }
                    }',
                '7.1',
                ['LessSpecificReturnType'],
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
