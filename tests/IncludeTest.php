<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Exception\CodeException;
use Psalm\Internal\Analyzer\FileAnalyzer;

use function getcwd;
use function preg_quote;
use function strpos;

use const DIRECTORY_SEPARATOR;

class IncludeTest extends TestCase
{
    /**
     * @dataProvider providerTestValidIncludes
     * @param array<int, string> $files_to_check
     * @param array<string, string> $files
     * @param list<string> $ignored_issues
     */
    public function testValidInclude(
        array $files,
        array $files_to_check,
        bool $hoist_constants = false,
        array $ignored_issues = [],
    ): void {
        $codebase = $this->project_analyzer->getCodebase();

        foreach ($files as $file_path => $contents) {
            $this->addFile($file_path, $contents);
            $codebase->scanner->addFilesToShallowScan([$file_path => $file_path]);
        }

        foreach ($files_to_check as $file_path) {
            $codebase->addFilesToAnalyze([$file_path => $file_path]);
        }

        $config = $codebase->config;
        $config->skip_checks_on_unresolvable_includes = true;

        foreach ($ignored_issues as $error_level) {
            $config->setCustomErrorLevel($error_level, Config::REPORT_SUPPRESS);
        }

        $codebase->scanFiles();

        $config->hoist_constants = $hoist_constants;

        foreach ($files_to_check as $file_path) {
            $file_analyzer = new FileAnalyzer($this->project_analyzer, $file_path, $config->shortenFileName($file_path));
            $file_analyzer->analyze();
        }
    }

    /**
     * @dataProvider providerTestInvalidIncludes
     * @param array<int, string> $files_to_check
     * @param array<string, string> $files
     * @param list<string> $directories
     */
    public function testInvalidInclude(
        array $files,
        array $files_to_check,
        string $error_message,
        array $directories = [],
    ): void {
        if (strpos($this->getTestName(), 'SKIPPED-') !== false) {
            $this->markTestSkipped();
        }

        $codebase = $this->project_analyzer->getCodebase();

        foreach ($files as $file_path => $contents) {
            $this->addFile($file_path, $contents);
            $codebase->scanner->addFilesToShallowScan([$file_path => $file_path]);
        }

        foreach ($files_to_check as $file_path) {
            $codebase->addFilesToAnalyze([$file_path => $file_path]);
        }

        foreach ($directories as $directory) {
            $this->file_provider->fake_directories[$directory] = true;
        }

        $config = $codebase->config;
        $config->skip_checks_on_unresolvable_includes = false;

        $this->expectException(CodeException::class);
        $this->expectExceptionMessageMatches('/\b' . preg_quote($error_message, '/') . '\b/');

        $codebase->scanFiles();

        foreach ($files_to_check as $file_path) {
            $file_analyzer = new FileAnalyzer($this->project_analyzer, $file_path, $config->shortenFileName($file_path));
            $file_analyzer->analyze();
        }
    }

    /**
     * @return array<string,array{files:array<string,string>,files_to_check:array<int,string>,hoist_constants?:bool,ignored_issues?:list<string>}>
     */
    public function providerTestValidIncludes(): array
    {
        return [
            'basicRequire' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require("file1.php");

                        class B {
                            public function foo(): void {
                                (new A)->fooFoo();
                            }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        class A{
                            public function fooFoo(): void {

                            }
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
            ],
            'requireSingleStringType' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        $a = "file1.php";
                        require($a);

                        class B {
                            public function foo(): void {
                                (new A)->fooFoo();
                            }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        class A{
                            public function fooFoo(): void {

                            }
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
            ],
            'nestedRequire' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        class A{
                            public function fooFoo(): void {

                            }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require("file1.php");

                        class B extends A{
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file3.php' => '<?php
                        require("file2.php");

                        class C extends B {
                            public function doFoo(): void {
                                $this->fooFoo();
                            }
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file3.php',
                ],
            ],
            'requireNamespace' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        namespace Foo;

                        class A{
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require("file1.php");

                        class B {
                            public function foo(): void {
                                (new Foo\A);
                            }
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
            ],
            'requireFunction' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        function fooFoo(): void {

                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require("file1.php");

                        fooFoo();',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
            ],
            'namespacedRequireFunction' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        function fooFoo(): void {

                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        namespace Foo;

                        require("file1.php");

                        \fooFoo();',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
            ],
            'requireConstant' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        const FOO = 5;
                        define("BAR", "bat");',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require("file1.php");

                        echo FOO;
                        echo BAR;',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
            ],
            'requireNamespacedWithUse' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        namespace Foo;

                        class A{
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require("file1.php");

                        use Foo\A;

                        class B {
                            public function foo(): void {
                                (new A);
                            }
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
            ],
            'noInfiniteRequireLoop' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        require_once("file2.php");
                        require_once("file3.php");

                        class B extends A {
                            public function doFoo(): void {
                                $this->fooFoo();
                            }
                        }

                        class C {}

                        new D();',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require_once("file3.php");

                        class A{
                            public function fooFoo(): void { }
                        }

                        new C();',

                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file3.php' => '<?php
                        require_once("file1.php");

                        class D{ }

                        new C();',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file3.php',
                ],
            ],
            'analyzeAllClasses' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        require_once("file2.php");
                        class B extends A {
                            public function doFoo(): void {
                                $this->fooFoo();
                            }
                        }
                        class C {
                            public function barBar(): void { }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require_once("file1.php");
                        class A{
                            public function fooFoo(): void { }
                        }
                        class D extends C {
                            public function doBar(): void {
                                $this->barBar();
                            }
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
            ],
            'loopWithInterdependencies' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        require_once("file2.php");
                        class A {}
                        class D extends C {}
                        new B();',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require_once("file1.php");
                        class C {}
                        class B extends A {}
                        new D();',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
            ],
            'variadicArgs' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        require_once("file2.php");
                        variadicArgs(5, 2, "hello");',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        function variadicArgs() : void {
                            $args = func_get_args();
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php',
                ],
            ],
            'globalIncludedVar' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        $a = 5;
                        require_once("file2.php");',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require_once("file3.php");',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file3.php' => '<?php
                        function getGlobal() : void {
                            global $a;

                            echo $a;
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php',
                ],
            ],
            'returnNamespacedFunctionCallType' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        namespace Foo;

                        class A{
                            function doThing() : void {}
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        namespace Bar;

                        require("file1.php");

                        use Foo\A;

                        /** @return A */
                        function getThing() {
                            return new A;
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file3.php' => '<?php
                        namespace Bat;

                        require("file2.php");

                        class C {
                            function boop() : void {
                                \Bar\getThing()->doThing();
                            }
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file3.php',
                ],
            ],
            'functionUsedElsewhere' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        require_once("file2.php");
                        require_once("file3.php");
                        function foo() : void {}',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        foo();
                        array_filter([1, 2, 3, 4], "bar");',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file3.php' => '<?php
                        function bar(int $i) : bool { return (bool) rand(0, 1); }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php',
                ],
            ],
            'closureInIncludedFile' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        require_once("file2.php");',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        return function(): string { return "asd"; };',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php',
                ],
            ],
            'hoistConstants' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        require_once("file2.php");',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        function bat() : void {
                            echo FOO . BAR;
                        }

                        define("FOO", 5);
                        const BAR = "BAR";',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
                'hoist_constants' => true,
            ],
            'duplicateClasses' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        class A {
                            /** @var string|null */
                            protected $a;
                            public function aa() : void {}
                            public function bb() : void { $this->aa(); }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        class A {
                            /** @var string|null */
                            protected $b;
                            public function dd() : void {}
                            public function zz() : void { $this->dd(); }
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
                'hoist_constants' => false,
                'ignored_issues' => ['DuplicateClass'],
            ],
            'duplicateClassesProperty' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        class A {
                            protected $a;
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        class A {
                            protected $b;
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
                'hoist_constants' => false,
                'ignored_issues' => ['DuplicateClass', 'MissingPropertyType'],
            ],
            'functionsDefined' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'index.php' => '<?php
                        include "func.php";
                        include "Base.php";
                        include "Child.php";',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'func.php' => '<?php
                        namespace ns;

                        function func(): void {}

                        define("ns\\cons", 0);

                        cons;',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'Base.php' => '<?php
                        namespace ns;

                        func();

                        cons;

                        class Base {
                            public function __construct() {}
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'Child.php' => '<?php
                        namespace ns;

                        func();

                        cons;

                        class Child extends Base {
                            /**
                             * @var int
                             */
                            public $x;

                            public function __construct() {
                                parent::__construct();

                                $this->x = 5;
                            }
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'index.php',
                ],
            ],
            'suppressMissingFile' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        function getEndpoints() : void {
                            $listFile = "tests/fixtures/stubs/custom_functions.phpstub";
                            if (!file_exists($listFile)) {
                                throw new RuntimeException("Endpoint list not found");
                            }
                            include $listFile;
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php',
                ],
            ],
            'nestedParentFile' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'b' . DIRECTORY_SEPARATOR . 'c' . DIRECTORY_SEPARATOR . 'd' . DIRECTORY_SEPARATOR . 'script.php' => '<?php
                        require_once __DIR__ . "/../../../../e/begin.php";',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'e' . DIRECTORY_SEPARATOR . 'begin.php' => '<?php
                        echo "hello";',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'b' . DIRECTORY_SEPARATOR . 'c' . DIRECTORY_SEPARATOR . 'd' . DIRECTORY_SEPARATOR . 'script.php',
                ],
            ],
            'undefinedMethodAfterInvalidRequire' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        /** @psalm-suppress MissingFile */
                        require("doesnotexist.php");
                        require("file1.php");

                        foo();
                        bar();
                        ',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        function bar(): void {}
                        ',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
            ],
            'returnValue' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        $a = require("file1.php");
                        echo $a;',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        return "hello";',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
            ],
            'noCrash' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'classes.php' => '<?php
                        // one.php

                        if (true) {
                            class One {}
                        }
                        else {
                            class One {}
                        }

                        class Two {}',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'user.php' => '<?php
                        include("classes.php");

                        new Two();',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'user.php',
                ],
            ],
            'pathStartingWithDot' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'test_1.php' => '<?php
                        // direct usage
                        require "./include_1.php";
                        require "./a/include_2.php";

                        Class_1::foo();
                        Class_2::bar();
                        ',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'include_1.php' => '<?php
                        class Class_1 {
                            public static function foo(): void {
                                // empty;
                            }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'include_2.php' => '<?php
                        class Class_2 {
                            public static function bar(): void {
                                // empty;
                            }
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'test_1.php',
                ],
            ],
        ];
    }

    /**
     * @return array<string,array{
     *     files: array<string,string>,
     *     files_to_check: array<int,string>,
     *     error_message: string,
     *     directories?: list<string>
     * }>
     */
    public function providerTestInvalidIncludes(): array
    {
        return [
            'undefinedMethodInRequire' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require("file1.php");

                        class B {
                            public function foo(): void {
                                (new A)->fooFo();
                            }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        class A{
                            public function fooFoo(): void {

                            }
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
                'error_message' => 'UndefinedMethod',
            ],
            'requireFunctionWithStrictTypes' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        function fooFoo(int $bar): void {

                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php declare(strict_types=1);
                        require("file1.php");

                        fooFoo("hello");',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
                'error_message' => 'InvalidArgument',
            ],
            'requireFunctionWithStrictTypesInClass' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        function fooFoo(int $bar): void {

                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php declare(strict_types=1);
                        require("file1.php");

                        class A {
                            public function foo() {
                                fooFoo("hello");
                            }
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
                'error_message' => 'InvalidArgument',
            ],
            'requireFunctionWithWeakTypes' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        function fooFoo(int $bar): void {

                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require("file1.php");

                        fooFoo("hello");',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
                'error_message' => 'InvalidScalarArgument',
            ],
            'requireFunctionWithStrictTypesButDocblockType' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        /** @param int $bar */
                        function fooFoo($bar): void {

                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php declare(strict_types=1);
                        require("file1.php");

                        fooFoo("hello");',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
                'error_message' => 'InvalidArgument',
            ],
            'namespacedRequireFunction' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        function fooFoo(): void {

                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        namespace Foo;

                        require("file1.php");

                        \Foo\fooFoo();',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
                'error_message' => 'UndefinedFunction',
            ],
            'globalIncludedIncorrectVar' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        $a = 5;
                        require_once("file2.php");',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require_once("file3.php");',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file3.php' => '<?php
                        function getGlobal() : void {
                            global $b;

                            echo $a;
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php',
                ],
                'error_message' => 'UndefinedVariable',
            ],
            'invalidTraitFunctionReturnInUncheckedFile' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require("file1.php");

                        class B {
                            use A;
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        trait A{
                            public function fooFoo(): string {
                                return 5;
                            }
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
                'error_message' => 'InvalidReturnType',
            ],
            'invalidDoubleNestedTraitFunctionReturnInUncheckedFile' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file3.php' => '<?php
                        namespace Foo;

                        require("file2.php");

                        use Bar\B;

                        class C {
                            use B;
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        namespace Bar;

                        require("file1.php");

                        use Bat\A;

                        trait B {
                            use A;
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        namespace Bat;

                        trait A{
                            public function fooFoo(): string {
                                return 5;
                            }
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file3.php',
                ],
                'error_message' => 'InvalidReturnType',
            ],
            'invalidTraitFunctionMissingNestedUse' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        trait A {
                            use C;
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                        require("A.php");

                        class B {
                            use A;
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php',
                ],
                'error_message' => 'UndefinedTrait - A.php:3:33',
            ],
            'SKIPPED-noHoistConstants' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        require_once("file2.php");',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        function bat() : void {
                            echo FOO . BAR;
                        }

                        define("FOO", 5);
                        const BAR = "BAR";',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php',
                ],
                'error_message' => 'UndefinedConstant',
            ],
            'undefinedMethodAfterInvalidRequire' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        /** @psalm-suppress MissingFile */
                        require("doesnotexist.php");
                        require("file1.php");

                        foo();
                        bar();
                        ',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        function bar(): void {}
                        ',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
                'error_message' => 'UndefinedFunction',
            ],
            'pathStartingWithDot' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'test_1.php' => '<?php
                        // start with single dot
                        require "./doesnotexist.php";
                        ',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'test_2.php' => '<?php
                        // start with 2 dots
                        require "../doesnotexist.php";
                        ',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'test_1.php',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'test_2.php',
                ],
                'error_message' => 'MissingFile',
            ],
            'directoryPath' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'test.php' => '<?php
                        // empty require resolves to a directory
                        require "";
                        ',
                ],
                'files_to_check' => [(string) getcwd() . DIRECTORY_SEPARATOR . 'test.php'],
                'error_message' => 'MissingFile',
                'directories' => [(string) getcwd() . DIRECTORY_SEPARATOR],
            ],
        ];
    }
}
