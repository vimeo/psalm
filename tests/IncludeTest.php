<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use Psalm\Config;
use Psalm\Exception\CodeException;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Storage\ClassLikeStorage;
use ReflectionProperty;

use function array_combine;
use function array_keys;
use function getcwd;
use function preg_quote;
use function set_time_limit;
use function sort;
use function strpos;

use const DIRECTORY_SEPARATOR;

final class IncludeTest extends TestCase
{
    /**
     * @param array<int, string> $files_to_check
     * @param array<string, string> $files
     * @param list<string> $ignored_issues
     */
    #[DataProvider('providerTestValidIncludes')]
    public function testValidInclude(
        array $files,
        array $files_to_check,
        bool $hoist_constants = false,
        array $ignored_issues = [],
        string $php_version = '7.4',
    ): void {
        $this->project_analyzer->setPhpVersion($php_version, 'tests');

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
     * @param array<int, string> $files_to_check
     * @param array<string, string> $files
     * @param list<string> $directories
     */
    #[DataProvider('providerTestInvalidIncludes')]
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
     * @return array<string,array{files:array<string,string>,files_to_check:array<int,string>,hoist_constants?:bool,ignored_issues?:list<string>,php_version?:string}>
     */
    public static function providerTestValidIncludes(): array
    {
        return [
            'requireGuardedByFileExistsWithParentDirectory' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'script.php' => '<?php
                        if (file_exists(__DIR__ . "/../optional.php")) {
                            require __DIR__ . "/../optional.php";
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'script.php',
                ],
            ],
            'requireOfAResolvedPathCheckedByFileExistsInAnAndCondition' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'script.php' => '<?php
                        function load(bool $flag): void {
                            if ($flag && is_file(__DIR__ . "/optional.php")) {
                                require __DIR__ . "/optional.php";
                            }
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'script.php',
                ],
            ],
            'requireGuardedByIsFileWithDotSegments' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'script.php' => '<?php
                        if (is_file(__DIR__ . "/./sub/../optional.php")) {
                            require __DIR__ . "/sub/../optional.php";
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'script.php',
                ],
            ],
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
                            /** @psalm-mutation-free */
                            public function fooFoo(): void {

                            }
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
            ],
            'includedCodeRefiningThisKeepsTheRefinement' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        class ParentExample {
                            public function main(): int {
                                require __DIR__ . "/file1.php";

                                return $this->childOnly();
                            }
                        }

                        class ChildExample extends ParentExample {
                            public function childOnly(): int {
                                return 1;
                            }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        if (!$this instanceof ChildExample) {
                            throw new RuntimeException();
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
                'hoist_constants' => false,
            ],
            'includedCodeCheckingThisDoesNotChangeIt' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        class Example {
                            public function main(): Example {
                                require __DIR__ . "/file1.php";

                                return $this;
                            }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        if (!$this instanceof stdClass) {
                            throw new RuntimeException();
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
                'hoist_constants' => false,
                'ignored_issues' => ['RedundantCondition'],
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
                            /** @psalm-mutation-free */
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
                            /** @psalm-mutation-free */
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
                            /** @psalm-mutation-free */
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
                        /** @psalm-mutation-free */
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
                        /** @psalm-mutation-free */
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
                            /** @psalm-mutation-free */
                            public function doFoo(): void {
                                $this->fooFoo();
                            }
                        }

                        class C {}

                        new D();',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require_once("file3.php");

                        class A{
                            /** @psalm-mutation-free */
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
                            /** @psalm-mutation-free */
                            public function barBar(): void { }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require_once("file1.php");
                        class A{
                            /** @psalm-mutation-free */
                            public function fooFoo(): void { }
                        }
                        class D extends C {
                            /** @psalm-mutation-free */
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
                        /** @psalm-mutation-free */
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
                            /** @psalm-mutation-free */
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
            'hoistConstantsSkipsFirstClassCallableDefine' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        define(...);',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php',
                ],
                'hoist_constants' => true,
                'ignored_issues' => [],
                'php_version' => '8.1',
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

                        /** @psalm-mutation-free */
                        function func(): void {}

                        define("ns\\cons", 0);

                        cons;',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'Base.php' => '<?php
                        namespace ns;

                        func();

                        cons;

                        class Base {
                            /** @psalm-mutation-free */
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

                            /** @psalm-external-mutation-free */
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
                        /** @psalm-mutation-free */
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
                            /** @psalm-mutation-free */
                            public static function foo(): void {
                                // empty;
                            }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'include_2.php' => '<?php
                        class Class_2 {
                            /** @psalm-mutation-free */
                            public static function bar(): void {
                                // empty;
                            }
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'test_1.php',
                ],
            ],
            'namespaceConstantKeepsItsTypeAfterASubclassCallsTheParentConstructor' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'Base.php' => '<?php
                        namespace App;

                        const LIMIT = 5;

                        class Base {
                            public int $limit;

                            public function __construct() {
                                $this->limit = 1;
                            }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'Child.php' => '<?php
                        namespace App;

                        final class Child extends Base {
                            public function __construct() {
                                parent::__construct();
                            }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'limit.php' => '<?php
                        function limit(): int {
                            return \App\LIMIT;
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'Base.php',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'Child.php',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'limit.php',
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
    public static function providerTestInvalidIncludes(): array
    {
        return [
            'requireGuardedByFileExistsOfAnotherFile' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'script.php' => '<?php
                        if (file_exists(__DIR__ . "/../optional.php")) {
                            require __DIR__ . "/../other.php";
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'script.php',
                ],
                'error_message' => 'MissingFile',
            ],
            'requireOfAResolvedPathCheckedByANegatedFileExistsInAnAndCondition' => [
                'files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'script.php' => '<?php
                        function load(bool $flag): void {
                            if ($flag && !is_file(__DIR__ . "/missing.php")) {
                                require __DIR__ . "/missing.php";
                            }
                        }',
                ],
                'files_to_check' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'script.php',
                ],
                'error_message' => 'MissingFile',
            ],
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
                        /** @psalm-suppress MissingPureAnnotation */
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
                        /** @psalm-suppress MissingPureAnnotation */
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
                        /** @psalm-suppress MissingPureAnnotation */
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
                        /** @psalm-suppress MissingPureAnnotation */
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
                        /**
                         * @param int $bar
                         * @psalm-suppress MissingPureAnnotation
                         */
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
                        /** @psalm-suppress MissingPureAnnotation */
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
                        /** @psalm-suppress MissingPureAnnotation */
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

                        /** @psalm-suppress MissingPureAnnotation */
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
                        /** @psalm-mutation-free */
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

    /**
     * Regression test: InterfaceAnalyzer must not crash when storage was
     * overwritten by a class with the same FQCN (e.g. loaded via reflection
     * from vendor). The interface's methods are absent from the overwritten
     * storage, so InterfaceAnalyzer must skip them gracefully.
     */
    public function testInterfaceAnalysisDoesNotCrashWhenStorageOverwritten(): void
    {
        $codebase = $this->project_analyzer->getCodebase();
        $config = $codebase->config;

        $config->setCustomErrorLevel('DuplicateClass', Config::REPORT_SUPPRESS);
        $config->throw_exception = false;

        $file_path = (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php';
        $this->addFile($file_path, '<?php
            namespace Foo;
            interface Bar {
                public function someMethod(): void;
            }
        ');

        $codebase->addFilesToAnalyze([$file_path => $file_path]);
        $codebase->scanFiles();

        // Simulate reflection overwriting the interface storage with a class
        // that lacks the interface's methods (this is what happens when a
        // vendor class with the same FQCN is loaded via reflection).
        $overwritten = new ClassLikeStorage('Foo\\Bar');
        $overwritten->is_interface = false;
        $overwritten->populated = true;

        $ref = new ReflectionProperty(ClassLikeStorageProvider::class, 'storage');
        /** @var array<string, ClassLikeStorage> $all */
        $all = $ref->getValue();
        $all['foo\\bar'] = $overwritten;
        $ref->setValue(null, $all);

        $file_analyzer = new FileAnalyzer(
            $this->project_analyzer,
            $file_path,
            $config->shortenFileName($file_path),
        );
        // This must not crash — previously threw UnexpectedValueException
        // from MethodAnalyzer when the method was missing from storage.
        $file_analyzer->analyze();
    }

    /**
     * Analysing a subclass follows the constructor it calls into the parent's file, and taint analysis analyses the
     * parent's body again for the subclass. The statements outside the parent's class are analysed with the parent's
     * file, which knows the functions its includes declare, not again for the subclass, whose file doesn't include
     * them.
     */
    #[DataProvider('providerTaintAnalysis')]
    public function testStatementsOutsideTheClassOfAFollowedFileAreNotAnalysedForTheSubclass(bool $taint): void
    {
        if ($taint) {
            $this->project_analyzer->trackTaintedInputs();
        }

        $codebase = $this->project_analyzer->getCodebase();
        $config = $codebase->config;
        $config->throw_exception = false;

        $dir = self::$src_dir_path;
        $files = [
            $dir . 'bootstrap.php' => '<?php
                function first(): void {
                    second();
                }

                function second(): void {}',
            $dir . 'Base.php' => '<?php
                namespace App;

                require_once __DIR__ . "/bootstrap.php";

                class Base {
                    public string $name;

                    public function __construct() {
                        $this->name = "base";
                    }
                }',
            $dir . 'Child.php' => '<?php
                namespace App;

                final class Child extends Base {
                    public function __construct() {
                        parent::__construct();
                    }

                    public function report(): void {
                        missing();
                    }
                }',
        ];

        foreach ($files as $file_path => $contents) {
            $this->addFile($file_path, $contents);
        }

        $codebase->addFilesToAnalyze(array_combine(array_keys($files), array_keys($files)));
        $codebase->scanFiles();

        foreach (array_keys($files) as $file_path) {
            (new FileAnalyzer($this->project_analyzer, $file_path, $config->shortenFileName($file_path)))->analyze();
        }

        $issues = [];

        foreach (array_keys($files) as $file_path) {
            foreach (IssueBuffer::getIssuesDataForFile($file_path) as $issue) {
                $issues[] = $issue->type . ' ' . $issue->file_name . ':' . $issue->line_from;
            }
        }

        // the issues of the files themselves are still reported
        $this->assertSame(['UndefinedFunction src' . DIRECTORY_SEPARATOR . 'Child.php:10'], $issues);
    }

    /**
     * @return iterable<string, array{bool}>
     * @psalm-pure
     */
    public static function providerTaintAnalysis(): iterable
    {
        yield 'without taint analysis' => [false];
        yield 'with taint analysis' => [true];
    }

    /**
     * The statements outside the class of a file are analysed once, with the file, however many subclasses follow
     * a method of the class.
     */
    #[DataProvider('providerTaintAnalysis')]
    public function testStatementsOutsideTheClassOfAFollowedFileAreAnalysedOnce(bool $taint): void
    {
        $hook = new class implements AfterExpressionAnalysisInterface {
            public static int $count = 0;

            #[Override]
            public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
            {
                $expr = $event->getExpr();

                if ($expr instanceof FuncCall && $expr->name instanceof Name && $expr->name->toString() === 'strlen') {
                    self::$count++;
                }

                return null;
            }
        };

        $hook::$count = 0;

        if ($taint) {
            $this->project_analyzer->trackTaintedInputs();
        }

        $codebase = $this->project_analyzer->getCodebase();
        $config = $codebase->config;
        $config->throw_exception = false;
        $config->eventDispatcher->after_expression_checks[] = $hook::class;

        $dir = self::$src_dir_path;
        $files = [
            $dir . 'Base.php' => '<?php
                namespace App;

                \strlen("namespace level");

                class Base {
                    public string $name;

                    public function __construct() {
                        $this->name = "base";
                    }
                }',
            $dir . 'Children.php' => '<?php
                namespace App;

                final class FirstChild extends Base {
                    public function __construct() {
                        parent::__construct();
                    }
                }

                final class SecondChild extends Base {
                    public function __construct() {
                        parent::__construct();
                    }
                }',
        ];

        foreach ($files as $file_path => $contents) {
            $this->addFile($file_path, $contents);
        }

        $codebase->addFilesToAnalyze(array_combine(array_keys($files), array_keys($files)));
        $codebase->scanFiles();

        foreach (array_keys($files) as $file_path) {
            (new FileAnalyzer($this->project_analyzer, $file_path, $config->shortenFileName($file_path)))->analyze();
        }

        $this->assertSame(1, $hook::$count);
    }

    /**
     * The constructor of the parent reads the constants its file declares: they are known when a subclass follows
     * it, whichever file is analysed first. Each order uses its own namespace, as the constants a namespace's
     * statements declare stay known to the process.
     *
     * @param list<string> $file_names
     */
    #[DataProvider('providerParentAndChildOrders')]
    public function testConstantsOfAFollowedFileAreKnownWhicheverFileIsAnalysedFirst(
        array $file_names,
        string $namespace,
    ): void {
        $this->addFile((string) getcwd() . DIRECTORY_SEPARATOR . 'Base.php', '<?php
            namespace ' . $namespace . ';

            const MAX_ITEMS = 5;
            define("DEFAULT_CURRENCY", "EUR");

            class Base {
                public int $max_items;
                public string $currency;

                public function __construct() {
                    $this->max_items = MAX_ITEMS;
                    $this->currency = DEFAULT_CURRENCY;
                }
            }
        ');
        $this->addFile((string) getcwd() . DIRECTORY_SEPARATOR . 'Child.php', '<?php
            namespace ' . $namespace . ';

            final class Child extends Base {
                public function __construct() {
                    parent::__construct();
                }
            }
        ');

        $this->assertSame([], $this->analyzeFilesInOrder($file_names));
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     * @psalm-pure
     */
    public static function providerParentAndChildOrders(): iterable
    {
        yield 'parent first' => [['Base.php', 'Child.php'], 'ParentFirst'];
        yield 'child first' => [['Child.php', 'Base.php'], 'ChildFirst'];
    }

    /**
     * A method whose signature type names an unknown class is not analyzed, and it must not be the first analysis
     * of it only: a class analyzed again (when another file includes it) has to be skipped the same way, or the
     * issues depend on which files a process analyzed before.
     */
    #[DataProvider('providerMethodWithUnknownClassInType')]
    public function testMethodWithUnknownClassInTypeIsSkippedWhenAnalyzedAgain(string $method_header, string $return): void
    {
        $class_file = (string) getcwd() . DIRECTORY_SEPARATOR . 'file1.php';
        $includer_file = (string) getcwd() . DIRECTORY_SEPARATOR . 'file2.php';

        $this->addFile($class_file, '<?php
            class Foo {
                ' . $method_header . ' {
                    missing_function();
                    ' . $return . '
                }
            }
        ');
        $this->addFile($includer_file, '<?php
            require_once __DIR__ . "/file1.php";
        ');

        $this->assertSame(
            ['file1.php: UndefinedDocblockClass@3'],
            $this->analyzeFilesInOrder(['file1.php', 'file2.php']),
        );
    }

    /**
     * @return array<string, array{string, string}>
     * @psalm-pure
     */
    public static function providerMethodWithUnknownClassInType(): array
    {
        return [
            'paramType' => ['/** @param Unknown $a */ public function bar($a): void', ''],
            'returnType' => ['/** @return list<Unknown> */ public function bar(): array', 'return [];'],
        ];
    }

    /**
     * A type declared outside the project is checked at each call, and each of its issues is reported once, at the
     * earliest call, whichever file is analysed first
     *
     * @param Config::REPORT_ERROR|Config::REPORT_INFO $reporting_level
     * @param list<string> $file_names
     */
    #[DataProvider('providerReportingLevelsInFileOrders')]
    public function testUnknownClassInExternalReturnTypeIsReportedAtTheEarliestCall(
        string $reporting_level,
        array $file_names,
    ): void {
        $this->project_analyzer->getCodebase()->config->setCustomErrorLevel('UndefinedDocblockClass', $reporting_level);
        $this->addLibrary();
        $this->addFile((string) getcwd() . DIRECTORY_SEPARATOR . 'first_caller.php', '<?php
            function first(Library $library): void {
                Library::staticMethod();
                Library::staticMethod();
                $library->method();
                $library->method();
                func();
                func();
                missing_function();
                missing_function();
            }
        ');
        $this->addFile((string) getcwd() . DIRECTORY_SEPARATOR . 'second_caller.php', '<?php
            function second(Library $library): void {
                Library::staticMethod();
                $library->method();
                func();
            }
        ');

        $this->assertSame(
            [
                'first_caller.php: UndefinedDocblockClass@3',
                'first_caller.php: UndefinedDocblockClass@5',
                'first_caller.php: UndefinedDocblockClass@7',
                'first_caller.php: UndefinedFunction@9',
                'first_caller.php: UndefinedFunction@10',
            ],
            $this->analyzeFilesInOrder($file_names),
        );
        $this->assertSame($reporting_level === Config::REPORT_ERROR ? 5 : 2, IssueBuffer::getErrorCount());
    }

    /**
     * A call whose issue is suppressed, or that a class_exists() check guards, doesn't take part: the earliest of
     * the other calls reports it, whichever file is analysed first
     *
     * @param list<string> $file_names
     */
    #[DataProvider('providerExcludedCallsInFileOrders')]
    public function testExcludedCallDoesNotHideTheUnknownClassAtTheOtherCalls(string $excluded_call, array $file_names): void
    {
        $this->addLibrary();
        $this->addFile((string) getcwd() . DIRECTORY_SEPARATOR . 'first_caller.php', '<?php
            ' . $excluded_call . '
        ');
        $this->addFile((string) getcwd() . DIRECTORY_SEPARATOR . 'second_caller.php', '<?php
            function second(): void {
                Library::staticMethod();
            }
        ');

        $this->assertSame(
            ['second_caller.php: UndefinedDocblockClass@3'],
            $this->analyzeFilesInOrder($file_names),
        );
    }

    /**
     * @return iterable<string, array{list<string>}>
     * @psalm-pure
     */
    public static function providerFileOrders(): iterable
    {
        yield 'first caller first' => [['first_caller.php', 'second_caller.php']];
        yield 'second caller first' => [['second_caller.php', 'first_caller.php']];
    }

    /**
     * @return iterable<string, array{Config::REPORT_ERROR|Config::REPORT_INFO, list<string>}>
     * @psalm-pure
     */
    public static function providerReportingLevelsInFileOrders(): iterable
    {
        foreach ([Config::REPORT_ERROR, Config::REPORT_INFO] as $reporting_level) {
            foreach (self::providerFileOrders() as $order => [$file_names]) {
                yield $reporting_level . ', ' . $order => [$reporting_level, $file_names];
            }
        }
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     * @psalm-pure
     */
    public static function providerExcludedCallsInFileOrders(): iterable
    {
        $excluded_calls = [
            'suppressed' => '/** @psalm-suppress UndefinedDocblockClass */
                function first(): void {
                    Library::staticMethod();
                }',
            'class_exists' => 'function first(): void {
                    if (class_exists(Unknown::class)) {
                        Library::staticMethod();
                    }
                }',
        ];

        foreach ($excluded_calls as $name => $excluded_call) {
            foreach (self::providerFileOrders() as $order => [$file_names]) {
                yield $name . ', ' . $order => [$excluded_call, $file_names];
            }
        }
    }

    /**
     * Adds a file outside the analysed ones, as the code in vendor/ is
     */
    private function addLibrary(): void
    {
        $library_file = (string) getcwd() . DIRECTORY_SEPARATOR . 'library.php';

        $this->addFile($library_file, '<?php
            class Library {
                /** @return Unknown */
                public static function staticMethod() { return 1; }

                /** @return Unknown */
                public function method() { return 1; }
            }

            /** @return Unknown */
            function func() { return 1; }
        ');
        $this->project_analyzer->getCodebase()->scanner->addFilesToDeepScan([$library_file => $library_file]);
    }

    /**
     * @param list<string> $file_names the files of the working directory to analyse, in that order
     * @return list<string> the issues of the analysis, as "file: type@line", by file
     */
    private function analyzeFilesInOrder(array $file_names): array
    {
        $codebase = $this->project_analyzer->getCodebase();
        $config = $codebase->config;
        $config->throw_exception = false;

        foreach ($file_names as $file_name) {
            $file_path = (string) getcwd() . DIRECTORY_SEPARATOR . $file_name;
            $codebase->scanner->addFilesToDeepScan([$file_path => $file_path]);
            $codebase->addFilesToAnalyze([$file_path => $file_path]);
        }

        $codebase->scanFiles();

        foreach ($file_names as $file_name) {
            $file_path = (string) getcwd() . DIRECTORY_SEPARATOR . $file_name;
            (new FileAnalyzer($this->project_analyzer, $file_path, $config->shortenFileName($file_path)))->analyze();
        }

        $sorted_file_names = $file_names;
        sort($sorted_file_names);
        $issues = [];

        foreach ($sorted_file_names as $file_name) {
            foreach (IssueBuffer::getIssuesDataForFile((string) getcwd() . DIRECTORY_SEPARATOR . $file_name) as $issue) {
                $issues[] = $file_name . ': ' . $issue->type . '@' . $issue->line_from;
            }
        }

        return $issues;
    }

    /**
     * The constructor of the parent initializes its property through a helper, in a file that requires the
     * child's file: collecting what the helper does must not be cut short by the include cycle.
     */
    public function testInitializationsAreCollectedAcrossFilesRequiringEachOther(): void
    {
        set_time_limit(30);

        $codebase = $this->project_analyzer->getCodebase();
        $config = $codebase->config;
        $config->throw_exception = true;

        $child_path = (string) getcwd() . DIRECTORY_SEPARATOR . 'Child.php';
        $parent_path = (string) getcwd() . DIRECTORY_SEPARATOR . 'ParentClass.php';

        $this->addFile($child_path, '<?php
            namespace Foo;

            class Child extends ParentClass {
                public function __construct() {
                    parent::__construct();
                }
            }
        ');
        $this->addFile($parent_path, '<?php
            namespace Foo;

            require_once __DIR__ . "/Child.php";

            class ParentClass {
                protected int $prop;

                public function __construct() {
                    $this->init();
                }

                private function init(): void {
                    $this->prop = 1;
                }
            }
        ');

        foreach ([$child_path, $parent_path] as $file_path) {
            $codebase->scanner->addFilesToShallowScan([$file_path => $file_path]);
            $codebase->addFilesToAnalyze([$file_path => $file_path]);
        }

        $codebase->scanFiles();

        foreach ([$child_path, $parent_path] as $file_path) {
            $file_analyzer = new FileAnalyzer(
                $this->project_analyzer,
                $file_path,
                $config->shortenFileName($file_path),
            );
            $file_analyzer->analyze();
        }

        self::assertTrue(true);
    }

    /**
     * A class whose parent's file requires the class's file made the analysis of the constructor call
     * collect the parent's file again and again. A hang shows as the time limit being hit.
     */
    public function testTaintAnalysisOfFilesRequiringEachOtherTerminates(): void
    {
        set_time_limit(30);

        $codebase = $this->project_analyzer->getCodebase();
        $config = $codebase->config;
        $config->throw_exception = false;

        $child_path = (string) getcwd() . DIRECTORY_SEPARATOR . 'Child.php';
        $parent_path = (string) getcwd() . DIRECTORY_SEPARATOR . 'ParentClass.php';

        $this->addFile($child_path, '<?php
            namespace Foo;

            class Child extends ParentClass {
                public $prop;
            }
        ');
        $this->addFile($parent_path, '<?php
            namespace Foo;

            require_once __DIR__ . "/Child.php";

            class ParentClass {
                public function __construct() {}
            }
        ');

        $this->project_analyzer->trackTaintedInputs();

        foreach ([$child_path, $parent_path] as $file_path) {
            $codebase->scanner->addFilesToShallowScan([$file_path => $file_path]);
            $codebase->addFilesToAnalyze([$file_path => $file_path]);
        }

        $codebase->scanFiles();

        $file_analyzer = new FileAnalyzer(
            $this->project_analyzer,
            $child_path,
            $config->shortenFileName($child_path),
        );
        $file_analyzer->analyze();

        self::assertTrue(true);
    }
}
