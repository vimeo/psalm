<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Context;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\IssueBuffer;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function array_map;

use const DIRECTORY_SEPARATOR;

final class ToStringTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    #[DataProvider('providerEchoOfAnObjectWithoutToString')]
    public function testEchoOfAnObjectWithoutToStringIsTheSameIssueInEveryMode(bool $track_taints): void
    {
        $this->testConfig->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';
        $this->addFile(
            $file_path,
            '<?php
                final class NoStr {}

                function g(NoStr $o): void {
                    echo $o;
                }',
        );

        if ($track_taints) {
            $this->project_analyzer->trackTaintedInputs();
        }

        $this->analyzeFile($file_path, new Context(), false);

        self::assertSame(
            ['InvalidArgument'],
            array_map(
                static fn(IssueData $issue): string => $issue->type,
                IssueBuffer::getIssuesDataForFile($file_path),
            ),
        );
    }

    public function testACallToAStrictTraitMethodKeepsTheStrictTypesOfTheCaller(): void
    {
        $this->testConfig->throw_exception = false;

        $strict_trait_path = self::$src_dir_path . 'stricttrait.php';
        $class_path = self::$src_dir_path . 'class.php';

        $this->addFile(
            $strict_trait_path,
            '<?php
                declare(strict_types=1);

                trait U {
                    private function init(): void {}
                }',
        );
        $this->addFile(
            $class_path,
            '<?php
                final class S {
                    public function __toString(): string {
                        return "s";
                    }
                }

                function takesString(string $s): void {}

                final class A {
                    use U;

                    public string $s;

                    public function __construct(S $o) {
                        $this->init();
                        $this->s = $o;
                        takesString($o);
                    }
                }',
        );

        $this->analyzeFile($class_path, new Context());

        self::assertSame(
            ['ImplicitToStringCast', 'ImplicitToStringCast'],
            array_map(
                static fn(IssueData $issue): string => $issue->type,
                IssueBuffer::getIssuesDataForFile($class_path),
            ),
        );
    }

    public function testTheCodeOfATraitRunsUnderTheStrictTypesOfItsOwnFile(): void
    {
        $this->testConfig->throw_exception = false;

        $defs_path = self::$src_dir_path . 'defs.php';
        $trait_path = self::$src_dir_path . 'trait.php';
        $strict_trait_path = self::$src_dir_path . 'stricttrait.php';
        $strict_class_path = self::$src_dir_path . 'strictclass.php';
        $class_path = self::$src_dir_path . 'class.php';

        $this->addFile(
            $defs_path,
            '<?php
                final class S {
                    public function __toString(): string {
                        return "s";
                    }
                }

                function takesString(string $s): void {}',
        );
        $this->addFile(
            $trait_path,
            '<?php
                trait T {
                    public string $s = "";

                    public function set(S $o): void {
                        $this->s = $o;
                        takesString($o);
                    }
                }',
        );
        $this->addFile(
            $strict_trait_path,
            '<?php
                declare(strict_types=1);

                trait U {
                    public string $s = "";

                    public function __construct(S $o) {
                        $this->s = $o;
                    }

                    public function call(S $o): void {
                        takesString($o);
                    }
                }',
        );
        $this->addFile(
            $strict_class_path,
            '<?php
                declare(strict_types=1);

                final class UsesT {
                    use T;
                }',
        );
        $this->addFile(
            $class_path,
            '<?php
                final class UsesU {
                    use U;
                }',
        );

        $this->analyzeFile($strict_class_path, new Context());
        $this->analyzeFile($class_path, new Context());

        $types = static fn(string $file_path): array => array_map(
            static fn(IssueData $issue): string => $issue->type,
            IssueBuffer::getIssuesDataForFile($file_path),
        );

        self::assertSame(['ImplicitToStringCast', 'ImplicitToStringCast'], $types($trait_path));
        self::assertSame(['InvalidPropertyAssignmentValue', 'InvalidArgument'], $types($strict_trait_path));
    }

    /**
     * @return iterable<string, array{bool}>
     * @psalm-pure
     */
    public static function providerEchoOfAnObjectWithoutToString(): iterable
    {
        return [
            'withoutTaintAnalysis' => [false],
            'withTaintAnalysis' => [true],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'intvalAndFloatvalOfValuesThatCastCleanly' => [
                'code' => '<?php
                    /** @param list<int> $parts */
                    function f(string $string, int|float|bool|null $scalar, mixed $mixed, array $parts): void {
                        $a = intval($string);
                        $b = intval($string, 16);
                        $c = intval($scalar);
                        $d = intval($mixed);
                        $e = floatval($string);
                        $f = floatval($scalar);
                        $g = floatval($mixed);
                        $h = intval(...$parts);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'validToString' => [
                'code' => '<?php
                    class A {
                        function __toString() {
                            return "hello";
                        }
                    }
                    echo (new A);',
            ],
            'inheritedToString' => [
                'code' => '<?php
                    class A {
                        function __toString() {
                            return "hello";
                        }
                    }
                    class B {
                        function __toString() {
                            return "goodbye";
                        }
                    }
                    class C extends B {}

                    $c = new C();
                    echo (string) $c;',
            ],
            'goodCast' => [
                'code' => '<?php
                    class A {
                        public function __toString(): string
                        {
                            return "hello";
                        }
                    }

                    /** @param string|A $b */
                    function fooFoo($b): void {}

                    /** @param A|string $b */
                    function barBar($b): void {}

                    fooFoo(new A());
                    barBar(new A());',
            ],
            'resourceToString' => [
                'code' => '<?php
                    $a = fopen("php://memory", "r");
                    if ($a === false) exit;
                    $b = (string) $a;',
            ],
            'canBeObject' => [
                'code' => '<?php
                    class A {
                        public function __toString() {
                            return "A";
                        }
                    }

                    /** @param string|object $s */
                    function foo($s) : void {}

                    foo(new A);',
            ],
            'castArrayKey' => [
                'code' => '<?php
                    /**
                     * @param string[] $arr
                     */
                    function foo(array $arr) : void {
                        if (!$arr) {
                            return;
                        }

                        foreach ($arr as $i => $_) {}

                        echo (string) $i;
                    }',
            ],
            'allowToStringAfterMethodExistsCheck' => [
                'code' => '<?php
                    function getString(object $value) : ?string {
                        if (method_exists($value, "__toString")) {
                            return (string) $value;
                        }

                        return null;
                    }',
            ],
            'refineToStringType' => [
                'code' => '<?php
                    /** @psalm-return non-empty-string */
                    function doesCast() : string {
                        return (string) (new A());
                    }

                    /** @psalm-return non-empty-string */
                    function callsToString() : string {
                        return (new A())->__toString();
                    }

                    class A {
                        /** @psalm-return non-empty-string */
                        function __toString(): string {
                            return "ha";
                        }
                    }',
            ],
            'intersectionCanBeString' => [
                'code' => '<?php
                    interface EmptyInterface {}

                    class StringCastable implements EmptyInterface
                    {
                        public function __toString()
                        {
                            return \'I am castable\';
                        }
                    }

                    function factory(): EmptyInterface
                    {
                        return new StringCastable();
                    }

                    $object = factory();
                    if (method_exists($object, \'__toString\')) {
                        $a = (string) $object;
                        echo $a;
                    }

                    if (is_callable([$object, \'__toString\'])) {
                        $a = (string) $object;
                        echo $a;
                    }',
            ],
            'PHP80-stringableInterface' => [
                'code' => '<?php
                    interface Foo extends Stringable {}

                    function takesString(string $s) : void {}

                    function takesFoo(Foo $foo) : void {
                        /** @psalm-suppress ImplicitToStringCast */
                        takesString($foo);
                    }

                    class FooImplementer implements Foo {
                        public function __toString() : string {
                            return "hello";
                        }
                    }

                    takesFoo(new FooImplementer());',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'implicitStringable' => [
                'code' => '<?php
                    function foo(Stringable $s): void {}

                    class Bar {
                        public function __toString() {
                            return "foo";
                        }
                    }

                    foo(new Bar());',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'toStringNever' => [
                'code' => '<?php
                    class B{
                        public function __toString() {
                            throw new BadMethodCallException("bad");
                        }
                    }
                ',
            ],
            'toStringToImplode' => [
                'code' => '<?php
                    class Bar {
                        public function __toString() {
                            return "foo";
                        }
                    }

                    echo implode(":", [new Bar()]);',
            ],
            'toStringObjectPassedToConditionalParamWithItsClass' => [
                'code' => '<?php
                    final class Foo {
                        public function __toString(): string { return "foo"; }
                    }

                    /** @template T of bool */
                    final class C {
                        /** @param (T is true ? string|Foo : Foo) $x */
                        public function a($x): void {}

                        public function b(Foo $f): void {
                            $this->a($f);
                        }
                    }',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'echoClass' => [
                'code' => '<?php
                    class A {}
                    echo (new A);',
                'error_message' => 'InvalidArgument',
            ],
            'echoCastClass' => [
                'code' => '<?php
                    class A {}
                    echo (string)(new A);',
                'error_message' => 'InvalidCast',
            ],
            'invalidToStringReturnType' => [
                'code' => '<?php
                    class A {
                        function __toString(): void { }
                    }',
                'error_message' => 'InvalidToString',
            ],
            'invalidInferredToStringReturnType' => [
                'code' => '<?php
                    class A {
                        function __toString() { }
                    }',
                'error_message' => 'InvalidToString',
            ],
            'invalidInferredToStringReturnTypeWithTruePhp8' => [
                'code' => '<?php
                    class A {
                        function __toString() {
                            /** @psalm-suppress InvalidReturnStatement */
                            return true;
                        }
                    }',
                'error_message' => 'InvalidToString',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'implicitCastWithStrictTypes' => [
                'code' => '<?php declare(strict_types=1);
                    class A {
                        public function __toString(): string
                        {
                            return "hello";
                        }
                    }

                    /** @psalm-mutation-free */
                    function fooFoo(string $b): void {}
                    fooFoo(new A());',
                'error_message' => 'InvalidArgument',
            ],
            'implicitCast' => [
                'code' => '<?php
                    class A {
                        public function __toString(): string
                        {
                            return "hello";
                        }
                    }

                    /** @psalm-mutation-free */
                    function fooFoo(string $b): void {}
                    fooFoo(new A());',
                'error_message' => 'ImplicitToStringCast',
            ],
            'implicitCastToUnion' => [
                'code' => '<?php
                    class A {
                        public function __toString(): string
                        {
                            return "hello";
                        }
                    }

                    /** 
                     * @param string|int $b
                     * @psalm-mutation-free 
                     */
                    function fooFoo($b): void {}
                    fooFoo(new A());',
                'error_message' => 'ImplicitToStringCast',
            ],
            'implicitCastFromInterface' => [
                'code' => '<?php
                    interface I {
                        public function __toString();
                    }

                    /** @psalm-mutation-free */
                    function takesString(string $str): void { }

                    function takesI(I $i): void
                    {
                        takesString($i);
                    }',
                'error_message' => 'ImplicitToStringCast',
            ],
            'resourceCannotBeCoercedToString' => [
                'code' => '<?php
                    /** @psalm-mutation-free */
                    function takesString(string $s) : void {}
                    $a = fopen("php://memory", "r");
                    takesString($a);',
                'error_message' => 'InvalidArgument',
            ],
            'resourceOrFalseToString' => [
                'code' => '<?php
                    $a = fopen("php://memory", "r");
                    if (rand(0, 1)) {
                        $a = [];
                    }
                    $b = (string) $a;',
                'error_message' => 'PossiblyInvalidCast',
            ],
            'cannotCastInsideString' => [
                'code' => '<?php
                    class NotStringCastable {}
                    $object = new NotStringCastable();
                    echo "$object";',
                'error_message' => 'InvalidCast',
            ],
            'warnAboutNullableCast' => [
                'code' => '<?php
                    class ClassWithToString {
                        public function __toString(): string {
                            return "";
                        }
                    }

                    function maybeShow(?string $message): void {
                        if ($message !== null) {
                            echo $message;
                        }
                    }

                    maybeShow(new ClassWithToString());',
                'error_message' => 'ImplicitToStringCast',
            ],
            'possiblyInvalidCastOnIsSubclassOf' => [
                'code' => '<?php
                    class Foo {}

                    /**
                     * @param mixed $a
                     */
                    function bar($a) : ?string {
                        /**
                         * @psalm-suppress MixedArgument
                         */
                        if (is_subclass_of($a, Foo::class)) {
                            return "hello" . $a;
                        }

                        return null;
                    }',
                'error_message' => 'PossiblyInvalidOperand',
            ],
            'allowToStringAfterMethodExistsCheckWithTypo' => [
                'code' => '<?php
                    function getString(object $value) : ?string {
                        if (method_exists($value, "__toStrong")) {
                            return (string) $value;
                        }

                        return null;
                    }',
                'error_message' => 'InvalidCast',
            ],
            'alwaysEvaluateToStringVar' => [
                'code' => '<?php
                    /** @psalm-suppress UndefinedFunction */
                    fora((string) $address);',
                'error_message' => 'UndefinedGlobalVariable',
            ],
            'implicitStringableDisallowed' => [
                'code' => '<?php
                    interface Stringable {
                        function __toString() {}
                    }
                    /** @psalm-mutation-free */
                    function foo(Stringable $s): void {}

                    class Bar {
                        public function __toString() {
                            return "foo";
                        }
                    }

                    foo(new Bar());',
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '7.4',
            ],
            'implicitCastInArray' => [
                'code' => '<?php
                    interface S {
                        public function __toString(): string;
                    }
                    /** @return array<array-key, string> */
                    function f(S $s): array {
                        return [$s];
                    }
                ',
                'error_message' => 'ImplicitToStringCast',
            ],
            'implicitCastInList' => [
                'code' => '<?php
                    interface S {
                        public function __toString(): string;
                    }
                    /** @return list<string> */
                    function f(S $s): array {
                        return [$s];
                    }
                ',
                'error_message' => 'ImplicitToStringCast',
            ],
            'implicitCastInTuple' => [
                'code' => '<?php
                    interface S {
                        public function __toString(): string;
                    }
                    /** @return array{string} */
                    function f(S $s): array {
                        return [$s];
                    }
                ',
                'error_message' => 'ImplicitToStringCast',
            ],
            'implicitCastInShape' => [
                'code' => '<?php
                    interface S {
                        public function __toString(): string;
                    }
                    /** @return array{0:string} */
                    function f(S $s): array {
                        return [$s];
                    }
                ',
                'error_message' => 'ImplicitToStringCast',
            ],
            'implicitCastInIterable' => [
                'code' => '<?php
                    interface S {
                        public function __toString(): string;
                    }
                    /** @return iterable<int, string> */
                    function f(S $s) {
                        return [$s];
                    }
                ',
                'error_message' => 'ImplicitToStringCast',
            ],
            'stringableArgumentAtTheTopLevelOfANamespacedStrictFile' => [
                'code' => '<?php
                    declare(strict_types=1);

                    namespace N;

                    final class S {
                        public function __toString(): string {
                            return "s";
                        }
                    }

                    function takesString(string $s): void {}

                    takesString(new S());',
                'error_message' => 'InvalidArgument',
            ],
            'stringableArgumentInAFunctionOfANamespacedStrictFile' => [
                'code' => '<?php
                    declare(strict_types=1);

                    namespace N;

                    final class S {
                        public function __toString(): string {
                            return "s";
                        }
                    }

                    function takesString(string $s): void {}

                    function f(S $s): void {
                        takesString($s);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'traitOfAStrictFileStartingWithAShebang' => [
                'code' => '#!/usr/bin/env php
<?php
                    declare(strict_types=1);

                    final class S {
                        public function __toString(): string {
                            return "s";
                        }
                    }

                    function takesString(string $s): void {}

                    trait T {
                        public function call(S $o): void {
                            takesString($o);
                        }
                    }

                    final class A {
                        use T;
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'implicitCastInToString' => [
                'code' => '<?php
                    final class A
                    {
                        public function __toString(): string
                        {
                            return new SplFileInfo("a");
                        }
                    }
                ',
                'error_message' => 'ImplicitToStringCast',
            ],
            'toStringTypecastNonString' => [
                'code' => '<?php
                    class A {
                        function __toString(): string {
                            return "ha";
                        }
                    }

                    $foo = new A();
                    echo (int) $foo;',
                'error_message' => 'InvalidCast',
            ],
            'riskyArrayToIntCast' => [
                'code' => '<?php
                    echo (int) array();',
                'error_message' => 'RiskyCast',
            ],
            'riskyArrayToFloatCast' => [
                'code' => '<?php
                    echo (float) array(\'hello\');',
                'error_message' => 'RiskyCast',
            ],
            'riskyArrayToIntvalCall' => [
                'code' => '<?php
                    function f(array $page): int {
                        return intval($page);
                    }',
                'error_message' => 'RiskyCast - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:39',
            ],
            'riskyArrayToIntvalCallWithABase' => [
                'code' => '<?php
                    function f(array $page): int {
                        return intval($page, 16);
                    }',
                'error_message' => 'RiskyCast - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:39',
            ],
            'riskyArrayToFloatvalCall' => [
                'code' => '<?php
                    function f(array $page): float {
                        return floatval($page);
                    }',
                'error_message' => 'RiskyCast - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:41',
            ],
            'riskyArrayToDoublevalCall' => [
                'code' => '<?php
                    function f(array $page): float {
                        return doubleval($page);
                    }',
                'error_message' => 'RiskyCast - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:42',
            ],
            'objectToIntvalCall' => [
                'code' => '<?php
                    function f(stdClass $object): int {
                        return intval($object);
                    }',
                'error_message' => 'InvalidCast - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:39',
            ],
            'riskyValueOfArrayTemplateToIntvalCall' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, array>
                     * @param value-of<T> $page
                     */
                    function f($page): int {
                        return intval($page);
                    }',
                'error_message' => 'RiskyCast - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:39',
            ],
            'riskyValueOfArrayTemplateToFloatvalCall' => [
                'code' => '<?php
                    /**
                     * @template T of array<string, array>
                     * @param value-of<T> $page
                     */
                    function f($page): float {
                        return floatval($page);
                    }',
                'error_message' => 'RiskyCast - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:41',
            ],
            'riskyArrayToIntvalCallWithTooManyArguments' => [
                'code' => '<?php
                    function f(array $page): int {
                        return intval($page, 16, 1);
                    }',
                'error_message' => 'RiskyCast - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:39',
                'error_levels' => ['TooManyArguments'],
            ],
            'riskyArrayToFloatvalCallWithTooManyArguments' => [
                'code' => '<?php
                    function f(array $page): float {
                        return floatval($page, 1);
                    }',
                'error_message' => 'RiskyCast - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:41',
                'error_levels' => ['TooManyArguments'],
            ],
        ];
    }
}
