<?php
namespace Psalm\Tests;

use const DIRECTORY_SEPARATOR;

class PureAnnotationTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'simplePureFunction' => [
                '<?php
                    namespace Bar;

                    class A {
                        public int $a = 5;
                    }

                    /** @psalm-pure */
                    function filterOdd(int $i, A $a) : ?int {
                        if ($i % 2 === 0 || $a->a === 2) {
                            return $i;
                        }

                        $a = new A();

                        return null;
                    }',
            ],
            'pureFunctionCallingBuiltinFunctions' => [
                '<?php
                    namespace Bar;

                    /** @psalm-pure */
                    function lower(string $s) : string {
                        return substr(strtolower($s), 0, 10);
                    }',
            ],
            'pureWithStrReplace' => [
                '<?php
                    /** @psalm-pure */
                    function highlight(string $needle, string $output) : string {
                        $needle = preg_quote($needle, \'#\');
                        $needles = str_replace([\'"\', \' \'], [\'\', \'|\'], $needle);
                        $output = preg_replace("#({$needles})#im", "<mark>$1</mark>", $output);

                        return $output;
                    }'
            ],
            'implicitAnnotations' => [
                '<?php
                    abstract class Foo {
                        private array $options;
                        private array $defaultOptions;

                        function __construct(array $options) {
                            $this->setOptions($options);
                            $this->setDefaultOptions($this->getOptions());
                        }

                        function getOptions(): array {
                            return $this->options;
                        }

                        function setOptions(array $options): void {
                            $this->options = $options;
                        }

                        function setDefaultOptions(array $defaultOptions): void {
                            $this->defaultOptions = $defaultOptions;
                        }
                    }',
            ],
            'canCreateObjectWithNoExternalMutations' => [
                '<?php
                    /** @psalm-external-mutation-free */
                    class Counter {
                        private int $count = 0;

                        public function __construct(int $count) {
                            $this->count = $count;
                        }

                        public function increment() : void {
                            $this->count++;
                        }

                        public function incrementByTwo() : void {
                            $this->count = $this->count + 2;
                        }

                        public function incrementByFive() : void {
                            $this->count += 5;
                        }
                    }

                    /** @psalm-pure */
                    function makesACounter(int $i) : Counter {
                        $c = new Counter($i);
                        $c->increment();
                        $c->incrementByTwo();
                        $c->incrementByFive();
                        return $c;
                    }',
            ],
            'canCreateImmutableObject' => [
                '<?php
                    /** @psalm-immutable */
                    class A {
                        private string $s;

                        public function __construct(string $s) {
                            $this->s = $s;
                        }

                        public function getShort() : string {
                            return substr($this->s, 0, 5);
                        }
                    }

                    /** @psalm-pure */
                    function makeA(string $s) : A {
                        $a = new A($s);

                        if ($a->getShort() === "bar") {
                            return new A("foo");
                        }

                        return $a;
                    }'
            ],
            'assertIsPureInProductionn' => [
                '<?php
                    /**
                     * @psalm-pure
                     */
                    function toDateTime(?DateTime $dateTime) : DateTime {
                        assert($dateTime instanceof DateTime);
                        return $dateTime;
                    }'
            ],
            'allowArrayMapClosure' => [
                '<?php
                    /**
                     * @psalm-pure
                     * @param string[] $arr
                     */
                    function foo(array $arr) : array {
                        return \array_map(function(string $s) { return $s;}, $arr);
                    }'
            ],
            'pureBuiltinCall' => [
                '<?php
                    final class Date
                    {
                        /** @psalm-pure */
                        public static function timeZone(string $tzString) : DateTimeZone
                        {
                            return new \DateTimeZone($tzString);
                        }
                    }',
            ],
            'sortFunction' => [
                '<?php
                    /**
                     * @psalm-pure
                     *
                     * @param int[] $ar
                     */
                    function foo(array $ar): int
                    {
                        usort($ar, static function (int $a, int $b): int {
                            return $a <=> $b;
                        });

                        return $ar[0] ?? 0;
                    }',
            ],
            'allowPureToString' => [
                '<?php
                    class A {
                        /** @psalm-pure */
                        public function __toString() {
                            return "bar";
                        }
                    }

                    /**
                     * @psalm-pure
                     */
                    function foo(string $s, A $a) : string {
                        if ($a == $s) {}
                        return $s;
                    }',
            ],
            'exceptionGetMessage' => [
                '<?php
                    /**
                     * @psalm-pure
                     */
                    function getMessage(Throwable $e): string {
                        return $e->getMessage();
                    }

                    echo getMessage(new Exception("test"));'
            ],
            'exceptionGetCode' => [
                '<?php
                    /**
                     * @psalm-pure
                     *
                     * @return int|string https://www.php.net/manual/en/throwable.getcode.php
                     */
                    function getCode(Throwable $e) {
                        return $e->getCode();
                    }

                    echo getCode(new Exception("test"));'
            ],
            'exceptionGetFile' => [
                '<?php
                    /**
                     * @psalm-pure
                     */
                    function getFile(Throwable $e): string {
                        return $e->getFile();
                    }

                    echo getFile(new Exception("test"));'
            ],
            'exceptionGetLine' => [
                '<?php
                    /**
                     * @psalm-pure
                     */
                    function getLine(Throwable $e): int {
                        return $e->getLine();
                    }

                    echo getLine(new Exception("test"));'
            ],
            'exceptionGetTrace' => [
                '<?php
                    /**
                     * @psalm-pure
                     */
                    function getTrace(Throwable $e): array {
                        return $e->getTrace();
                    }

                    echo count(getTrace(new Exception("test")));'
            ],
            'exceptionGetPrevious' => [
                '<?php
                    /**
                     * @psalm-pure
                     */
                    function getPrevious(Throwable $e): ?Throwable {
                        return $e->getPrevious();
                    }

                    echo gettype(getPrevious(new Exception("test")));'
            ],
            'exceptionGetTraceAsString' => [
                '<?php
                    /**
                     * @psalm-pure
                     */
                    function getTraceAsString(Throwable $e): string {
                        return $e->getTraceAsString();
                    }

                    echo getTraceAsString(new Exception("test"));'
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse()
    {
        return [
            'impurePropertyAssignment' => [
                '<?php
                    namespace Bar;

                    class A {
                        public int $a = 5;
                    }

                    /** @psalm-pure */
                    function filterOdd(int $i, A $a) : ?int {
                        $a->a++;

                        if ($i % 2 === 0 || $a->a === 2) {
                            return $i;
                        }

                        return null;
                    }',
                'error_message' => 'ImpurePropertyAssignment',
            ],
            'impureMethodCall' => [
                '<?php
                    namespace Bar;

                    class A {
                        public int $a = 5;

                        public function foo() : void {
                            $this->a++;
                        }
                    }

                    /** @psalm-pure */
                    function filterOdd(int $i, A $a) : ?int {
                        $a->foo();

                        if ($i % 2 === 0 || $a->a === 2) {
                            return $i;
                        }

                        return null;
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'impureFunctionCall' => [
                '<?php
                    namespace Bar;

                    function impure() : ?string {
                        /** @var int */
                        static $i = 0;

                        ++$i;

                        return $i % 2 ? "hello" : null;
                    }

                    /** @psalm-pure */
                    function filterOdd(array $a) : void {
                        impure();
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'impureConstructorCall' => [
                '<?php
                    namespace Bar;

                    class A {
                        public int $a = 5;
                    }

                    class B {
                        public function __construct(A $a) {
                            $a->a++;
                        }
                    }

                    /** @psalm-pure */
                    function filterOdd(int $i, A $a) : ?int {
                        $b = new B($a);

                        if ($i % 2 === 0 || $a->a === 2) {
                            return $i;
                        }

                        return null;
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'canCreateObjectWithNoExternalMutations' => [
                '<?php
                    class Counter {
                        private int $count = 0;

                        public function __construct(int $count) {
                            $this->count = $count;
                        }

                        public function increment() : void {
                            $this->count += rand(0, 5);
                        }
                    }

                    /** @psalm-pure */
                    function makesACounter(int $i) : Counter {
                        $c = new Counter($i);
                        $c->increment();
                        return $c;
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'useOfStaticMakesFunctionImpure' => [
                '<?php
                    /** @psalm-pure */
                    function addCumulative(int $left) : int {
                        /** @var int */
                        static $i = 0;
                        $i += $left;
                        return $left;
                    }',
                'error_message' => 'ImpureStaticVariable',
            ],
            'preventImpureArrayMapClosure' => [
                '<?php
                    /**
                     * @psalm-pure
                     * @param string[] $arr
                     */
                    function foo(array $arr) : array {
                        return \array_map(function(string $s) { return $s . rand(0, 1);}, $arr);
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'sortFunctionImpure' => [
                '<?php
                    /**
                     * @psalm-pure
                     *
                     * @param int[] $ar
                     */
                    function foo(array $ar): int
                    {
                        usort($ar, static function (int $a, int $b): int {
                            session_start();
                            return $a <=> $b;
                        });

                        return $ar[0] ?? 0;
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'impureByRef' => [
                '<?php
                    /**
                     * @psalm-pure
                     */
                    function foo(string &$a): string {
                        $a = "B";
                        return $a;
                    }',
                'error_message' => 'ImpureByReferenceAssignment'
            ],
            'staticPropertyFetch' => [
                '<?php
                    final class Number1 {
                        public static ?string $zero = null;

                        /**
                         * @psalm-pure
                         */
                        public static function zero(): ?string {
                            return self::$zero;
                        }
                    }',
                'error_message' => 'ImpureStaticProperty',
            ],
            'staticPropertyAssignment' => [
                '<?php
                    final class Number1 {
                        /** @var string|null */
                        private static $zero;

                        /**
                         * @psalm-pure
                         */
                        public static function zero(): string {
                            self::$zero = "Zero";
                            return "hello";
                        }
                    }',
                'error_message' => 'ImpureStaticProperty',
            ],
            'preventImpureToStringViaComparison' => [
                '<?php
                    class A {
                        public function __toString() {
                            echo "hi";
                            return "bar";
                        }
                    }

                    /**
                     * @psalm-pure
                     */
                    function foo(string $s, A $a) : string {
                        if ($a == $s) {}
                        return $s;
                    }',
                'error_message' => 'ImpureMethodCall'
            ],
            'preventImpureToStringViaConcatenation' => [
                '<?php
                    class A {
                        public function __toString() {
                            echo "hi";
                            return "bar";
                        }
                    }

                    /**
                     * @psalm-pure
                     */
                    function foo(string $s, A $a) : string {
                        return $a . $s;
                    }',
                'error_message' => 'ImpureMethodCall'
            ],
        ];
    }
}
